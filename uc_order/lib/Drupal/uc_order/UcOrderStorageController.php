<?php

/**
 * @file
 * Contains \Drupal\uc_order\UcOrderStorageController.
 */

namespace Drupal\uc_order;

use Drupal\Core\Entity\FieldableDatabaseStorageController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Controller class for orders.
 */
class UcOrderStorageController extends FieldableDatabaseStorageController {

  /**
   * {@inheritdoc}
   */
  public function create(array $values) {
    $store_config = \Drupal::config('uc_store.settings');

    // Set the primary email address.
    if (empty($values['primary_email']) && !empty($values['uid'])) {
      if ($account = user_load($values['uid'])) {
        $values['primary_email'] = $account->mail;
      }
    }

    // Set the default order status.
    if (empty($values['order_status'])) {
      $values['order_status'] = uc_order_state_default('in_checkout');
    }

    // Set the default currency.
    if (empty($values['currency'])) {
      $values['currency'] = $store_config->get('currency.code');
    }

    // Set the default country codes.
    if (empty($values['billing_country'])) {
      $values['billing_country'] = $store_config->get('address.country');
    }
    if (empty($values['delivery_country'])) {
      $values['delivery_country'] = $store_config->get('address.country');
    }

    // Set the created time to now.
    if (empty($values['created'])) {
      $values['created'] = REQUEST_TIME;
    }

    return parent::create($values);
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
