<?php

/**
 * @file
 * Contains \Drupal\uc_cart\UcCartItemStorageController.
 */

namespace Drupal\uc_cart;

use Drupal\Core\Entity\FieldableDatabaseStorageController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Controller class for cart items.
 */
class UcCartItemStorageController extends FieldableDatabaseStorageController {

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::save().
   *
   * Cart items are deleted if saved with a quantity of zero.
   */
  public function save(EntityInterface $entity) {
    if ($entity->qty->value < 1) {
      if (isset($entity->cart_item_id->value)) {
        parent::delete(array($entity->cart_item_id->value => $entity));
      }
    }
    else {
      $entity->changed = REQUEST_TIME;
      parent::save($entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function mapToStorageRecord(EntityInterface $entity, $table_key = 'base_table') {
    $record = parent::mapToStorageRecord($entity, $table_key);
    $record->data = $entity->data;
    return $record;
  }

}
