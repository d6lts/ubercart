<?php

/**
 * @file
 * Definition of Drupal\uc_order\Plugin\views\field\Status.
 */

namespace Drupal\uc_order\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Component\Annotation\PluginID;
use Drupal\views\ViewExecutable;

/**
 * Field handler to provide order status.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("uc_order_status")
 */
class Status extends FieldPluginBase {

  /**
   * Overrides FieldPluginBase::render().
   */
  function render($values) {
    $value = $this->getValue($values);
    return $this->sanitizeValue(uc_order_status_data($value, 'title'));
  }

}
