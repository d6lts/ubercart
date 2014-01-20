<?php

/**
 * @file
 * Contains \Drupal\uc_cybersource\Controller\HOPController.
 */

namespace Drupal\uc_cybersource\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for HOP postback.
 */
class HOPController extends ControllerBase {

  /**
   * Processes a payment POST from the CyberSource Hosted Order Page API.
   */
  public static function post() {
    if (!uc_cybersource_hop_include()) {
      watchdog('uc_cybersource_hop', 'Unable to receive HOP POST due to missing or unreadable HOP.php file.', array(), 'error');
      drupal_add_http_header('Status', '503 Service unavailable');
      drupal_set_title(t('Unable to receive HOP POST.'));
      print t('The site was unable to receive a HOP post because of a missing or unreadble HOP.php');
      exit();
    }
    $verify = VerifyTransactionSignature($_POST);
    watchdog('uc_cybersource_hop', 'Receiving payment notification at URL for order @orderNumber',
      array('@orderNumber' => $_POST['orderNumber'] ));

    if (!isset($_POST['orderNumber'])) {
      watchdog('uc_cybersource_hop', 'CS HOP attempted with invalid order number.', array(), WATCHDOG_ERROR);
      return;
    }

    if (!$verify) {
      watchdog('uc_cybersource_hop', 'Receiving invalid payment notification at URL for order @orderNumber. <pre>@debug</pre>',
      array('@orderNumber' => $_POST['orderNumber'], '@debug' => print_r($_POST, TRUE) ));
      return;
    }

    // Assign posted variables to local variables.
    $decision = check_plain($_POST['decision']);
    $reason_code = check_plain($_POST['reasonCode']);
    $reason = _parse_cs_reason_code($reason_code);
    $payment_amount = check_plain($_POST['orderAmount']);
    $payment_currency = check_plain($_POST['paymentCurrency']);
    $request_id = check_plain($_POST['requestID']);
    $request_token = check_plain($_POST['orderPage_requestToken']);
    $reconciliation_id = check_plain($_POST['reconciliationID']);
    $order_id = check_plain($_POST['orderNumber']);
    $payer_email = check_plain($_POST['billTo_email']);
    $order = uc_order_load($_POST['orderNumber']);

    switch ($decision) {
      case 'ACCEPT':
        watchdog('uc_cybersource_hop', 'CyberSource verified successful payment.');
        $duplicate = (bool) db_query_range('SELECT 1 FROM {uc_payment_cybersource_hop_post} WHERE order_id = :order_id AND decision = :decision', 0, 1, array(':order_id' => $order_id, ':decision' => 'ACCEPT'))->fetchField();
        if ($duplicate) {
          watchdog('uc_cybersource_hop', 'CS HOP transaction for order @order-id has been processed before.', array('@order_id' => $order_id), WATCHDOG_NOTICE);
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

        $comment = t('CyberSource request ID: @txn_id', array('@txn_id' => $request_id));
        uc_payment_enter($order_id, 'cybersource_hop', $payment_amount, $order->getUserId(), NULL, $comment);
        uc_cart_complete_sale($order);
        uc_order_comment_save($order_id, 0, t('Payment of @amount @currency submitted through CyberSource with request ID @rid.', array('@amount' => $payment_amount, '@currency' => $payment_currency, '@rid' => $request_id)), 'order', 'payment_received');
        break;
      case 'ERROR':
        uc_order_comment_save($order_id, 0, t("Payment error:@reason with request ID @rid", array('@reason' => $reason, '@rid' => '@request_id')), 'admin');
        break;
      case 'REJECT':
        uc_order_comment_save($order_id, 0, t("Payment is rejected:@reason with request ID @rid", array('@reason' => $reason, '@rid' => '@request_id')), 'admin');
        break;
      case 'REVIEW':
        $order->setStatusId('review')->save();
        uc_order_comment_save($order_id, 0, t('Payment is in review & not complete: @reason. Request ID @rid', array('@reason' => $reason, '@rid' => '@request_id')), 'admin');
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
    if (intval($_SESSION['cart_order']) != $uc_order->id()) {
      drupal_set_message(t('Thank you for your order! We will be notified by CyberSource that we have received your payment.'));
      drupal_goto('cart');
    }
    // This lets us know it's a legitimate access of the complete page.
    $_SESSION['uc_checkout'][$_SESSION['cart_order']]['do_complete'] = TRUE;
    drupal_goto('cart/checkout/complete');
  }
}
