<?php

/**
 * @file
 * Definition of Drupal\uc_order\Entity\UcOrder.
 */

namespace Drupal\uc_order\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;

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
class UcOrder extends Entity implements ContentEntityInterface {

  /**
   * The order ID.
   *
   * @var integer
   */
  public $order_id;

  /**
   * The order owner's user ID.
   *
   * @var integer
   */
  public $uid;

  public $currency = '';
  public $order_status = '';
  public $order_total = 0;
  public $primary_email = '';

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
   * Implements Drupal\Core\Entity\EntityInterface::id().
   */
  public function id() {
    return $this->order_id;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    $this->order_total = uc_order_get_total($this);
    $this->product_count = uc_order_get_product_count($this);
    if (is_null($this->delivery_country) || $this->delivery_country == 0) {
      $this->delivery_country = config('uc_store.settings')->get('address.country');
    }
    if (is_null($this->billing_country) || $this->billing_country == 0) {
      $this->billing_country = config('uc_store.settings')->get('address.country');
    }
    $this->host = \Drupal::request()->getClientIp();
    $this->modified = REQUEST_TIME;

    uc_order_module_invoke('presave', $this, NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    foreach ($this->products as $product) {
      drupal_alter('uc_order_product', $product, $this);
      uc_order_product_save($this->order_id, $product);
    }

    uc_order_module_invoke('save', $this, NULL);
    $this->order_total = uc_order_get_total($this);
  }

  /**
   * {@inheritdoc}
   */
  static public function preDelete(EntityStorageControllerInterface $storage_controller, array $orders) {
    foreach ($orders as $order_id => $order) {
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
      watchdog('uc_order', 'Order @order_id deleted by user @uid.', array('@order_id' => $order_id, '@uid' => $GLOBALS['user']->uid));
    }
  }

}
