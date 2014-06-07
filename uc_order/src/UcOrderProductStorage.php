<?php

/**
 * @file
 * Contains \Drupal\uc_order\UcOrderProductStorage.
 */

namespace Drupal\uc_order;

use Drupal\Core\Entity\ContentEntityDatabaseStorage;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Controller class for ordered products.
 */
class UcOrderProductStorage extends ContentEntityDatabaseStorage {

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $product) {
    // Product kits, particularly, shouldn't actually be added to an order,
    // but instead they cause other products to be added.
    if (isset($product->skip_save) && $product->skip_save == TRUE) {
      return;
    }

    if (empty($product->weight_units->value)) {
      if (empty($product->nid->value)) {
        $product->weight_units->value = \Drupal::config('uc_store.settings')->get('units.weight');
      }
      else {
        $units = db_query("SELECT weight_units FROM {node} n JOIN {uc_products} p ON n.vid = p.vid WHERE n.nid = :nid", array(':nid' => $product->nid->value))->fetchField();
        $product->weight_units->value = empty($units) ? \Drupal::config('uc_store.settings')->get('units.weight') : $units;
      }
    }
    return parent::save($product);
  }

  /**
    * {@inheritdoc}
    */
  public function getSchema() {
    $schema = parent::getSchema();

    // @todo Create a numeric field type and use that instead.
    $schema['uc_order_products']['fields']['cost']['type'] = 'numeric';
    $schema['uc_order_products']['fields']['cost']['precision'] = 16;
    $schema['uc_order_products']['fields']['cost']['scale'] = 5;
    // @todo Create a numeric field type and use that instead.
    $schema['uc_order_products']['fields']['price']['type'] = 'numeric';
    $schema['uc_order_products']['fields']['price']['precision'] = 16;
    $schema['uc_order_products']['fields']['price']['scale'] = 5;

    // Marking the respective fields as NOT NULL makes the indexes more
    // performant.
    $schema['uc_order_products']['fields']['order_id']['not null'] = TRUE;
    $schema['uc_order_products']['fields']['qty']['not null'] = TRUE;
    $schema['uc_order_products']['fields']['nid']['not null'] = TRUE;

    $schema['uc_order_products']['indexes'] += array(
      'order_id' => array('order_id'),
      'qty' => array('qty'),
      'nid' => array('nid'),
    );
    $schema['uc_order_products']['foreign keys'] += array(
      'uc_orders' => array(
        'table' => 'uc_orders',
        'columns' => array('order_id' => 'order_id'),
      ),
      'node' => array(
        'table' => 'node',
        'columns' => array('nid' => 'nid'),
      ),
    );
    return $schema;
  }

}