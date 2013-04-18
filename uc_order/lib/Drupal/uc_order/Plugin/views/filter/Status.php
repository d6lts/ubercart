<?php

/**
 * @file
 * Definition of Drupal\uc_order\Plugin\views\filter\Status.
 */

namespace Drupal\uc_order\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\Component\Annotation\PluginID;

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
  function getValueOptions() {
    if (!isset($this->value_options)) {
      $options['_active'] = t('Active');
      foreach (uc_order_status_list() as $status => $name) {
        $options[$name['id']] = $name['title'];
      }

      $this->value_title = t('Order status');
      $this->value_options = $options;
    }
  }

  /**
   * Overrides InOperator::query().
   */
  function query() {
    if (is_array($this->value) && in_array('_active', $this->value)) {
      $this->value = array_merge($this->value, array_values(uc_order_status_list('general', TRUE)));
    }

    parent::query();
  }

}
