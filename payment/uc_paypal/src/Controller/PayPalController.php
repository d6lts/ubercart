<?php

/**
 * @file
 * Contains \Drupal\uc_paypal\Controller\PayPalController.
 */

namespace Drupal\uc_paypal\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for PayPal routes.
 */
class PayPalController extends ControllerBase {

  /**
   * Processes Instant Payment Notifiations from PayPal.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   An empty Response with HTTP status code 200.
   */
  public function ipn(Request $request) {
    if (!$request->request->has('invoice')) {
      \Drupal::logger('uc_paypal')->error('IPN attempted with invalid order ID.');
      return new Response();
    }
    $paypal_config = $this->config('uc_paypal.settings');

    if (strpos($request->request->get('invoice'), '-') > 0) {
      list($order_id, $cart_id) = explode('-', $request->request->get('invoice'));

      // Sanitize order ID and cart ID
      $order_id = intval($order_id);
      $cart_id  = SafeMarkup::checkPlain($cart_id);

      if (!empty($cart_id)) {
        // Needed later by uc_complete_sale to empty the correct cart
        $_SESSION['uc_cart_id'] = $cart_id;
      }
    }
    else {
      $order_id = intval($request->request->get('invoice'));
    }

    // Log IPN receipt and (optionally) IPN contents.
    if ($paypal_config->get('wps_debug_ipn')) {
      \Drupal::logger('uc_paypal')->notice('Receiving IPN at URL for order @order_id. <pre>@debug</pre>', ['@order_id' => $order_id, '@debug' => print_r($request->request->all(), TRUE)]);
    }
    else {
      \Drupal::logger('uc_paypal')->notice('Receiving IPN at URL for order @order_id.', ['@order_id' => $order_id]);
    }

    $order = Order::load($order_id);

    if ($order == FALSE) {
      \Drupal::logger('uc_paypal')->error('IPN attempted for non-existent order @order_id.', ['@order_id' => $order_id]);
      return new Response();
    }

    // Assign posted variables to local variables
    $payment_status = SafeMarkup::checkPlain($request->request->get('payment_status'));
    $payment_amount = SafeMarkup::checkPlain($request->request->get('mc_gross'));
    $payment_currency = SafeMarkup::checkPlain($request->request->get('mc_currency'));
    $receiver_email = SafeMarkup::checkPlain($request->request->get('business'));
    if ($receiver_email == '') {
      $receiver_email = SafeMarkup::checkPlain($request->request->get('receiver_email'));
    }
    $txn_id = SafeMarkup::checkPlain($request->request->get('txn_id'));
    $txn_type = SafeMarkup::checkPlain($request->request->get('txn_type'));
    $payer_email = SafeMarkup::checkPlain($request->request->get('payer_email'));

    // Express Checkout IPNs may not have the WPS email stored. But if it is,
    // make sure that the right account is being paid.
    $uc_paypal_wps_email = trim($paypal_config->get('wps_email'));
    if (!empty($uc_paypal_wps_email) && Unicode::strtolower($receiver_email) != Unicode::strtolower($uc_paypal_wps_email)) {
      \Drupal::logger('uc_paypal')->error('IPN for a different PayPal account attempted.');
      return new Response();
    }

    // POST IPN data back to PayPal to validate IPN.
    $req = '';
    foreach ($request->request->all() as $key => $value) {
      $value = urlencode(stripslashes($value));
      $req .= $key . '=' . $value . '&';
    }
    // Append key/value to identify this as a validation.
    $req .= 'cmd=_notify-validate';

    // Determine server.
    if ($paypal_config->get('wpp_server') == 'https://api-3t.paypal.com/nvp') {
      $host = 'https://www.paypal.com/cgi-bin/webscr';
    }
    else {
      $host = $paypal_config->get('wps_server');
    }
    // Send POST.
    $response = \Drupal::httpClient()
      ->post($host, NULL, $req)
      ->send();
// @todo: Make sure these are set
// curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
// curl_setopt($ch, CURLOPT_POST, 1);
// curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
// curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
// curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
// curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
    if ($response->isError()) {
      \Drupal::logger('uc_paypal')->error('IPN validation failed with HTTP error @error, code @code.', ['@error' => $response->getReasonPhrase(), '@code' => $response->getStatusCode()]);
      return new Response();
    }

    // Check IPN validation response to determine if the IPN was valid..
    if (strcmp($response->getBody(TRUE), 'VERIFIED') == 0) {
      \Drupal::logger('uc_paypal')->notice('IPN transaction verified.');

      $duplicate = (bool) db_query_range('SELECT 1 FROM {uc_payment_paypal_ipn} WHERE txn_id = :id AND status <> :status', 0, 1, [':id' => $txn_id, ':status' => 'Pending'])->fetchField();
      if ($duplicate) {
        if ($order->getPaymentMethodId() != 'credit') {
          \Drupal::logger('uc_paypal')->notice('IPN transaction ID has been processed before.');
        }
        return new Response();
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
          uc_order_comment_save($order_id, 0, $this->t('Payment is pending at PayPal: @reason', ['@reason' => $this->pendingMessage(SafeMarkup::checkPlain($request->request->get('pending_reason')))]), 'admin');
          break;

        // You, the merchant, refunded the payment.
        case 'Refunded':
          $comment = $this->t('PayPal transaction ID: @txn_id', ['@txn_id' => $txn_id]);
          uc_payment_enter($order_id, 'paypal_wps', $payment_amount, $order->getOwnerId(), NULL, $comment);
          break;

        case 'Reversed':
          \Drupal::logger('uc_paypal')->error('PayPal has reversed a payment!');
          uc_order_comment_save($order_id, 0, $this->t('Payment has been reversed by PayPal: @reason', ['@reason' => $this->reversalMessage(SafeMarkup::checkPlain($request->request->get('reason_code')))]), 'admin');
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

    return new Response();
  }

  /**
   * Returns a message for the pending reason of a PayPal payment.
   */
  protected function pendingMessage($reason) {
    switch ($reason) {
      case 'address':
        return t('The payment is pending because your customer did not include a confirmed shipping address and your Payment Receiving Preferences is set to allow you to manually accept or deny each of these payments.');
      case 'authorization':
        return t('The payment is pending because you set the payment action to Authorization and have not yet captured funds.');
      case 'echeck':
        return t('The payment is pending because it was made by an eCheck that has not yet cleared.');
      case 'intl':
        return t('The payment is pending because you hold a non-U.S. account and do not have a withdrawal mechanism. You must manually accept or deny this international payment from your Account Overview.');
      case 'multi_currency':
        return t('The payment is pending because you do not have a balance in the currency sent, and you do not have your Payment Receiving Preferences set to automatically convert and accept this payment. You must manually accept or deny a payment of this currency from your Account Overview.');
      case 'order':
        return t('The payment is pending because you set the payment action to Order and have not yet captured funds.');
      case 'paymentreview':
        return t('The payment is pending while it is being reviewed by PayPal for risk.');
      case 'unilateral':
        return t('The payment is pending because it was made to an e-mail address that is not yet registered or confirmed.');
      case 'upgrade':
        return t('The payment is pending because it was either made via credit card and you do not have a Business or Premier account or you have reached the monthly limit for transactions on your account.');
      case 'verify':
        return t('The payment is pending because you are not yet a verified PayPal member. Please verify your account.');
      case 'other':
        return t('The payment is pending for a reason other than those listed above. For more information, contact PayPal Customer Service.');
      default:
        return t('Reason "@reason" unknown; contact PayPal Customer Service for more information.', ['@reason' => $reason]);
    }
  }

  /**
   * Returns a message for the reason code of a PayPal reversal.
   */
  protected function reversalMessage($reason) {
    switch ($reason) {
      case 'chargeback':
        return t('The customer has initiated a chargeback.');
      case 'guarantee':
        return t('The customer triggered a money-back guarantee.');
      case 'buyer-complaint':
        return t('The customer filed a complaint about the transaction.');
      case 'refund':
        return t('You gave the customer a refund.');
      case 'other':
      default:
        return t('Reason "@reason" unknown; contact PayPal Customer Service for more information.', ['@reason' => $reason]);
    }
  }

}
