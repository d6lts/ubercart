<?php

/**
 * @file
 * Contains \Drupal\uc_cart\Plugin\Ubercart\CheckoutPane\CartPane.
 */

namespace Drupal\uc_cart\Plugin\Ubercart\CheckoutPane;

use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_cart\CheckoutPanePluginBase;
use Drupal\uc_order\UcOrderInterface;

/**
 * Displays the cart contents for review during checkout.
 *
 * @Plugin(
 *   id = "cart",
 *   title = @Translation("Cart contents"),
 *   weight = 1,
 * )
 */
class CartPane extends CheckoutPanePluginBase {

  /**
   * {@inheritdoc}
   */
  public function view(UcOrderInterface $order, array $form, FormStateInterface $form_state) {
    $build = array(
      '#theme' => 'uc_cart_review_table',
      '#items' => $order->products,
    );
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function review(UcOrderInterface $order) {
    $review[] = array(
      '#theme' => 'uc_cart_review_table',
      '#items' => $order->products,
      '#show_subtotal' => FALSE,
    );
    return $review;
  }

}
