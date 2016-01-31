<?php

/**
 * @file
 * Contains \Drupal\uc_fulfillment\Form\ShipmentEditForm.
 */

namespace Drupal\uc_fulfillment\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;

/**
 * Creates or edits a shipment.
 */
class ShipmentEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_fulfillment_shipment_edit';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $order = NULL, $shipment = NULL) {
    $form['order_id'] = array('#type' => 'value', '#value' => $order->id());
    if (isset($shipment->sid)) {
      $form['sid'] = array('#type' => 'value', '#value' => $shipment->sid);
      $methods = \Drupal::moduleHandler()->invokeAll('uc_fulfillment_method');
      if (isset($methods[$shipment->shipping_method])) {
        $method = $methods[$shipment->shipping_method];
      }
    }
    $addresses = array();
    $form['packages'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Packages'),
      '#tree' => TRUE,
    );
    if (isset($shipment->o_street1)) {
      $o_address = new stdClass();
      foreach ($shipment as $field => $value) {
        if (substr($field, 0, 2) == 'o_') {
          $o_address->{substr($field, 2)} = $value;
        }
      }
      $addresses[] = $o_address;
    }
    foreach ($shipment->packages as $id => $package) {
      foreach ($package->addresses as $address) {
        if (!in_array($address, $addresses)) {
          $addresses[] = $address;
        }
      }

      // Create list of products and get a representative product (last one in
      // the loop) to use for some default values
      $product_list = array();
      $declared_value = 0;
      foreach ($package->products as $product) {
        $product_list[]  = $product->qty->value . ' x ' . SafeMarkup::checkPlain($product->model->value);
        $declared_value += $product->qty->value * $product->price;
      }
      $pkg_form = array(
        '#type'  => 'fieldset',
        '#title' => $this->t('Package @id', ['@id' => $id]),
      );
      $pkg_form['products'] = array(
        '#theme' => 'item_list',
        '#items' => $product_list,
      );
      $pkg_form['package_id'] = array(
        '#type'  => 'hidden',
        '#value' => $id,
      );
      $pkg_form['pkg_type'] = array(
        '#type'          => 'textfield',
        '#title'         => $this->t('Package type'),
        '#default_value' => $package->pkg_type,
        '#description'   => $this->t('For example: Box, pallet, tube, envelope, etc.'),
      );
      if (isset($method) && is_array($method['ship']['pkg_types'])) {
        $pkg_form['pkg_type']['#type']        = 'select';
        $pkg_form['pkg_type']['#options']     = $method['ship']['pkg_types'];
        $pkg_form['pkg_type']['#description'] = '';
      }
      $pkg_form['declared_value'] = array(
        '#type'          => 'uc_price',
        '#title'         => $this->t('Declared value'),
        '#default_value' => isset($package->value) ? $package->value : $declared_value,
      );
      $pkg_form['weight'] = array(
        '#type'        => 'container',
        '#attributes'  => array('class' => array('uc-inline-form', 'clearfix')),
        '#description' => $this->t('Weight of the package. Default value is sum of product weights in the package.'),
        '#weight'      => 15,
      );
      $pkg_form['weight']['weight'] = array(
        '#type'          => 'textfield',
        '#title'         => $this->t('Weight'),
        '#default_value' => isset($package->weight) ? $package->weight : 0,
        '#size'          => 10,
      );
      $pkg_form['weight']['units'] = array(
        '#type'          => 'select',
        '#title'         => $this->t('Units'),
        '#options'       => array(
          'lb' => $this->t('Pounds'),
          'kg' => $this->t('Kilograms'),
          'oz' => $this->t('Ounces'),
          'g'  => $this->t('Grams'),
        ),
        '#default_value' => isset($package->weight_units) ? $package->weight_units : \Drupal::config('uc_store.settings')->get('weight.units'),
      );
      $pkg_form['dimensions'] = array(
        '#type'        => 'container',
        '#attributes'  => array('class' => array('uc-inline-form', 'clearfix')),
        '#title'       => $this->t('Dimensions'),
        '#description' => $this->t('Physical dimensions of the packaged product.'),
        '#weight'      => 20,
      );
      $pkg_form['dimensions']['length'] = array(
        '#type'          => 'textfield',
        '#title'         => $this->t('Length'),
        '#default_value' => isset($package->length) ? $package->length : 1,
        '#size'          => 8,
      );
      $pkg_form['dimensions']['width'] = array(
        '#type'          => 'textfield',
        '#title'         => $this->t('Width'),
        '#default_value' => isset($package->width) ? $package->width : 1,
        '#size'          => 8,
      );
      $pkg_form['dimensions']['height'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Height'),
        '#default_value' => isset($package->height) ? $package->height : 1,
        '#size' => 8,
      );
      $pkg_form['dimensions']['units'] = array(
        '#type'    => 'select',
        '#title'   => $this->t('Units of measurement'),
        '#options' => array(
          'in' => $this->t('Inches'),
          'ft' => $this->t('Feet'),
          'cm' => $this->t('Centimeters'),
          'mm' => $this->t('Millimeters'),
        ),
        '#default_value' => isset($package->length_units) ? $package->length_units : \Drupal::config('uc_store.settings')->get('length.units'),
      );
      $pkg_form['tracking_number'] = array(
        '#type'          => 'textfield',
        '#title'         => $this->t('Tracking number'),
        '#default_value' => isset($package->tracking_number) ? $package->tracking_number : '',
      );

      $form['packages'][$id] = $pkg_form;
    }

    if (!empty($shipment->d_street1)) {
      foreach ($shipment as $field => $value) {
        if (substr($field, 0, 2) == 'd_') {
          $order->{'delivery_' . substr($field, 2)} = $value;
        }
      }
    }
    $form = uc_fulfillment_address_form($form, $form_state, $addresses, $order);

    $form['shipment'] = array(
      '#type'        => 'fieldset',
      '#title'       => $this->t('Shipment data'),
    );

    // Determine shipping option chosen by the customer.
    $message = '';
    if (isset($order->quote['method'])) {
      // Order has a quote attached.
      $method  = $order->quote['method'];
      $methods = \Drupal::moduleHandler()->invokeAll('uc_fulfillment_method');
      if (isset($methods[$method])) {
        // Quote is from a currently-active shipping method.
        $services = $methods[$method]['quote']['accessorials'];
        $method   = $services[$order->quote['accessorials']];
      }
      $message = $this->t('Customer selected "@method" as the shipping method and paid @rate', ['@method' => $method, '@rate' => uc_currency_format($order->quote['rate'])]);
    }
    else {
      // No quotes for this order.
      $message = $this->t('There are no shipping quotes attached to this order. Customer was not charged for shipping.');
    }

    // Inform administrator of customer's shipping choice.
    $form['shipment']['shipping_choice'] = array(
      '#type'   => 'markup',
      '#prefix' => '<div>',
      '#markup' => $message,
      '#suffix' => '</div>',
    );

    $form['shipment']['shipping_method'] = array(
      '#type'  => 'hidden',
      '#value' => isset($shipment->shipping_method) ? $shipment->shipping_method : 'manual',
    );
    $form['shipment']['carrier'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Carrier'),
      '#default_value' => isset($shipment->carrier) ? $shipment->carrier : '',
    );
    $form['shipment']['accessorials'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Shipment options'),
      '#default_value' => isset($shipment->accessorials) ? $shipment->accessorials : '',
      '#description'   => $this->t('Short notes about the shipment, e.g. residential, overnight, etc.'),
    );
    $form['shipment']['transaction_id'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Transaction ID'),
      '#default_value' => isset($shipment->transaction_id) ? $shipment->transaction_id : '',
    );
    $form['shipment']['tracking_number'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Tracking number'),
      '#default_value' => isset($shipment->tracking_number) ? $shipment->tracking_number : '',
    );

    if (isset($shipment->ship_date)) {
      $ship_date = getdate($shipment->ship_date);
    }
    else {
      $ship_date = getdate();
    }
    if (isset($shipment->expected_delivery)) {
      $exp_delivery = getdate($shipment->expected_delivery);
    }
    else {
      $exp_delivery = getdate();
    }
    $form['shipment']['ship_date'] = array(
      '#type' => 'date',
      '#title' => $this->t('Ship date'),
      '#default_value' => array(
        'year'  => $ship_date['year'],
        'month' => $ship_date['mon'],
        'day'   => $ship_date['mday']
      ),
    );
    $form['shipment']['expected_delivery'] = array(
      '#type'          => 'date',
      '#title'         => $this->t('Expected delivery'),
      '#default_value' => array(
        'year'  => $exp_delivery['year'],
        'month' => $exp_delivery['mon'],
        'day'   => $exp_delivery['mday']
      ),
    );
    $form['shipment']['cost'] = array(
      '#type'          => 'uc_price',
      '#title'         => $this->t('Shipping cost'),
      '#default_value' => isset($shipment->cost) ? $shipment->cost : 0,
    );

    $form['actions'] = array(
      '#type' => 'actions'
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save shipment'),
      '#weight' => 10
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('packages') as $key => $package) {
      foreach (array('length', 'width', 'height') as $property) {
        if (!empty($package['dimensions'][$property]) && (!is_numeric($package['dimensions'][$property]) || $package['dimensions'][$property] < 0)) {
          $form_state->setErrorByName('packages][' . $key . '][dimensions][' . $property, $this->t('@property must be a positive number. No commas and only one decimal point.', ['@property' => Unicode::ucfirst($property)]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $shipment = new stdClass();
    $shipment->order_id = $form_state->getValue('order_id');
    if ($form_state->hasValue('sid')) {
      $shipment->sid = $form_state->getValue('sid');
    }
    $shipment->origin = (object) $form_state->getValue('pickup_address');
    $shipment->destination = new stdClass();
    foreach ($form_state->getValues() as $key => $value) {
      if (substr($key, 0, 9) == 'delivery_') {
        $field = substr($key, 9);
        $shipment->destination->$field = $value;
      }
    }
    $shipment->packages = array();
    foreach ($form_state->getValue('packages') as $id => $pkg_form) {
      $package = uc_fulfillment_package_load($id);
      $package->pkg_type = $pkg_form['pkg_type'];
      $package->value = $pkg_form['declared_value'];
      $package->length = $pkg_form['dimensions']['length'];
      $package->width = $pkg_form['dimensions']['width'];
      $package->height = $pkg_form['dimensions']['height'];
      $package->length_units = $pkg_form['dimensions']['units'];
      $package->tracking_number = $pkg_form['tracking_number'];
      $package->qty = 1;
      $shipment->packages[$id] = $package;
    }

    $shipment->shipping_method = $form_state->getValue('shipping_method');
    $shipment->accessorials = $form_state->getValue('accessorials');
    $shipment->carrier = $form_state->getValue('carrier');
    $shipment->transaction_id = $form_state->getValue('transaction_id');
    $shipment->tracking_number = $form_state->getValue('tracking_number');
    $shipment->ship_date = gmmktime(12, 0, 0, $form_state->getValue(['ship_date', 'month']), $form_state->getValue(['ship_date', 'day']), $form_state->getValue(['ship_date', 'year']));
    $shipment->expected_delivery = gmmktime(12, 0, 0, $form_state->getValue(['expected_delivery', 'month']), $form_state->getValue(['expected_delivery', 'day']), $form_state->getValue(['expected_delivery', 'year']));
    $shipment->cost = $form_state->getValue('cost');

    uc_fulfillment_shipment_save($shipment);

    $form_state['redirect'] = 'admin/store/orders/' . $form_state->getValue('order_id') . '/shipments';
  }

}
