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

  public $currency = '';

  public $delivery_first_name = '';
  public $delivery_last_name = '';
  public $delivery_phone = '';
  public $delivery_company = '';
  public $delivery_street1 = '';
  public $delivery_street2 = '';
  public $delivery_city = '';
  public $delivery_zone = 0;
  public $delivery_postal_code = '';
  public $delivery_country = 0;

  public $billing_first_name = '';
  public $billing_last_name = '';
  public $billing_phone = '';
  public $billing_company = '';
  public $billing_street1 = '';
  public $billing_street2 = '';
  public $billing_city = '';
  public $billing_zone = 0;
  public $billing_postal_code = '';
  public $billing_country = 0;

  public $products = array();
  public $line_items = array();

  public $payment_method = '';
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
    unset($this->currency);
    unset($this->delivery_first_name);
    unset($this->delivery_last_name);
    unset($this->delivery_phone);
    unset($this->delivery_company);
    unset($this->delivery_street1);
    unset($this->delivery_street2);
    unset($this->delivery_city);
    unset($this->delivery_zone);
    unset($this->delivery_postal_code);
    unset($this->delivery_country);
    unset($this->billing_first_name);
    unset($this->billing_last_name);
    unset($this->billing_phone);
    unset($this->billing_company);
    unset($this->billing_street1);
    unset($this->billing_street2);
    unset($this->billing_city);
    unset($this->billing_zone);
    unset($this->billing_postal_code);
    unset($this->billing_country);
    // unset($this->products);
    // unset($this->line_items);
    unset($this->payment_method);
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

}
