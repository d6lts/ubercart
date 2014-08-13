<?php

/**
 * @file
 * Contains \Drupal\uc_payment_pack\Form\ReceiveCheckForm.
 */

namespace Drupal\uc_payment_pack\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\UcOrderInterface;

/**
 * Form for recording received checks.
 */
class ReceiveCheckForm extends FormBase {

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'uc_payment_pack_receive_check_form';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   *
   * Receives a check for an order and put in a clear date.
   */
  public function buildForm(array $form, FormStateInterface $form_state, UcOrderInterface $order = NULL) {
    $balance = uc_payment_balance($order);
    $form['balance'] = array(
      '#prefix' => '<strong>' . t('Order balance:') . '</strong> ',
      '#markup' => uc_currency_format($balance),
    );
    $form['order_id'] = array(
      '#type' => 'hidden',
      '#value' => $order->id(),
    );
    $form['amount'] = array(
      '#type' => 'uc_price',
      '#title' => t('Amount'),
      '#default_value' => $balance,
    );
    $form['comment'] = array(
      '#type' => 'textfield',
      '#title' => t('Comment'),
      '#description' => t('Any notes about the check, like type or check number.'),
      '#size' => 64,
      '#maxlength' => 256,
    );
    $form['clear'] = array(
      '#type' => 'fieldset',
      '#title' => t('Expected clear date'),
      '#attributes' => array('class' => array('uc-inline-form', 'clearfix')),
    );
    $form['clear']['clear_month'] = uc_select_month(NULL, format_date(REQUEST_TIME, 'custom', 'n'));
    $form['clear']['clear_day'] = uc_select_day(NULL, format_date(REQUEST_TIME, 'custom', 'j'));
    $form['clear']['clear_year'] = uc_select_year(NULL, format_date(REQUEST_TIME, 'custom', 'Y'), format_date(REQUEST_TIME, 'custom', 'Y'), format_date(REQUEST_TIME, 'custom', 'Y') + 1);

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Receive check'),
    );

    return $form;
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    uc_payment_enter($form_state['values']['order_id'], 'check', $form_state['values']['amount'], \Drupal::currentUser()->id(), '', $form_state['values']['comment']);

    db_insert('uc_payment_check')
      ->fields(array(
        'order_id' => $form_state['values']['order_id'],
        'clear_date' => mktime(12, 0, 0, $form_state['values']['clear_month'], $form_state['values']['clear_day'], $form_state['values']['clear_year']),
      ))
      ->execute();

    drupal_set_message(t('Check received, expected clear date of @date.', array('@date' => uc_date_format($form_state['values']['clear_month'], $form_state['values']['clear_day'], $form_state['values']['clear_year']))));

    $form_state->setRedirect('uc_order.admin_view', array('uc_order' => $form_state->getValue('order_id')));
  }
}
