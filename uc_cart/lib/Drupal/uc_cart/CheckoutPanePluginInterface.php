<?php

/**
 * @file
 * Contains \Drupal\uc_cart\CheckoutPanePluginInterface.
 */

namespace Drupal\uc_cart;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\uc_order\UcOrderInterface;

/**
 * Defines an interface for checkout pane plugins.
 */
interface CheckoutPanePluginInterface extends PluginInspectionInterface {

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
  public function prepare(UcOrderInterface $order, array $form, array &$form_state);

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
  public function view(UcOrderInterface $order, array $form, array &$form_state);

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
  public function process(UcOrderInterface $order, array $form, array &$form_state);

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

}
