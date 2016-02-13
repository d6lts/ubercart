<?php

/**
 * @file
 * Contains \Drupal\uc_2checkout\Controller\TwoCheckoutController.
 */

namespace Drupal\uc_2checkout\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\uc_cart\CartManagerInterface;
use Drupal\uc_order\Entity\Order;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller routines for uc_2checkout.
 */
class TwoCheckoutController extends ControllerBase {

  /**
   * The cart manager.
   *
   * @var \Drupal\uc_cart\CartManager
   */
  protected $cartManager;

  /**
   * Constructs a TwoCheckoutController.
   *
   * @param \Drupal\uc_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   */
  public function __construct(CartManagerInterface $cart_manager) {
    $this->cartManager = $cart_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // @todo: Also need to inject logger
    return new static(
      $container->get('uc_cart.manager')
    );
  }

  /**
   * Finalizes 2Checkout transaction.
   *
   * @param int $cart_id
   *   The cart identifier.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   */
  public function complete($cart_id = 0, Request $request) {
    $cart_config = $this->config('uc_cart.settings');
    $module_config = $this->config('uc_2checkout.settings');

    \Drupal::logger('uc_2checkout')->notice('Receiving new order notification for order @order_id.', ['@order_id' => SafeMarkup::checkPlain($request->request->get('merchant_order_id'))]);

    $order = Order::load($request->request->get('merchant_order_id'));

    if (!$order || $order->getStateId() != 'in_checkout') {
      return $this->t('An error has occurred during payment.  Please contact us to ensure your order has submitted.');
    }

    $key = $request->request->get('key');
    $order_number = $module_config->get('demo') ? 1 : $request->request->get('order_number');
    $valid = md5($module_config->get('secret_word') . $request->request->get('sid') . $order_number . $request->request->get('total'));
    if (Unicode::strtolower($key) != Unicode::strtolower($valid)) {
      uc_order_comment_save($order->id(), 0, $this->t('Attempted unverified 2Checkout completion for this order.'), 'admin');
      throw new AccessDeniedHttpException();
    }

    if ($request->request->get('demo') == 'Y' xor $module_config->get('demo')) {
      \Drupal::logger('uc_2checkout')->error('The 2Checkout payment for order <a href=":order_url">@order_id</a> demo flag was set to %flag, but the module is set to %mode mode.', array(
        ':order_url' => $order->toUrl()->toString(),
        '@order_id' => $order->id(),
        '%flag' => $request->request->get('demo') == 'Y' ? 'Y' : 'N',
        '%mode' => $module_config->get('demo') ? 'Y' : 'N',
      ));

      if (!$module_config->get('demo')) {
        throw new AccessDeniedHttpException();
      }
    }

//@todo: Check if this is the right way to save the order
    $order->billing_street1 = $request->request->get('street_address');
    $order->billing_street2 = $request->request->get('street_address2');
    $order->billing_city = $request->request->get('city');
    $order->billing_postal_code = $request->request->get('zip');
    $order->billing_phone = $request->request->get('phone');
    $order->billing_zone = $request->request->get('state');
    $order->billing_country = $request->request->get('country');
    $order->save();

    if (Unicode::strtolower($request->request->get('email')) !== Unicode::strtolower($order->getEmail())) {
      uc_order_comment_save($order->id(), 0, $this->t('Customer used a different e-mail address during payment: @email', ['@email' => SafeMarkup::checkPlain($request->request->get('email'))]), 'admin');
    }

    if ($request->request->get('credit_card_processes') == 'Y' && is_numeric($request->request->get('total'))) {
      $comment = $this->t('Paid by @type, 2Checkout.com order #@order.', ['@type' => $request->request->get('pay_method') == 'CC' ? $this->t('credit card') : $this->t('echeck'), '@order' => SafeMarkup::checkPlain($request->request->get('order_number'))]);
      uc_payment_enter($order->id(), '2Checkout', $request->request->get('total'), 0, NULL, $comment);
    }
    else {
      drupal_set_message($this->t('Your order will be processed as soon as your payment clears at 2Checkout.com.'));
      uc_order_comment_save($order->id(), 0, $this->t('@type payment is pending approval at 2Checkout.com.', ['@type' => $request->request->get('pay_method') == 'CC' ? $this->t('Credit card') : $this->t('eCheck')]), 'admin');
    }

    // Empty that cart...
    $cart = $this->cartManager->get($cart_id);
    $cart->emptyCart();

    // Add a comment to let sales team know this came in through the site.
    uc_order_comment_save($order->id(), 0, $this->t('Order created through website.'), 'admin');

    $build = $cart->completeSale($order, $cart_config->get('new_customer_login'));

    return $build;
  }

  /**
   * React on INS messages from 2Checkout.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   */
  public function notification(Request $request) {
    $values = $request->request;
    \Drupal::logger('uc_2checkout')->notice('Received 2Checkout notification with following data: @data', ['@data' => print_r($values->all(), TRUE)]);
    $module_config = $this->config('uc_2checkout.settings');

    if ($values->has('message_type') && $values->has('md5_hash') && $values->has('message_id')) {
      // Validate the hash
      $secret_word = $module_config->get('secret_word');
      $sid = $module_config->get('sid');
      $twocheckout_order_id = $values->get('sale_id');
      $twocheckout_invoice_id = $values->get('invoice_id');
      $hash = strtoupper(md5($twocheckout_order_id . $sid . $twocheckout_invoice_id . $secret_word));

      if ($hash != $values->get('md5_hash')) {
        \Drupal::logger('uc_2checkout')->notice('2Checkout notification #@num had a wrong hash.', ['@num' => $values->get('message_id')]);
        die('Hash Incorrect');
      }

      $order_id = $values->get('vendor_order_id');
      $order = Order::load($order_id);
      if ($values->get('message_type') == 'FRAUD_STATUS_CHANGED') {
        switch ($values->get('fraud_status')) {
// @todo: I think this still needs a lot of work, I don't see anywhere that it
// validates the INS against an order in the DB then changes order status if the
// payment was successful, like PayPal IPN does ...
          case 'pass':
            break;

          case 'wait':
            break;

          case 'fail':
            // @todo uc_order_update_status($order_id, uc_order_state_default('canceled'));
            $order->setStatusId('canceled')->save();
            uc_order_comment_save($order_id, 0, $this->t('Order have not passed 2Checkout fraud review.'));
            die('fraud');
            break;
        }
      }
      elseif ($values->get('message_type') == 'REFUND_ISSUED') {
        // @todo uc_order_update_status($order_id, uc_order_state_default('canceled'));
        $order->setStatusId('canceled')->save();
        uc_order_comment_save($order_id, 0, $this->t('Order have been refunded through 2Checkout.'));
        die('refund');
      }
    }
    die('ok');
  }

}
