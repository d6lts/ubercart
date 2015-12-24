<?php

/**
 * @file
 * Theme functions for the uc_credit module.
 */

use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Themes the credit card CVV help link.
 */
function theme_uc_credit_cvv_help(array $variables) {
  $output = '<div class="uc-credit-cvv-help">';
  $image = array(
    '#theme' => 'image',
    '#uri' => drupal_get_path('module', 'uc_credit') . '/images/info.png',
  );
  $output .= drupal_render($image);
  $output .= ' ' . Link::fromTextAndUrl(t("What's the CVV?"), Url::fromRoute('uc_credit.cvv', [], ['attributes' => [
    'onclick' => "window.open(this.href, 'CVV_Info', 'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,width=480,height=460'); return false;",
  ]]))->toString();
  $output .= '</div>';

  return $output;
}
