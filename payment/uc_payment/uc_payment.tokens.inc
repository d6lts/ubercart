<?php

/**
 * @file
 * Token hooks for the uc_payment module.
 */

use Drupal\Component\Plugin\Exception\PluginException;

/**
 * Implements hook_token_info().
 */
function uc_payment_token_info() {
  $order['payment-method'] = array(
    'name' => t('Payment method'),
    'description' => t('The payment method of the order.'),
  );
  $order['payment-balance'] = array(
    'name' => t('Balance'),
    'description' => t('The payment balance of the order'),
  );

  return array(
    'tokens' => array('uc_order' => $order),
  );
}

/**
 * Implements hook_tokens().
 */
function uc_payment_tokens($type, $tokens, $data = array(), $options = array()) {
  $values = array();

  if ($type == 'uc_order' && !empty($data['uc_order'])) {
    $order = $data['uc_order'];

    if (isset($tokens['payment-method'])) {
      $original = $tokens['payment-method'];
      try {
        $values[$original] = Drupal::service('plugin.manager.uc_payment.method')->createFromOrder($order)->cartReviewTitle();
      }
      catch (PluginException $e) {
        $values[$original] = t('Other');
      }
    }

    if (isset($tokens['payment-balance'])) {
      $original = $tokens['payment-balance'];
      $values[$original] = uc_currency_format(uc_payment_balance($order));
    }
  }

  return $values;
}
