<?php

/**
 * @file
 * Definition of Drupal\uc_order\UcOrderStorageController.
 */

namespace Drupal\uc_order;

use Drupal\Core\Entity\DatabaseStorageController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Controller class for orders.
 */
class UcOrderStorageController extends DatabaseStorageController {

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::create().
   */
  public function create(array $values) {
    $order = parent::create($values);

    // Set the primary email address.
    if (!empty($order->uid)) {
      if ($account = user_load($order->uid)) {
        $this->primary_email = $account->mail;
      }
    }

    // Set the default order status.
    if (empty($order->order_status)) {
      $order->order_status = uc_order_state_default('in_checkout');
    }

    // Set the default currency.
    if (empty($order->currency)) {
      $order->currency = variable_get('uc_currency_code', 'USD');
    }

    // Set the default country codes.
    if (empty($order->billing_country)) {
      $order->billing_country = variable_get('uc_store_country', 840);
    }
    if (empty($order->delivery_country)) {
      $order->delivery_country = variable_get('uc_store_country', 840);
    }

    // Set the created time to now.
    if (empty($order->created)) {
      $order->created = REQUEST_TIME;
    }

    return $order;
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::attachLoad().
   */
  protected function attachLoad(&$orders, $load_revision = FALSE) {
    foreach ($orders as &$order) {
      $order->data = unserialize($order->data);
      $result = entity_query('uc_order_product')
        ->condition('order_id', $order->order_id)
        ->sort('order_product_id', 'ASC')
        ->execute();
      if (!empty($result)) {
        $order->products = entity_load_multiple('uc_order_product', array_keys($result), TRUE);
        foreach ($order->products as $product) {
          $product->order = $order;
          $product->order_uid = $order->uid;
        }
      }
      else {
        $order->products = array();
      }

      uc_order_module_invoke('load', $order, NULL);

      // Load line items... has to be last after everything has been loaded.
      $order->line_items = uc_order_load_line_items($order);

      $fields = array();

      // Make sure the total still matches up...
      if (($total = uc_order_get_total($order)) !== $order->order_total) {
        $fields['order_total'] = $total;
        $order->order_total = $total;
      }

      if (($count = uc_order_get_product_count($order)) !== $order->product_count) {
        $fields['product_count'] = $count;
        $order->product_count = $count;
      }

      if (count($fields)) {
        $query = db_update('uc_orders')
          ->fields($fields)
          ->condition('order_id', $order->order_id)
          ->execute();
      }
    }

    parent::attachLoad($orders, $load_revision);
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::preSave().
   */
  protected function preSave(EntityInterface $order) {
    $order->order_total = uc_order_get_total($order);
    $order->product_count = uc_order_get_product_count($order);
    if (is_null($order->delivery_country) || $order->delivery_country == 0) {
      $order->delivery_country = config('uc_store.settings')->get('address.country');
    }
    if (is_null($order->billing_country) || $order->billing_country == 0) {
      $order->billing_country = config('uc_store.settings')->get('address.country');
    }
    $order->host = ip_address();
    $order->modified = REQUEST_TIME;

    uc_order_module_invoke('presave', $order, NULL);
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::postSave().
   */
  protected function postSave(EntityInterface $order, $update) {
    foreach ($order->products as $product) {
      drupal_alter('uc_order_product', $product, $order);
      uc_order_product_save($order->order_id, $product);
    }

    uc_order_module_invoke('save', $order, NULL);
    $order->order_total = uc_order_get_total($order);
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::preDelete().
   */
  protected function preDelete($orders) {
    foreach ($orders as $order_id => $order) {
      uc_order_module_invoke('delete', $order, NULL);
    }
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::postDelete().
   */
  protected function postDelete($orders) {
    // Delete data from the appropriate Ubercart order tables.
    $ids = array_keys($orders);
    $result = entity_query('uc_order_product')
      ->condition('order_id', $ids, 'IN')
      ->execute();
    if (!empty($result)) {
      $product_ids = array_keys($result);
      uc_order_product_delete_multiple($product_ids);
    }
    db_delete('uc_order_comments')
      ->condition('order_id', $ids, 'IN')
      ->execute();
    db_delete('uc_order_admin_comments')
      ->condition('order_id', $ids, 'IN')
      ->execute();
    db_delete('uc_order_log')
      ->condition('order_id', $ids, 'IN')
      ->execute();

    foreach ($orders as $order_id => $order) {
      // Delete line items for the order.
      uc_order_delete_line_item($order_id, TRUE);

      // Log the action in the database.
      watchdog('uc_order', 'Order @order_id deleted by user @uid.', array('@order_id' => $order_id, '@uid' => $GLOBALS['user']->uid));
    }
  }

}
