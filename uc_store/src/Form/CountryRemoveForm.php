<?php

/**
 * @file
 * Contains \Drupal\uc_store\Form\CountryRemoveForm.
 */

namespace Drupal\uc_store\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form to completely remove a country.
 */
class CountryRemoveForm extends ConfirmFormBase {

 /**
   * The country_id to be deleted.
   */
  protected $country;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'uc_country_remove';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to remove @country from the system?', array('@country' => $this->country));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Remove');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('uc_countries.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $country_id = NULL) {
    // Fetch the country name from the database.
    $this->country = t(db_query("SELECT country_name FROM {uc_countries} WHERE country_id = :id", array(':id' => $country_id))->fetchField());
  
drupal_set_message('te=' .$form_state->getTriggeringElement());
    // If orders exist for this country, show a warning message prior to removal.
    if (NULL !== $form_state->getTriggeringElement() &&
        $form_state->getTriggeringElement() != t('Remove') &&
        module_exists('uc_order')) {
      $count = db_query("SELECT COUNT(order_id) FROM {uc_orders} WHERE delivery_country = :delivery_country OR billing_country = :billing_country", array(':delivery_country' => $country_id, ':billing_country' => $country_id))->fetchField();
      if ($count > 0) {
        drupal_set_message(t('Warning: @count orders were found with addresses in this country. Removing this country now will cause errors to show on those order pages. You might consider simply disabling this country instead.', array('@count' => $count)), 'error');
      }
    }
  
    // Store the country ID in the form array for processing.
    $form['country_id'] = array(
      '#type' => 'value',
      '#value' => $country_id,
    );
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $country_id = $form_state->getValue('country_id');
  
drupal_set_message(var_export($form_state->getValues(), TRUE));
drupal_set_message(var_export($form_state->cleanValues(), TRUE));
    $result = db_query("SELECT * FROM {uc_countries} WHERE country_id = :id", array(':id' => $country_id));
    if (!($country = $result->fetchObject())) {
      drupal_set_message(t('Attempted to remove an invalid country.'), 'error');
      drupal_goto('admin/store/settings/countries');
    }
  
    db_delete('uc_countries')
      ->condition('country_id', $country_id)
      ->execute();
    db_delete('uc_zones')
      ->condition('zone_country_id', $country_id)
      ->execute();
    variable_del('uc_address_format_' . $country_id);
  
    $func_base = uc_country_import_include($country_id, $country->version);
    if ($func_base !== FALSE) {
      $func = $func_base . '_uninstall';
      if (function_exists($func)) {
        $func();
      }
    }
  
    drupal_set_message(t('@country removed.', array('@country' => t($country->country_name))));
    drupal_goto('admin/store/settings/countries');
  }
}
