<?php

/**
 * @file
 * Contains \Drupal\uc_cart\UcCartItemStorage.
 */

namespace Drupal\uc_cart;

use Drupal\Core\Entity\ContentEntityDatabaseStorage;
use Drupal\Core\Entity\EntityInterface;

/**
 * Controller class for cart items.
 */
class UcCartItemStorage extends ContentEntityDatabaseStorage {

  /**
   * Overrides Drupal\Core\Entity\ContentEntityDatabaseStorage::save().
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
