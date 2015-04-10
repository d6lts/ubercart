<?php

/**
 * @file
 * Contains \Drupal\uc_payment\PaymentMethodPluginBase.
 */

namespace Drupal\uc_payment;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\uc_order\OrderInterface;

/**
 * Defines a base payment method plugin implementation.
 */
abstract class PaymentMethodPluginBase extends PluginBase implements PaymentMethodPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function cartDetails(OrderInterface $order, array $form, FormStateInterface $form_state) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function cartProcess(OrderInterface $order, array $form, FormStateInterface $form_state) {
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
  public function cartReview(OrderInterface $order) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function orderDelete(OrderInterface $order) {
  }

  /**
   * {@inheritdoc}
   */
  public function orderEditDetails(OrderInterface $order) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function orderEditProcess(OrderInterface $order, array $form, FormStateInterface $form_state) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function orderLoad(OrderInterface $order) {
  }

  /**
   * {@inheritdoc}
   */
  public function orderSave(OrderInterface $order) {
  }

  /**
   * {@inheritdoc}
   */
  public function orderSubmit(OrderInterface $order) {
  }

  /**
   * {@inheritdoc}
   */
  public function orderView(OrderInterface $order) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function customerView(OrderInterface $order) {
    return $this->orderView($order);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm() {
    return NULL;
  }
}
