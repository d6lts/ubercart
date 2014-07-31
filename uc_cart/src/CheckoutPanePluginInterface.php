<?php

/**
 * @file
 * Contains \Drupal\uc_cart\CheckoutPanePluginInterface.
 */

namespace Drupal\uc_cart;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\uc_order\UcOrderInterface;

/**
 * Defines an interface for checkout pane plugins.
 */
interface CheckoutPanePluginInterface extends PluginInspectionInterface, ConfigurablePluginInterface {

  /**
   * Prepares a pane for display.
   *
   * @param \Drupal\uc_order\UcOrderInterface $order
   *   The order that is being processed.
   * @param array $form
   *   The checkout form array.
   * @param array $form_state
   *   The checkout form state array.
   */
  public function prepare(UcOrderInterface $order, array $form, FormStateInterface $form_state);

  /**
   * Returns the contents of a checkout pane.
   *
   * @param \Drupal\uc_order\UcOrderInterface $order
   *   The order that is being processed.
   * @param array $form
   *   The checkout form array.
   * @param array $form_state
   *   The checkout form state array.
   *
   * @return array
   *   A form array.
   */
  public function view(UcOrderInterface $order, array $form, FormStateInterface $form_state);

  /**
   * Processes a checkout pane.
   *
   * @param \Drupal\uc_order\UcOrderInterface $order
   *   The order that is being processed.
   * @param array $form
   *   The checkout form array.
   * @param array $form_state
   *   The checkout form state array.
   *
   * @return bool
   *   TRUE if the pane is valid, FALSE otherwise..
   */
  public function process(UcOrderInterface $order, array $form, FormStateInterface $form_state);

  /**
   * Returns the review contents of a checkout pane.
   *
   * @param \Drupal\uc_order\UcOrderInterface $order
   *   The order that is being processed.
   *
   * @return array
   *   A checkout review array.
   */
  public function review(UcOrderInterface $order);

  /**
   * Returns the settings form for a checkout pane.
   *
   * @return array
   *   A form array.
   */
  public function settingsForm();

  /**
   * Returns the title of the checkout pane.
   *
   * @return string
   *   The pane title.
   */
  public function getTitle();

  /**
   * Returns whether the checkout pane is enabled.
   *
   * @return bool
   *   TRUE if the pane is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Returns the weight of the checkout pane.
   *
   * @return int
   *   The integer weight of the checkout pane.
   */
  public function getWeight();
}
