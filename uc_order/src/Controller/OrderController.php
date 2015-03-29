<?php

/**
 * @file
 * Contains \Drupal\uc_order\Controller\OrderController.
 */

namespace Drupal\uc_order\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\uc_order\OrderInterface;

/**
 * Controller routines for order routes.
 */
class OrderController extends ControllerBase {

  /**
   * Displays an order invoice.
   */
  public function invoice(OrderInterface $uc_order, $print = FALSE) {
    $build = array(
      '#theme' => 'uc_order_invoice',
      '#order' => $uc_order,
      '#op' => $print ? 'print' : 'view',
    );

    if ($print) {
      //@todo fix this
      //drupal_add_http_header('Content-Type', 'text/html; charset=utf-8');
      //print drupal_render(array('#theme' => 'uc_order_invoice_page', 'content' => $build));
      exit();
    }

    return $build;
  }

  /**
   * Displays a log of changes made to an order.
   */
  public function log(OrderInterface $uc_order) {
    $result = db_query("SELECT * FROM {uc_order_log} WHERE order_id = :id ORDER BY created, order_log_id", [':id' => $uc_order->id()]);

    $header = array(t('Time'), t('User'), t('Changes'));
    $rows = array();
    foreach ($result as $change) {
      $rows[] = array(
        \Drupal::service('date.formatter')->format($change->created, 'short'),
        array('#theme' => 'uc_uid',  '#uid' => $change->uid),
        $change->changes,
      );
    }

    $build['log'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No changes have been logged for this order.'),
    );

    return $build;
  }

  /**
   * The title callback for the Order view routes.
   *
   * @param \Drupal\uc_order\OrderInterface $uc_order
   */
  public function pageTitle(OrderInterface $uc_order) {
    return SafeMarkup::checkPlain($uc_order->label());
  }

}
