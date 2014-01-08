<?php

/**
 * @file
 * Contains \Drupal\uc_cart\Plugin\Ubercart\CheckoutPane\LegacyCheckoutPane.
 */

namespace Drupal\uc_cart\Plugin\Ubercart\CheckoutPane;

use Drupal\uc_cart\CheckoutPanePluginBase;
use Drupal\uc_order\UcOrderInterface;

/**
 * Defines a plugin implementation for legacy checkout panes.
 */
class LegacyCheckoutPane extends CheckoutPanePluginBase {

  /**
   * {@inheritdoc}
   */
  public function prepare(UcOrderInterface $order, array $form, array &$form_state) {
    $this->pluginDefinition['callback']('prepare', $order, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function view(UcOrderInterface $order, array $form, array &$form_state) {
    $pane = $this->pluginDefinition['callback']('view', $order, $form, $form_state);

    $build = $pane['contents'];
    if (isset($pane['description'])) {
      $build['#description'] = $pane['description'];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function process(UcOrderInterface $order, array $form, array &$form_state) {
    $this->pluginDefinition['callback']('process', $order, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function review(UcOrderInterface $order) {
    return $this->pluginDefinition['callback']('review', $order, NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    $null = NULL;
    return $this->pluginDefinition['callback']('settings', $null, array());
  }

}
