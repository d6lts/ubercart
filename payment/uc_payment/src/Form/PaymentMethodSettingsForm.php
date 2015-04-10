<?php

/**
 * @file
 * Contains \Drupal\uc_payment\Form\PaymentMethodSettingsForm.
 */

namespace Drupal\uc_payment\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure available payment methods for the store.
 */
class PaymentMethodSettingsForm extends ConfigFormBase {

  /**
   * The plugin instance that is being configured.
   *
   * @var \Drupal\uc_payment\PaymentMethodPluginInterface
   */
  protected $instance;

  /**
   * The plugin settings form.
   *
   * @var \Drupal\Core\Form\FormInterface
   */
  protected $settings;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'uc_payment_method_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'uc_payment.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $method = NULL) {
    $definition = \Drupal::service('plugin.manager.uc_payment.method')->getDefinition($method);
    $form['#title'] = $this->t('!method settings', ['!method' => $definition['name']]);

    $this->instance = \Drupal::service('plugin.manager.uc_payment.method')->createInstance($method);
    $this->settings = $this->instance->getSettingsForm();

    $form = $this->settings->buildForm($form, $form_state);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->settings->validateForm($form, $form_state);

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $this->settings->submitForm($form, $form_state);

    return parent::submitForm($form, $form_state);
  }

}
