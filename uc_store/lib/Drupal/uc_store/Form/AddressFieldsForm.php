<?php

/**
 * @file
 * Contains \Drupal\uc_store\Form\AddressFieldsForm.
 */

namespace Drupal\uc_store\Form;

use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure address field settings for this store.
 */
class AddressFieldsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'uc_store_address_fields_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['fields'] = array(
      '#type' => 'table',
      '#header' => array(t('Field'), t('Required'), t('List position')),
      '#tabledrag' => array(
        array('order', 'sibling', 'uc-store-address-fields-weight'),
      ),
    );

    $fields = array(
      'first_name' => t('First name'),
      'last_name' => t('Last name'),
      'company' => t('Company'),
      'street1' => t('Street address 1'),
      'street2' => t('Street address 2'),
      'city' => t('City'),
      'zone' => t('State/Province'),
      'country' => t('Country'),
      'postal_code' => t('Postal code'),
      'phone' => t('Phone number'),
    );
    $current = variable_get('uc_address_fields', drupal_map_assoc(array('first_name', 'last_name', 'phone', 'company', 'street1', 'street2', 'city', 'zone', 'postal_code', 'country')));
    $required = variable_get('uc_address_fields_required', drupal_map_assoc(array('first_name', 'last_name', 'street1', 'city', 'zone', 'postal_code', 'country')));
    $weight = uc_store_address_field_weights();
    foreach ($fields as $field => $label) {
      $form['fields'][$field]['#attributes']['class'][] = 'draggable';
      $form['fields'][$field]['#weight'] = (isset($weight[$field])) ? $weight[$field] : 0;
      $form['fields'][$field]['status'] = array(
        '#type' => 'checkbox',
        '#title' => $label,
        '#default_value' => !empty($current[$field]),
      );
      $form['fields'][$field]['required'] = array(
        '#type' => 'checkbox',
        '#title' => t('@title is required', array('@title' => $label)),
        '#title_display' => 'invisible',
        '#default_value' => !empty($required[$field]),
      );
      $form['fields'][$field]['weight'] = array(
        '#type' => 'weight',
        '#title' => t('Weight for @title', array('@title' => $label)),
        '#title_display' => 'invisible',
        '#default_value' => (isset($weight[$field])) ? $weight[$field] : 0,
        '#attributes' => array('class' => array('uc-store-address-fields-weight')),
      );
    }
    uasort($form['fields'], 'element_sort');

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $enabled = array();
    $required = array();
    $weights = array();
    foreach (element_children($form['fields']) as $field) {
      $enabled[$field] = $form_state['values']['fields'][$field]['status'];
      $required[$field] = $form_state['values']['fields'][$field]['required'];
      $weights[$field] = $form_state['values']['fields'][$field]['weight'];
    }
    variable_set('uc_address_fields', array_filter($enabled));
    variable_set('uc_address_fields_required', array_filter($required));
    variable_set('uc_address_fields_weight', $weights);

    parent::submitForm($form, $form_state);
  }

}
