<?php

/**
 * @file
 * Theme functions for the uc_product_kit module.
 */

use Drupal\Component\Utility\SafeMarkup;
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
      $title = \Drupal::l($product->label(), Url::fromRoute('entity.node.canonical', ['node' => $product->id()]));
    }
    else {
      $title = SafeMarkup::checkPlain($product->label());
    }
    $qty = $product->qty;
  }
  else {
    $node = node_load($product->nid->target_id);
    if ($node->access('view')) {
      $title = \Drupal::l($product->title, Url::fromRoute('entity.node.canonical', ['node' => $product->nid->target_id]));
    }
    else {
      $title = SafeMarkup::checkPlain($product->title);
    }
    $qty = $product->qty->value;
  }

  $build = array(
    '#theme' => 'uc_qty',
    '#qty' => $qty,
  );
  return drupal_render($build) . ' ' . $title;
}
