<?php

/**
 * @file
 * Contains \Drupal\uc_cart\Controller\CartInterface.
 */

namespace Drupal\uc_cart\Controller;

/**
 * Handles all things concerning Ubercart's shopping cart.
 *
 * The Ubercart cart system functions much like the e-commerce cart at its base
 * level... in fact, most carts do.  This module handles the cart display,
 * adding items to a cart, and checking out.  The module enables the cart,
 * products, and checkout to be extensible.
 */
interface CartInterface {

  /**
   * Completes a sale, including adjusting order status and creating user account.
   *
   * @param \Drupal\uc_order\Entity\Order $order
   *   The order entity that has just been completed.
   * @param $login
   *   Whether or not to login a new user when this function is called.
   *
   * @return
   *   The HTML text of the default order completion page.
   */
  public function completeSale($order, $login);

  /**
   * Link a completed sale to a user.
   *
   * @param \Drupal\uc_order\Entity\Order $order
   *   The order entity that has just been completed.
   */
  public function completeSaleAccount($order);

  /**
   * Returns the unique cart ID for the current user.
   *
   * @param $create
   *   If TRUE, a cart ID will be generated if none is set.
   *
   * @return
   *   The cart ID. If $create is FALSE, returns FALSE if no cart exists.
   */
  public function getId($create);

  /**
   * Grabs the items in a shopping cart for a user.
   *
   * @param $cid
   *   (optional) The cart ID to load, or NULL to load the current user's cart.
   * @param $action
   *   (optional) Carts are statically cached by default. If set to "rebuild",
   *   the cache will be ignored and the cart reloaded from the database.
   *
   * @return
   *   An array of cart items.
   */
  public function getContents($cid, $action);

  /**
   * Adds an item to a user's cart.
   *
   * @param int $nid
   *   Node ID to add to cart.
   * @param int $qty
   *   Quantity to add to cart.
   * @param array $data
   *   Array of module-specific data to add to cart.
   * @param int $cid
   *   ID of user's cart.
   * @param string $msg
   *   Message to display upon adding an item to the cart.
   * @param bool $check_redirect
   *   TRUE to return a redirect URL.
   * @param bool $rebuild
   *   TRUE to rebuild the cart item cache after adding an item.
   *
   * @return null|\Drupal\Core\Url
   *   If $check_redirect is TRUE, a Url to redirect to. Otherwise null.
   */
  public function addItem($nid, $qty, $data, $cid, $msg, $check_redirect, $rebuild);

  /**
   * Computes the destination Url for an add-to-cart action.
   *
   * Redirect Url is chosen in the following order:
   *  - Query parameter "destination"
   *  - Cart config variable "uc_cart.settings.add_item_redirect"
   *
   * @return \Drupal\Core\Url
   *   A Url destination for redirection.
   */
  public function getAddItemRedirect();

  /**
   * Empties a cart of its contents.
   *
   * @param $cart_id
   *   The ID of the cart, or NULL to empty the current cart.
   */
  public function emptyCart($cart_id);

  /**
   * Determines whether a cart contains shippable items or not.
   *
   * @param integer $cart_id
   *   The ID of the cart.
   *
   * @return bool
   */
  public function isShippable($cart_id);

}
