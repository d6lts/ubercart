<?php

/**
 * @file
 * Contains \Drupal\uc_cart\Entity\UcCartItem.
 */

namespace Drupal\uc_cart\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Field\FieldDefinition;

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
      'nid' => $this->nid->value,
      'title' => $this->title,
      'model' => $this->model,
      'qty' => $this->qty->value,
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
    $fields['cart_item_id'] = FieldDefinition::create('integer')
      ->setLabel(t('Cart item ID'))
      ->setDescription(t('The cart item ID.'))
      ->setReadOnly(TRUE);
    $fields['cart_id'] = FieldDefinition::create('string')
      ->setLabel(t('Cart ID'))
      ->setDescription(t('A user-specific cart ID. For authenticated users, their {users}.uid. For anonymous users, a token.'));
    $fields['nid'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('Node ID'))
      ->setDescription(t('The node ID of the product.'))
      ->setFieldSetting('target_type', 'node');
    $fields['qty'] = FieldDefinition::create('integer')
      ->setLabel(t('Quantity'))
      ->setDescription(t('The number of this product in the cart.'));
    $fields['changed'] = FieldDefinition::create('integer')
      ->setLabel(t('Changed'))
      ->setDescription(t('The Unix timestamp indicating the time the product in the cart was changed.'));
//    $fields['data'] = FieldDefinition::create('string')
//      ->setLabel(t('Data'))
//      ->setDescription(t('A serialized array of extra data.'));

    return $fields;
  }

}
