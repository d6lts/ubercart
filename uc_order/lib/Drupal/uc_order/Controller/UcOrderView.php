<?php

/**
 * @file
 * Contains \Drupal\uc_order\Controller\UcOrderView.
 */

namespace Drupal\uc_order\Controller;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\EntityInterface;
use Drupal\uc_order\UcOrderInterface;

/**
 * Returns responses for UcOrder routes.
 */
class UcOrderView {

  /**
   * The title callback for the UcOrder view routes.
   *
   * @param \Drupal\uc_order\UcOrderInterface $uc_order
   */
  public function pageTitle(UcOrderInterface $uc_order) {
    return String::checkPlain($uc_order->label());
  }

}
