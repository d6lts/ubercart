<?php

/**
 * @file
 * Contains \Drupal\uc_quote\Form\ShippingQuoteSettingsForm.
 */

namespace Drupal\uc_quote\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\uc_store\UcAddress;

/**
 * Default shipping settings form.
 *
 * Sets the default shipping location of the store. Allows the user to
 * determine which quoting methods are enabled and which take precedence over
 * the others. Also sets the default quote and shipping types of all products
 * in the store. Individual products may be configured differently.
 */
class ShippingQuoteSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'uc_quote_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $address = variable_get('uc_quote_store_default_address', new UcAddress());

    $form['uc_quote_log_errors'] = array(
      '#type' => 'checkbox',
      '#title' => t('Log errors during checkout to watchdog'),
      '#default_value' => variable_get('uc_quote_log_errors', FALSE),
    );
    $form['uc_quote_display_debug'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display debug information to administrators.'),
      '#default_value' => variable_get('uc_quote_display_debug', FALSE),
    );
    $form['uc_quote_require_quote'] = array(
      '#type' => 'checkbox',
      '#title' => t('Prevent the customer from completing an order if a shipping quote is not selected.'),
      '#default_value' => variable_get('uc_quote_require_quote', TRUE),
    );

    $form['uc_quote_pane_description'] = array(
      '#type' => 'details',
      '#title' => t('Shipping quote pane description'),
      '#tree' => TRUE,
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['uc_quote_pane_description']['text'] = array(
      '#type' => 'textarea',
      '#title' => t('Message text'),
      '#default_value' => variable_get('uc_quote_pane_description', t('Shipping quotes are generated automatically when you enter your address and may be updated manually with the button below.')),
    );

    $form['uc_quote_err_msg'] = array(
      '#type' => 'details',
      '#title' => t('Shipping quote error message'),
      '#tree' => TRUE,
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['uc_quote_err_msg']['text'] = array(
      '#type' => 'textarea',
      '#title' => t('Message text'),
      '#default_value' => variable_get('uc_quote_err_msg', t("There were problems getting a shipping quote. Please verify the delivery and product information and try again.\nIf this does not resolve the issue, please call in to complete your order.")),
    );

    $form['default_address'] = array(
      '#type' => 'details',
      '#title' => t('Default pickup address'),
      '#description' => t("When delivering products to customers, the original location of the product must be known in order to accurately quote the shipping cost and set up a delivery. This form provides the default location for all products in the store. If a product's individual pickup address is blank, Ubercart uses the store's default pickup address specified here."),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['default_address']['address'] = array(
      '#type' => 'uc_address',
      '#default_value' => isset($form_state['values']) ? $form_state['values'] : $address,
      '#required' => FALSE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $address = new UcAddress();
    $address->first_name = $form_state['values']['first_name'];
    $address->last_name = $form_state['values']['last_name'];
    $address->company = $form_state['values']['company'];
    $address->phone = $form_state['values']['phone'];
    $address->street1 = $form_state['values']['street1'];
    $address->street2 = $form_state['values']['street2'];
    $address->city = $form_state['values']['city'];
    $address->zone = $form_state['values']['zone'];
    $address->postal_code = $form_state['values']['postal_code'];
    $address->country = $form_state['values']['country'];

    variable_set('uc_quote_store_default_address', $address);
    variable_set('uc_quote_log_errors', $form_state['values']['uc_quote_log_errors']);
    variable_set('uc_quote_display_debug', $form_state['values']['uc_quote_display_debug']);
    variable_set('uc_quote_require_quote', $form_state['values']['uc_quote_require_quote']);
    variable_set('uc_quote_pane_description', $form_state['values']['uc_quote_pane_description']['text']);
    variable_set('uc_quote_err_msg', $form_state['values']['uc_quote_err_msg']['text']);

    parent::submitForm($form, $form_state);
  }

}
