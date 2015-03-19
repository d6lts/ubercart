<?php

/**
 * @file
 * Theme functions for the uc_product_kit module.
 */

use Drupal\Component\Utility\String;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

/**
 * Renders a product kit component.
 *
 * @ingroup themeable
 */
function theme_uc_product_kit_list_item(array $variables) {
  $product = $variables['product'];

  if ($product instanceof NodeInterface) {
    $node = node_load($product->id());
    if ($node->access('view')) {
      $title = \Drupal::l($product->label(), new Url('entity.node.canonical', ['node' => $product->id()]));
    }
    else {
      $title = String::checkPlain($product->label());
    }
    $qty = $product->qty;
  }
  else {
    $node = node_load($product->nid->target_id);
    if ($node->access('view')) {
      $title = \Drupal::l($product->title, new Url('entity.node.canonical', ['node' => $product->nid->target_id]));
    }
    else {
      $title = String::checkPlain($product->title);
    }
    $qty = $product->qty->value;
  }

  $build = array(
    '#theme' => 'uc_qty',
    '#qty' => $qty,
  );
  return drupal_render($build) . ' ' . $title;
}
