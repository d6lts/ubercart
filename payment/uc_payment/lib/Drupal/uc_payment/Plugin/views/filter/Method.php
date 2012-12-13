<?php

/**
 * @file
 * Definition of Drupal\uc_payment\Plugin\views\filter\Method.
 */

namespace Drupal\uc_payment\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\Core\Annotation\Plugin;

/**
 * Filter handler for payment methods.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "uc_payment_method",
 *   module = "uc_payment"
 * )
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
