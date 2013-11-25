<?php

/**
 * @file
 * Contains \Drupal\uc_payment\Form\OrderPaymentsForm.
 */

namespace Drupal\uc_payment\Form;

use Drupal\Core\Form\FormBase;
use Drupal\uc_order\UcOrderInterface;

/**
 * Displays a list of payments attached to an order.
 */
class OrderPaymentsForm extends FormBase {

  /**
   * The order that is being viewed.
   */
  protected $order;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_payment_by_order_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, UcOrderInterface $uc_order = NULL) {
    $this->order = $uc_order;

    $form['#attached']['css'][] = drupal_get_path('module', 'uc_payment') . '/uc_payment.css';

    $total = $this->order->getTotal();
    $payments = uc_payment_load_payments($this->order->id());

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
          $action_value = l(t('Delete'), 'admin/store/orders/' . $this->order->id() . '/payments/'
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
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    if (!is_numeric($form_state['values']['payments']['new']['amount'])) {
      form_set_error('payments][new][amount', $form_state, t('You must enter a number for the amount.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    global $user;

    $payment = $form_state['values']['payments']['new'];
    $received = strtotime($payment['received']['year'] . '-' . $payment['received']['month'] . '-' . $payment['received']['day'] . ' 00:00:00');

    // If the value entered is today, use the exact timestamp instead
    $startofday = mktime(0, 0, 0);

    if ($received == $startofday) {
      $received = REQUEST_TIME;
    }

    uc_payment_enter($this->order->id(), $payment['method'], $payment['amount'], $user->id(), '', $payment['comment'], $received);

    drupal_set_message(t('Payment entered.'));
  }

}
