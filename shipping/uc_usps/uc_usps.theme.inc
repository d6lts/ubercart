<?php

/**
 * @file
 * Theme functions for the uc_usps module.
 */

/**
 * Theme function to format the USPS service name and rate amount line-item
 * shown to the customer.
 *
 * @param array $variables
 *   Associative array containing information needed to theme a quote.
 *   Contains two keys:
 *   - service: The USPS service name.
 *   - packages: Package information.
 *
 * @return string
 *   Formatted HTML.
 *
 * @ingroup themeable
 */
function theme_uc_usps_option_label(array $variables) {
  $service = $variables['service'];
  $packages = $variables['packages'];

  // Start with USPS logo.
  $output  = theme('image', array(
    'uri' => drupal_get_path('module', 'uc_usps') . '/uc_usps_logo.gif',
    'alt' => t('U.S.P.S.'),
    'attributes' => array('class' => 'usps-logo')
  ));

  // Add USPS service name, removing any 'U.S.P.S.' prefix.
  $output .= preg_replace('/^U\.S\.P\.S\./', '', $service);

  // Add package information
  $output .= ' (' . \Drupal::translation()->formatPlural(count($packages), '1 package', '@count packages') . ')';

  return $output;
}
