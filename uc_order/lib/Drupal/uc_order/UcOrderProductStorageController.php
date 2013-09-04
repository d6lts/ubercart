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

}
