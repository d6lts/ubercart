<?php

/**
 * @file
 * Contains \Drupal\uc_payment_pack\Form\CashOnDeliverySettingsForm.
 */

namespace Drupal\uc_payment_pack\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for recording a received check and expected clearance date.
 */
class CashOnDeliverySettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'uc_payment_pack_cod_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cod_config = $this->config('uc_payment_pack.cod.settings');

    $form['uc_cod_policy'] = array(
      '#type' => 'textarea',
      '#title' => t('Policy message'),
      '#default_value' => $cod_config->get('policy'),
      '#description' => t('Help message shown at checkout.'),
    );
    $form['uc_cod_max_order'] = array(
      '#type' => 'textfield',
      '#title' => t('Maximum order total eligible for COD'),
      '#default_value' => $cod_config->get('max_order'),
      '#description' => t('Set to 0 for no maximum order limit.'),
    );
    $form['uc_cod_delivery_date'] = array(
      '#type' => 'checkbox',
      '#title' => t('Let customers enter a desired delivery date.'),
      '#default_value' => $cod_config->get('delivery_date'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cod_config = $this->configFactory()->getEditable('uc_payment_pack.cod.settings');
    $cod_config
      ->set('policy', $form_state->getValue('uc_cod_policy'))
      ->set('max_order', $form_state->getValue('uc_cod_max_order'))
      ->set('delivery_date', $form_state->getValue('uc_cod_delivery_date'))
      ->save();
  }
}

