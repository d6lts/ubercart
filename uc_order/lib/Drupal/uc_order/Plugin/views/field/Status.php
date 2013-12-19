<?php

/**
 * @file
 * Contains \Drupal\uc_order\Plugin\views\field\Status.
 */

namespace Drupal\uc_order\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

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
  public function render(ResultRow $values) {
    $status = entity_load('uc_order_status', $this->getValue($values));
    return $this->sanitizeValue($status->name);
  }

}
