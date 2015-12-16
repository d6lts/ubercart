<?php

/**
 * @file
 * Hooks provided by the Payment module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Takes action when a payment is entered for an order.
 *
 * @param $order
 *   The order object.
 * @param $method
 *   The name of the payment method used.
 * @param $amount
 *   The value of the payment.
 * @param $account
 *   The user account that entered the order. When the payment is entered
 *   during checkout, this is probably the order's user. Otherwise, it is
 *   likely a store administrator.
 * @param $data
 *   Extra data associated with the transaction.
 * @param $comment
 *   Any comments from the user about the transaction.
 */
function hook_uc_payment_entered($order, $method, $amount, $account, $data, $comment) {
  drupal_set_message(t('User @uid entered a @method payment of @amount for order @order_id.',
    array(
      '@uid' => $account->id(),
      '@method' => $method,
      '@amount' => uc_currency_format($amount),
      '@order_id' => $order->id(),
    ))
  );
}

/**
 * Alter payment methods.
 *
 * @param $methods
 *   Array of payment methods plugins passed by reference.
 */
function hook_uc_payment_method_alter(&$methods) {
  // Change the title of the Check payment method.
  $methods['check']['name'] = t('Cheque');
}

/**
 * Alter payment methods available at checkout.
 *
 * @param $methods
 *   Array of payment methods passed by reference. Keys are payment method IDs,
 *   strings are payment method titles.
 * @param $order
 *   The order that is being checked out.
 */
function hook_uc_payment_method_checkout_alter(&$methods, $order) {
  // Remove the Check payment method for orders under $100.
  if ($order->getTotal() < 100) {
    unset($methods['check']);
  }
}

/**
 * @} End of "addtogroup hooks".
 */
