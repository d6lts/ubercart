<?php

/**
 * @file
 * Contains the callbacks for the payment order pane supplied with
 * Ubercart and their corresponding helper functions.
 *
 * Order panes are defined using hook_uc_order_pane() and use a callback to
 * handle the different processes involved in order viewing/editing. The
 * payment order pane is defined in uc_payment_uc_order_pane() in
 * uc_payment.module.
 */

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Handles the Payment order pane.
 */
function uc_order_pane_payment($op, $order, &$form = NULL, &$form_state = NULL) {
  switch ($op) {
    case 'view':
      $build['balance'] = array('#markup' => t('Balance: @balance', array('@balance' => uc_currency_format(uc_payment_balance($order)))));

      $account = \Drupal::currentUser();
      if ($account->hasPermission('view payments')) {
        $build['view_payments'] = array(
          '#markup' => ' (' . l(t('View'), 'admin/store/orders/' . $order->id() . '/payments') . ')',
        );
      }

      $method_name = _uc_payment_method_data($order->getPaymentMethod(), 'review');
      if (empty($method_name)) {
        $method_name = _uc_payment_method_data($order->getPaymentMethod(), 'name');
      }
      $build['method'] = array(
        '#markup' => t('Method: @payment_method', array('@payment_method' => $method_name)),
        '#prefix' => '<br />',
      );
      $func = _uc_payment_method_data($order->getPaymentMethod(), 'callback');
      if (function_exists($func)) {
        $method_output = $func('order-view', $order);
        if (!empty($method_output)) {
          $build['output'] = $method_output + array(
            '#prefix' => '<br />',
          );
        }
      }

      return $build;

    case 'customer':
      $method_name = _uc_payment_method_data($order->getPaymentMethod(), 'review');
      if (empty($method_name)) {
        $method_name = _uc_payment_method_data($order->getPaymentMethod(), 'name');
      }
      $build['method'] = array('#markup' => t('Method: @payment_method', array('@payment_method' => $method_name)));
      $func = _uc_payment_method_data($order->getPaymentMethod(), 'callback');
      if (function_exists($func)) {
        $method_output = $func('customer-view', $order);
        if (!empty($method_output)) {
          $build['output'] = $method_output + array(
            '#prefix' => '<br />',
          );
        }
      }

      return $build;

    case 'edit-form':
      $methods = _uc_payment_method_list();
      $options = array();
      foreach ($methods as $id => $method) {
        $options[$id] = $method['name'];
      }
      $form['payment']['payment_method'] = array(
        '#type' => 'select',
        '#title' => t('Payment method'),
        '#default_value' => $order->getPaymentMethod(),
        '#options' => !empty($options) ? $options : array(t('None available')),
        '#disabled' => empty($options),
        '#ajax' => array(
          'callback' => 'uc_payment_order_pane_ajax_callback',
          'progress' => array('type' => 'throbber'),
          'wrapper' => 'payment-details',
        ),
      );

      $form['payment']['payment_details'] = array(
        '#tree' => TRUE,
        '#prefix' => '<div id="payment-details">',
        '#suffix' => '</div>',
      );

      $method = isset($form_state['values']['payment_method']) ? $form_state['values']['payment_method'] : $order->getPaymentMethod();
      $func = _uc_payment_method_data($method, 'callback');
      if (function_exists($func) && $details = $func('order-details', $order)) {
        if (is_array($details)) {
          $form['payment']['payment_details'] += $details;
        }
        else {
          $form['payment']['payment_details']['#markup'] = $details;
        }
      }
      return $form;

    case 'edit-theme':
      return drupal_render($form['payment']);

    case 'edit-process':
      $changes['payment_method'] = $form_state['values']['payment_method'];
      $changes['payment_details'] = isset($form_state['values']['payment_details']) ? $form_state['values']['payment_details'] : array();
      $func = _uc_payment_method_data($form_state['values']['payment_method'], 'callback');
      if (function_exists($func) && ($return = $func('edit-process', $order, $form, $form_state)) != NULL && is_array($return)) {
        $changes['payment_details'] = array_merge($changes['payment_details'], $return);
      }

      if (!isset($order->payment_details)) {
        $order->payment_details = array();
      }
      return $changes;
  }
}

/**
 * AJAX callback to render the payment method pane.
 */
function uc_payment_order_pane_ajax_callback($form, &$form_state) {
  $response = new AjaxResponse();
  $response->addCommand(new ReplaceCommand('#payment-details', trim(drupal_render($form['payment']['payment_details']))));
  $response->addCommand(new PrependCommand('#payment-details', trim(theme('status_messages'))));

  return $response;
}
