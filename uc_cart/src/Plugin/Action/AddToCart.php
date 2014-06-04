<?php

/**
 * @file
 * Contains \Drupal\uc_cart\Plugin\Action\AddToCart.
 */

namespace Drupal\uc_cart\Plugin\Action;

use Drupal\Core\Action\ActionBase;

/**
 * Provides an action that can add a product to the cart.
 *
 * @Action(
 *   id = "uc_cart_add_product_action",
 *   label = @Translation("Add to cart"),
 *   type = "node"
 * )
 */
class AddToCart extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    uc_cart_add_item($entity->id(), 1, NULL, NULL, TRUE, FALSE, TRUE);
  }

}
