<?php

/**
 * @file
 * Contains \Drupal\uc_payment\Plugin\Ubercart\PaymentMethod\FreeOrder.
 */

namespace Drupal\uc_payment\Plugin\Ubercart\PaymentMethod;

use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\UcOrderInterface;
use Drupal\uc_payment\PaymentMethodPluginBase;

/**
 * Defines a free order payment method.
 *
 * @Plugin(
 *   id = "free_order",
 *   name = @Translation("Free order"),
 *   title = @Translation("No payment required"),
 *   checkout = TRUE,
 *   no_gateway = TRUE,
 *   configurable = FALSE,
 *   weight = 0,
 * )
 */
class FreeOrder extends PaymentMethodPluginBase {

  /**
   * {@inheritdoc}
   */
  public function cartDetails(UcOrderInterface $order, array $form, FormStateInterface $form_state) {
    return array(
      '#markup' => t('Continue with checkout to complete your order.'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function orderSubmit(UcOrderInterface $order) {
    if ($order->getTotal() >= 0.01) {
      return array(array(
        'pass' => FALSE,
        'message' => t('We cannot process your order without payment.'),
      ));
    }

    uc_payment_enter($order->id(), 'free_order', 0, 0, NULL, t('Checkout completed for a free order.'));
  }

}
