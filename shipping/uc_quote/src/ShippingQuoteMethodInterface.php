<?php

/**
 * @file
 * Contains \Drupal\uc_quote\ShippingQuoteMethodInterface.
 */

namespace Drupal\uc_quote;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining shipping quote method entities.
 */
interface ShippingQuoteMethodInterface extends ConfigEntityInterface {

  /**
   * Returns the weight of this shipping quote method (used for sorting).
   *
   * @return int
   *   The shipping quote method weight.
   */
  public function getWeight();

  /**
   * Returns the plugin ID.
   *
   * @return string
   *   The plugin ID for this shipping quote method.
   */
  public function getPluginId();

  /**
   * Returns the plugin configuration.
   *
   * @return array
   *   The plugin configuration for this shipping quote method.
   */
  public function getPluginConfiguration();

}
