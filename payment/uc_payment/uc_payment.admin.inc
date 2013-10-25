<?php

/**
 * @file
 * Payment administration menu items.
 */


/**
 * Displays settings for a single payment method.
 */
function uc_payment_method_settings_form($form, &$form_state, $method_id) {
  $callback = _uc_payment_method_data($method_id, 'callback');
  $null = NULL;
  $form = $callback('settings', $null, array(), $form_state);
  return system_settings_form($form);
}

/**
 * Confirmation form to delete a payment from an order.
 *
 * @see uc_payment_delete_confirm_form_submit()
 * @ingroup forms
 */
function uc_payment_delete_confirm_form($form, &$form_state, $order, $payment) {
  // Make sure the payment is for the specified order.
  if ($payment->order_id != $order->id()) {
    drupal_set_message(t('An error loading the payment information occurred.'));
    drupal_goto('admin/store/orders/' . $order->id() . '/payments');
  }

  $desc = '<strong>' . t('Payment information:') . '</strong> '
        . t('@method payment of @amount received on @date.', array('@method' => $payment->method, '@amount' => uc_currency_format($payment->amount), '@date' => format_date($payment->received, 'short')));

  $form['order_id'] = array(
    '#type' => 'value',
    '#value' => $order->id()
  );
  $form['receipt_id'] = array(
    '#type' => 'value',
    '#value' => $payment->receipt_id,
  );

  return confirm_form($form, t('Are you sure you want to delete this payment?'), 'admin/store/orders/' . $order->id() . '/payments', $desc, t('Delete'));
}

/**
 * Form submission handler for uc_payment_delete_confirm_form().
 *
 * @see uc_payment_delete_confirm_form()
 */
function uc_payment_delete_confirm_form_submit($form, &$form_state) {
  uc_payment_delete($form_state['values']['receipt_id']);

  drupal_set_message(t('Payment deleted.'));

  $form_state['redirect'] = 'admin/store/orders/' . $form_state['values']['order_id'] . '/payments';
}
