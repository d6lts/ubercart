<?php

/**
 * @file
 * Definition of Drupal\uc_order\UcOrderStorageController.
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
    $store_config = config('uc_store.settings');

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
   * Overrides Drupal\Core\Entity\DatabaseStorageController::attachLoad().
   */
  protected function attachLoad(&$queried_entities, $load_revision = FALSE) {
    $queried_entities = $this->mapFromStorageRecords($queried_entities, $load_revision);

    foreach ($queried_entities as $id => $order) {
      $order->data = unserialize($order->data);

      $order->products = entity_load_multiple_by_properties('uc_order_product', array('order_id' => $id));

      uc_order_module_invoke('load', $order, NULL);

      // Load line items... has to be last after everything has been loaded.
      $order->line_items = uc_order_load_line_items($order);

      $fields = array();

      if (($count = uc_order_get_product_count($order)) !== $order->product_count) {
        $fields['product_count'] = $count;
        $order->product_count = $count;
      }

      if (count($fields)) {
        $query = db_update('uc_orders')
          ->fields($fields)
          ->condition('order_id', $order->id())
          ->execute();
      }
    }

//    parent::attachLoad($queried_entities, $load_revision);
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
