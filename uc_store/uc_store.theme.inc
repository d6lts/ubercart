<?php

/**
 * @file
 * Theme functions for the uc_store module.
 */

/**
 * Displays a price in the standard format and with consistent markup.
 *
 * @param array $variables
 *   An associative array containing:
 *   - price: A numerical price value.
 *   - suffixes: An array of suffixes to be attached to this price.
 *
 * @ingroup themeable
 */
function theme_uc_price(array $variables) {
  $output = '<span class="uc-price">' . uc_currency_format($variables['price']) . '</span>';
  if (!empty($variables['suffixes'])) {
    $output .= '<span class="price-suffixes">' . implode(' ', $variables['suffixes']) . '</span>';
  }
  return $output;
}

/**
 * Displays "Quantity" abbreviated as "Qty".
 *
 * @ingroup themeable
 */
function theme_uc_qty_label() {
  return '<abbr title="' . t('Quantity') . '">' . t('Qty') . '</abbr>';
}

/**
 * Displays a quantity.
 *
 * @param array $variables
 *   An associative array containing:
 *   - qty: The quantity to display.
 *
 * @ingroup themeable
 */
function theme_uc_qty(array $variables) {
  return $variables['qty'] . ' ×';
}

/**
 * Displays a username in the standard format and with consistent markup.
 *
 * @param array $variables
 *   An associative array containing:
 *   - uid: A user ID value.
 *
 * @ingroup themeable
 */
function theme_uc_uid(array $variables) {
  if ($variables['uid']) {
    $username = array(
      '#theme' => 'username',
      '#account' => user_load($variables['uid']),
    );
    return drupal_render($username);
  }
  else {
    return '-';
  }
}
