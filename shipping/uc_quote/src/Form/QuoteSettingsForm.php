<?php

/**
 * @file
 * Contains \Drupal\uc_quote\Form\QuoteSettingsForm.
 */

namespace Drupal\uc_quote\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_store\Address;

/**
 * Default shipping settings form.
 *
 * Sets the default shipping location of the store. Allows the user to
 * determine which quoting methods are enabled and which take precedence over
 * the others. Also sets the default quote and shipping types of all products
 * in the store. Individual products may be configured differently.
 */
class QuoteSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'uc_quote_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'uc_quote.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $quote_config = $this->config('uc_quote.settings');
    $address = $quote_config->get('store_default_address');

    $form['uc_quote_log_errors'] = array(
      '#type' => 'checkbox',
      '#title' => t('Log errors during checkout to watchdog'),
      '#default_value' => $quote_config->get('log_errors'),
    );
    $form['uc_quote_display_debug'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display debug information to administrators.'),
      '#default_value' => $quote_config->get('display_debug'),
    );
    $form['uc_quote_require_quote'] = array(
      '#type' => 'checkbox',
      '#title' => t('Prevent the customer from completing an order if a shipping quote is not selected.'),
      '#default_value' => $quote_config->get('require_quote'),
    );

    $form['default_address'] = array(
      '#type' => 'details',
      '#title' => t('Default pickup address'),
      '#description' => t("When delivering products to customers, the original location of the product must be known in order to accurately quote the shipping cost and set up a delivery. This form provides the default location for all products in the store. If a product's individual pickup address is blank, Ubercart uses the store's default pickup address specified here."),
    );
    $form['default_address']['address'] = array(
      '#type' => 'uc_address',
      '#default_value' => $form_state->getValues() ?: $address,
      '#required' => FALSE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $address = new Address();
    $address->first_name = $form_state->getValue('first_name');
    $address->last_name = $form_state->getValue('last_name');
    $address->company = $form_state->getValue('company');
    $address->phone = $form_state->getValue('phone');
    $address->street1 = $form_state->getValue('street1');
    $address->street2 = $form_state->getValue('street2');
    $address->city = $form_state->getValue('city');
    $address->zone = $form_state->getValue('zone');
    $address->postal_code = $form_state->getValue('postal_code');
    $address->country = $form_state->getValue('country');

    $quote_config = $this->config('uc_quote.settings');
    $quote_config
      ->set('store_default_address', (array) $address)
      ->set('log_errors', $form_state->getValue('uc_quote_log_errors'))
      ->set('display_debug', $form_state->getValue('uc_quote_display_debug'))
      ->set('require_quote', $form_state->getValue('uc_quote_require_quote'))
      ->set('pane_description', $form_state->getValue(['uc_quote_pane_description', 'text']))
      ->set('error_message', $form_state->getValue(['uc_quote_error_message', 'text']))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
