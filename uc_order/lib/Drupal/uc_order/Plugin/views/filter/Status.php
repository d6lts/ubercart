<?php

/**
 * @file
 * Contains \Drupal\uc_order\Plugin\views\filter\Status.
 */

namespace Drupal\uc_order\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter handler for order statuses.
 *
 * @ingroup views_filter_handlers
 *
 * @PluginID("uc_order_status")
 */
class Status extends InOperator {

  /**
   * Overrides InOperator::getValueOptions().
   */
  public function getValueOptions() {
    if (!isset($this->value_options)) {
      $this->value_title = t('Order status');
      $this->value_options = array_merge(array('_active' => t('Active')), uc_order_status_options_list());
    }
  }

  /**
   * Overrides InOperator::query().
   */
  public function query() {
    if (is_array($this->value) && in_array('_active', $this->value)) {
      $active = \Drupal::entityQuery('uc_order_status')
        ->condition('weight', 0, '>=')
        ->execute();
      $this->value = array_merge($this->value, $active);
    }

    parent::query();
  }

}
