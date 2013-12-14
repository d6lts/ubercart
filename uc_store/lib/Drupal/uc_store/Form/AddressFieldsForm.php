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
    $config = $this->configFactory->get('uc_store.settings')
      ->get('address_fields');

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

    foreach ($fields as $field => $label) {
      $form['fields'][$field]['#attributes']['class'][] = 'draggable';
      $form['fields'][$field]['#weight'] = $config[$field]['weight'];
      $form['fields'][$field]['status'] = array(
        '#type' => 'checkbox',
        '#title' => $label,
        '#default_value' => $config[$field]['status'],
      );
      $form['fields'][$field]['required'] = array(
        '#type' => 'checkbox',
        '#title' => t('@title is required', array('@title' => $label)),
        '#title_display' => 'invisible',
        '#default_value' => $config[$field]['required'],
      );
      $form['fields'][$field]['weight'] = array(
        '#type' => 'weight',
        '#title' => t('Weight for @title', array('@title' => $label)),
        '#title_display' => 'invisible',
        '#default_value' => $config[$field]['weight'],
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
    $this->configFactory->get('uc_store.settings')
      ->set('address_fields', $form_state['values']['fields'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
