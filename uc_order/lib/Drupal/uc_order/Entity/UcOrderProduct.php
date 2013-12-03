<?php

/**
 * @file
 * Contains \Drupal\uc_order\Entity\UcOrderProduct.
 */

namespace Drupal\uc_order\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\uc_order\UcOrderProductInterface;

/**
 * Defines the order product entity class.
 *
 * @EntityType(
 *   id = "uc_order_product",
 *   label = @Translation("Order product"),
 *   module = "uc_order",
 *   controllers = {
 *     "view_builder" = "Drupal\uc_order\UcOrderProductViewBuilder",
 *     "storage" = "Drupal\uc_order\UcOrderProductStorageController"
 *   },
 *   base_table = "uc_order_products",
 *   fieldable = TRUE,
 *   route_base_path = "admin/store/settings/orders/products",
 *   entity_keys = {
 *     "id" = "order_product_id",
 *   }
 * )
 */
class UcOrderProduct extends ContentEntityBase implements UcOrderProductInterface {

  /**
   * The order product ID.
   *
   * @var integer
   */
  public $order_product_id;

  /**
   * The order ID.
   *
   * @var integer
   */
  public $order_id;

  /**
   * The node ID of this product.
   *
   * @var integer
   */
  public $nid;

  /**
   * The title of this product.
   *
   * @var string
   */
  public $title;

  /**
   * The SKU of this product.
   *
   * @var string
   */
  public $model;

  /**
   * The quantity of this product.
   *
   * @var integer
   */
  public $qty;

  /**
   * The cost of this product.
   *
   * @var float
   */
  public $cost;

  /**
   * The price of this product.
   *
   * @var float
   */
  public $price;

  /**
   * The weight of this product.
   *
   * @var float
   */
  public $weight;

  /**
   * The units of weight of this product.
   *
   * @var string
   */
  public $weight_units;

  /**
   * An array of extra data about this product.
   *
   * @var array
   */
  public $data;

  /**
   * {@inheritdoc}
   */
  public function init() {
    parent::init();

    // We unset all defined properties, so magic getters apply.
    unset($this->order_product_id);
    unset($this->order_id);
    unset($this->nid);
    unset($this->title);
    unset($this->model);
    unset($this->qty);
    unset($this->cost);
    unset($this->price);
    unset($this->weight);
    unset($this->weight_units);
    // unset($this->data);
  }

  /**
   * Implements Drupal\Core\Entity\EntityInterface::id().
   */
  public function id() {
    return $this->get('order_product_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {
    $properties['order_product_id'] = array(
      'label' => t('Order product ID'),
      'description' => t('The order ID.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $properties['order_id'] = array(
      'label' => t('Order ID'),
      'description' => t('The order ID.'),
      'type' => 'entity_reference_field',
      'settings' => array('target_type' => 'uc_order'),
    );
    $properties['nid'] = array(
      'label' => t('Node ID'),
      'description' => 'The user that placed the order.',
      'type' => 'entity_reference_field',
      'settings' => array('target_type' => 'node'),
    );
    $properties['title'] = array(
      'label' => t('Title'),
      'description' => 'The product title.',
      'type' => 'string_field',
    );
    $properties['model'] = array(
      'label' => t('SKU'),
      'description' => 'The product model/SKU.',
      'type' => 'string_field',
    );
    $properties['qty'] = array(
      'label' => t('Quantity'),
      'description' => 'The number of the product ordered.',
      'type' => 'integer_field',
    );
    $properties['cost'] = array(
      'label' => t('Cost'),
      'description' => 'The cost to the store for the product.',
      'type' => 'integer_field',
    );
    $properties['price'] = array(
      'label' => t('Price'),
      'description' => 'The price paid for the ordered product.',
      'type' => 'integer_field',
    );
    $properties['weight'] = array(
      'label' => t('Weight'),
      'description' => 'The physical weight.',
      'type' => 'integer_field',
    );
    $properties['weight_units'] = array(
      'label' => t('Weight units'),
      'description' => 'Unit of measure for the weight field.',
      'type' => 'string_field',
    );
    $properties['data'] = array(
      'label' => t('Data'),
      'description' => 'A serialized array of extra data.',
      'type' => 'string_field',
    );
    return $properties;
  }

}
