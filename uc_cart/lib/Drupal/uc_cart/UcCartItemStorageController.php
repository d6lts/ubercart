<?php

/**
 * @file
 * Definition of Drupal\uc_cart\UcCartItemStorageController.
 */

namespace Drupal\uc_cart;

use Drupal\Core\Entity\DatabaseStorageController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Controller class for cart items.
 */
class UcCartItemStorageController extends DatabaseStorageController {

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::attachLoad().
   */
  protected function attachLoad(&$items, $load_revision = FALSE) {
    foreach ($items as &$item) {
      $item->data = unserialize($item->data);
      $item->product = uc_product_load_variant($item->nid, $item->data);
      if ($item->product) {
        $item->title = $item->product->title;
        $item->model = $item->product->model;
        $item->cost = $item->product->cost;
        $item->price = $item->product->price;
        $item->weight = $item->product->weight;
        $item->weight_units = $item->product->weight_units;
      }

      $item->module = $item->data['module'];
    }
    parent::attachLoad($items, $load_revision);
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::save().
   *
   * Cart items are deleted if saved with a quantity of zero.
   */
  public function save(EntityInterface $entity) {
    if ($entity->qty < 1) {
      if (isset($entity->cart_item_id)) {
        parent::delete(array($entity->cart_item_id => $entity));
      }
    }
    else {
      $entity->changed = REQUEST_TIME;
      parent::save($entity);
    }
  }

}
