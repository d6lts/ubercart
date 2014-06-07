<?php

/**
 * @file
 * Contains \Drupal\uc_order\Plugin\Action\DeleteOrderAction.
 */

namespace Drupal\uc_order\Plugin\Action;

use Drupal\Core\Action\ActionBase;

/**
 * Deletes an order.
 *
 * @Action(
 *   id = "uc_order_order_delete_action",
 *   label = @Translation("Delete order"),
 *   type = "uc_order"
 * )
 */
class DeleteOrderAction extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($order = NULL) {
    $order->delete();
  }

}
