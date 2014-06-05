<?php

/**
 * @file
 * Contains \Drupal\uc_order\UcOrderStorage.
 */

namespace Drupal\uc_order;

use Drupal\Core\Entity\ContentEntityDatabaseStorage;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Controller class for orders.
 */
class UcOrderStorage extends ContentEntityDatabaseStorage {

  /**
   * {@inheritdoc}
   */
  public function create(array $values = array()) {
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
  protected function mapToStorageRecord(ContentEntityInterface $entity, $table_name = NULL) {
    $record = parent::mapToStorageRecord($entity, $table_name);
    $record->data = $entity->data;
    return $record;
  }

  /**
    * {@inheritdoc}
    */
  public function getSchema() {
    $schema = parent::getSchema();
    // @todo Create a numeric field type and use that instead.
    $schema['uc_orders']['fields']['order_total']['type'] = 'numeric';
    $schema['uc_orders']['fields']['order_total']['precision'] = 16;
    $schema['uc_orders']['fields']['order_total']['scale'] = 5;

    // Marking the respective fields as NOT NULL makes the indexes more
    // performant.
    $schema['uc_orders']['fields']['uid']['not null'] = TRUE;
    $schema['uc_orders']['fields']['order_status']['not null'] = TRUE;

    $schema['uc_orders']['indexes'] += array(
      'uid' => array('uid'),
      'order_status' => array('order_status'),
    );
    $schema['uc_orders']['foreign keys'] += array(
      'users' => array(
        'table' => 'users',
        'columns' => array('uid' => 'uid'),
      ),
    );
    return $schema;
  }

}
