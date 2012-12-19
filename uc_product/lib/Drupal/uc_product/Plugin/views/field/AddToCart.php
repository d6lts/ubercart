<?php

/**
 * @file
 * Definition of Drupal\uc_product\Plugin\views\field\AddToCart.
 */

namespace Drupal\uc_product\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to provide payment method.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "uc_product_add_to_cart",
 *   module = "uc_product"
 * )
 */
class AddToCart extends FieldPluginBase {

  /**
   * Overrides FieldPluginBase::render().
   */
  function render($values) {
    $nid = $this->get_value($values);
    $node = node_load($nid);
    if (uc_product_is_product($node)) {
      $form = drupal_get_form('uc_product_add_to_cart_form_' . $nid, $node);
      return drupal_render($form);
    }
  }

}
