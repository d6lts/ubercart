<?php

/**
 * @file
 * Theme functions for the uc_attribute module.
 */

/**
 * Displays an attribute option with an optional total or adjustment price.
 *
 * @param $variables
 *   An associative array containing:
 *   - option: The option name.
 *   - price: The price total or adjustment, if any.
 *
 * @see _uc_attribute_alter_form()
 * @ingroup themeable
 */
function theme_uc_attribute_option($variables) {
  $output = $variables['option'];
  if ($variables['price']) {
    $output .= ', ' . $variables['price'];
  }
  return $output;
}
