<?php

/**
 * @file
 * Contains \Drupal\uc_cart\Entity\UcCartItem.
 */

namespace Drupal\uc_cart\Entity;

use Drupal\Core\Entity\ContentEntityBase;

/**
 * Defines the cart item entity class.
 *
 * @EntityType(
 *   id = "uc_cart_item",
 *   label = @Translation("Cart item"),
 *   module = "uc_cart",
 *   controllers = {
 *     "storage" = "Drupal\uc_cart\UcCartItemStorageController",
 *     "view_builder" = "Drupal\uc_cart\UcCartItemViewBuilder",
 *   },
 *   base_table = "uc_cart_products",
 *   entity_keys = {
 *     "id" = "cart_item_id",
 *   }
 * )
 */
class UcCartItem extends ContentEntityBase {

  public $product;
  public $title;
  public $model;
  public $cost;
  public $price;
  public $weight;
  public $weight_units;

  /**
   * The timestamp when this item was last updated.
   *
   * @var integer
   */
  public $changed;

  /**
   * An array of data about this item.
   *
   * @var array
   */
  public $data;

  /**
   * Implements Drupal\Core\Entity\EntityInterface::id().
   */
  public function id() {
    return $this->cart_item_id->value;
  }

  /**
   * Converts a cart item into an order product.
   */
  public function toOrderProduct() {
    return entity_create('uc_order_product', array(
      'nid' => $this->nid,
      'title' => $this->title,
      'model' => $this->model,
      'qty' => $this->qty,
      'cost' => $this->cost,
      'price' => $this->price,
      'weight' => $this->weight,
      'weight_units' => $this->weight_units,
      'data' => $this->data,
    ));
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {
    $properties['cart_item_id'] = array(
      'label' => t('Cart item ID'),
      'description' => 'The cart item ID.',
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $properties['cart_id'] = array(
      'label' => t('Cart ID'),
      'description' => 'A user-specific cart ID. For authenticated users, their {users}.uid. For anonymous users, a token.',
      'type' => 'string_field',
    );
    $properties['nid'] = array(
      'label' => t('Node ID'),
      'description' => 'The node ID of the product.',
      'type' => 'entity_reference_field',
      'settings' => array('target_type' => 'node'),
    );
    $properties['qty'] = array(
      'label' => t('Quantity'),
      'description' => 'The number of this product in the cart.',
      'type' => 'integer_field',
    );
    $properties['changed'] = array(
      'label' => t('Changed'),
      'description' => 'The Unix timestamp indicating the time the product in the cart was changed.',
      'type' => 'integer_field',
    );
    // $properties['data'] = array(
    //   'label' => t('Data'),
    //   'description' => 'A serialized array of extra data.',
    //   'type' => 'string_field',
    // );
    return $properties;
  }

}
