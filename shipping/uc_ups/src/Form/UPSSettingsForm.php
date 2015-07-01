<?php

/**
 * @file
 * Contains \Drupal\uc_ups\Form\UPSSettingsForm.
 */

namespace Drupal\uc_ups\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configures UPS settings.
 *
 * Records UPS account information necessary to use the service. Allows testing
 * or production mode. Configures which UPS services are quoted to customers.
 */
class UPSSettingsForm extends ConfigFormBase {

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'uc_ups_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'uc_ups.settings',
    ];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $ups_config = $this->config('uc_ups.settings');

    // Put fieldsets into vertical tabs
    $form['ups-settings'] = array(
      '#type' => 'vertical_tabs',
      '#attached' => array(
        'library' => array(
          'uc_ups/uc_ups.scripts',
        ),
      ),
    );

    // Container for credential forms
    $form['uc_ups_credentials'] = array(
      '#type'          => 'details',
      '#title'         => t('Credentials'),
      '#description'   => t('Account number and authorization information.'),
      '#group'         => 'ups-settings',
    );

    $form['uc_ups_credentials']['uc_ups_access_license'] = array(
      '#type' => 'textfield',
      '#title' => t('UPS OnLine Tools XML Access Key'),
      '#default_value' => $ups_config->get('access_license'),
      '#required' => TRUE,
    );
    $form['uc_ups_credentials']['uc_ups_shipper_number'] = array(
      '#type' => 'textfield',
      '#title' => t('UPS Shipper #'),
      '#description' => t('The 6-character string identifying your UPS account as a shipper.'),
      '#default_value' => $ups_config->get('shipper_number'),
      '#required' => TRUE,
    );
    $form['uc_ups_credentials']['uc_ups_user_id'] = array(
      '#type' => 'textfield',
      '#title' => t('UPS.com user ID'),
      '#default_value' => $ups_config->get('user_id'),
      '#required' => TRUE,
    );
    $form['uc_ups_credentials']['uc_ups_password'] = array(
      '#type' => 'password',
      '#title' => t('Password'),
      '#default_value' => $ups_config->get('password'),
    );
    $form['uc_ups_credentials']['uc_ups_connection_address'] = array(
      '#type' => 'select',
      '#title' => t('Server mode'),
      '#description' => t('Use the Testing server while developing and configuring your site. Switch to the Production server only after you have demonstrated that transactions on the Testing server are working and you are ready to go live.'),
      '#options' => array('https://wwwcie.ups.com/ups.app/xml/' => t('Testing'),
        'https://onlinetools.ups.com/ups.app/xml/' => t('Production'),
      ),
      '#default_value' => $ups_config->get('connection_address'),
    );

    $form['services'] = array(
      '#type' => 'details',
      '#title' => t('Service options'),
      '#description' => t('Set the conditions that will return a UPS quote.'),
      '#group'         => 'ups-settings',
    );

    $form['services']['uc_ups_services'] = array(
      '#type' => 'checkboxes',
      '#title' => t('UPS services'),
      '#default_value' => $ups_config->get('services'),
      '#options' => \Drupal\uc_ups\UPSUtilities::services(),
      '#description' => t('Select the UPS services that are available to customers.'),
    );

    // Container for quote options
    $form['uc_ups_quote_options'] = array(
      '#type'          => 'details',
      '#title'         => t('Quote options'),
      '#description'   => t('Preferences that affect computation of quote.'),
      '#group'         => 'ups-settings',
    );

    $form['uc_ups_quote_options']['uc_ups_all_in_one'] = array(
      '#type' => 'radios',
      '#title' => t('Product packages'),
      '#default_value' => $ups_config->get('all_in_one'),
      '#options' => array(
        0 => t('Each product in its own package'),
        1 => t('All products in one package'),
      ),
      '#description' => t('Indicate whether each product is quoted as shipping separately or all in one package. Orders with one kind of product will still use the package quantity to determine the number of packages needed, however.'),
    );

    // Form to select package types
    $form['uc_ups_quote_options']['uc_ups_package_type'] = array(
      '#type' => 'select',
      '#title' => t('Default Package Type'),
      '#default_value' => $ups_config->get('package_type'),
      '#options' => \Drupal\uc_ups\UPSUtilities::packageTypes(),
      '#description' => t('Type of packaging to be used.  May be overridden on a per-product basis via the product node edit form.'),
    );
    $form['uc_ups_quote_options']['uc_ups_classification'] = array(
      '#type' => 'select',
      '#title' => t('UPS Customer classification'),
      '#options' => array(
        '01' => t('Wholesale'),
        '03' => t('Occasional'),
        '04' => t('Retail'),
      ),
      '#default_value' => $ups_config->get('classification'),
      '#description' => t('The kind of customer you are to UPS. For daily pickups the default is wholesale; for customer counter pickups the default is retail; for other pickups the default is occasional.'),
    );

    $form['uc_ups_quote_options']['uc_ups_negotiated_rates'] = array(
      '#type' => 'radios',
      '#title' => t('Negotiated rates'),
      '#default_value' => $ups_config->get('negotiated_rates'),
      '#options' => array(1 => t('Yes'), 0 => t('No')),
      '#description' => t('Is your UPS account receiving negotiated rates on shipments?'),
    );

    // Form to select pickup type
    $form['uc_ups_quote_options']['uc_ups_pickup_type'] = array(
      '#type' => 'select',
      '#title' => t('Pickup type'),
      '#options' => array(
        '01' => 'Daily Pickup',
        '03' => 'Customer Counter',
        '06' => 'One Time Pickup',
        '07' => 'On Call Air',
        '11' => 'Suggested Retail Rates',
        '19' => 'Letter Center',
        '20' => 'Air Service Center',
      ),
      '#default_value' => $ups_config->get('pickup_type'),
    );

    $form['uc_ups_quote_options']['uc_ups_residential_quotes'] = array(
      '#type' => 'radios',
      '#title' => t('Assume UPS shipping quotes will be delivered to'),
      '#default_value' => $ups_config->get('residential_quotes'),
      '#options' => array(
        0 => t('Business locations'),
        1 => t('Residential locations (extra fees)'),
      ),
    );

    $form['uc_ups_quote_options']['uc_ups_unit_system'] = array(
      '#type' => 'select',
      '#title' => t('System of measurement'),
      '#default_value' => $ups_config->get('unit_system', \Drupal::config('uc_store.settings')->get('length.units')),
      '#options' => array(
        'in' => t('Imperial'),
        'cm' => t('Metric'),
      ),
      '#description' => t('Choose the standard system of measurement for your country.'),
    );

    $form['uc_ups_quote_options']['uc_ups_insurance'] = array(
      '#type' => 'checkbox',
      '#title' => t('Package insurance'),
      '#default_value' => $ups_config->get('insurance'),
      '#description' => t('When enabled, the quotes presented to the customer will include the cost of insurance for the full sales price of all products in the order.'),
    );

    // Container for markup forms
    $form['uc_ups_markups'] = array(
      '#type'          => 'details',
      '#title'         => t('Markups'),
      '#description'   => t('Modifiers to the shipping weight and quoted rate.'),
      '#group'         => 'ups-settings',
    );

    // Form to select type of rate markup
    $form['uc_ups_markups']['uc_ups_rate_markup_type'] = array(
      '#type' => 'select',
      '#title' => t('Rate markup type'),
      '#default_value' => $ups_config->get('rate_markup_type'),
      '#options' => array(
        'percentage' => t('Percentage (%)'),
        'multiplier' => t('Multiplier (×)'),
        'currency' => t('Addition (!currency)', array('!currency' => \Drupal::config('uc_store.settings')->get('currency.symbol'))),
      ),
    );

    // Form to select rate markup amount
    $form['uc_ups_markups']['uc_ups_rate_markup'] = array(
      '#type' => 'textfield',
      '#title' => t('Shipping rate markup'),
      '#default_value' => $ups_config->get('rate_markup'),
      '#description' => t('Markup shipping rate quote by currency amount, percentage, or multiplier.'),
    );

    // Form to select type of weight markup
    $form['uc_ups_markups']['uc_ups_weight_markup_type'] = array(
      '#type'          => 'select',
      '#title'         => t('Weight markup type'),
      '#default_value' => $ups_config->get('weight_markup_type'),
      '#options'       => array(
        'percentage' => t('Percentage (%)'),
        'multiplier' => t('Multiplier (×)'),
        'mass'       => t('Addition (!mass)', array('!mass' => '#')),
      ),
      '#disabled' => TRUE,
    );

    // Form to select weight markup amount
    $form['uc_ups_markups']['uc_ups_weight_markup'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Shipping weight markup'),
      '#default_value' => $ups_config->get('weight_markup'),
      '#description'   => t('Markup UPS shipping weight on a per-package basis before quote, by weight amount, percentage, or multiplier.'),
      '#disabled' => TRUE,
    );

    // Container for label printing
    $form['uc_ups_labels'] = array(
      '#type'          => 'details',
      '#title'         => t('Label Printing'),
      '#description'   => t('Preferences for UPS Shipping Label Printing.  Additional permissions from UPS are required to use this feature.'),
      '#group'         => 'ups-settings',
    );

    $intervals = array(86400, 302400, 604800, 1209600, 2419200, 0);
    $period = array_map(array(\Drupal::service('date.formatter'), 'formatInterval'), array_combine($intervals, $intervals));
    $period[0] = t('Forever');

    // Form to select how long labels stay on server
    $form['uc_ups_labels']['uc_ups_label_lifetime'] = array(
      '#type'          => 'select',
      '#title'         => t('Label lifetime'),
      '#default_value' => $ups_config->get('label_lifetime'),
      '#options'       => $period,
      '#description'   => t('Controls how long labels are stored on the server before being automatically deleted. Cron must be enabled for automatic deletion. Default is never delete the labels, keep them forever.'),
    );

    // Taken from system_settings_form(). Only, don't use its submit handler.
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save configuration'),
    );
    $form['actions']['cancel'] = array(
      '#markup' => \Drupal::l(t('Cancel'), new Url('uc_quote.methods')),
    );

    if (!empty($_POST) && $form_state->getErrors()) {
      drupal_set_message(t('The settings have not been saved because of the errors.'), 'error');
    }
    if (!isset($form['#theme'])) {
      $form['#theme'] = 'system_settings_form';
    }

    return parent::buildForm($form, $form_state);
  }


  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $old_password = $this->config('uc_ups.settings')->get('password');
    if (!$form_state->getValue('uc_ups_password')) {
      if ($old_password) {
        $form_state->setValueForElement($form['uc_ups_credentials']['uc_ups_password'], $old_password);
      }
      else {
        $form_state->setErrorByName('uc_ups_password', t('Password field is required.'));
      }
    }

    if (!is_numeric($form_state->getValue('uc_ups_rate_markup'))) {
      $form_state->setErrorByName('uc_ups_rate_markup', t('Rate markup must be a numeric value.'));
    }
    if (!is_numeric($form_state->getValue('uc_ups_weight_markup'))) {
      $form_state->setErrorByName('uc_ups_weight_markup', t('Weight markup must be a numeric value.'));
    }
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $ups_config = $this->config('uc_ups.settings');

    $values = $form_state->getValues();
    $ups_config
      ->set('access_license', $values['uc_ups_access_license'])
      ->set('shipper_number', $values['uc_ups_shipper_number'])
      ->set('user_id', $values['uc_ups_user_id'])
      ->set('password', $values['uc_ups_password'])
      ->set('connection_address', $values['uc_ups_connection_address'])
      ->set('services', $values['uc_ups_services'])
      ->set('pickup_type', $values['uc_ups_pickup_type'])
      ->set('package_type', $values['uc_ups_package_type'])
      ->set('classification', $values['uc_ups_classification'])
      ->set('negotiated_rates', $values['uc_ups_negotiated_rates'])
      ->set('residential_quotes', $values['uc_ups_residential_quotes'])
      ->set('rate_markup_type', $values['uc_ups_rate_markup_type'])
      ->set('rate_markup', $values['uc_ups_rate_markup'])
      ->set('weight_markup_type', $values['uc_ups_weight_markup_type'])
      ->set('weight_markup', $values['uc_ups_weight_markup'])
      ->set('label_lifetime', $values['uc_ups_label_lifetime'])
      ->set('all_in_one', $values['uc_ups_all_in_one'])
      ->set('unit_system', $values['uc_ups_unit_system'])
      ->set('insurance', $values['uc_ups_insurance'])
      ->save();

    drupal_set_message(t('The configuration options have been saved.'));

    // @todo: Still need these two lines?
    //cache_clear_all();
    //drupal_theme_rebuild();

    parent::submitForm($form, $form_state);
  }

}
