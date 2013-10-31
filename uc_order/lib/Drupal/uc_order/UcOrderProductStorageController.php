<?php

/**
 * @file
 * Definition of Drupal\uc_order\UcOrderProductStorageController.
 */

namespace Drupal\uc_order;

use Drupal\Core\Entity\FieldableDatabaseStorageController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Controller class for ordered products.
 */
class UcOrderProductStorageController extends FieldableDatabaseStorageController {

  /**
   * {@inheritdoc}
   */
  protected function attachLoad(&$queried_entities, $load_revision = FALSE) {
    parent::attachLoad($queried_entities, $load_revision);

    foreach ($queried_entities as $id => $product) {
      $product->data = unserialize($product->data);
    }
  }

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
        $product->weight_units->value = config('uc_store.settings')->get('units.weight');
      }
      else {
        $units = db_query("SELECT weight_units FROM {node} n JOIN {uc_products} p ON n.vid = p.vid WHERE n.nid = :nid", array(':nid' => $product->nid->value))->fetchField();
        $product->weight_units->value = empty($units) ? config('uc_store.settings')->get('units.weight') : $units;
      }
    }
    return parent::save($product);
  }

  /**
   * {@inheritdoc}
   */
  protected function mapToStorageRecord(EntityInterface $entity, $table_key = 'base_table') {
    $record = new \stdClass();
    foreach (drupal_schema_fields_sql($this->entityInfo[$table_key]) as $name) {
      if ($name == 'data') {
        $record->$name = $entity->$name;
      }
      else {
        $record->$name = $entity->$name->value;
      }
    }
    return $record;
  }

}
