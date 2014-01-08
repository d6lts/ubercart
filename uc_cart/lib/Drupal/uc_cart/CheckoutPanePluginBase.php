<?php

/**
 * @file
 * Contains \Drupal\uc_cart\CheckoutPanePluginBase.
 */

namespace Drupal\uc_cart;

use Drupal\Core\Plugin\PluginBase;
use Drupal\uc_order\UcOrderInterface;

/**
 * Defines a base checkout pane plugin implementation.
 */
abstract class CheckoutPanePluginBase extends PluginBase implements CheckoutPanePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function prepare(UcOrderInterface $order, array $form, array &$form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function process(UcOrderInterface $order, array $form, array &$form_state) {
    return TRUE;
  }

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
