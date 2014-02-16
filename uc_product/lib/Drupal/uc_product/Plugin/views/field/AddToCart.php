<?php

/**
 * @file
 * Contains \Drupal\uc_product\Plugin\views\field\AddToCart.
 */

namespace Drupal\uc_product\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to provide payment method.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("uc_product_add_to_cart")
 */
class AddToCart extends FieldPluginBase {

  /**
   * Overrides FieldPluginBase::render().
   */
  public function render(ResultRow $values) {
    $nid = $this->getValue($values);
    $node = node_load($nid);
    if (uc_product_is_product($node)) {
      $form = drupal_get_form('\Drupal\uc_product\Form\AddToCartForm', $node);
      return drupal_render($form);
    }
  }

}
