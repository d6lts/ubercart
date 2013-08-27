<?php

/**
 * @file
 * Definition of Drupal\uc_order\UcOrderProductStorageController.
 */

namespace Drupal\uc_order;

use Drupal\Core\Entity\DatabaseStorageControllerNG;
use Drupal\Core\Entity\EntityInterface;

/**
 * Controller class for ordered products.
 */
class UcOrderProductStorageController extends DatabaseStorageControllerNG {

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::create().
   */
  public function create(array $values) {
    return parent::create($values)->getBCEntity();
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::attachLoad().
   */
  protected function attachLoad(&$queried_entities, $load_revision = FALSE) {
    $products = $this->mapFromStorageRecords($queried_entities, $load_revision);

    foreach ($products as $id => $product) {
      $product = $product->getBCEntity();
      $queried_entities[$id] = $product;

      $product->data = unserialize($product->data);
    }
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::save().
   */
  public function save(EntityInterface $product) {
    // Product kits, particularly, shouldn't actually be added to an order,
    // but instead they cause other products to be added.
    if (isset($product->skip_save) && $product->skip_save == TRUE) {
      return;
    }

    if (empty($product->weight_units)) {
      if (empty($product->nid)) {
        $product->weight_units = variable_get('uc_weight_unit', 'lb');
      }
      else {
        $units = db_query("SELECT weight_units FROM {node} n JOIN {uc_products} p ON n.vid = p.vid WHERE n.nid = :nid", array(':nid' => $product->nid))->fetchField();
        $product->weight_units = empty($units) ? variable_get('uc_weight_unit', 'lb') : $units;
      }
    }
    return parent::save($product);
  }

  /**
   * {@inheritdoc}
   */
  public function baseFieldDefinitions() {
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
