<?php

/**
 * @file
 * Contains \Drupal\uc_2checkout\Controller\CheckoutController.
 */

namespace Drupal\uc_2checkout\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller routines for uc_2checkout.
 */
class CheckoutController extends ControllerBase {

  /**
   * Finalizes 2checkout transaction.
   */
  public function complete($cart_id = 0) {
    $cart_config = \Drupal::config('uc_cart.settings');
    $module_config = \Drupal::config('uc_2checkout.settings');
    \Drupal::logger('2Checkout')->notice('Receiving new order notification for order !order_id.', array('!order_id' => SafeMarkup::checkPlain($_REQUEST['merchant_order_id'])));

    $order = uc_order_load($_REQUEST['merchant_order_id']);

    if (!$order || $order->getStateId() != 'in_checkout') {
      return t('An error has occurred during payment.  Please contact us to ensure your order has submitted.');
    }

    $key = $_REQUEST['key'];
    $order_number = $module_config->get('demo') ? 1 : $_REQUEST['order_number'];
    $valid = md5($module_config->get('secret_word') . $_REQUEST['sid'] . $order_number . $_REQUEST['total']);
    if (Unicode::strtolower($key) != Unicode::strtolower($valid)) {
      uc_order_comment_save($order->id(), 0, t('Attempted unverified 2Checkout completion for this order.'), 'admin');
      throw new AccessDeniedHttpException();
    }

    if ($_REQUEST['demo'] == 'Y' xor $module_config->get('demo')) {
      \Drupal::logger('uc_2checkout')->error('The 2checkout payment for order <a href="@order_url">@order_id</a> demo flag was set to %flag, but the module is set to %mode mode.', array(
        '@order_url' => url('admin/store/orders/' . $order->id()),
        '@order_id' => $order->id(),
        '%flag' => $_REQUEST['demo'] == 'Y' ? 'Y' : 'N',
        '%mode' => $module_config->get('demo') ? 'Y' : 'N',
      ));

      if (!$module_config->get('demo')) {
        throw new AccessDeniedHttpException();
      }
    }

    $order->billing_street1 = $_REQUEST['street_address'];
    $order->billing_street2 = $_REQUEST['street_address2'];
    $order->billing_city = $_REQUEST['city'];
    $order->billing_postal_code = $_REQUEST['zip'];
    $order->billing_phone = $_REQUEST['phone'];
    $order->billing_zone = $_REQUEST['state'];
    $order->billing_country = $_REQUEST['country'];
    $order->save();

    if (Unicode::strtolower($_REQUEST['email']) !== Unicode::strtolower($order->getEmail())) {
      uc_order_comment_save($order->id(), 0, t('Customer used a different e-mail address during payment: !email', array('!email' => SafeMarkup::checkPlain($_REQUEST['email']))), 'admin');
    }

    if ($_REQUEST['credit_card_processed'] == 'Y' && is_numeric($_REQUEST['total'])) {
      $comment = t('Paid by !type, 2Checkout.com order #!order.', array('!type' => $_REQUEST['pay_method'] == 'CC' ? t('credit card') : t('echeck'), '!order' => SafeMarkup::checkPlain($_REQUEST['order_number'])));
      uc_payment_enter($order->id(), '2checkout', $_REQUEST['total'], 0, NULL, $comment);
    }
    else {
      drupal_set_message(t('Your order will be processed as soon as your payment clears at 2Checkout.com.'));
      uc_order_comment_save($order->id(), 0, t('!type payment is pending approval at 2Checkout.com.', array('!type' => $_REQUEST['pay_method'] == 'CC' ? t('Credit card') : t('eCheck'))), 'admin');
    }

    // Empty that cart...
    uc_cart_empty($cart_id);

    // Add a comment to let sales team know this came in through the site.
    uc_order_comment_save($order->id(), 0, t('Order created through website.'), 'admin');

    $build = uc_cart_complete_sale($order, $cart_config->get('new_customer_login'));

    $page = $cart_config->get('checkout_complete_page');

    if (!empty($page)) {
      drupal_goto($page);
    }

    return $build;
  }
}
