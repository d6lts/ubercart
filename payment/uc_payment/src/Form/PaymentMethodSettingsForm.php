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
   */
  protected $instance;

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
    $form['#title'] = $this->t('!method settings', array('!method' => $definition['name']));

    $this->instance = \Drupal::service('plugin.manager.uc_payment.method')->createInstance($method);
    $form = $this->instance->settingsForm($form, $form_state);
    $form['#submit'][] = array($this, 'submitForm');
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    form_state_values_clean($form_state);
    $this->instance->submitConfigurationForm($form, $form_state);
    parent::submitForm($form, $form_state);
  }

}
