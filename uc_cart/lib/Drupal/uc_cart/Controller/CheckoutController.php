<?php

/**
 * @file
 * Contains \Drupal\uc_cart\Controller\CheckoutController.
 */

namespace Drupal\uc_cart\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\uc_cart\Plugin\CheckoutPaneManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
   * Constructs a CheckoutController.
   *
   * @param \Drupal\uc_cart\Plugin\CheckoutPaneManager $checkout_pane_manager
   *   The checkout pane plugin manager.
   */
  public function __construct(CheckoutPaneManager $checkout_pane_manager) {
    $this->checkoutPaneManager = $checkout_pane_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.uc_cart.checkout_pane')
    );
  }

  /**
   * Displays the cart checkout page built of checkout panes from enabled modules.
   */
  public function checkout() {
    $user = \Drupal::currentUser();
    $cart_config = \Drupal::config('uc_cart.settings');

    $items = uc_cart_get_contents();
    if (count($items) == 0 || !$cart_config->get('checkout_enabled')) {
      return $this->redirect('uc_cart.cart');
    }

    // Send anonymous users to login page when anonymous checkout is disabled.
    if ($user->isAnonymous() && !$cart_config->get('checkout_anonymous')) {
      drupal_set_message(t('You must login before you can proceed to checkout.'));
      if (\Drupal::config('user.settings')->get('register') != USER_REGISTER_ADMINISTRATORS_ONLY) {
        drupal_set_message(t('If you do not have an account yet, you should <a href="!url">register now</a>.', array('!url' => url('user/register', array('query' => drupal_get_destination())))));
      }
      return new RedirectResponse(url('user', array('query' => drupal_get_destination(), 'absolute' => TRUE)));
    }

    // Load an order from the session, if available.
    if (isset($_SESSION['cart_order'])) {
      $order = uc_order_load($_SESSION['cart_order']);
      if ($order) {
        // Don't use an existing order if it has changed status or owner, or if
        // there has been no activity for 10 minutes (to prevent identity theft).
        if ($order->getStateId() != 'in_checkout' ||
            ($user->isAuthenticated() && $user->id() != $order->getUserId()) ||
            $order->modified->value < REQUEST_TIME - UC_CART_CHECKOUT_TIMEOUT) {
          if ($order->getStateId() == 'in_checkout' && $order->modified->value < REQUEST_TIME - UC_CART_CHECKOUT_TIMEOUT) {
            // Mark expired orders as abandoned.
            uc_order_update_status($order->id(), 'abandoned');
          }
          unset($order);
        }
      }
      else {
        // Ghost session.
        unset($_SESSION['cart_order']);
        drupal_set_message(t('Your session has expired or is no longer valid.  Please review your order and try again.'));
        return $this->redirect('uc_cart.cart');
      }
    }

    // Determine whether the form is being submitted or built for the first time.
    if (isset($_POST['form_id']) && $_POST['form_id'] == 'uc_cart_checkout_form') {
      // If this is a form submission, make sure the cart order is still valid.
      if (!isset($order)) {
        drupal_set_message(t('Your session has expired or is no longer valid.  Please review your order and try again.'));
        return $this->redirect('uc_cart.cart');
      }
      elseif (!empty($_SESSION['uc_cart_order_rebuild'])) {
        drupal_set_message(t('Your shopping cart contents have changed. Please review your order and try again.'));
        return $this->redirect('uc_cart.cart');
      }
    }
    else {
      // Prepare the cart order.
      $rebuild = FALSE;
      if (!isset($order)) {
        // Create a new order if necessary.
        $order = uc_order_new($user->id());
        $_SESSION['cart_order'] = $order->id();
        $rebuild = TRUE;
      }
      elseif (!empty($_SESSION['uc_cart_order_rebuild'])) {
        // Or, if the cart has changed, then remove old products and line items.
        $result = \Drupal::entityQuery('uc_order_product')
          ->condition('order_id', $order->id())
          ->execute();
        if (!empty($result)) {
          entity_delete_multiple('uc_order_product', array_keys($result));
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
        unset($_SESSION['uc_cart_order_rebuild']);
      }
      elseif (!uc_order_product_revive($order->products)) {
        drupal_set_message(t('Some of the products in this order are no longer available.'), 'error');
        return $this->redirect('uc_cart.cart');
      }
    }

    $min = $cart_config->get('minimum_subtotal');
    if ($min > 0 && $order->getSubtotal() < $min) {
      drupal_set_message(t('The minimum order subtotal for checkout is @min.', array('@min' => uc_currency_format($min))), 'error');
      return $this->redirect('uc_cart.cart');
    }

    // Trigger the "Customer starts checkout" hook and event.
    \Drupal::moduleHandler()->invokeAll('uc_cart_checkout_start', array($order));
    // rules_invoke_event('uc_cart_checkout_start', $order);

    module_load_include('inc', 'uc_cart', 'uc_cart.pages');
    return drupal_get_form('Drupal\uc_cart\Form\CheckoutForm', $order);
  }

  /**
   * Allows a customer to review their order before finally submitting it.
   */
  public function review() {
    if (empty($_SESSION['cart_order']) || empty($_SESSION['uc_checkout'][$_SESSION['cart_order']]['do_review'])) {
      return $this->redirect('uc_cart.checkout');
    }

    $order = uc_order_load($_SESSION['cart_order']);

    if (!$order || $order->getStateId() != 'in_checkout') {
      unset($_SESSION['uc_checkout'][$order->id()]['do_review']);
      return $this->redirect('uc_cart.checkout');
    }
    elseif (!uc_order_product_revive($order->products)) {
      drupal_set_message(t('Some of the products in this order are no longer available.'), 'error');
      return $this->redirect('uc_cart.cart');
    }

    $filter = array('enabled' => FALSE);

    // If the cart isn't shippable, bypass panes with shippable == TRUE.
    if (!$order->isShippable() && \Drupal::config('uc_cart.settings')->get('delivery_not_shippable')) {
      $filter['shippable'] = TRUE;
    }

    $panes = $this->checkoutPaneManager->getDefinitions($filter);
    foreach ($panes as $pane) {
      $return = $pane['callback']('review', $order, NULL);
      if (!is_null($return)) {
        $data[$pane['title']] = $return;
      }
    }

    $build = array(
      '#theme' => 'uc_cart_checkout_review',
      '#panes' => $data,
      '#form' => drupal_get_form('Drupal\uc_cart\Form\CheckoutReviewForm', $order),
    );

    $build['#attached']['library'][] = array('system', 'drupal');
    $build['#attached']['library'][] = array('system', 'jquery.once');
    $build['#attached']['js'][] = drupal_get_path('module', 'uc_cart') . '/js/uc_cart.js';

    return $build;
  }

  /**
   * Completes the sale and finishes checkout.
   */
  public function complete() {
    if (empty($_SESSION['cart_order']) || empty($_SESSION['uc_checkout'][$_SESSION['cart_order']]['do_complete'])) {
      return $this->redirect('uc_cart.cart');
    }

    $order = uc_order_load(intval($_SESSION['cart_order']));

    if (empty($order)) {
      // Display messages to customers and the administrator if the order was lost.
      drupal_set_message(t("We're sorry.  An error occurred while processing your order that prevents us from completing it at this time. Please contact us and we will resolve the issue as soon as possible."), 'error');
      watchdog('uc_cart', 'An empty order made it to checkout! Cart order ID: @cart_order', array('@cart_order' => $_SESSION['cart_order']), WATCHDOG_ERROR);
      return $this->redirect('uc_cart.cart');
    }

    $cart_config = \Drupal::config('uc_cart.settings');
    $build = uc_cart_complete_sale($order, $cart_config->get('new_customer_login'));
    unset($_SESSION['uc_checkout'][$order->id()], $_SESSION['cart_order']);

    // Add a comment to let sales team know this came in through the site.
    uc_order_comment_save($order->id(), 0, t('Order created through website.'), 'admin');

    $page = $cart_config->get('checkout_complete_page');
    if (!empty($page)) {
      return new RedirectResponse(url($page, array('absolute' => TRUE)));
    }

    return $build;
  }

}
