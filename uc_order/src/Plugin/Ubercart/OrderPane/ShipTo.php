<?php

/**
 * @file
 * Contains \Drupal\uc_order\Plugin\Ubercart\OrderPane\ShipTo.
 */

namespace Drupal\uc_order\Plugin\Ubercart\OrderPane;

use Drupal\uc_order\OrderInterface;

/**
 * Manage the order's shipping address and contact information.
 *
 * @UbercartOrderPane(
 *   id = "delivery",
 *   title = @Translation("Ship to"),
 *   weight = 1,
 * )
 */
class ShipTo extends AddressPaneBase {

  /**
   * {@inheritdoc}
   */
  public function view(OrderInterface $order, $view_mode) {
    if ($view_mode != 'customer' || $order->isShippable()) {
      return parent::view($order, $view_mode);
    }
  }

}
