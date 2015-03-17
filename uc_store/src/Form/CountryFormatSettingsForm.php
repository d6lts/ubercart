<?php

/**
 * @file
 * Contains \Drupal\uc_store\Form\CountryFormatSettingsForm.
 */

namespace Drupal\uc_store\Form;

use Drupal\Component\Utility\String;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form builder to set country address formats.
 */
class CountryFormatSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'uc_country_formats';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'uc_country.formats',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $formats = $this->config('uc_country.formats');

    $form['instructions'] = array(
      '#type' => 'details',
      '#title' => t('Address format variables'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $header = array(t('Variable'), t('Description'));
    $rows = array(
      array('!first_name', t("Customer's first name")),
      array('!last_name', t("Customer's last name")),
      array('!company', t('Company name')),
      array('!street1', t('First street address field')),
      array('!street2', t('Second street address field')),
      array('!city', t('City name')),
      array('!zone_name', t('Full name of the zone')),
      array('!zone_code', t('Abbreviation of the zone')),
      array('!postal_code', t('Postal code')),
      array('!country_name', t('Name of the country')),
      array('!country_code2', t('2 character country abbreviation')),
      array('!country_code3', t('3 character country abbreviation')),
    );
    $form['instructions']['text'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#prefix' => '<div><p>' . t('The following variables should be used in configuring addresses for the countries you ship to:') . '</p>',
      '#suffix' => '<p>' . t('Adding _if to any country variable will make it display only for addresses whose country is different than the default store country.') . '</p></div>',
    );

    $countries = [];
    $result = db_query("SELECT * FROM {uc_countries}");
    foreach ($result as $country) {
      $countries[t($country->country_name)] = $country;
    }
    uksort($countries, 'strnatcasecmp');

    if (is_array($countries)) {
      $form['country'] = array(
        '#type' => 'vertical_tabs',
        '#tree' => TRUE,
      );
      foreach ($countries as $country) {
        $form['countries'][$country->country_id] = array(
          '#type' => 'details',
          '#title' => String::checkPlain(t($country->country_name)),
          '#group' => 'country',
        );
        $form['countries'][$country->country_id]['address_format'] = array(
          '#type' => 'textarea',
          '#title' => t('@country address format', ['@country' => t($country->country_name)]),
          '#default_value' => $formats->get($country->country_id),
          '#description' => t('Use the variables mentioned in the instructions to format an address for this country.'),
          '#rows' => 7,
        );
      }
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit changes'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $formats = $this->config('uc_country.formats');

    foreach ($form_state->getValues() as $country_id => $data) {
      $formats->set($country_id, $data['address_format']);
    }
    $formats->save();
    drupal_set_message(t('Country settings saved.'));

    parent::submitForm($form, $form_state);
  }
}
