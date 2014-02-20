<?php

/**
 * @file
 * Theme functions for the uc_product_kit module.
 */

use Drupal\node\NodeInterface;

/**
 * Renders a product kit component.
 *
 * @ingroup themeable
 */
function theme_uc_product_kit_list_item($variables) {
  $product = $variables['product'];

  if ($product instanceof NodeInterface) {
    $node = node_load($product->id());
    if ($node->access('view')) {
      $title = l($product->label(), 'node/' . $product->id());
    }
    else {
      $title = check_plain($product->label());
    }
    $qty = $product->qty;
  }
  else {
    $node = node_load($product->nid->value);
    if ($node->access('view')) {
      $title = l($product->title, 'node/' . $product->nid->value);
    }
    else {
      $title = check_plain($product->title);
    }
    $qty = $product->qty->value;
  }

  $build = array(
    '#theme' => 'uc_qty',
    '#qty' => $qty,
  );
  return drupal_render($build) . ' ' . $title;
}
