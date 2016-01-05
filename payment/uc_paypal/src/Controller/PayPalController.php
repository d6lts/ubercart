<?php

/**
 * @file
 * Contains \Drupal\uc_paypal\Controller\PayPalController.
 */

namespace Drupal\uc_paypal\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\uc_order\OrderInterface;

/**
 * Returns responses for PayPal routes.
 */
class PayPalController extends ControllerBase {

  /**
   * Handles the review page for Express Checkout Shortcut Flow.
   */
  public function ecReview() {
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

      $nvp_response = uc_paypal_api_request($nvp_request, variable_get('uc_paypal_wpp_server', 'https://api-3t.sandbox.paypal.com/nvp'));

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
   * Handles a canceled Website Payments Standard sale.
   */
  public function wpsCancel() {
    $config = $this->config('uc_paypal.settings');

    unset($_SESSION['cart_order']);

    drupal_set_message($this->t('Your PayPal payment was canceled. Please feel free to continue shopping or contact us for assistance.'));

    $this->redirect($config->get('uc_paypal_wps_cancel_return_url'));
  }

  /**
   * Processes Instant Payment Notifiations from PayPal.
   */
  public function ipn() {
    if (!isset($_POST['invoice'])) {
      \Drupal::logger('uc_paypal')->error('IPN attempted with invalid order ID.');
      return;
    }

    if (strpos($_POST['invoice'], '-') > 0) {
      list($order_id, $cart_id) = explode('-', $_POST['invoice']);

      // Sanitize order ID and cart ID
      $order_id = intval($order_id);
      $cart_id  = SafeMarkup::checkPlain($cart_id);

      if (!empty($cart_id)) {
        // Needed later by uc_complete_sale to empty the correct cart
        $_SESSION['uc_cart_id'] = $cart_id;
      }
    }
    else {
      $order_id = intval($_POST['invoice']);
    }

    \Drupal::logger('uc_paypal')->notice('Receiving IPN at URL for order @order_id. <pre>@debug</pre>', ['@order_id' => $order_id, '@debug' => variable_get('uc_paypal_wps_debug_ipn', FALSE) ? print_r($_POST, TRUE) : '']);

    $order = Order::load($order_id);

    if ($order == FALSE) {
      \Drupal::logger('uc_paypal')->error('IPN attempted for non-existent order @order_id.', ['@order_id' => $order_id]);
      return;
    }

    // Assign posted variables to local variables
    $payment_status = SafeMarkup::checkPlain($_POST['payment_status']);
    $payment_amount = SafeMarkup::checkPlain($_POST['mc_gross']);
    $payment_currency = SafeMarkup::checkPlain($_POST['mc_currency']);
    $receiver_email = SafeMarkup::checkPlain($_POST['business']);
    if ($receiver_email == '') {
      $receiver_email = SafeMarkup::checkPlain($_POST['receiver_email']);
    }
    $txn_id = SafeMarkup::checkPlain($_POST['txn_id']);
    $txn_type = SafeMarkup::checkPlain($_POST['txn_type']);
    $payer_email = SafeMarkup::checkPlain($_POST['payer_email']);

    // Express Checkout IPNs may not have the WPS email stored. But if it is,
    // make sure that the right account is being paid.
    $uc_paypal_wps_email = trim(variable_get('uc_paypal_wps_email', ''));
    if (!empty($uc_paypal_wps_email) && Unicode::strtolower($receiver_email) != Unicode::strtolower($uc_paypal_wps_email)) {
      \Drupal::logger('uc_paypal')->error('IPN for a different PayPal account attempted.');
      return;
    }

    $req = '';

    foreach ($_POST as $key => $value) {
      $value = urlencode(stripslashes($value));
      $req .= $key . '=' . $value . '&';
    }

    $req .= 'cmd=_notify-validate';

    if (variable_get('uc_paypal_wpp_server', '') == 'https://api-3t.paypal.com/nvp') {
      $host = 'https://www.paypal.com/cgi-bin/webscr';
    }
    else {
      $host = variable_get('uc_paypal_wps_server', 'https://www.sandbox.paypal.com/cgi-bin/webscr');
    }
    $response = \Drupal::httpClient()
      ->post($host, NULL, $req)
      ->send();

    if ($response->isError()) {
      \Drupal::logger('uc_paypal')->error('IPN failed with HTTP error @error, code @code.', ['@error' => $response->getReasonPhrase(), '@code' => $response->getStatusCode()]);
      return;
    }

    if (strcmp($response->getBody(TRUE), 'VERIFIED') == 0) {
      \Drupal::logger('uc_paypal')->notice('IPN transaction verified.');

      $duplicate = (bool) db_query_range('SELECT 1 FROM {uc_payment_paypal_ipn} WHERE txn_id = :id AND status <> :status', 0, 1, [':id' => $txn_id, ':status' => 'Pending'])->fetchField();
      if ($duplicate) {
        if ($order->getPaymentMethodId() != 'credit') {
          \Drupal::logger('uc_paypal')->notice('IPN transaction ID has been processed before.');
        }
        return;
      }

      db_insert('uc_payment_paypal_ipn')
        ->fields(array(
          'order_id' => $order_id,
          'txn_id' => $txn_id,
          'txn_type' => $txn_type,
          'mc_gross' => $payment_amount,
          'status' => $payment_status,
          'receiver_email' => $receiver_email,
          'payer_email' => $payer_email,
          'received' => REQUEST_TIME,
        ))
        ->execute();

      switch ($payment_status) {
        case 'Canceled_Reversal':
          uc_order_comment_save($order_id, 0, $this->t('PayPal has canceled the reversal and returned @amount @currency to your account.', ['@amount' => uc_currency_format($payment_amount, FALSE), '@currency' => $payment_currency]), 'admin');
          break;

        case 'Completed':
          if (abs($payment_amount - $order->getTotal()) > 0.01) {
            \Drupal::logger('uc_paypal')->warning('Payment @txn_id for order @order_id did not equal the order total.', ['@txn_id' => $txn_id, '@order_id' => $order->id(), 'link' => Link::createFromRoute($this->t('view'), 'entity.uc_order.canonical', ['uc_order' => $order->id()])->toString()]);
          }
          $comment = $this->t('PayPal transaction ID: @txn_id', ['@txn_id' => $txn_id]);
          uc_payment_enter($order_id, 'paypal_wps', $payment_amount, $order->getOwnerId(), NULL, $comment);
          uc_cart_complete_sale($order);
          uc_order_comment_save($order_id, 0, $this->t('PayPal IPN reported a payment of @amount @currency.', ['@amount' => uc_currency_format($payment_amount, FALSE), '@currency' => $payment_currency]));
          break;

        case 'Denied':
          uc_order_comment_save($order_id, 0, $this->t("You have denied the customer's payment."), 'admin');
          break;

        case 'Expired':
          uc_order_comment_save($order_id, 0, $this->t('The authorization has failed and cannot be captured.'), 'admin');
          break;

        case 'Failed':
          uc_order_comment_save($order_id, 0, $this->t("The customer's attempted payment from a bank account failed."), 'admin');
          break;

        case 'Pending':
          $order->setStatusId('paypal_pending')->save();
          uc_order_comment_save($order_id, 0, $this->t('Payment is pending at PayPal: @reason', ['@reason' => _uc_paypal_pending_message(SafeMarkup::checkPlain($_POST['pending_reason']))]), 'admin');
          break;

        // You, the merchant, refunded the payment.
        case 'Refunded':
          $comment = $this->t('PayPal transaction ID: @txn_id', ['@txn_id' => $txn_id]);
          uc_payment_enter($order_id, 'paypal_wps', $payment_amount, $order->getOwnerId(), NULL, $comment);
          break;

        case 'Reversed':
          \Drupal::logger('uc_paypal')->error('PayPal has reversed a payment!');
          uc_order_comment_save($order_id, 0, $this->t('Payment has been reversed by PayPal: @reason', ['@reason' => _uc_paypal_reversal_message(SafeMarkup::checkPlain($_POST['reason_code']))]), 'admin');
          break;

        case 'Processed':
          uc_order_comment_save($order_id, 0, $this->t('A payment has been accepted.'), 'admin');
          break;

        case 'Voided':
          uc_order_comment_save($order_id, 0, $this->t('The authorization has been voided.'), 'admin');
          break;
      }
    }
    elseif (strcmp($response->getBody(TRUE), 'INVALID') == 0) {
      \Drupal::logger('uc_paypal')->error('IPN transaction failed verification.');
      uc_order_comment_save($order_id, 0, $this->t('An IPN transaction failed verification for this order.'), 'admin');
    }
  }

}
