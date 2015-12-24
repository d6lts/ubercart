<?php

/**
 * @file
 * Theme functions for the uc_product_kit module.
 */

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Link;
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
      $title = Link::createFromRoute($product->label(), 'entity.node.canonical', ['node' => $product->id()])->toString();
    }
    else {
      $title = SafeMarkup::checkPlain($product->label());
    }
    $qty = $product->qty;
  }
  else {
    $node = node_load($product->nid->target_id);
    if ($node->access('view')) {
      $title = Link::createFromRoute($product->title, 'entity.node.canonical', ['node' => $product->nid->target_id])->toString();
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
