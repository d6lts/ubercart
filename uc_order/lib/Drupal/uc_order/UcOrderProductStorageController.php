<?php

/**
 * @file
 * Definition of Drupal\uc_order\UcOrderProductStorageController.
 */

namespace Drupal\uc_order;

use Drupal\Core\Entity\DatabaseStorageController;

/**
 * Controller class for ordered products.
 */
class UcOrderProductStorageController extends DatabaseStorageController {

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::attachLoad().
   */
  protected function attachLoad(&$items, $load_revision = FALSE) {
    foreach ($items as &$item) {
      $item->data = unserialize($item->data);
    }

    parent::attachLoad($items, $load_revision);
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
