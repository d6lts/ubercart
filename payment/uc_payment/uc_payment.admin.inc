<?php

/**
 * @file
 * Payment administration menu items.
 */


/**
 * Displays settings for a single payment method.
 */
function uc_payment_method_settings_form($form, &$form_state, $method_id) {
  $callback = _uc_payment_method_data($method_id, 'callback');
  $null = NULL;
  $form = $callback('settings', $null, array(), $form_state);
  return system_settings_form($form);
}

/**
 * Displays a list of payments attached to an order.
 *
 * @see uc_payment_by_order_form_validate()
 * @see uc_payment_by_order_form_submit()
 * @ingroup forms
 */
function uc_payment_by_order_form($form, &$form_state, $order) {
  $form['#attached']['css'][] = drupal_get_path('module', 'uc_payment') . '/uc_payment.css';

  $total = $order->order_total;
  $payments = uc_payment_load_payments($order->order_id);

  $form['order_total'] = array(
    '#type' => 'item',
    '#title' => t('Order total'),
    '#theme' => 'uc_price',
    '#price' => $total,
  );
  $form['payments'] = array(
    '#type' => 'table',
    '#tree' => TRUE,
    '#header' => array(t('Received'), t('User'), t('Method'), t('Amount'), t('Balance'), t('Comment'), t('Action')),
    '#weight' => 10,
  );

  if ($payments !== FALSE) {
    foreach ($payments as $payment) {
      $form['payments'][$payment->receipt_id]['received'] = array(
        '#markup' => format_date($payment->received, 'custom', variable_get('date_format_uc_store', 'm/d/Y') . '<b\r />H:i:s'),
      );
      $form['payments'][$payment->receipt_id]['user'] = array(
        '#markup' => theme('uc_uid', array('uid' => $payment->uid)),
      );
      $form['payments'][$payment->receipt_id]['method'] = array(
        '#markup' => ($payment->method == '') ? t('Unknown') : $payment->method,
      );
      $form['payments'][$payment->receipt_id]['amount'] = array(
        '#theme' => 'uc_price',
        '#price' => $payment->amount,
      );
      $total -= $payment->amount;
      $form['payments'][$payment->receipt_id]['balance'] = array(
        '#theme' => 'uc_price',
        '#price' => $total,
      );
      $form['payments'][$payment->receipt_id]['comment'] = array(
        '#markup' => ($payment->comment == '') ? '-' : filter_xss_admin($payment->comment),
      );
      if (user_access('delete payments')) {
        $action_value = l(t('Delete'), 'admin/store/orders/' . $order->order_id . '/payments/'
                          . $payment->receipt_id . '/delete');
      }
      else {
        $action_value = '-';
      }
      $form['payments'][$payment->receipt_id]['action'] = array(
        '#markup' => $action_value,
      );
    }
  }

  $form['balance'] = array(
    '#type' => 'item',
    '#title' => t('Current balance'),
    '#theme' => 'uc_price',
    '#price' => $total,
  );
  $form['order_id'] = array(
    '#type' => 'hidden',
    '#value' => $order->order_id,
  );

  if (user_access('manual payments')) {
    $form['payments']['new']['received'] = array(
      '#type' => 'date',
      '#default_value' => array(
        'month' => format_date(REQUEST_TIME, 'custom', 'n'),
        'day' => format_date(REQUEST_TIME, 'custom', 'j'),
        'year' => format_date(REQUEST_TIME, 'custom', 'Y'),
      ),
    );
    $form['payments']['new']['user'] = array(
      '#markup' => '-',
    );
    $methods = _uc_payment_method_list();
    foreach ($methods as $id => $method) {
      $options[$id] = $method['name'];
    }
    $form['payments']['new']['method'] = array(
      '#type' => 'select',
      '#title' => t('Method'),
      '#title_display' => 'invisible',
      '#options' => $options,
    );
    $form['payments']['new']['amount'] = array(
      '#type' => 'textfield',
      '#title' => t('Amount'),
      '#title_display' => 'invisible',
      '#size' => 6,
    );
    $form['payments']['new']['balance'] = array(
      '#markup' => '-',
    );
    $form['payments']['new']['comment'] = array(
      '#type' => 'textfield',
      '#title' => t('Comment'),
      '#title_display' => 'invisible',
      '#size' => 32,
      '#maxlength' => 256,
    );
    $form['payments']['new']['action'] = array('#type' => 'actions');
    $form['payments']['new']['action']['action'] = array(
      '#type' => 'submit',
      '#value' => t('Enter'),
    );
  }

  return $form;
}

/**
 * Form validation for uc_payment_by_order_form().
 *
 * @see uc_payment_by_order_form()
 * @see uc_payment_by_order_form_submit()
 */
function uc_payment_by_order_form_validate($form, &$form_state) {
  if (!is_numeric($form_state['values']['payments']['new']['amount'])) {
    form_set_error('payments][new][amount', t('You must enter a number for the amount.'));
  }
  return TRUE;
}

/**
 * Form submission handler for uc_payment_by_order_form().
 *
 * @see uc_payment_by_order_form()
 * @see uc_payment_by_order_form_validate()
 */
function uc_payment_by_order_form_submit($form, &$form_state) {
  global $user;

  $payment = $form_state['values']['payments']['new'];
  $received = strtotime($payment['received']['year'] . '-' . $payment['received']['month'] . '-' . $payment['received']['day'] . ' 00:00:00');

  // If the value entered is today, use the exact timestamp instead
  $startofday = mktime(0, 0, 0);

  if ($received == $startofday) {
    $received = REQUEST_TIME;
  }

  uc_payment_enter($form_state['values']['order_id'], $payment['method'], $payment['amount'], $user->uid, '', $payment['comment'], $received);

  drupal_set_message(t('Payment entered.'));
}

/**
 * Confirmation form to delete a payment from an order.
 *
 * @see uc_payment_delete_confirm_form_submit()
 * @ingroup forms
 */
function uc_payment_delete_confirm_form($form, &$form_state, $order, $payment) {
  // Make sure the payment is for the specified order.
  if ($payment->order_id != $order->order_id) {
    drupal_set_message(t('An error loading the payment information occurred.'));
    drupal_goto('admin/store/orders/' . $order->order_id . '/payments');
  }

  $desc = '<strong>' . t('Payment information:') . '</strong> '
        . t('@method payment of @amount received on @date.', array('@method' => $payment->method, '@amount' => uc_currency_format($payment->amount), '@date' => format_date($payment->received, 'short')));

  $form['order_id'] = array(
    '#type' => 'value',
    '#value' => $order->order_id
  );
  $form['receipt_id'] = array(
    '#type' => 'value',
    '#value' => $payment->receipt_id,
  );

  return confirm_form($form, t('Are you sure you want to delete this payment?'), 'admin/store/orders/' . $order->order_id . '/payments', $desc, t('Delete'));
}

/**
 * Form submission handler for uc_payment_delete_confirm_form().
 *
 * @see uc_payment_delete_confirm_form()
 */
function uc_payment_delete_confirm_form_submit($form, &$form_state) {
  uc_payment_delete($form_state['values']['receipt_id']);

  drupal_set_message(t('Payment deleted.'));

  $form_state['redirect'] = 'admin/store/orders/' . $form_state['values']['order_id'] . '/payments';
}
