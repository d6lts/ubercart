<?php

/**
 * @file
 * Contains \Drupal\uc_cart\CartManagerInterface.
 */

namespace Drupal\uc_cart;

/**
 * Defines a common interface for cart managers.
 */
interface CartManagerInterface {

  /**
   * Loads a cart object.
   *
   * @param string|NULL $id
   *   (optional) The ID of the cart to load, or NULL to load the current cart.
   *
   * @return CartInterface
   *   An object representing the cart.
   */
  public function get($id = NULL);

  /**
   * Empties a cart.
   *
   * @param int $id
   *   The ID of the cart to empty.
   */
  public function emptyCart($id);

  /**
   * Completes a sale, including adjusting order status and creating user account.
   *
   * @param \Drupal\uc_order\Entity\Order $order
   *   The order entity that has just been completed.
   * @param bool $login
   *   Whether or not to login a new user when this function is called.
   *
   * @return
   *   The HTML text of the default order completion page.
   */
  public function completeSale($order, $login = FALSE);

}
