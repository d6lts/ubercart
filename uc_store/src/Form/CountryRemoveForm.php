<?php

/**
 * @file
 * Contains \Drupal\uc_store\Form\CountryRemoveForm.
 */

namespace Drupal\uc_store\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_store\Controller\CountryController;

/**
 * Form to completely remove a country.
 */
class CountryRemoveForm extends ConfirmFormBase {

  /**
   * The name of the country to be deleted.
   */
  protected $country_name;

  /**
   * The numeric id of the country to be deleted.
   */
  protected $country_id;

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
    return $this->t('Are you sure you want to remove @country from the system?', ['@country' => $this->country_name]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    // If orders exist for this country, show a warning message prior to removal.
    $count = db_query("SELECT COUNT(order_id) FROM {uc_orders} WHERE delivery_country = :delivery_country OR billing_country = :billing_country", [':delivery_country' => $this->country_id, ':billing_country' => $this->country_id])->fetchField();
    if ($count > 0) {
      return t('<p><strong>Warning:</strong> @count orders were found with addresses in this country.</p><p>Removing this country now will cause errors to show on those order pages. You might consider simply disabling this country instead.</p>', ['@count' => $count]);
    }
    else {
      return t('<p>No orders were found with addresses in this country, so this country is safe to remove.</p>');
    }
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
    $this->country_name = t(db_query("SELECT country_name FROM {uc_countries} WHERE country_id = :id", [':id' => $country_id])->fetchField());
    $this->country_id = $country_id;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $result = db_query("SELECT * FROM {uc_countries} WHERE country_id = :id", [':id' => $this->country_id]);
    if (!($country = $result->fetchObject())) {
      drupal_set_message(t('Attempted to remove an invalid country.'), 'error');
      $form_state->setRedirect('uc_countries.settings');
      return;
    }

    db_delete('uc_countries')
      ->condition('country_id', $country->country_id)
      ->execute();
    db_delete('uc_zones')
      ->condition('zone_country_id', $country->country_id)
      ->execute();

    $formats = \Drupal::configFactory()->getEditable('uc_country.formats');
    $formats->set($country->country_id, '')->save();

    $func_base = CountryController::importInclude($country->country_id, $country->version);
    if ($func_base !== FALSE) {
      $func = $func_base . '_uninstall';
      if (function_exists($func)) {
        $func();
      }
    }

    drupal_set_message(t('@country removed.', ['@country' => t($country->country_name)]));
    $form_state->setRedirect('uc_countries.settings');
  }
}
