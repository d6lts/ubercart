<?php

/**
 * @file
 * Definition of Drupal\uc_order\Entity\UcOrder.
 */

namespace Drupal\uc_order\Entity;

use Drupal\Core\Entity\EntityNG;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\uc_order\UcOrderBCDecorator;
use Drupal\uc_order\UcOrderInterface;
use Drupal\uc_store\UcAddress;

/**
 * Defines the order entity class.
 *
 * @EntityType(
 *   id = "uc_order",
 *   label = @Translation("Order"),
 *   module = "uc_order",
 *   controllers = {
 *     "storage" = "Drupal\uc_order\UcOrderStorageController",
 *     "render" = "Drupal\uc_order\UcOrderRenderController",
 *   },
 *   base_table = "uc_orders",
 *   uri_callback = "uc_order_uri",
 *   fieldable = TRUE,
 *   route_base_path = "admin/store/settings/orders",
 *   entity_keys = {
 *     "id" = "order_id",
 *   }
 * )
 */
class UcOrder extends EntityNG implements UcOrderInterface {

  public $products = array();
  public $line_items = array();

  public $data = array();

  /**
   * The order creation timestamp.
   *
   * @var integer
   */
  public $created;

  /**
   * The order modification timestamp.
   *
   * @var integer
   */
  public $modified;

  /**
   * Overrides Drupal\Core\Entity\EntityNG::init().
   */
  public function init() {
    parent::init();

    // We unset all defined properties, so magic getters apply.
    // unset($this->products);
    // unset($this->line_items);
    unset($this->data);
    unset($this->created);
    unset($this->modified);
  }

  /**
   * Implements Drupal\Core\Entity\EntityInterface::id().
   */
  public function id() {
    return $this->get('order_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getBCEntity() {
    if (!isset($this->bcEntity)) {
      $this->getPropertyDefinitions();
      $this->bcEntity = new UcOrderBCDecorator($this, $this->fieldDefinitions);
    }
    return $this->bcEntity;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    $this->order_total->value = $this->getTotal();
    $this->product_count->value = uc_order_get_product_count($this);
    if (is_null($this->delivery_country->value) || $this->delivery_country->value == 0) {
      $this->delivery_country->value = config('uc_store.settings')->get('address.country');
    }
    if (is_null($this->billing_country->value) || $this->billing_country->value == 0) {
      $this->billing_country->value = config('uc_store.settings')->get('address.country');
    }
    $this->host->value = \Drupal::request()->getClientIp();
    $this->modified->value = REQUEST_TIME;

    $order = $this->getBCEntity();
    uc_order_module_invoke('presave', $order, NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    foreach ((array) $this->getBCEntity()->products as $product) {
      drupal_alter('uc_order_product', $product, $this);
      uc_order_product_save($this->id(), $product);
    }

    $order = $this->getBCEntity();
    uc_order_module_invoke('save', $order, NULL);
  }

  /**
   * {@inheritdoc}
   */
  static public function preDelete(EntityStorageControllerInterface $storage_controller, array $orders) {
    foreach ($orders as $order_id => $order) {
      $order = $order->getBCEntity();
      uc_order_module_invoke('delete', $order, NULL);
    }
  }

  /**
   * {@inheritdoc}
   */
  static public function postDelete(EntityStorageControllerInterface $storage_controller, array $orders) {
    // Delete data from the appropriate Ubercart order tables.
    $ids = array_keys($orders);
    $result = \Drupal::entityQuery('uc_order_product')
      ->condition('order_id', $ids, 'IN')
      ->execute();
    if (!empty($result)) {
      $product_ids = array_keys($result);
      uc_order_product_delete_multiple($product_ids);
    }
    db_delete('uc_order_comments')
      ->condition('order_id', $ids, 'IN')
      ->execute();
    db_delete('uc_order_admin_comments')
      ->condition('order_id', $ids, 'IN')
      ->execute();
    db_delete('uc_order_log')
      ->condition('order_id', $ids, 'IN')
      ->execute();

    foreach ($orders as $order_id => $order) {
      // Delete line items for the order.
      uc_order_delete_line_item($order_id, TRUE);

      // Log the action in the database.
      watchdog('uc_order', 'Order @order_id deleted by user @uid.', array('@order_id' => $order_id, '@uid' => $GLOBALS['user']->id()));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getUser() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setUserId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusId() {
    return $this->get('order_status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatusId($status) {
    $this->set('order_status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStateId() {
    return uc_order_status_data($this->get('order_status')->value, 'state');
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail() {
    return $this->get('primary_email')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmail($email) {
    $this->set('primary_email', $email);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubtotal() {
    return uc_order_get_total($this->getBCEntity(), TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getTotal() {
    return uc_order_get_total($this->getBCEntity());
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrency() {
    return $this->get('currency')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentMethod() {
    return $this->get('payment_method')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPaymentMethod($payment_method) {
    $this->set('payment_method', $payment_method);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddress($type) {
    $address = new UcAddress();
    $address->first_name = $this->get($type . '_first_name')->value;
    $address->last_name = $this->get($type . '_last_name')->value;
    $address->company = $this->get($type . '_company')->value;
    $address->street1 = $this->get($type . '_street1')->value;
    $address->street2 = $this->get($type . '_street2')->value;
    $address->city = $this->get($type . '_city')->value;
    $address->zone = $this->get($type . '_zone')->value;
    $address->country = $this->get($type . '_country')->value;
    $address->postal_code = $this->get($type . '_postal_code')->value;
    $address->phone = $this->get($type . '_phone')->value;
    return $address;
  }

  /**
   * {@inheritdoc}
   */
  public function setAddress($type, UcAddress $address) {
    $this->set($type . '_first_name', $address->first_name);
    $this->set($type . '_last_name', $address->last_name);
    $this->set($type . '_company', $address->company);
    $this->set($type . '_street1', $address->street1);
    $this->set($type . '_street2', $address->street2);
    $this->set($type . '_city', $address->city);
    $this->set($type . '_zone', $address->zone);
    $this->set($type . '_country', $address->country);
    $this->set($type . '_postal_code', $address->postal_code);
    $this->set($type . '_phone', $address->phone);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {
    $properties['order_id'] = array(
      'label' => t('Order ID'),
      'description' => t('The order ID.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $properties['uid'] = array(
      'label' => t('Customer'),
      'description' => 'The user that placed the order.',
      'type' => 'entity_reference_field',
      'settings' => array('target_type' => 'user'),
    );
    $properties['order_status'] = array(
      'label' => t('Order status'),
      'description' => 'The {uc_order_statuses}.order_status_id indicating the order status.',
      'type' => 'string_field',
    );
    $properties['order_total'] = array(
      'label' => t('Order total'),
      'description' => 'The total amount to be paid for the order.',
      'type' => 'integer_field',
      // 'type' => 'float',
    );
    $properties['product_count'] = array(
      'label' => t('Product count'),
      'description' => 'The total product quantity of the order.',
      'type' => 'integer_field',
    );
    $properties['primary_email'] = array(
      'label' => t('Primary e-mail'),
      'description' => 'The email address of the customer.',
      'type' => 'string_field',
    );
    $properties['delivery_first_name'] = array(
      'label' => t('Delivery first name'),
      'description' => 'The first name of the person receiving shipment.',
      'type' => 'string_field',
    );
    $properties['delivery_last_name'] = array(
      'label' => t('Delivery last name'),
      'description' => 'The last name of the person receiving shipment.',
      'type' => 'string_field',
    );
    $properties['delivery_phone'] = array(
      'label' => t('Delivery phone'),
      'description' => 'The phone number at the delivery location.',
      'type' => 'string_field',
    );
    $properties['delivery_company'] = array(
      'label' => t('Delivery company'),
      'description' => 'The company at the delivery location.',
      'type' => 'string_field',
    );
    $properties['delivery_street1'] = array(
      'label' => t('Delivery street 1'),
      'description' => 'The street address of the delivery location.',
      'type' => 'string_field',
    );
    $properties['delivery_street2'] = array(
      'label' => t('Delivery street 2'),
      'description' => 'The second line of the street address.',
      'type' => 'string_field',
    );
    $properties['delivery_city'] = array(
      'label' => t('Delivery city'),
      'description' => 'The city of the delivery location.',
      'type' => 'string_field',
    );
    $properties['delivery_zone'] = array(
      'label' => t('Delivery state/province'),
      'description' => 'The state/zone/province id of the delivery location.',
      'type' => 'integer_field',
    );
    $properties['delivery_postal_code'] = array(
      'label' => t('Delivery postal code'),
      'description' => 'The postal code of the delivery location.',
      'type' => 'string_field',
    );
    $properties['delivery_country'] = array(
      'label' => t('Delivery country'),
      'description' => 'The country ID of the delivery location.',
      'type' => 'integer_field',
    );
    $properties['billing_first_name'] = array(
      'label' => t('Billing first name'),
      'description' => 'The first name of the person paying for the order.',
      'type' => 'string_field',
    );
    $properties['billing_last_name'] = array(
      'label' => t('Billing last name'),
      'description' => 'The last name of the person paying for the order.',
      'type' => 'string_field',
    );
    $properties['billing_phone'] = array(
      'label' => t('Billing phone'),
      'description' => 'The phone number for the billing address.',
      'type' => 'string_field',
    );
    $properties['billing_company'] = array(
      'label' => t('Billing company'),
      'description' => 'The company of the billing address.',
      'type' => 'string_field',
    );
    $properties['billing_street1'] = array(
      'label' => t('Billing street 1'),
      'description' => 'The street address where the bill will be sent.',
      'type' => 'string_field',
    );
    $properties['billing_street2'] = array(
      'label' => t('Billing street 2'),
      'description' => 'The second line of the street address.',
      'type' => 'string_field',
    );
    $properties['billing_city'] = array(
      'label' => t('Billing city'),
      'description' => 'The city where the bill will be sent.',
      'type' => 'string_field',
    );
    $properties['billing_zone'] = array(
      'label' => t('Billing state/province'),
      'description' => 'The state/zone/province ID where the bill will be sent.',
      'type' => 'integer_field',
    );
    $properties['billing_postal_code'] = array(
      'label' => t('Billing postal code'),
      'description' => 'The postal code where the bill will be sent.',
      'type' => 'string_field',
    );
    $properties['billing_country'] = array(
      'label' => t('Billing country'),
      'description' => 'The country ID where the bill will be sent.',
      'type' => 'integer_field',
    );
    $properties['payment_method'] = array(
      'label' => t('Payment method'),
      'description' => 'The method of payment.',
      'type' => 'string_field',
    );
    $properties['data'] = array(
      'label' => t('Data'),
      'description' => 'A serialized array of extra data.',
      'type' => 'string_field',
    );
    $properties['created'] = array(
      'label' => t('Created'),
      'description' => 'The Unix timestamp indicating when the order was created.',
      'type' => 'integer_field',
    );
    $properties['modified'] = array(
      'label' => t('Modified'),
      'description' => 'The Unix timestamp indicating when the order was last modified.',
      'type' => 'integer_field',
    );
    $properties['host'] = array(
      'label' => t('Host'),
      'description' => 'Host IP address of the person paying for the order.',
      'type' => 'string_field',
    );
    $properties['currency'] = array(
      'label' => t('Currency'),
      'description' => 'The ISO currency code for the order.',
      'type' => 'string_field',
      // 'settings' => array(
      //   'default_value' => '',
      // ),
      // 'property_constraints' => array(
      //   'value' => array('Length' => array('max' => 3)),
      // ),
    );
    return $properties;
  }

}
