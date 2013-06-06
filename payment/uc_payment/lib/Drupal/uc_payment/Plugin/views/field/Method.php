<?php

/**
 * @file
 * Definition of Drupal\uc_payment\Plugin\views\field\Method.
 */

namespace Drupal\uc_payment\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Component\Annotation\PluginID;

/**
 * Field handler to provide payment method.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("uc_payment_method")
 */
class Method extends FieldPluginBase {

  /**
   * Overrides FieldPluginBase::render().
   */
  function render($values) {
    $value = $this->getValue($values);
    return $this->sanitizeValue(_uc_payment_method_data($value, 'name'));
  }

}
