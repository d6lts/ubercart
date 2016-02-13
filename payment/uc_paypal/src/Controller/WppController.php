<?php

/**
 * @file
 * Contains \Drupal\uc_paypal\Controller\WppController.
 */

namespace Drupal\uc_paypal\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Processes a credit card payment through Website Payments Pro.
 */
class WppController extends ControllerBase {

  public function wppCharge($order_id, $amount, $data) {
    $order = Order::load($order_id);
    $paypal_config = $this->config('uc_paypal.settings');

    if ($data['txn_type'] == UC_CREDIT_PRIOR_AUTH_CAPTURE) {
      $nvp_request = array(
        'METHOD' => 'DoCapture',
        'AUTHORIZATIONID' => $data['auth_id'],
        'AMT' => uc_currency_format($amount, FALSE, FALSE, '.'),
        'CURRENCYCODE' => $order->getCurrency(),
        'COMPLETETYPE' => 'Complete',
      );
    }
    else {
      list($desc, $subtotal) = _uc_paypal_product_details($order->products);

      if (intval($order->payment_details['cc_exp_month']) < 10) {
        $expdate = '0' . $order->payment_details['cc_exp_month'] . $order->payment_details['cc_exp_year'];
      }
      else {
        $expdate = $order->payment_details['cc_exp_month'] . $order->payment_details['cc_exp_year'];
      }

      $cc_type = NULL;
      if (isset($order->payment_details['cc_type'])) {
        switch (strtolower($order->payment_details['cc_type'])) {
          case 'amex':
          case 'american express':
            $cc_type = 'Amex';
            break;
          case 'visa':
            $cc_type = 'Visa';
            break;
          case 'mastercard':
          case 'master card':
            $cc_type = 'MasterCard';
            break;
          case 'discover':
            $cc_type = 'Discover';
            break;
        }
      }
      if (is_null($cc_type)) {
        $cc_type = $this->cardType($order->payment_details['cc_number']);
        if ($cc_type === FALSE) {
          drupal_set_message(t('The credit card type did not pass validation.'), 'error');
          \Drupal::logger('uc_paypal')->error('Could not figure out cc type: @number / @type', ['@number' => $order->payment_details['cc_number'], '@type' => $order->payment_details['cc_type']]);
          return array('success' => FALSE);
        }
      }

      // PayPal doesn't accept IPv6 addresses.
      $ip_address = ltrim(\Drupal::request()->getClientIp(), '::ffff:');

      $nvp_request = array(
        'METHOD' => 'DoDirectPayment',
        'PAYMENTACTION' => $data['txn_type'] == UC_CREDIT_AUTH_ONLY ? 'Authorization' : 'Sale',
        'IPADDRESS' => $ip_address,
        'AMT' => uc_currency_format($amount, FALSE, FALSE, '.'),
        'CREDITCARDTYPE' => $cc_type,
        'ACCT' =>  $order->payment_details['cc_number'],
        'EXPDATE' => $expdate,
        'CVV2' => $order->payment_details['cc_cvv'],
        'FIRSTNAME' => substr($order->billing_first_name, 0, 25),
        'LASTNAME' => substr($order->billing_last_name, 0, 25),
        'STREET' => substr($order->billing_street1, 0, 100),
        'STREET2' => substr($order->billing_street2, 0, 100),
        'CITY' => substr($order->billing_city, 0, 40),
        'STATE' => $order->billing_zone,
        'ZIP' => $order->billing_postal_code,
        'COUNTRYCODE' => $order->billing_country,
        'CURRENCYCODE' => $order->getCurrency(),
        'DESC' => substr($desc, 0, 127),
        'INVNUM' => $order_id . '-' . REQUEST_TIME,
        'BUTTONSOURCE' => 'Ubercart_ShoppingCart_DP_US',
        'NOTIFYURL' => Url::fromRoute('uc_paypal.ipn', [], ['absolute' => TRUE])->toString(),
        'EMAIL' => substr($order->getEmail(), 0, 127),
        'PHONENUM' => substr($order->billing_phone, 0, 20),
      );

      if ($order->isShippable() && !empty($order->delivery_first_name)) {
        $shipdata = array(
          'SHIPTONAME' => substr($order->delivery_first_name . ' ' . $order->delivery_last_name, 0, 25),
          'SHIPTOSTREET' => substr($order->delivery_street1, 0, 100),
          'SHIPTOSTREET2' => substr($order->delivery_street2, 0, 100),
          'SHIPTOCITY' => substr($order->delivery_city, 0, 40),
          'SHIPTOSTATE' => $order->delivery_zone,
          'SHIPTOZIP' => $order->delivery_postal_code,
          'SHIPTOCOUNTRYCODE' => $order->delivery_country,
        );
        $nvp_request += $shipdata;
      }

      if ($paypal_config->get('uc_credit_cvv_enabled')) {
        $nvp_request['CVV2'] = $order->payment_details['cc_cvv'];
      }
    }

    $nvp_response = uc_paypal_api_request($nvp_request, $paypal_config->get('wpp_server'));
    $types = uc_credit_transaction_types();

    switch ($nvp_response['ACK']) {
      case 'SuccessWithWarning':
        \Drupal::logger('uc_paypal')->warning('<b>@type succeeded with a warning.</b>@paypal_message',
          array(
            '@paypal_message' => $this->buildErrorMessages($nvp_response),
            '@type' => $types[$data['txn_type']],
            'link' => $order->toLink($this->t('view order'))->toString(),
          )
        );
        // Fall through.
      case 'Success':
        $message = t('<b>@type</b><br /><b>Success: </b>@amount @currency', ['@type' => $types[$data['txn_type']], '@amount' => uc_currency_format($nvp_response['AMT'], FALSE), '@currency' => $nvp_response['CURRENCYCODE']]);
        if ($data['txn_type'] != UC_CREDIT_PRIOR_AUTH_CAPTURE) {
          $message .= '<br />' . t('<b>Address:</b> @avscode', ['@avscode' => $this->avscodeMessage($nvp_response['AVSCODE'])]);
          if ($paypal_config->get('uc_credit_cvv_enabled')) {
            $message .= '<br />' . t('<b>CVV2:</b> @cvvmatch', ['@cvvmatch' => $this->cvvmatchMessage($nvp_response['CVV2MATCH'])]);
          }
        }
        $result = array(
          'success' => TRUE,
          'comment' => t('PayPal transaction ID: @transactionid', ['@transactionid' => $nvp_response['TRANSACTIONID']]),
          'message' => $message,
          'data' => SafeMarkup::checkPlain($nvp_response['TRANSACTIONID']),
          'uid' => $this->currentUser()->id(),
        );

        // If this was an authorization only transaction...
        if ($data['txn_type'] == UC_CREDIT_AUTH_ONLY) {
          // Log the authorization to the order.
          uc_credit_log_authorization($order_id, $nvp_response['TRANSACTIONID'], $nvp_response['AMT']);
        }
        elseif ($data['txn_type'] == UC_CREDIT_PRIOR_AUTH_CAPTURE) {
          uc_credit_log_prior_auth_capture($order_id, $data['auth_id']);
        }

        // Log the IPN to the database.
        db_insert('uc_payment_paypal_ipn')
          ->fields(array(
            'order_id' => $order->id(),
            'txn_id' => $nvp_response['TRANSACTIONID'],
            'txn_type' => 'web_accept',
            'mc_gross' => $amount,
            'status' => 'Completed',
            'payer_email' => $order->getEmail(),
            'received' => REQUEST_TIME,
          ))
          ->execute();

        break;
      case 'FailureWithWarning':
        // Fall through.
      case 'Failure':
        $message = t('<b>@type failed.</b>', ['@type' => $types[$data['txn_type']]]) . $this->buildErrorMessages($nvp_response);
        $result = array(
          'success' => FALSE,
          'message' => $message,
          'uid' => $this->currentUser()->id(),
        );
        break;
      default:
        $message = t('Unexpected acknowledgement status: @status', ['@status' => $nvp_response['ACK']]);
        $result = array(
          'success' => NULL,
          'message' => $message,
          'uid' => $this->currentUser()->id(),
        );
        break;
    }

    uc_order_comment_save($order_id, $this->currentUser()->id(), $message, 'admin');

    // Don't log this as a payment money wasn't actually captured.
    if (in_array($data['txn_type'], array(UC_CREDIT_AUTH_ONLY))) {
      $result['log_payment'] = FALSE;
    }

    return $result;
  }

  /**
   * Builds error message(s) from PayPal failure responses.
   */
  protected function buildErrorMessages($nvp_response) {
    $code = 0;
    $message = '';
    while (array_key_exists('L_SEVERITYCODE' . $code, $nvp_response)) {
      $message .= '<br /><b>' . SafeMarkup::checkPlain($nvp_response['L_SEVERITYCODE' . $code]) . ':</b> ' . SafeMarkup::checkPlain($nvp_response['L_ERRORCODE' . $code]) . ': ' . SafeMarkup::checkPlain($nvp_response['L_LONGMESSAGE' . $code]);
      $code++;
    }
    return $message;
  }

  /**
   * Returns the PayPal approved credit card type for a card number.
   */
  protected function cardType($cc_number) {
    switch (substr(strval($cc_number), 0, 1)) {
      case '3':
        return 'Amex';
      case '4':
        return 'Visa';
      case '5':
        return 'MasterCard';
      case '6':
        return 'Discover';
    }

    return FALSE;
  }

  /**
   * Returns a human readable message for the AVS code.
   */
  protected function avscodeMessage($code) {
    if (is_numeric($code)) {
      switch ($code) {
        case '0':
          return t('All the address information matched.');
        case '1':
          return t('None of the address information matched; transaction declined.');
        case '2':
          return t('Part of the address information matched.');
        case '3':
          return t('The merchant did not provide AVS information. Not processed.');
        case '4':
          return t('Address not checked, or acquirer had no response. Service not available.');
        default:
          return t('No AVS response was obtained.');
      }
    }

    switch ($code) {
      case 'A':
      case 'B':
        return t('Address matched; postal code did not');
      case 'C':
      case 'N':
        return t('Nothing matched; transaction declined');
      case 'D':
      case 'F':
      case 'X':
      case 'Y':
        return t('Address and postal code matched');
      case 'E':
        return t('Not allowed for MOTO transactions; transaction declined');
      case 'G':
        return t('Global unavailable');
      case 'I':
        return t('International unavailable');
      case 'P':
      case 'W':
      case 'Z':
        return t('Postal code matched; address did not');
      case 'R':
        return t('Retry for validation');
      case 'S':
        return t('Service not supported');
      case 'U':
        return t('Unavailable');
      case 'Null':
        return t('No AVS response was obtained.');
      default:
        return t('An unknown error occurred.');
    }
  }

  /**
   * Returns a human readable message for the CVV2 match code.
   */
  protected function cvvmatchMessage($code) {
    if (is_numeric($code)) {
      switch ($code) {
        case '0':
          return t('Matched');
        case '1':
          return t('No match');
        case '2':
          return t('The merchant has not implemented CVV2 code handling.');
        case '3':
          return t('Merchant has indicated that CVV2 is not present on card.');
        case '4':
          return t('Service not available');
        default:
          return t('Unkown error');
      }
    }

    switch ($code) {
      case 'M':
        return t('Match');
      case 'N':
        return t('No match');
      case 'P':
        return t('Not processed');
      case 'S':
        return t('Service not supported');
      case 'U':
        return t('Service not available');
      case 'X':
        return t('No response');
      default:
        return t('Not checked');
    }
  }

}
