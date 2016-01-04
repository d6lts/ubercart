<?php

/**
 * @file
 * Contains \Drupal\uc_cybersource\Controller\TaxController.
 */

namespace Drupal\uc_cybersource\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for HOP postback.
 */
class TaxController extends ControllerBase {

  /**
   * Displays the taxes for an order.
   */
  public function calculate($uc_order) {
    // Fetch the taxes for the order.
    $data = uc_cybersource_uc_calculate_tax($uc_order);

    // Build an item list for the taxes.
    $items = array();
    foreach ($data as $tax) {
      $items[] = $this->t('@tax: @amount', ['@tax' => $tax['name'], '@amount' => uc_currency_format($tax['amount'])]);
    }

    // Display a message if there are no taxes.
    if (empty($items)) {
      $items[] = $this->t('No taxes returned for this order.');
    }

    return array(
      '#theme' => 'item_list',
      '#items' => $items,
    );
  }
}
