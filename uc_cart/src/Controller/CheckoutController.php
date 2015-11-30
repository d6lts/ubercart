<?php

/**
 * @file
 * Contains \Drupal\uc_cart\Controller\CheckoutController.
 */

namespace Drupal\uc_cart\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\uc_cart\CartInterface;
use Drupal\uc_cart\CartManagerInterface;
use Drupal\uc_cart\Plugin\CheckoutPaneManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for the checkout.
 */
class CheckoutController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The checkout pane manager.
   *
   * @var \Drupal\uc_cart\Plugin\CheckoutPaneManager
   */
  protected $checkoutPaneManager;

  /**
   * The cart manager.
   *
   * @var \Drupal\uc_cart\CartManager
   */
  protected $cartManager;

  /**
   * Constructs a CheckoutController.
   *
   * @param \Drupal\uc_cart\Plugin\CheckoutPaneManager $checkout_pane_manager
   *   The checkout pane plugin manager.
   * @param \Drupal\uc_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   */
  public function __construct(CheckoutPaneManager $checkout_pane_manager, CartManagerInterface $cart_manager) {
    $this->checkoutPaneManager = $checkout_pane_manager;
    $this->cartManager = $cart_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.uc_cart.checkout_pane'),
      $container->get('uc_cart.manager')
    );
  }

  /**
   * Displays the cart checkout page built of checkout panes from enabled modules.
   */
  public function checkout() {
    $cart_config = $this->config('uc_cart.settings');

    $items = $this->cartManager->get()->getContents();
    if (count($items) == 0 || !$cart_config->get('checkout_enabled')) {
      return $this->redirect('uc_cart.cart');
    }

    // Send anonymous users to login page when anonymous checkout is disabled.
    if ($this->currentUser()->isAnonymous() && !$cart_config->get('checkout_anonymous')) {
      drupal_set_message($this->t('You must login before you can proceed to checkout.'));
      if ($this->config('user.settings')->get('register') != USER_REGISTER_ADMINISTRATORS_ONLY) {
        drupal_set_message($this->t('If you do not have an account yet, you should <a href=":url">register now</a>.', [':url' => $this->url('user.register', [], ['query' => drupal_get_destination()])]));
      }
      return $this->redirect('user.page', [], ['query' => drupal_get_destination()]);
    }

    // Load an order from the session, if available.
    $session = \Drupal::service('session');
    if ($session->has('cart_order')) {
      $order = uc_order_load($session->get('cart_order'), TRUE);
      if ($order) {
        // Don't use an existing order if it has changed status or owner, or if
        // there has been no activity for 10 minutes (to prevent identity theft).
        if ($order->getStateId() != 'in_checkout' ||
            ($this->currentUser()->isAuthenticated() && $this->currentUser()->id() != $order->getOwnerId()) ||
            $order->getChangedTime() < REQUEST_TIME - CartInterface::CHECKOUT_TIMEOUT) {
          if ($order->getStateId() == 'in_checkout' && $order->getChangedTime() < REQUEST_TIME - CartInterface::CHECKOUT_TIMEOUT) {
            // Mark expired orders as abandoned.
            $order->setStatusId('abandoned')->save();
          }
          unset($order);
        }
      }
      else {
        // Ghost session.
        $session->remove('cart_order');
        drupal_set_message($this->t('Your session has expired or is no longer valid.  Please review your order and try again.'));
        return $this->redirect('uc_cart.cart');
      }
    }

    // Determine whether the form is being submitted or built for the first time.
    if (isset($_POST['form_id']) && $_POST['form_id'] == 'uc_cart_checkout_form') {
      // If this is a form submission, make sure the cart order is still valid.
      if (!isset($order)) {
        drupal_set_message($this->t('Your session has expired or is no longer valid.  Please review your order and try again.'));
        return $this->redirect('uc_cart.cart');
      }
      elseif ($session->has('uc_cart_order_rebuild')) {
        drupal_set_message($this->t('Your shopping cart contents have changed. Please review your order and try again.'));
        return $this->redirect('uc_cart.cart');
      }
    }
    else {
      // Prepare the cart order.
      $rebuild = FALSE;
      if (!isset($order)) {
        // Create a new order if necessary.
        $order = \Drupal\uc_order\Entity\Order::create(array(
          'uid' => $this->currentUser()->id(),
        ));
        $order->save();
        $session->set('cart_order', $order->id());
        $rebuild = TRUE;
      }
      elseif ($session->has('uc_cart_order_rebuild')) {
        // Or, if the cart has changed, then remove old products and line items.
        $result = \Drupal::entityQuery('uc_order_product')
          ->condition('order_id', $order->id())
          ->execute();
        if (!empty($result)) {
          $storage = $this->entityManager()->getStorage('uc_order_product');
          $entities = $storage->loadMultiple(array_keys($result));
          $storage->delete($entities);
        }
        uc_order_delete_line_item($order->id(), TRUE);
        $rebuild = TRUE;
      }

      if ($rebuild) {
        // Copy the cart contents to the cart order.
        $order->products = array();
        foreach ($items as $item) {
          $order->products[] = $item->toOrderProduct();
        }
        $session->remove('uc_cart_order_rebuild');
      }
      elseif (!uc_order_product_revive($order->products)) {
        drupal_set_message($this->t('Some of the products in this order are no longer available.'), 'error');
        return $this->redirect('uc_cart.cart');
      }
    }

    $min = $cart_config->get('minimum_subtotal');
    if ($min > 0 && $order->getSubtotal() < $min) {
      drupal_set_message($this->t('The minimum order subtotal for checkout is @min.', array('@min' => uc_currency_format($min))), 'error');
      return $this->redirect('uc_cart.cart');
    }

    // Trigger the "Customer starts checkout" hook and event.
    $this->moduleHandler()->invokeAll('uc_cart_checkout_start', array($order));
    // rules_invoke_event('uc_cart_checkout_start', $order);

    return $this->formBuilder()->getForm('Drupal\uc_cart\Form\CheckoutForm', $order);
  }

  /**
   * Allows a customer to review their order before finally submitting it.
   */
  public function review() {
    $session = \Drupal::service('session');
    if (!$session->has('cart_order') || empty($_SESSION['uc_checkout'][$session->get('cart_order')]['do_review'])) {
      return $this->redirect('uc_cart.checkout');
    }

    $order = uc_order_load($session->get('cart_order'), TRUE);

    if (!$order || $order->getStateId() != 'in_checkout') {
      unset($_SESSION['uc_checkout'][$order->id()]['do_review']);
      return $this->redirect('uc_cart.checkout');
    }
    elseif (!uc_order_product_revive($order->products)) {
      drupal_set_message($this->t('Some of the products in this order are no longer available.'), 'error');
      return $this->redirect('uc_cart.cart');
    }

    $filter = array('enabled' => FALSE);

    // If the cart isn't shippable, bypass panes with shippable == TRUE.
    if (!$order->isShippable() && $this->config('uc_cart.settings')->get('delivery_not_shippable')) {
      $filter['shippable'] = TRUE;
    }

    $panes = $this->checkoutPaneManager->getPanes($filter);
    foreach ($panes as $pane) {
      $return = $pane->review($order);
      if (!is_null($return)) {
        $data[$pane->getTitle()] = $return;
      }
    }

    $build = array(
      '#theme' => 'uc_cart_checkout_review',
      '#panes' => $data,
      '#form' => $this->formBuilder()->getForm('Drupal\uc_cart\Form\CheckoutReviewForm', $order),
    );

    $build['#attached']['library'][] = 'system/drupal.system';
    $build['#attached']['library'][] = 'uc_cart/uc_cart.styles';
    $build['#attached']['library'][] = 'uc_cart/uc_cart.scripts';

    return $build;
  }

  /**
   * Completes the sale and finishes checkout.
   */
  public function complete() {
    $session = \Drupal::service('session');
    if (!$session->has('cart_order') || empty($_SESSION['uc_checkout'][$session->get('cart_order')]['do_complete'])) {
      return $this->redirect('uc_cart.cart');
    }

    $order = uc_order_load($session->get('cart_order'), TRUE);

    if (empty($order)) {
      // Display messages to customers and the administrator if the order was lost.
      drupal_set_message($this->t("We're sorry.  An error occurred while processing your order that prevents us from completing it at this time. Please contact us and we will resolve the issue as soon as possible."), 'error');
      $this->logger('uc_cart')->error('An empty order made it to checkout! Cart order ID: @cart_order', ['@cart_order' => $session->get('cart_order')]);
      return $this->redirect('uc_cart.cart');
    }

    $cart_config = $this->config('uc_cart.settings');
    $build = $this->cartManager->completeSale($order, $cart_config->get('new_customer_login'));
    $session->remove('cart_order');
    unset($_SESSION['uc_checkout'][$order->id()]);

    // Add a comment to let sales team know this came in through the site.
    uc_order_comment_save($order->id(), 0, $this->t('Order created through website.'), 'admin');

    return $build;
  }

}
