<?php

/**
 * @file
 * Contains \Drupal\uc_payment\Plugin\views\filter\Method.
 */

namespace Drupal\uc_payment\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter handler for payment methods.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("uc_payment_method")
 */
class Method extends InOperator {

  /**
   * Overrides InOperator::getValueOptions().
   */
  function getValueOptions() {
    if (!isset($this->value_options)) {
      $this->value_options = array();

      $methods = _uc_payment_method_list();
      foreach ($methods as $method) {
        $this->value_options[$method['id']] = $method['name'];
      }
    }
  }

}
