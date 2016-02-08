<?php

/**
 * @file
 * Contains \Drupal\uc_fulfillment\Plugin\Ubercart\OrderPane\Tracking.
 */

namespace Drupal\uc_fulfillment\Plugin\Ubercart\OrderPane;

use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderPanePluginBase;
use Drupal\uc_fulfillment\Shipment;
use Drupal\uc_order\OrderInterface;

/**
 * Display tracking numbers of shipped packages.
 *
 * @UbercartOrderPane(
 *   id = "tracking",
 *   title = @Translation("Tracking numbers"),
 *   weight = 7,
 * )
 */
class Tracking extends OrderPanePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getClasses() {
    return 'pos-left';
  }

  /**
   * {@inheritdoc}
   */
  public function view(OrderInterface $order, $view_mode) {
    if ($view_mode == 'customer' || $view_mode == 'view') {
      $tracking = array();
      $result = db_query('SELECT sid FROM {uc_shipments} WHERE order_id = :id', [':id' => $order->id()]);
      foreach ($result as $shipment) {
        $shipment = Shipment::load($shipment->sid);
        if ($shipment->tracking_number) {
          $tracking[$shipment->carrier]['data'] = $shipment->carrier;
          $tracking[$shipment->carrier]['children'][] = $shipment->tracking_number;
        }
        else {
          foreach ($shipment->packages as $package) {
            if ($package->tracking_number) {
              $tracking[$shipment->carrier]['data'] = $shipment->carrier;
              $tracking[$shipment->carrier]['children'][] = $package->tracking_number;
            }
          }
        }
      }

      // Do not show an empty pane to customers.
      if ($view_mode == 'view' || !empty($tracking)) {
        $build['tracking'] = array(
          '#theme' => 'item_list',
          '#items' => $tracking,  // @todo #plain_text ?
        );

        return $build;
      }
    }
  }

}
