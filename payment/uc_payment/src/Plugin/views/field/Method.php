<?php

/**
 * @file
 * Contains \Drupal\uc_payment\Plugin\views\field\Method.
 */

namespace Drupal\uc_payment\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to provide payment method.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("uc_payment_method")
 */
class Method extends FieldPluginBase {

  /**
   * Overrides FieldPluginBase::render().
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    $methods = uc_payment_method_list();
    return $this->sanitizeValue($methods[$value]['name']);
  }

}
