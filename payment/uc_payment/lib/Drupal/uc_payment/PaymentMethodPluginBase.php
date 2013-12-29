<?php

/**
 * @file
 * Contains \Drupal\uc_payment\PaymentMethodPluginBase.
 */

namespace Drupal\uc_payment;

use Drupal\Core\Plugin\PluginBase;

/**
 * Defines a base payment method plugin implementation.
 */
abstract class PaymentMethodPluginBase extends PluginBase implements PaymentMethodPluginInterface {

 /**
   * {@inheritdoc}
   */
  function cartReviewTitle() {
    return !empty($this->pluginDefinition['review']) ? $this->pluginDefinition['review'] : $this->pluginDefinition['name'];
  }

}
