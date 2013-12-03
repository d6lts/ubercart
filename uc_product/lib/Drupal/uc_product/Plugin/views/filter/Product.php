<?php

/**
 * @file
 * Contains \Drupal\uc_product\Plugin\views\filter\Product.
 */

namespace Drupal\uc_product\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\BooleanOperator;

/**
 * Filter handler for "is a product".
 *
 * @ingroup views_filter_handlers
 *
 * @PluginID("uc_product")
 */
class Product extends BooleanOperator {

  /**
   * Overrides BooleanOperator::query().
   */
  function query() {
    $types = uc_product_types();
    $this->query->addField('node', 'type');
    $this->query->addWhere($this->options['group'], 'node.type', $types, empty($this->value) ? 'NOT IN' : 'IN');
  }

}
