<?php

/**
 * @file
 * Definition of Drupal\uc_payment\Plugin\views\field\Method.
 */

namespace Drupal\uc_payment\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to provide payment method.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "uc_payment_method",
 *   module = "uc_payment"
 * )
 */
class Method extends FieldPluginBase {

  /**
   * Overrides FieldPluginBase::render().
   */
  function render($values) {
    $value = $this->get_value($values);
    return $this->sanitizeValue(_uc_payment_method_data($value, 'name'));
  }

}
