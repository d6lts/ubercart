<?php

/**
 * @file
 * Contains \Drupal\uc_cart\CheckoutPanePluginBase.
 */

namespace Drupal\uc_cart;

use Drupal\Core\Plugin\PluginBase;

/**
 * Defines a base checkout pane plugin implementation.
 */
abstract class CheckoutPanePluginBase extends PluginBase implements CheckoutPanePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->pluginDefinition['title'];
  }

}
