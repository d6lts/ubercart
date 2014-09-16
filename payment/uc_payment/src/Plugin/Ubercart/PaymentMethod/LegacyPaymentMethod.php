<?php

/**
 * @file
 * Contains \Drupal\uc_payment\Plugin\Ubercart\PaymentMethod\LegacyPaymentMethod.
 */

namespace Drupal\uc_payment\Plugin\Ubercart\PaymentMethod;

use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\UcOrderInterface;
use Drupal\uc_payment\PaymentMethodPluginBase;

/**
 * Defines a payment method plugin implementation for legacy payment methods.
 */
class LegacyPaymentMethod extends PaymentMethodPluginBase {

  /**
   * {@inheritdoc}
   */
  function cartDetails(UcOrderInterface $order, array $form, FormStateInterface $form_state) {
    return $this->pluginDefinition['callback']('cart-details', $order, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  function cartProcess(UcOrderInterface $order, array $form, FormStateInterface $form_state) {
    return $this->pluginDefinition['callback']('cart-process', $order, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  function cartReview(UcOrderInterface $order) {
    return $this->pluginDefinition['callback']('cart-review', $order);
  }

  /**
   * {@inheritdoc}
   */
  public function orderDelete(UcOrderInterface $order) {
    return $this->pluginDefinition['callback']('order-delete', $order);
  }

  /**
   * {@inheritdoc}
   */
  public function orderEditDetails(UcOrderInterface $order) {
    return $this->pluginDefinition['callback']('order-details', $order);
  }

  /**
   * {@inheritdoc}
   */
  public function orderEditProcess(UcOrderInterface $order, array $form, FormStateInterface $form_state) {
    return $this->pluginDefinition['callback']('edit-process', $order, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function orderLoad(UcOrderInterface $order) {
    return $this->pluginDefinition['callback']('order-load', $order);
  }

  /**
   * {@inheritdoc}
   */
  public function orderSave(UcOrderInterface $order) {
    return $this->pluginDefinition['callback']('order-save', $order);
  }

  /**
   * {@inheritdoc}
   */
  public function orderSubmit(UcOrderInterface $order) {
    return $this->pluginDefinition['callback']('order-submit', $order);
  }

  /**
   * {@inheritdoc}
   */
  public function orderView(UcOrderInterface $order) {
    return $this->pluginDefinition['callback']('order-view', $order);
  }

  /**
   * {@inheritdoc}
   */
  public function customerView(UcOrderInterface $order) {
    return $this->pluginDefinition['callback']('customer-view', $order);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $null = NULL;
    return $this->pluginDefinition['callback']('settings', $null, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state) {
    // @todo Refactor when uc_credit is moved to a separate plugin.
    if ($this->pluginId == 'credit') {
      \Drupal::config('uc_credit.settings')
        ->set('encryption_path', $form_state->getValue('uc_credit_encryption_path'))
        ->save();
    }
  }

}
