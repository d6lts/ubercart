<?php

/**
 * @file
 * Contains \Drupal\uc_cybersource\Controller\HOPController.
 */

namespace Drupal\uc_cybersource\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\uc_order\Entity\Order;

/**
 * Controller routines for HOP postback.
 */
class HOPController extends ControllerBase {

  /**
   * Processes a payment POST from the CyberSource Hosted Order Page API.
   */
  public static function post() {
    if (!uc_cybersource_hop_include()) {
      \Drupal::logger('uc_cybersource_hop')->error('Unable to receive HOP POST due to missing or unreadable HOP.php file.');
      drupal_add_http_header('Status', '503 Service unavailable');
      print $this->t('The site was unable to receive a HOP post because of a missing or unreadble HOP.php');
      exit();
    }
    $verify = VerifyTransactionSignature($_POST);
    \Drupal::logger('uc_cybersource_hop')->notice('Receiving payment notification at URL for order @orderNumber',
      array('@orderNumber' => $_POST['orderNumber'] ));

    if (!isset($_POST['orderNumber'])) {
      \Drupal::logger('uc_cybersource_hop')->error('CS HOP attempted with invalid order number.');
      return;
    }

    if (!$verify) {
      \Drupal::logger('uc_cybersource_hop')->notice('Receiving invalid payment notification at URL for order @orderNumber. <pre>@debug</pre>',
      array('@orderNumber' => $_POST['orderNumber'], '@debug' => print_r($_POST, TRUE) ));
      return;
    }

    // Assign posted variables to local variables.
    $decision = SafeMarkup::checkPlain($_POST['decision']);
    $reason_code = SafeMarkup::checkPlain($_POST['reasonCode']);
    $reason = _parse_cs_reason_code($reason_code);
    $payment_amount = SafeMarkup::checkPlain($_POST['orderAmount']);
    $payment_currency = SafeMarkup::checkPlain($_POST['paymentCurrency']);
    $request_id = SafeMarkup::checkPlain($_POST['requestID']);
    $request_token = SafeMarkup::checkPlain($_POST['orderPage_requestToken']);
    $reconciliation_id = SafeMarkup::checkPlain($_POST['reconciliationID']);
    $order_id = SafeMarkup::checkPlain($_POST['orderNumber']);
    $payer_email = SafeMarkup::checkPlain($_POST['billTo_email']);
    $order = Order::load($_POST['orderNumber']);

    switch ($decision) {
      case 'ACCEPT':
        \Drupal::logger('uc_cybersource_hop')->notice('CyberSource verified successful payment.');
        $duplicate = (bool) db_query_range('SELECT 1 FROM {uc_payment_cybersource_hop_post} WHERE order_id = :order_id AND decision = :decision', 0, 1, array(':order_id' => $order_id, ':decision' => 'ACCEPT'))->fetchField();
        if ($duplicate) {
          \Drupal::logger('uc_cybersource_hop')->notice('CS HOP transaction for order @order-id has been processed before.', array('@order_id' => $order_id));
          return;
        }
        db_insert('uc_payment_cybersource_hop_post')
          ->fields(array(
            'order_id' => $order_id,
            'request_id' => $request_id,
            'request_token' => $request_token,
            'reconciliation_id' => $reconciliation_id,
            'gross' => $payment_amount,
            'decision' => $decision,
            'reason_code' => $reason_code,
            'payer_email' => $payer_email,
            'received' => REQUEST_TIME,
          ))
          ->execute();

        $comment = $this->t('CyberSource request ID: @txn_id', array('@txn_id' => $request_id));
        uc_payment_enter($order_id, 'cybersource_hop', $payment_amount, $order->getOwnerId(), NULL, $comment);
        uc_cart_complete_sale($order);
        uc_order_comment_save($order_id, 0, $this->t('Payment of @amount @currency submitted through CyberSource with request ID @rid.', array('@amount' => $payment_amount, '@currency' => $payment_currency, '@rid' => $request_id)), 'order', 'payment_received');
        break;
      case 'ERROR':
        uc_order_comment_save($order_id, 0, $this->t("Payment error:@reason with request ID @rid", array('@reason' => $reason, '@rid' => '@request_id')), 'admin');
        break;
      case 'REJECT':
        uc_order_comment_save($order_id, 0, $this->t("Payment is rejected:@reason with request ID @rid", array('@reason' => $reason, '@rid' => '@request_id')), 'admin');
        break;
      case 'REVIEW':
        $order->setStatusId('review')->save();
        uc_order_comment_save($order_id, 0, $this->t('Payment is in review & not complete: @reason. Request ID @rid', array('@reason' => $reason, '@rid' => '@request_id')), 'admin');
        break;
    }
  }

  /**
   * Finalizes CyberSource transaction.
   */
  public static function complete($uc_order) {
    // If the order ID specified in the return URL is not the same as the one in
    // the user's session, we need to assume this is either a spoof or that the
    // user tried to adjust the order on this side while at PayPal. If it was a
    // legitimate checkout, the CyberSource POST will still register, so the
    // gets processed correctly. We'll leave an ambiguous message just in case.
    $session = \Drupal::service('session');
    if (intval($session->get('cart_order')) != $uc_order->id()) {
      drupal_set_message($this->t('Thank you for your order! We will be notified by CyberSource that we have received your payment.'));
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
}
