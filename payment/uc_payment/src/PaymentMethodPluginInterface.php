<?php

/**
 * @file
 * Contains \Drupal\uc_payment\PaymentMethodPluginInterface.
 */

namespace Drupal\uc_payment;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\UcOrderInterface;

/**
 * Defines an interface for payment method plugins.
 */
interface PaymentMethodPluginInterface extends PluginInspectionInterface {

  /**
   * Returns the form or render array to be displayed at checkout.
   *
   * @param \Drupal\uc_order\UcOrderInterface $order
   *   The order which is being processed.
   * @param array $form
   *   The checkout form array.
   * @param array $form_state
   *   The checkout form state array.
   *
   * @return array
   *   A form or render array.
   */
  public function cartDetails(UcOrderInterface $order, array $form, FormStateInterface $form_state);

  /**
   * Called when checkout is submitted with this payment method selected.
   *
   * Use this method to process any form elements output by the cartDetails()
   * method.
   *
   * @param \Drupal\uc_order\UcOrderInterface $order
   *   The order which is being processed.
   * @param array $form
   *   The checkout form array.
   * @param array $form_state
   *   The checkout form state array.
   *
   * @return bool
   *   Return FALSE to abort the checkout process, or any other value to
   *   continue the checkout process.
   */
  public function cartProcess(UcOrderInterface $order, array $form, FormStateInterface $form_state);

  /**
   * Returns the payment method title to be used on the checkout review page.
   *
   * @return string
   *   The payment method title.
   */
  public function cartReviewTitle();

  /**
   * Returns the payment method review details.
   *
   * @param \Drupal\uc_order\UcOrderInterface $order
   *   The order that is being processed.
   *
   * @return array
   */
  public function cartReview(UcOrderInterface $order);

  /**
   * Called when an order is being deleted.
   *
   * @param \Drupal\uc_order\UcOrderInterface $order
   *   The order that is being deleted.
   */
  public function orderDelete(UcOrderInterface $order);

  /**
   * Called when an order is being edited with this payment method.
   *
   * @param \Drupal\uc_order\UcOrderInterface $order
   *   The order that is being edited.
   *
   * @return array
   *   A form array.
   */
  public function orderEditDetails(UcOrderInterface $order);

  /**
   * Called when an order is being submitted after being edited.
   *
   * @param \Drupal\uc_order\UcOrderInterface $order
   *   The order that is being edited.
   * @param array $form
   *   The form array.
   * @param array $form_state
   *   The form state array.
   *
   * @return array
   *   An array of changes to log against the order.
   */
  public function orderEditProcess(UcOrderInterface $order, array $form, FormStateInterface $form_state);

  /**
   * Called when an order is being loaded with this payment method.
   *
   * @param \Drupal\uc_order\UcOrderInterface $order
   *   The order that is being loaded.
   */
  public function orderLoad(UcOrderInterface $order);

  /**
   * Called when an order is being saved with this payment method.
   *
   * @param \Drupal\uc_order\UcOrderInterface $order
   *   The order that is being saved.
   */
  public function orderSave(UcOrderInterface $order);

  /**
   * Called when an order is being submitted with this payment method.
   *
   * @param \Drupal\uc_order\UcOrderInterface $order
   *   The order that is being submitted.
   */
  public function orderSubmit(UcOrderInterface $order);

  /**
   * Called when an order is being viewed by an administrator.
   *
   * @param \Drupal\uc_order\UcOrderInterface $order
   *   The order that is being viewed.
   *
   * @return array
   *   A render array.
   */
  public function orderView(UcOrderInterface $order);

  /**
   * Called when an order is being viewed by a customer.
   *
   * @param \Drupal\uc_order\UcOrderInterface $order
   *   The order that is being viewed.
   *
   * @return array
   *   A render array.
   */
  public function customerView(UcOrderInterface $order);

  /**
   * Form builder function for the payment method settings form.
   *
   * @param array $form
   *   The form array.
   * @param array $form_state
   *   The form state array.
   *
   * @return array
   *   The settings form.
   */
  public function settingsForm(array $form, FormStateInterface $form_state);

}
