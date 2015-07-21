<?php

/**
 * @file
 * Contains \Drupal\uc_order\Plugin\views\field\Status.
 */

namespace Drupal\uc_order\Plugin\views\field;

use Drupal\uc_order\Entity\OrderStatus;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to provide order status.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("uc_order_status")
 */
class Status extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $status = OrderStatus::load($this->getValue($values));
    return $this->sanitizeValue($status->getName());
  }

}
