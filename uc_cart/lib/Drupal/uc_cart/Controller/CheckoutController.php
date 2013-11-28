<?php

/**
 * @file
 * Contains \Drupal\uc_cart\Controller\CheckoutController.
 */

namespace Drupal\uc_cart\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller routines for the checkout.
 */
class CheckoutController extends ControllerBase {

  /**
   * Displays the cart checkout page built of checkout panes from enabled modules.
   */
  public function checkout() {
    global $user;

    $items = uc_cart_get_contents();
    if (count($items) == 0 || !variable_get('uc_checkout_enabled', TRUE)) {
      return $this->redirect('uc_cart.cart');
    }

    if (($min = variable_get('uc_minimum_subtotal', 0)) > 0) {
      $subtotal = 0;
      if (is_array($items) && count($items) > 0) {
        foreach ($items as $item) {
          $data = module_invoke($item->module, 'uc_cart_display', $item);
          if (!empty($data)) {
            $subtotal += $data['#total'];
          }
        }
      }

      if ($subtotal < $min) {
        drupal_set_message(t('The minimum order subtotal for checkout is @min.', array('@min' => uc_currency_format($min))), 'error');
        return $this->redirect('uc_cart.cart');
      }
    }

    // Send anonymous users to login page when anonymous checkout is disabled.
    if ($user->isAnonymous() && !variable_get('uc_checkout_anonymous', TRUE)) {
      drupal_set_message(t('You must login before you can proceed to checkout.'));
      if (variable_get('user_register', 1) != 0) {
        drupal_set_message(t('If you do not have an account yet, you should <a href="!url">register now</a>.', array('!url' => url('user/register', array('query' => drupal_get_destination())))));
      }
      return new RedirectResponse(url('cart', array('query' => drupal_get_destination(), 'absolute' => TRUE)));
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

    // Trigger the "Customer starts checkout" hook and event.
    module_invoke_all('uc_cart_checkout_start', $order);
    // rules_invoke_event('uc_cart_checkout_start', $order);

    module_load_include('inc', 'uc_cart', 'uc_cart.pages');
    return drupal_get_form('Drupal\uc_cart\Form\CheckoutForm', $order);
  }

  /**
   * Allows a customer to review their order before finally submitting it.
   */
  function review() {
    drupal_add_js(drupal_get_path('module', 'uc_cart') . '/js/uc_cart.js');

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

    $panes = _uc_checkout_pane_list();

    // If the cart isn't shippable, bypass panes with shippable == TRUE.
    if (!uc_order_is_shippable($order) && variable_get('uc_cart_delivery_not_shippable', TRUE)) {
      $panes = uc_cart_filter_checkout_panes($panes, array('shippable' => TRUE));
    }

    foreach ($panes as $pane) {
      if ($pane['enabled']) {
        $func = $pane['callback'];
        if (function_exists($func)) {
          $return = $func('review', $order, NULL);
          if (!is_null($return)) {
            $data[$pane['title']] = $return;
          }
        }
      }
    }

    module_load_include('inc', 'uc_cart', 'uc_cart.pages');

    return array(
      '#theme' => 'uc_cart_checkout_review',
      '#panes' => $data,
      '#form' => drupal_get_form('uc_cart_checkout_review_form', $order),
    );
  }

  /**
   * Completes the sale and finishes checkout.
   */
  function complete() {
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

    $build = uc_cart_complete_sale($order, variable_get('uc_new_customer_login', FALSE));
    unset($_SESSION['uc_checkout'][$order->id()], $_SESSION['cart_order']);

    // Add a comment to let sales team know this came in through the site.
    uc_order_comment_save($order->id(), 0, t('Order created through website.'), 'admin');

    $page = variable_get('uc_cart_checkout_complete_page', '');
    if (!empty($page)) {
      return new RedirectResponse(url($page, array('absolute' => TRUE)));
    }

    return $build;
  }

}
