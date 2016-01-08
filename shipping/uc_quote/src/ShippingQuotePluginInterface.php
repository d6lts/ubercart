<?php

/**
 * @file
 * Contains \Drupal\uc_quote\ShippingQuotePluginInterface.
 */

namespace Drupal\uc_quote;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\uc_order\OrderInterface;

/**
 * Defines an interface for shipping quote plugins.
 */
interface ShippingQuotePluginInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Returns a description of this shipping quote.
   *
   * @return string
   *   The description.
   */
  public function getDescription();

  /**
   * Retrieves shipping quotes for this method.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order to retrieve the quotes for.
   *
   * @return array
   *   An array of shipping quotes.
   */
  public function getQuotes(OrderInterface $order);

}
