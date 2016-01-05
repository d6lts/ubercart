<?php

/**
 * @file
 * PayPal administration menu items.
 */

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\uc_order\Entity\Order;


/**
 * Handles the review page for Express Checkout Mark Flow.
 */
function uc_paypal_ec_review_redirect() {
  if (!isset($_SESSION['TOKEN']) || !($order = Order::load($_SESSION['cart_order']))) {
    unset($_SESSION['cart_order']);
    unset($_SESSION['have_details']);
    unset($_SESSION['TOKEN'], $_SESSION['PAYERID']);
    drupal_set_message(t('An error has occurred in your PayPal payment. Please review your cart and try again.'));
    drupal_goto('cart');
  }

  $nvp_request = array(
    'METHOD' => 'GetExpressCheckoutDetails',
    'TOKEN' => $_SESSION['TOKEN'],
  );

  $nvp_response = uc_paypal_api_request($nvp_request, variable_get('uc_paypal_wpp_server', 'https://api-3t.sandbox.paypal.com/nvp'));

  $_SESSION['PAYERID'] = $nvp_response['PAYERID'];

  drupal_goto('cart/checkout/review');
}

/**
 * Returns the form for the custom Review Payment screen for Express Checkout.
 */
function uc_paypal_ec_review_form($form, FormStateInterface $form_state, $order) {
  if (\Drupal::moduleHandler()->moduleExists('uc_quote') && variable_get('uc_paypal_ec_review_shipping', TRUE) && $order->isShippable()) {
    uc_checkout_pane_quotes('prepare', $order, NULL);
    $order->line_items = $order->getLineItems();
    $order->save();

    $result = uc_checkout_pane_quotes('view', $order, NULL);
    $form['panes']['quotes'] = array(
      '#type' => 'fieldset',
      '#title' => t('Shipping cost'),
    );
    $form['panes']['quotes'] += $result['contents'];
    unset($form['panes']['quotes']['quote_button']);

    $form['shippable'] = array('#type' => 'value', '#value' => 'true');
  }

  if (variable_get('uc_paypal_ec_review_company', TRUE)) {
    $form['delivery_company'] = array(
      '#type' => 'textfield',
      '#title' => t('Company'),
      '#description' => $order->isShippable() ? t('Leave blank if shipping to a residence.') : '',
      '#default_value' => $order->delivery_company,
    );
  }

  if (variable_get('uc_paypal_ec_review_phone', TRUE)) {
    $form['delivery_phone'] = array(
      '#type' => 'textfield',
      '#title' => t('Contact phone number'),
      '#default_value' => $order->delivery_phone,
      '#size' => 24,
    );
  }

  if (variable_get('uc_paypal_ec_review_comment', TRUE)) {
    $form['order_comments'] = array(
      '#type' => 'textarea',
      '#title' => t('Order comments'),
      '#description' => t('Special instructions or notes regarding your order.'),
    );
  }

  if (empty($form)) {
    drupal_goto('cart/echeckout/submit');
  }

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Continue checkout'),
  );

  return $form;
}

function uc_paypal_ec_review_form_validate($form, FormStateInterface $form_state) {
  if (!$form_state->isValueEmpty('shippable') && $form_state->isValueEmpty(['quotes', 'quote_option'])) {
    $form_state->setErrorByName('shipping', t('You must calculate and select a shipping option.'));
  }
}

function uc_paypal_ec_review_form_submit($form, FormStateInterface $form_state) {
  $order = Order::load($_SESSION['cart_order']);

  if (!$form_state->isValueEmpty('shippable')) {
    $quote_option = explode('---', $form_state->getValue(['quotes', 'quote_option']));
    $order->quote['method'] = $quote_option[0];
    $order->quote['accessorials'] = $quote_option[1];
    $methods = uc_quote_methods();
    $method = $methods[$quote_option[0]];

    $label = $method['quote']['accessorials'][$quote_option[1]];

    $quote_option = $form_state->getValue(['quotes', 'quote_option']);
    $order->quote['rate'] = $form_state->getValue(['quotes', $quote_option, 'rate']);

    $result = db_query("SELECT line_item_id FROM {uc_order_line_items} WHERE order_id = :id AND type = :type", [':id' => $order->id(), ':type' => 'shipping']);
    if ($lid = $result->fetchField()) {
      uc_order_update_line_item($lid, $label, $order->quote['rate']);
    }
    else {
      uc_order_line_item_add($order->id(), 'shipping', $label, $order->quote['rate']);
    }
  }

  if (variable_get('uc_paypal_ec_review_company', TRUE)) {
    $order->delivery_company = $form_state->getValue('delivery_company');
  }

  if (variable_get('uc_paypal_ec_review_phone', TRUE)) {
    $order->delivery_phone = $form_state->getValue('delivery_phone');
  }

  if (variable_get('uc_paypal_ec_review_comment', TRUE)) {
    db_delete('uc_order_comments')
      ->condition('order_id', $order->id())
      ->execute();
    uc_order_comment_save($order->id(), 0, $form_state->getValue('order_comments'), 'order');
  }

  $order->save();

  $form_state['redirect'] = 'cart/echeckout/submit';
}

/**
 * Presents the final total to the user for checkout!
 */
function uc_paypal_ec_submit() {
  if (!isset($_SESSION['TOKEN']) || !($order = Order::load($_SESSION['cart_order']))) {
    unset($_SESSION['cart_order'], $_SESSION['have_details']);
    unset($_SESSION['TOKEN'], $_SESSION['PAYERID']);
    drupal_set_message(t('An error has occurred in your PayPal payment. Please review your cart and try again.'));
    drupal_goto('cart');
  }

  $build['#attached']['library'][] = 'uc_cart/uc_cart.styles';

  $build['review'] = array(
    '#theme' => 'uc_cart_review_table',
    '#items' => $order->products,
    '#show_subtotal' => FALSE,
  );

  $build['line_items'] = uc_order_pane_line_items('customer', $order);

  $build['instructions'] = array('#markup' => '<p>' . t("Your order is not complete until you click the 'Submit order' button below. Your PayPal account will be charged for the amount shown above once your order is placed. You will receive confirmation once your payment is complete.") . '</p>');

  $build['submit_form'] = \Drupal::formBuilder()->getForm('uc_paypal_ec_submit_form');

  return $build;
}

/**
 * Submits an order, calling the NVP API to send the order total to PayPal.
 */
function uc_paypal_ec_submit_form($form, FormStateInterface $form_state) {
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit order'),
  );

  return $form;
}

/**
 * Handles a complete Website Payments Standard sale.
 */
function uc_paypal_complete($order) {
  // If the order ID specified in the return URL is not the same as the one in
  // the user's session, we need to assume this is either a spoof or that the
  // user tried to adjust the order on this side while at PayPal. If it was a
  // legitimate checkout, the IPN will still come in from PayPal so the order
  // gets processed correctly. We'll leave an ambiguous message just in case.
  if (!isset($_SESSION['cart_order']) || intval($_SESSION['cart_order']) != $order->id()) {
    drupal_set_message(t('Thank you for your order! PayPal will notify us once your payment has been processed.'));
    drupal_goto('cart');
  }

  // Ensure the payment method is PayPal WPS.
  if ($order->getPaymentMethodId() != 'paypal_wps') {
    drupal_goto('cart');
  }

  // This lets us know it's a legitimate access of the complete page.
  $_SESSION['uc_checkout'][$_SESSION['cart_order']]['do_complete'] = TRUE;
  drupal_goto('cart/checkout/complete');
}

/**
 * Handles a canceled Website Payments Standard sale.
 */
function uc_paypal_cancel() {
  unset($_SESSION['cart_order']);

  drupal_set_message(t('Your PayPal payment was canceled. Please feel free to continue shopping or contact us for assistance.'));

  drupal_goto(variable_get('uc_paypal_wps_cancel_return_url', 'cart'));
}
