<?php

/**
 * @file
 * Contains \Drupal\uc_paypal\Controller\WpsController.
 */

namespace Drupal\uc_paypal\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\uc_order\OrderInterface;

/**
 * Returns responses for PayPal routes.
 */
class WpsController extends ControllerBase {

  /**
   * Handles a complete Website Payments Standard sale.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the cart or checkout complete page.
   */
  public function wpsComplete(OrderInterface $order) {
    // If the order ID specified in the return URL is not the same as the one in
    // the user's session, we need to assume this is either a spoof or that the
    // user tried to adjust the order on this side while at PayPal. If it was a
    // legitimate checkout, the IPN will still come in from PayPal so the order
    // gets processed correctly. We'll leave an ambiguous message just in case.
    $session = \Drupal::service('session');
    if (!$session->has('cart_order') || intval($session->get('cart_order')) != $order->id()) {
      drupal_set_message($this->t('Thank you for your order! PayPal will notify us once your payment has been processed.'));
      $this->redirect('uc_cart.cart');
    }

    // Ensure the payment method is PayPal WPS.
    if ($order->getPaymentMethodId() != 'paypal_wps') {
      $this->redirect('uc_cart.cart');
    }

    $complete = array();
    if ($session->has('uc_checkout')) {
      $complete = $session->get('uc_checkout');
    }
    // This lets us know it's a legitimate access of the complete page.
    $complete[$session->get('cart_order')]['do_complete'] = TRUE;
    $session->set('uc_checkout', $complete);

    $this->redirect('uc_cart.checkout_complete');
  }

  /**
   * Handles a canceled Website Payments Standard sale.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the WPS cancel page.
   */
  public function wpsCancel() {
    $paypal_config = $this->config('uc_paypal.settings');
    $session = \Drupal::service('session');

    $session->remove('cart_order');

    drupal_set_message($this->t('Your PayPal payment was canceled. Please feel free to continue shopping or contact us for assistance.'));

    $this->redirect($paypal_config->get('wps_cancel_return_url'));
  }

}
