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
