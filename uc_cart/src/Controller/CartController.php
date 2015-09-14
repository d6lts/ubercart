<?php

/**
 * @file
 * Contains \Drupal\uc_cart\Controller\CartController.
 */

namespace Drupal\uc_cart\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for the shopping cart.
 */
class CartController extends ControllerBase {

  /**
   * Displays the cart view page.
   *
   * Show the products in the cart with a form to adjust cart contents or go to
   * checkout.
   */
  public function listing() {
    // Load the array of shopping cart items.
    $cart = Cart::create(\Drupal::getContainer());
    $items = $cart->getContents();

    // Display the empty cart page if there are no items in the cart.
    if (empty($items)) {
      $build = [
        '#theme' => 'uc_cart_empty',
      ];

      \Drupal::service('renderer')->addCacheableDependency($build, $cart);

      return $build;
    }

    return $this->formBuilder()->getForm('Drupal\uc_cart\Form\CartForm', $cart);
  }

}
