<?php

/**
 * @file
 * Contains \Drupal\uc_cart\Entity\UcCartItem.
 */

namespace Drupal\uc_cart\Entity;

use Drupal\Core\Entity\Entity;

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
class UcCartItem extends Entity {

  /**
   * The cart item ID.
   *
   * @var integer
   */
  public $cart_item_id;

  /**
   * The cart ID.
   *
   * @var string
   */
  public $cart_id;

  /**
   * The node ID of the product.
   *
   * @var integer
   */
  public $nid;

  /**
   * The quantity of this product.
   *
   * @var integer
   */
  public $qty;

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
    return $this->cart_item_id;
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
   * Dummy implementation of getPropertyDefinitions().
   *
   * UcCartItem is not a true content entity, but we implement this to
   * avoid errors when this entity is rendered.
   */
  public function getPropertyDefinitions() {
    return array();
  }

}
