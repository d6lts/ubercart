<?php

/**
 * @file
 * Attribute administration menu items.
 */

/**
 * Returns a themed set of attribute options for use in order displays.
 *
 * @param $variables
 *   An associative array containing:
 *   - attributes: An associative array containing the set of attributes,
 *     with each element keyed by attribute ID:
 *     - <aid>: An associative array containing:
 *       - #attribute_name: Attribute name.
 *       - #options: Array of option names.
 *
 * @return
 *   Themed set of attribute options.
 *
 * @ingroup themeable
 */
function theme_uc_product_attributes($variables) {
  $attributes = $variables['attributes'];

  $option_rows = array();

  foreach (element_children($attributes) as $key) {
    $optionstr = '';

    foreach ((array)$attributes[$key]['#options'] as $option) {
      // We only need to allow translation from the second option onward
      if (empty($optionstr)) {
        $optionstr .= $option;
      }
      else {
        $optionstr .= t(', !option', array('!option' => $option));
      }
    }

    if ($optionstr != '') {
      $option_rows[$key] = t('@attribute: @option', array('@attribute' => $attributes[$key]['#attribute_name'], '@option' => $optionstr));
    }
  }

  if (!empty($option_rows)) {
    $item_list = array(
      '#theme' => 'item_list',
      '#items' => array_values($option_rows),
      '#attributes' => array(
        'class' => array('product-description'),
      ),
    );
    return drupal_render($item_list);
  }

  return '';
}
