<?php

/**
 * @file
 * Contains \Drupal\uc_payment\PaymentMethodInterface.
 */

namespace Drupal\uc_payment;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining payment method entities.
 */
interface PaymentMethodInterface extends ConfigEntityInterface {

  /**
   * Returns the weight of this payment method (used for sorting).
   *
   * @return int
   *   The payment method weight.
   */
  public function getWeight();

  /**
   * Returns the plugin instance.
   *
   * @return \Drupal\uc_payment\PaymentMethodPluginInterface
   *   The plugin instance for this payment method.
   */
  public function getPlugin();

}
