<?php

/**
 * @file
 * Contains \Drupal\uc_fulfillment\Form\ShipmentEditForm.
 */

namespace Drupal\uc_fulfillment\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_fulfillment\Package;
use Drupal\uc_fulfillment\ShipmentInterface;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_store\Address;

/**
 * Creates or edits a shipment.
 */
class ShipmentEditForm extends FormBase {

  /**
   * The order id.
   *
   * @var \Drupal\uc_order\OrderInterface
   */
  protected $order_id;

  /**
   * The shipment.
   *
   * @var \Drupal\uc_fulfillment\Shipment
   */
  protected $shipment;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_fulfillment_shipment_edit';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $uc_order = NULL, ShipmentInterface $uc_shipment = NULL) {
    $this->order_id = $uc_order->id();
    $this->shipment = $uc_shipment;

    $addresses = array();
    $form['packages'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Packages'),
      '#tree' => TRUE,
    );
    if (NULL != $this->shipment->getOrigin()) {
      $addresses[] = $this->shipment->getOrigin();
    }
    foreach ($this->shipment->getPackages() as $id => $package) {
      foreach ($package->addresses as $address) {
        if (!in_array($address, $addresses)) {
          $addresses[] = $address;
        }
      }

      // Create list of products and get a representative product (last one in
      // the loop) to use for some default values
      $declared_value = 0;
      foreach ($package->products as $product) {
        $product_list[]  = $product->qty . ' x ' . $product->model;
        $declared_value += $product->qty * $product->price;
      }
      $pkg_form = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Package @id', ['@id' => $id]),
      );
      $pkg_form['products'] = array(
        '#theme' => 'item_list',
        '#items' => $product_list,
      );
      $pkg_form['pkg_type'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Package type'),
        '#default_value' => $package->pkg_type,
        '#description' => $this->t('For example: Box, pallet, tube, envelope, etc.'),
      );
      if (isset($method) && is_array($method['ship']['pkg_types'])) {
        $pkg_form['pkg_type']['#type'] = 'select';
        $pkg_form['pkg_type']['#options'] = $method['ship']['pkg_types'];
        $pkg_form['pkg_type']['#description'] = '';
      }
      $pkg_form['declared_value'] = array(
        '#type' => 'uc_price',
        '#title' => $this->t('Declared value'),
        '#default_value' => isset($package->value) ? $package->value : $declared_value,
      );
      $pkg_form['weight'] = array(
        '#type' => 'container',
        '#attributes' => array('class' => array('uc-inline-form', 'clearfix')),
        '#description' => $this->t('Weight of the package. Default value is sum of product weights in the package.'),
        '#weight' => 15,
      );
      $pkg_form['weight']['weight'] = array(
        '#type' => 'number',
        '#title' => $this->t('Weight'),
        '#min' => 0,
        '#step' => 'any',
        '#default_value' => isset($package->weight) ? $package->weight : 0,
        '#size' => 10,
      );
      $pkg_form['weight']['units'] = array(
        '#type' => 'select',
        '#title' => $this->t('Units'),
        '#options' => array(
          'lb' => $this->t('Pounds'),
          'kg' => $this->t('Kilograms'),
          'oz' => $this->t('Ounces'),
          'g'  => $this->t('Grams'),
        ),
        '#default_value' => isset($package->weight_units) ? $package->weight_units : \Drupal::config('uc_store.settings')->get('weight.units'),
      );
      $pkg_form['dimensions'] = array(
        '#type' => 'container',
        '#attributes' => array('class' => array('uc-inline-form', 'clearfix')),
        '#title' => $this->t('Dimensions'),
        '#description' => $this->t('Physical dimensions of the packaged product.'),
        '#weight' => 20,
      );
      $pkg_form['dimensions']['length'] = array(
        '#type' => 'number',
        '#title' => $this->t('Length'),
        '#min' => 0,
        '#step' => 'any',
        '#default_value' => isset($package->length) ? $package->length : 1,
        '#size' => 8,
      );
      $pkg_form['dimensions']['width'] = array(
        '#type' => 'number',
        '#title' => $this->t('Width'),
        '#min' => 0,
        '#step' => 'any',
        '#default_value' => isset($package->width) ? $package->width : 1,
        '#size' => 8,
      );
      $pkg_form['dimensions']['height'] = array(
        '#type' => 'number',
        '#title' => $this->t('Height'),
        '#min' => 0,
        '#step' => 'any',
        '#default_value' => isset($package->height) ? $package->height : 1,
        '#size' => 8,
      );
      $pkg_form['dimensions']['units'] = array(
        '#type' => 'select',
        '#title' => $this->t('Units of measurement'),
        '#options' => array(
          'in' => $this->t('Inches'),
          'ft' => $this->t('Feet'),
          'cm' => $this->t('Centimeters'),
          'mm' => $this->t('Millimeters'),
        ),
        '#default_value' => isset($package->length_units) ? $package->length_units : \Drupal::config('uc_store.settings')->get('length.units'),
      );
      $pkg_form['tracking_number'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Tracking number'),
        '#default_value' => isset($package->tracking_number) ? $package->tracking_number : '',
      );

      $form['packages'][$id] = $pkg_form;
    }

    if (!empty($this->shipment->d_street1)) {
      foreach ($this->shipment as $field => $value) {
        if (substr($field, 0, 2) == 'd_') {
          $uc_order->{'delivery_' . substr($field, 2)} = $value;
        }
      }
    }
    $form += \Drupal::formBuilder()->getForm('\Drupal\uc_fulfillment\Form\AddressForm', $addresses, $uc_order);

    $form['shipment'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Shipment data'),
    );

    // Determine shipping option chosen by the customer.
    $message = '';
    if (isset($uc_order->quote['method'])) {
      // Order has a quote attached.
      $method  = $uc_order->quote['method'];
      $methods = \Drupal::moduleHandler()->invokeAll('uc_fulfillment_method');
      if (isset($methods[$method])) {
        // Quote is from a currently-active shipping method.
        $services = $methods[$method]['quote']['accessorials'];
        $method = $services[$uc_order->quote['accessorials']];
      }
      $message = $this->t('Customer selected "@method" as the shipping method and paid @rate', ['@method' => $method, '@rate' => uc_currency_format($uc_order->quote['rate'])]);
    }
    else {
      // No quotes for this order.
      $message = $this->t('There are no shipping quotes attached to this order. Customer was not charged for shipping.');
    }

    // Inform administrator of customer's shipping choice.
    $form['shipment']['shipping_choice'] = array(
      '#type' => 'container',
      '#markup' => $message,
    );

    $form['shipment']['shipping_method'] = array(
      '#type'  => 'hidden',
      '#value' => $this->shipment->getShippingMethod(),
    );
    $form['shipment']['carrier'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Carrier'),
      '#default_value' => $this->shipment->getCarrier(),
    );
    $form['shipment']['accessorials'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Shipment options'),
      '#default_value' => $this->shipment->getAccessorials(),
      '#description' => $this->t('Short notes about the shipment, e.g. residential, overnight, etc.'),
    );
    $form['shipment']['transaction_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Transaction ID'),
      '#default_value' => $this->shipment->getTransactionId(),
    );
    $form['shipment']['tracking_number'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Tracking number'),
      '#default_value' => $this->shipment->getTrackingNumber(),
    );

    $ship_date = REQUEST_TIME;
    if (isset($this->shipment->ship_date)) {
      $ship_date = $this->shipment->ship_date;
    }
    $exp_delivery = REQUEST_TIME;
    if (isset($this->shipment->expected_delivery)) {
      $exp_delivery = $this->shipment->expected_delivery;
    }
    $form['shipment']['ship_date'] = array(
      '#type' => 'datetime',
      '#title' => $this->t('Ship date'),
      '#date_date_element' => 'date',
      '#date_time_element' => 'none',
      '#default_value' => DrupalDateTime::createFromTimestamp($ship_date),
    );
    $form['shipment']['expected_delivery'] = array(
      '#type' => 'datetime',
      '#title' => $this->t('Expected delivery'),
      '#date_date_element' => 'date',
      '#date_time_element' => 'none',
      '#default_value' => DrupalDateTime::createFromTimestamp($exp_delivery),
    );
    $form['shipment']['cost'] = array(
      '#type' => 'uc_price',
      '#title' => $this->t('Shipping cost'),
      '#default_value' => $this->shipment->getCost(),
    );

    $form['actions'] = array('#type' => 'actions');
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->shipment->setOrderId($this->order_id);
    // The pickup_address and delivery_address form elements are defined in AddressForm.
    $this->shipment->setOrigin(Address::create($form_state->getValue('pickup_address')));
    $this->shipment->setDestination(Address::create($form_state->getValue('delivery_address')));

    $packages = array();
    foreach ($form_state->getValue('packages') as $id => $pkg_form) {
      $package = Package::load($id);
      $package->pkg_type = $pkg_form['pkg_type'];
      $package->value = $pkg_form['declared_value'];
      $package->length = $pkg_form['dimensions']['length'];
      $package->width = $pkg_form['dimensions']['width'];
      $package->height = $pkg_form['dimensions']['height'];
      $package->length_units = $pkg_form['dimensions']['units'];
      $package->tracking_number = $pkg_form['tracking_number'];
      $package->qty = 1;
      $packages[$id] = $package;
    }
    $this->shipment->setPackages($packages);

    $this->shipment->setShippingMethod($form_state->getValue('shipping_method'));
    $this->shipment->setAccessorials($form_state->getValue('accessorials'));
    $this->shipment->setCarrier($form_state->getValue('carrier'));
    $this->shipment->setTransactionId($form_state->getValue('transaction_id'));
    $this->shipment->setTrackingNumber($form_state->getValue('tracking_number'));
    $this->shipment->setShipDate($form_state->getValue('ship_date')->getTimestamp());
    $this->shipment->setExpectedDelivery($form_state->getValue('expected_delivery')->getTimestamp());
    $this->shipment->setCost($form_state->getValue('cost'));

    $this->shipment->save();

    $form_state->setRedirect('uc_fulfillment.shipments', ['uc_order' => $this->order_id]);
  }

}
