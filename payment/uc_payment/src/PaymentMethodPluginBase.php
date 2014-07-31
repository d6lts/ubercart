<?php

/**
 * @file
 * Contains \Drupal\uc_payment\PaymentMethodPluginBase.
 */

namespace Drupal\uc_payment;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\uc_order\UcOrderInterface;

/**
 * Defines a base payment method plugin implementation.
 */
abstract class PaymentMethodPluginBase extends PluginBase implements PaymentMethodPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function cartDetails(UcOrderInterface $order, array $form, FormStateInterface $form_state) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function cartProcess(UcOrderInterface $order, array $form, FormStateInterface $form_state) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function cartReviewTitle() {
    return !empty($this->pluginDefinition['review']) ? $this->pluginDefinition['review'] : $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function cartReview(UcOrderInterface $order) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function orderDelete(UcOrderInterface $order) {
  }

  /**
   * {@inheritdoc}
   */
  public function orderEditDetails(UcOrderInterface $order) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function orderEditProcess(UcOrderInterface $order, array $form, FormStateInterface $form_state) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function orderLoad(UcOrderInterface $order) {
  }

  /**
   * {@inheritdoc}
   */
  public function orderSave(UcOrderInterface $order) {
  }

  /**
   * {@inheritdoc}
   */
  public function orderSubmit(UcOrderInterface $order) {
  }

  /**
   * {@inheritdoc}
   */
  public function orderView(UcOrderInterface $order) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function customerView(UcOrderInterface $order) {
    return $this->orderView($order);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state) {
  }

}
