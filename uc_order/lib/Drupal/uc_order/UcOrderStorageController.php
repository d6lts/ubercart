<?php

/**
 * @file
 * Definition of Drupal\uc_order\UcOrderStorageController.
 */

namespace Drupal\uc_order;

use Drupal\Core\Entity\DatabaseStorageController;

/**
 * Controller class for orders.
 */
class UcOrderStorageController extends DatabaseStorageController {

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::create().
   */
  public function create(array $values) {
    $order = parent::create($values);
    $store_config = config('uc_store.settings');

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
      $order->currency = $store_config->get('currency.code');
    }

    // Set the default country codes.
    if (empty($order->billing_country)) {
      $order->billing_country = $store_config->get('address.country');
    }
    if (empty($order->delivery_country)) {
      $order->delivery_country = $store_config->get('address.country');
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

      $order->products = entity_load_multiple_by_properties('uc_order_product', array('order_id' => $order->order_id));
      foreach ($order->products as $product) {
        $product->order = $order;
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

}
