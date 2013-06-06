<?php

/**
 * @file
 * Definition of Drupal\uc_order\Plugin\views\field\Actions.
 */

namespace Drupal\uc_order\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Component\Annotation\PluginID;
use Drupal\views\ViewExecutable;

/**
 * Field handler to provide action icons.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("uc_order_actions")
 */
class Actions extends FieldPluginBase {

  /**
   * Overrides FieldPluginBase::render().
   */
  function render($values) {
    $order_id = $this->getValue($values);
    $order = uc_order_load($values->order_id);
    return uc_order_actions($order, TRUE);
  }

}
