<?php

/**
 * @file
 * Contains \Drupal\uc_paypal\Controller\EcController.
 */

namespace Drupal\uc_paypal\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for PayPal routes.
 */
class EcController extends ControllerBase {

  /**
   * Handles the review page for Express Checkout Mark Flow.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the cart or cart review page.
   */
  public function ecReviewRedirect() {
    $paypal_config = $this->config('uc_paypal.settings');
    if (!isset($_SESSION['TOKEN']) || !($order = Order::load($_SESSION['cart_order']))) {
      unset($_SESSION['cart_order']);
      unset($_SESSION['have_details']);
      unset($_SESSION['TOKEN'], $_SESSION['PAYERID']);
      drupal_set_message(t('An error has occurred in your PayPal payment. Please review your cart and try again.'));
      $this->redirect('uc_cart.cart');
    }

    $nvp_request = array(
      'METHOD' => 'GetExpressCheckoutDetails',
      'TOKEN' => $_SESSION['TOKEN'],
    );

    $nvp_response = uc_paypal_api_request($nvp_request, $paypal_config->get('wpp_server'));

    $_SESSION['PAYERID'] = $nvp_response['PAYERID'];

    $this->redirect('uc_cart.checkout_review');
  }

  /**
   * Handles the review page for Express Checkout Shortcut Flow.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|array
   *   A redirect to the cart or a build array.
   */
  public function ecReview() {
    $paypal_config = $this->config('uc_paypal.settings');
    if (!isset($_SESSION['TOKEN']) || !($order = Order::load($_SESSION['cart_order']))) {
      unset($_SESSION['cart_order']);
      unset($_SESSION['have_details']);
      unset($_SESSION['TOKEN'], $_SESSION['PAYERID']);
      drupal_set_message($this->t('An error has occurred in your PayPal payment. Please review your cart and try again.'));
      return $this->redirect('uc_cart.cart');
    }

    if (!isset($_SESSION['have_details'][$order->id()])) {
      $nvp_request = array(
        'METHOD' => 'GetExpressCheckoutDetails',
        'TOKEN' => $_SESSION['TOKEN'],
      );

      $nvp_response = uc_paypal_api_request($nvp_request, $paypal_config->get('uc_paypal_wpp_server'));

      $_SESSION['PAYERID'] = $nvp_response['PAYERID'];

      $shipname = SafeMarkup::checkPlain($nvp_response['SHIPTONAME']);
      if (strpos($shipname, ' ') > 0) {
        $order->delivery_first_name = substr($shipname, 0, strrpos(trim($shipname), ' '));
        $order->delivery_last_name = substr($shipname, strrpos(trim($shipname), ' ') + 1);
      }
      else {
        $order->delivery_first_name = $shipname;
        $order->delivery_last_name = '';
      }

      $order->delivery_street1 = SafeMarkup::checkPlain($nvp_response['SHIPTOSTREET']);
      $order->delivery_street2 = isset($nvp_response['SHIPTOSTREET2']) ? SafeMarkup::checkPlain($nvp_response['SHIPTOSTREET2']) : '';
      $order->delivery_city = SafeMarkup::checkPlain($nvp_response['SHIPTOCITY']);
      $order->delivery_zone = $nvp_response['SHIPTOSTATE'];
      $order->delivery_postal_code = SafeMarkup::checkPlain($nvp_response['SHIPTOZIP']);
      $order->delivery_country = $nvp_response['SHIPTOCOUNTRYCODE'];

      $order->billing_first_name = SafeMarkup::checkPlain($nvp_response['FIRSTNAME']);
      $order->billing_last_name = SafeMarkup::checkPlain($nvp_response['LASTNAME']);
      $order->billing_street1 = SafeMarkup::checkPlain($nvp_response['EMAIL']);

      if (!$order->getEmail()) {
        $order->setEmail($nvp_response['EMAIL']);
      }
      $order->setPaymentMethodId('paypal_ec');

      $order->save();

      $_SESSION['have_details'][$order->id()] = TRUE;
    }

    $build['instructions'] = array('#markup' => $this->t("Your order is almost complete!  Please fill in the following details and click 'Continue checkout' to finalize the purchase."));

    $build['form'] = $this->formBuilder()->getForm('uc_paypal_ec_review_form', $order);

    return $build;
  }

  /**
   * Presents the final total to the user for checkout!
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|array
   *   A redirect to the cart or a build array.
   */
  function ecSubmit() {
    if (!isset($_SESSION['TOKEN']) || !($order = Order::load($_SESSION['cart_order']))) {
      unset($_SESSION['cart_order'], $_SESSION['have_details']);
      unset($_SESSION['TOKEN'], $_SESSION['PAYERID']);
      drupal_set_message(t('An error has occurred in your PayPal payment. Please review your cart and try again.'));
      $this->redirect('uc_cart.cart');
    }

    $build['#attached']['library'][] = 'uc_cart/uc_cart.styles';

    $build['review'] = array(
      '#theme' => 'uc_cart_review_table',
      '#items' => $order->products,
      '#show_subtotal' => FALSE,
    );

    $build['line_items'] = uc_order_pane_line_items('customer', $order);

    $build['instructions'] = array('#markup' => '<p>' . t("Your order is not complete until you click the 'Submit order' button below. Your PayPal account will be charged for the amount shown above once your order is placed. You will receive confirmation once your payment is complete.") . '</p>');

    $build['submit_form'] = \Drupal::formBuilder()->getForm('\Drupal\uc_paypal\Form\EcSubmitForm');

    return $build;
  }

}
