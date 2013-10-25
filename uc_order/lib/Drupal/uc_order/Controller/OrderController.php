<?php

/**
 * @file
 * Contains \Drupal\uc_order\Controller\OrderController.
 */

namespace Drupal\uc_order\Controller;

use Drupal\Component\Utility\String;
use Drupal\Core\Controller\ControllerBase;
use Drupal\uc_order\UcOrderInterface;

/**
 * Controller routines for order routes.
 */
class OrderController extends ControllerBase {

  /**
   * Displays an order invoice.
   */
  function invoice(UcOrderInterface $uc_order, $print = FALSE) {
    $build = array(
      '#theme' => 'uc_order_invoice',
      '#order' => $uc_order,
      '#op' => $print ? 'print' : 'view',
    );

    if ($print) {
      drupal_add_http_header('Content-Type', 'text/html; charset=utf-8');
      print theme('uc_order_invoice_page', array('content' => drupal_render($build)));
      exit();
    }

    return $build;
  }

  /**
   * The title callback for the UcOrder view routes.
   *
   * @param \Drupal\uc_order\UcOrderInterface $uc_order
   */
  public function pageTitle(UcOrderInterface $uc_order) {
    return String::checkPlain($uc_order->label());
  }

}
