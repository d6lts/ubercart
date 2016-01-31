<?php

/**
 * @file
 * Contains \Drupal\uc_fulfillment\Controller\FulfillmentController.
 */

namespace Drupal\uc_fulfillment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\uc_order\OrderInterface;

/**
 * Controller routines for order routes.
 */
class FulfillmentController extends ControllerBase {

  /**
   * Checks access to the Shipments tab for this order.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The Order to check access for.
   */
  public function accessOrder(OrderInterface $uc_order) {
    $account = \Drupal::currentUser();
    return $account->hasPermission('fulfill orders') && $uc_order->isShippable();
  }

  /**
   * Checks access to the Shipments tab for this order.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The Order to check access for.
   */
  public function accessNewShipment(OrderInterface $uc_order) {
    return $this->accessOrder($uc_order) && db_query('SELECT COUNT(*) FROM {uc_packages} WHERE order_id = :id AND sid IS NULL', [':id' => $uc_order->id()])->fetchField();
  }

}

