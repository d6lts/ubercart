<?php

/**
 * @file
 * Contains \Drupal\uc_fulfillment\Plugin\views\field\PackageWeight.
 */

namespace Drupal\uc_fulfillment\Plugin\views\field;

use Drupal\uc_store\Plugin\views\field\Weight;

/**
 * Field handler to provide the weight of the package.
 *
 * We cannot use a subquery because there is no way to make sure that all products
 * in packages have the same weight unit.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("uc_fulfillment_package_weight")
 */
class PackageWeight extends Weight {

  /**
   * Overrides views_handler::use_group_by().
   *
   * Disables aggregation for this field.
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * Overrides uc_product_handler_field_weight::query().
   */
  public function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  /**
   * Overrides uc_product_handler_field_weight::render().
   */
  public function render($values) {
    $package = Package::load($values->{$this->aliases['package_id']});

    if ($this->options['format'] == 'numeric') {
      return $package->getWeight();
    }

    if ($this->options['format'] == 'uc_weight') {
      return uc_weight_format($package->getWeight(), $package->getWeightUnits());
    }
  }
}
