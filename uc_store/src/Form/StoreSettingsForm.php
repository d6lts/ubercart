<?php

/**
 * @file
 * Contains \Drupal\uc_store\Form\StoreSettingsForm.
 */

namespace Drupal\uc_store\Form;

use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure store settings for this site.
 */
class StoreSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'uc_store_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->config('uc_store.settings');

    $form['store'] = array('#type' => 'vertical_tabs');

    $form['basic'] = array(
      '#type' => 'details',
      '#title' => t('Basic information'),
      '#group' => 'store',
    );
    $form['basic']['uc_store_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Store name'),
      '#default_value' => uc_store_name(),
    );
    $form['basic']['uc_store_email'] = array(
      '#type' => 'email',
      '#title' => t('E-mail address'),
      '#size' => 32,
      '#required' => TRUE,
      '#default_value' => uc_store_email(),
    );
    $form['basic']['uc_store_email_include_name'] = array(
      '#type' => 'checkbox',
      '#title' => t('Include the store name in the "From" line of store e-mails.'),
      '#description' => t('May not be available on all server configurations. Turn off if this causes problems.'),
      '#default_value' => $config->get('mail_include_name'),
    );
    $form['basic']['uc_store_phone'] = array(
      '#type' => 'textfield',
      '#title' => t('Phone number'),
      '#default_value' => $config->get('phone'),
    );
    $form['basic']['uc_store_fax'] = array(
      '#type' => 'textfield',
      '#title' => t('Fax number'),
      '#default_value' => $config->get('fax'),
    );
    $form['basic']['uc_store_help_page'] = array(
      '#type' => 'textfield',
      '#title' => t('Store help page'),
      '#description' => t('The Drupal page for the store help link.'),
      '#default_value' => $config->get('help_page'),
      '#size' => 32,
      '#field_prefix' => url(NULL, array('absolute' => TRUE)),
    );

    $form['address'] = array(
      '#type' => 'details',
      '#title' => t('Store address'),
      '#group' => 'store',
    );
    $form['address']['address'] = array(
      '#type' => 'uc_address',
      '#default_value' => array(
        'uc_store_street1' => $config->get('address.street1'),
        'uc_store_street2' => $config->get('address.street2'),
        'uc_store_city' => $config->get('address.city'),
        'uc_store_zone' => $config->get('address.zone'),
        'uc_store_country' => isset($form_state['values']) ? $form_state['values']['uc_store_country'] : $config->get('address.country'),
        'uc_store_postal_code' => $config->get('address.postal_code'),
      ),
      '#required' => FALSE,
      '#key_prefix' => 'uc_store',
    );

    $form['currency'] = array(
      '#type' => 'details',
      '#title' => t('Currency format'),
      '#group' => 'store',
    );
    $form['currency']['uc_currency_code'] = array(
      '#type' => 'textfield',
      '#title' => t('Currency code'),
      '#description' => t('While not used directly in formatting, the currency code is used by other modules as the primary currency for your site.  Enter here your three character <a href="!url">ISO 4217</a> currency code.', array('!url' => 'http://en.wikipedia.org/wiki/ISO_4217#Active_codes')),
      '#default_value' => $config->get('currency.code'),
      '#maxlength' => 3,
      '#size' => 5,
    );
    $form['currency']['example'] = array(
      '#type' => 'textfield',
      '#title' => t('Current format'),
      '#value' => uc_currency_format(1000.1234),
      '#disabled' => TRUE,
      '#size' => 10,
    );
    $form['currency']['uc_currency_sign'] = array(
      '#type' => 'textfield',
      '#title' => t('Currency sign'),
      '#default_value' => $config->get('currency.symbol'),
      '#size' => 10,
      '#maxlength' => 10,
    );
    $form['currency']['uc_sign_after_amount'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display currency sign after amount.'),
      '#default_value' => $config->get('currency.symbol_after'),
    );
    $form['currency']['uc_currency_thou'] = array(
      '#type' => 'textfield',
      '#title' => t('Thousands marker'),
      '#default_value' => $config->get('currency.thousands_marker'),
      '#size' => 10,
      '#maxlength' => 10,
    );
    $form['currency']['uc_currency_dec'] = array(
      '#type' => 'textfield',
      '#title' => t('Decimal marker'),
      '#default_value' => $config->get('currency.decimal_marker'),
      '#size' => 10,
      '#maxlength' => 10,
    );
    $form['currency']['uc_currency_prec'] = array(
      '#type' => 'select',
      '#title' => t('Number of decimal places'),
      '#options' => array(0 => 0, 1 => 1, 2 => 2),
      '#default_value' => $config->get('currency.precision'),
    );

    $form['display'] = array(
      '#type' => 'details',
      '#title' => t('Display settings'),
      '#group' => 'store',
    );
    $form['display']['uc_weight_unit'] = array(
      '#type' => 'select',
      '#title' => t('Default weight units'),
      '#default_value' => $config->get('units.weight'),
      '#options' => array(
        'lb' => t('Pounds'),
        'oz' => t('Ounces'),
        'kg' => t('Kilograms'),
        'g' => t('Grams'),
      ),
    );
    $form['display']['uc_length_unit'] = array(
      '#type' => 'select',
      '#title' => t('Default length units'),
      '#default_value' => $config->get('units.length'),
      '#options' => array(
        'in' => t('Inches'),
        'ft' => t('Feet'),
        'cm' => t('Centimeters'),
        'mm' => t('Millimeters'),
      ),
    );
    $form['display']['uc_customer_list_address'] = array(
      '#type' => 'radios',
      '#title' => t('Primary customer address'),
      '#description' => t('Select the address to be used on customer lists and summaries.'),
      '#options' => array(
        'billing' => t('Billing address'),
        'shipping' => t('Shipping address'),
      ),
      '#default_value' => $config->get('customer_address'),
    );
    $form['display']['uc_order_capitalize_addresses'] = array(
      '#type' => 'checkbox',
      '#title' => t('Capitalize address on order screens'),
      '#default_value' => $config->get('capitalize_address'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->config('uc_store.settings')
      ->set('name', $form_state['values']['uc_store_name'])
      ->set('mail', $form_state['values']['uc_store_email'])
      ->set('mail_include_name', $form_state['values']['uc_store_email_include_name'])
      ->set('phone', $form_state['values']['uc_store_phone'])
      ->set('fax', $form_state['values']['uc_store_fax'])
      ->set('help_page', $form_state['values']['uc_store_help_page'])
      ->set('address.street1', $form_state['values']['uc_store_street1'])
      ->set('address.street2', $form_state['values']['uc_store_street2'])
      ->set('address.city', $form_state['values']['uc_store_city'])
      ->set('address.zone', $form_state['values']['uc_store_zone'])
      ->set('address.country', $form_state['values']['uc_store_country'])
      ->set('address.postal_code', $form_state['values']['uc_store_postal_code'])
      ->set('currency.code', $form_state['values']['uc_currency_code'])
      ->set('currency.symbol', $form_state['values']['uc_currency_sign'])
      ->set('currency.symbol_after', $form_state['values']['uc_sign_after_amount'])
      ->set('currency.thousands_marker', $form_state['values']['uc_currency_thou'])
      ->set('currency.decimal_marker', $form_state['values']['uc_currency_dec'])
      ->set('currency.precision', $form_state['values']['uc_currency_prec'])
      ->set('units.weight', $form_state['values']['uc_weight_unit'])
      ->set('units.length', $form_state['values']['uc_length_unit'])
      ->set('customer_address', $form_state['values']['uc_customer_list_address'])
      ->set('capitalize_address', $form_state['values']['uc_order_capitalize_addresses'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
