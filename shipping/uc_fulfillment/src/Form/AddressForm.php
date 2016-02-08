<?php

/**
 * @file
 * Contains \Drupal\uc_fulfillment\Form\AddressForm.
 */

namespace Drupal\uc_fulfillment\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;

/**
 * Helper function for addresses in forms.
 */
class AddressForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_fulfillment_address_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $addresses = [], OrderInterface $uc_order = NULL) {
    $form['#attached']['library'][] = 'uc_fulfillment/uc_fulfillment.scripts';

    $form['origin'] = array(
      '#type' => 'fieldset',
      '#title' => t('Origin address'),
      '#weight' => -2,
    );
    $form['origin']['pickup_address_select'] = $this->selectAddress($addresses);
    $form['origin']['pickup_address_select']['#weight'] = -2;
    $form['origin']['pickup_email'] = array(
      '#type' => 'email',
      '#title' => t('E-mail'),
      '#default_value' => uc_store_email(),
      '#weight' => -1,
    );
    $form['origin']['pickup_email']['#weight'] = -1;
    $form['origin']['pickup_address']['#tree'] = TRUE;
    $form['origin']['pickup_address']['pickup_address'] = array(
      '#type' => 'uc_address',
      '#default_value' => reset($addresses),
      '#required' => FALSE,
    );

    $form['destination'] = array(
      '#type' => 'fieldset',
      '#title' => t('Destination address'),
      '#weight' => -1,
    );
    if ($form_state->hasValue('delivery_country')) {
      $uc_order->delivery_country = $form_state->getValue('delivery_country');
    }
    $form['destination']['delivery_email'] = array(
      '#type' => 'email',
      '#title' => t('E-mail'),
      '#default_value' => $uc_order->getEmail(),
      '#weight' => -1,
    );
    $form['destination']['delivery_email']['#weight'] = -1;
    $form['destination']['delivery_address'] = array(
      '#type' => 'uc_address',
      '#default_value' => $uc_order->getAddress('delivery'),
      '#required' => FALSE,
      '#key_prefix' => 'delivery',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Chooses an address to fill out a form.
   */
  protected function selectAddress(array $addresses = []) {
    $quote_config = \Drupal::config('uc_quote.settings');
    $store_address = $quote_config->get('store_default_address');
$store_address = new \Drupal\uc_store\Address();
    if (!in_array($store_address, $addresses)) {
      $addresses[] = $store_address;
    }

    $blank = array(
      'first_name' => '',
      'last_name' => '',
      'phone' => '',
      'company' => '',
      'street1' => '',
      'street2' => '',
      'city' => '',
      'postal_code' => '',
      'country' => 0,
      'zone' => 0,
    );
    $options = array(Json::encode($blank) => t('- Reset fields -'));
    foreach ($addresses as $address) {
      $options[Json::encode($address)] = $address->company . ' ' . $address->street1 . ' ' . $address->city;
    }

    $select = array(
      '#type' => 'select',
      '#title' => t('Saved addresses'),
      '#options' => $options,
      '#default_value' => Json::encode($addresses[0]),
      '#attributes' => array('onchange' => 'apply_address(\'pickup\', this.value);'),
      //'#attributes' => array('id' => array('uc-fulfillment-select-shipment-address')),
    );

    return $select;
  }

}
