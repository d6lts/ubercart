<?php

/**
 * @file
 * Contains \Drupal\uc_cart\Entity\UcCartItem.
 */

namespace Drupal\uc_cart\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinition;

/**
 * Defines the cart item entity class.
 *
 * @ContentEntityType(
 *   id = "uc_cart_item",
 *   label = @Translation("Cart item"),
 *   module = "uc_cart",
 *   controllers = {
 *     "storage" = "Drupal\uc_cart\UcCartItemStorage",
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
      'nid' => $this->nid->target_id,
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
  public static function postLoad(EntityStorageInterface $storage, array &$items) {
    foreach ($items as $item) {
      $item->product = uc_product_load_variant($item->nid->target_id, $item->data);
      if ($item->product) {
        $item->title = $item->product->label();
        $item->model = $item->product->model;
        $item->cost = $item->product->cost;
        $item->price = $item->product->price;
        $item->weight = $item->product->weight;
        $item->weight_units = $item->product->weight_units;
      }

      $item->module = $item->data->module;
    }
    parent::postLoad($storage, $items);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['cart_item_id'] = FieldDefinition::create('integer')
      ->setLabel(t('Cart item ID'))
      ->setDescription(t('The cart item ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);
    $fields['cart_id'] = FieldDefinition::create('string')
      ->setLabel(t('Cart ID'))
      ->setDescription(t('A user-specific cart ID. For authenticated users, their {users}.uid. For anonymous users, a token.'))
      ->setSetting('default_value', 0);
    $fields['nid'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('Node ID'))
      ->setDescription(t('The node ID of the product.'))
      ->setSetting('target_type', 'node')
      ->setSetting('default_value', 0);
    $fields['qty'] = FieldDefinition::create('integer')
      ->setLabel(t('Quantity'))
      ->setDescription(t('The number of this product in the cart.'))
      ->setSetting('default_value', 0)
      ->setSetting('unsigned', TRUE);
    $fields['changed'] = FieldDefinition::create('integer')
      ->setLabel(t('Changed'))
      ->setDescription(t('The Unix timestamp indicating the time the product in the cart was changed.'))
      ->setSetting('default_value', 0);
    $fields['data'] = FieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('A serialized array of extra data.'));

    return $fields;
  }

}
