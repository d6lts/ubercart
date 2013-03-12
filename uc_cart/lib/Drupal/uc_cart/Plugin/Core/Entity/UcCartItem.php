<?php

/**
 * @file
 * Definition of Drupal\uc_cart\Plugin\Core\Entity\UcCartItem.
 */

namespace Drupal\uc_cart\Plugin\Core\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the cart item entity class.
 *
 * @Plugin(
 *   id = "uc_cart_item",
 *   label = @Translation("Cart item"),
 *   module = "uc_cart",
 *   controller_class = "Drupal\uc_cart\UcCartItemStorageController",
 *   render_controller_class = "Drupal\uc_cart\UcCartItemRenderController",
 *   base_table = "uc_cart_products",
 *   entity_keys = {
 *     "id" = "cart_item_id",
 *   }
 * )
 */
class UcCartItem extends Entity implements ContentEntityInterface {

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

}
