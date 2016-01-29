<?php

/**
 * @file
 * Contains \Drupal\uc_2checkout\Form\WppSettingsForm.
 */

namespace Drupal\uc_paypal\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings for Website Payments Pro on the credit card gateways form.
 *
 * This provides a subset of the Express Checkout settings.
 */
class WppSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_paypal_wpp_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // This form is buildConfigurationForm() from PayPalPaymentMethodPluginBase

    // @todo: figure out how to get that in here - doesn't matter right now
    // because we don't have a payement gateway architecture yet so this
    // WppSettingsForm may look entirely different.

    //$form = uc_payment_method_paypal_ec('settings');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }

}
