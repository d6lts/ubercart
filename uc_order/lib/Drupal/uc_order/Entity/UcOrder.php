<?php

/**
 * @file
 * Contains \Drupal\uc_order\Entity\UcOrder.
 */

namespace Drupal\uc_order\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\uc_order\UcOrderInterface;
use Drupal\uc_store\Address;

/**
 * Defines the order entity class.
 *
 * @EntityType(
 *   id = "uc_order",
 *   label = @Translation("Order"),
 *   module = "uc_order",
 *   controllers = {
 *     "storage" = "Drupal\uc_order\UcOrderStorageController",
 *     "view_builder" = "Drupal\uc_order\UcOrderViewBuilder",
 *     "access" = "Drupal\uc_order\UcOrderAccessController",
 *     "form" = {
 *       "delete" = "Drupal\uc_order\Form\OrderDeleteForm",
 *     }
 *   },
 *   base_table = "uc_orders",
 *   uri_callback = "uc_order_uri",
 *   fieldable = TRUE,
 *   links = {
 *     "admin-form" = "uc_order.workflow"
 *   },
 *   entity_keys = {
 *     "id" = "order_id",
 *   }
 * )
 */
class UcOrder extends ContentEntityBase implements UcOrderInterface {

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
   * {@inheritdoc}
   */
  public function init() {
    parent::init();

    // We unset all defined properties, so magic getters apply.
    // unset($this->products);
    // unset($this->line_items);
    // unset($this->data);
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
   * Implements Drupal\Core\Entity\EntityInterface::label().
   */
  public function label($langcode = NULL) {
    return t('Order @order_id', array('@order_id' => $this->id()));
  }

  /**
   * {@inheritdoc}
   */
  public function uuid() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageControllerInterface $storage_controller, array &$entities) {
    parent::postLoad($storage_controller, $entities);

    foreach ($entities as $id => $order) {
      // @todo Move unserialize() back to the storage controller.
      $order->data = unserialize($order->data);

      $order->products = entity_load_multiple_by_properties('uc_order_product', array('order_id' => $id));

      uc_order_module_invoke('load', $order, NULL);

      // Load line items... has to be last after everything has been loaded.
      $order->line_items = uc_order_load_line_items($order);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    $this->order_total->value = $this->getTotal();
    $this->product_count->value = $this->getProductCount();
    if (is_null($this->delivery_country->value) || $this->delivery_country->value == 0) {
      $this->delivery_country->value = \Drupal::config('uc_store.settings')->get('address.country');
    }
    if (is_null($this->billing_country->value) || $this->billing_country->value == 0) {
      $this->billing_country->value = \Drupal::config('uc_store.settings')->get('address.country');
    }
    $this->host->value = \Drupal::request()->getClientIp();
    $this->modified->value = REQUEST_TIME;

    uc_order_module_invoke('presave', $this, NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    foreach ($this->products as $product) {
      \Drupal::moduleHandler()->alter('uc_order_product', $product, $this);
      uc_order_product_save($this->id(), $product);
    }

    uc_order_module_invoke('save', $this, NULL);

    // Record a log entry if the order status has changed.
    if ($update && $this->getStatusId() != $this->original->getStatusId()) {
      $this->logChanges(array(t('Order status') => array(
        'old' => $this->original->getStatus()->name,
        'new' => $this->getStatus()->name,
      )));

      // rules_invoke_event('uc_order_status_update', $this->original, $this);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageControllerInterface $storage_controller, array $orders) {
    foreach ($orders as $order) {
      uc_order_module_invoke('delete', $order, NULL);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageControllerInterface $storage_controller, array $orders) {
    // Delete data from the appropriate Ubercart order tables.
    $ids = array_keys($orders);
    $result = \Drupal::entityQuery('uc_order_product')
      ->condition('order_id', $ids, 'IN')
      ->execute();
    if (!empty($result)) {
      entity_delete_multiple('uc_order_product', array_keys($result));
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
  public function getStatus() {
    return $this->get('order_status')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusId() {
    return $this->get('order_status')->target_id;
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
    return $this->getStatus()->state;
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
    $subtotal = 0;
    foreach ($this->products as $product) {
      $subtotal += $product->price->value * $product->qty->value;
    }
    return $subtotal;
  }

  /**
   * {@inheritdoc}
   */
  public function getTotal() {
    return $this->getSubtotal() + uc_line_items_calculate($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getProductCount() {
    $count = 0;
    foreach ($this->products as $product) {
      $count += $product->qty->value;
    }
    return $count;
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
  public function getPaymentMethodId() {
    return $this->get('payment_method')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPaymentMethodId($payment_method) {
    $this->set('payment_method', $payment_method);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddress($type) {
    $address = new Address();
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
  public function setAddress($type, Address $address) {
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
  public function isShippable() {
    foreach ($this->products as $product) {
      if (uc_order_product_is_shippable($product)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function logChanges($changes) {
    global $user;

    if (!empty($changes)) {
      foreach ($changes as $key => $value) {
        if (is_array($value)) {
          $items[] = t('@key changed from %old to %new.', array('@key' => $key, '%old' => $value['old'], '%new' => $value['new']));
        }
        elseif (is_string($value)) {
          $items[] = $value;
        }
      }

      db_insert('uc_order_log')
        ->fields(array(
          'order_id' => $this->id(),
          'uid' => $user->id(),
          'changes' => theme('item_list', array('items' => $items)),
          'created' => REQUEST_TIME,
        ))
        ->execute();
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {
    $fields['order_id'] = FieldDefinition::create('integer')
      ->setLabel(t('Order ID'))
      ->setDescription(t('The order ID.'))
      ->setReadOnly(TRUE);
    $fields['uid'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('Customer'))
      ->setDescription(t('The user that placed the order.'))
      ->setSetting('target_type', 'user');
    $fields['order_status'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('Order status'))
      ->setDescription(t('The uc_order_status entity ID indicating the order status'))
      ->setSetting('target_type', 'uc_order_status');
    $fields['order_total'] = FieldDefinition::create('integer') // float?
      ->setLabel(t('Order total'))
      ->setDescription(t('The total amount to be paid for the order.'));
    $fields['product_count'] = FieldDefinition::create('integer')
      ->setLabel(t('Product count'))
      ->setDescription(t('The total product quantity of the order.'));
    $fields['primary_email'] = FieldDefinition::create('email')
      ->setLabel(t('E-mail address'))
      ->setDescription(t('The email address of the customer.'));
    $fields['delivery_first_name'] = FieldDefinition::create('string')
      ->setLabel(t('Delivery first name'))
      ->setDescription(t('The first name of the person receiving shipment.'));
    $fields['delivery_last_name'] = FieldDefinition::create('string')
      ->setLabel(t('Delivery last name'))
      ->setDescription(t('The last name of the person receiving shipment.'));
    $fields['delivery_phone'] = FieldDefinition::create('string')
      ->setLabel(t('Delivery phone'))
      ->setDescription(t('The phone number at the delivery location.'));
    $fields['delivery_company'] = FieldDefinition::create('string')
      ->setLabel(t('Delivery company'))
      ->setDescription(t('The company at the delivery location.'));
    $fields['delivery_street1'] = FieldDefinition::create('string')
      ->setLabel(t('Delivery street 1'))
      ->setDescription(t('The street address of the delivery location.'));
    $fields['delivery_street2'] = FieldDefinition::create('string')
      ->setLabel(t('Delivery street 2'))
      ->setDescription(t('The second line of the street address.'));
    $fields['delivery_city'] = FieldDefinition::create('string')
      ->setLabel(t('Delivery city'))
      ->setDescription(t('The city of the delivery location.'));
    $fields['delivery_zone'] = FieldDefinition::create('integer')
      ->setLabel(t('Delivery state/province'))
      ->setDescription(t('The state/zone/province id of the delivery location.'));
    $fields['delivery_postal_code'] = FieldDefinition::create('string')
      ->setLabel(t('Delivery postal code'))
      ->setDescription(t('The postal code of the delivery location.'));
    $fields['delivery_country'] = FieldDefinition::create('integer')
      ->setLabel(t('Delivery country'))
      ->setDescription(t('The country ID of the delivery location.'));
    $fields['billing_first_name'] = FieldDefinition::create('string')
      ->setLabel(t('Billing first name'))
      ->setDescription(t('The first name of the person paying for the order.'));
    $fields['billing_last_name'] = FieldDefinition::create('string')
      ->setLabel(t('Billing last name'))
      ->setDescription(t('The last name of the person paying for the order.'));
    $fields['billing_phone'] = FieldDefinition::create('string')
      ->setLabel(t('Billing phone'))
      ->setDescription(t('The phone number for the billing address.'));
    $fields['billing_company'] = FieldDefinition::create('string')
      ->setLabel(t('Billing company'))
      ->setDescription(t('The company of the billing address.'));
    $fields['billing_street1'] = FieldDefinition::create('string')
      ->setLabel(t('Billing street 1'))
      ->setDescription(t('The street address where the bill will be sent.'));
    $fields['billing_street2'] = FieldDefinition::create('string')
      ->setLabel(t('Billing street 2'))
      ->setDescription(t('The second line of the street address.'));
    $fields['billing_city'] = FieldDefinition::create('string')
      ->setLabel(t('Billing city'))
      ->setDescription(t('The city where the bill will be sent.'));
    $fields['billing_zone'] = FieldDefinition::create('integer')
      ->setLabel(t('Billing state/province'))
      ->setDescription(t('The state/zone/province ID where the bill will be sent.'));
    $fields['billing_postal_code'] = FieldDefinition::create('string')
      ->setLabel(t('Billing postal code'))
      ->setDescription(t('The postal code where the bill will be sent.'));
    $fields['billing_country'] = FieldDefinition::create('integer')
      ->setLabel(t('Billing country'))
      ->setDescription(t('The country ID where the bill will be sent.'));
    $fields['payment_method'] = FieldDefinition::create('string')
      ->setLabel(t('Payment method'))
      ->setDescription(t('The method of payment.'));
//    $fields['data'] = FieldDefinition::create('string')
//      ->setLabel(t('Data'))
//      ->setDescription(t('A serialized array of extra data.'));
    $fields['created'] = FieldDefinition::create('integer')
      ->setLabel(t('Created'))
      ->setDescription(t('The Unix timestamp indicating when the order was created.'));
    $fields['modified'] = FieldDefinition::create('integer')
      ->setLabel(t('Modified'))
      ->setDescription(t('The Unix timestamp indicating when the order was last modified.'));
    $fields['host'] = FieldDefinition::create('string')
      ->setLabel(t('Host'))
      ->setDescription(t('Host IP address of the person paying for the order.'));
    $fields['currency'] = FieldDefinition::create('string')
      ->setLabel(t('Currency'))
      ->setDescription(t('The ISO currency code for the order.'))
      ->setPropertyConstraints('value', array('Length' => array('max' => 3)));

    return $fields;
  }

}
