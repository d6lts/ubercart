<?php

/**
 * @file
 * Contains \Drupal\uc_payment_pack\Form\ReceiveCheckForm.
 */

namespace Drupal\uc_payment_pack\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;

/**
 * Form for recording a received check and expected clearance date.
 */
class ReceiveCheckForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'uc_payment_pack_receive_check_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $uc_order = NULL) {
    $balance = uc_payment_balance($uc_order);
    $form['balance'] = array(
      '#prefix' => '<strong>' . $this->t('Order balance:') . '</strong> ',
      '#markup' => uc_currency_format($balance),
    );
    $form['order_id'] = array(
      '#type' => 'hidden',
      '#value' => $uc_order->id(),
    );
    $form['amount'] = array(
      '#type' => 'uc_price',
      '#title' => $this->t('Amount'),
      '#default_value' => $balance,
    );
    $form['comment'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Comment'),
      '#description' => $this->t('Any notes about the check, like type or check number.'),
      '#size' => 64,
      '#maxlength' => 256,
    );
    $form['clear'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Expected clear date'),
      '#attributes' => array('class' => array('uc-inline-form', 'clearfix')),
    );
    $form['clear']['clear_month'] = uc_select_month(NULL, \Drupal::service('date.formatter')->format(REQUEST_TIME, 'custom', 'n'));
    $form['clear']['clear_day'] = uc_select_day(NULL, \Drupal::service('date.formatter')->format(REQUEST_TIME, 'custom', 'j'));
    $form['clear']['clear_year'] = uc_select_year(NULL, \Drupal::service('date.formatter')->format(REQUEST_TIME, 'custom', 'Y'), \Drupal::service('date.formatter')->format(REQUEST_TIME, 'custom', 'Y'), \Drupal::service('date.formatter')->format(REQUEST_TIME, 'custom', 'Y') + 1);

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Receive check'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    uc_payment_enter($form_state->getValue('order_id'), 'check', $form_state->getValue('amount'), $this->currentUser()->id(), '', $form_state->getValue('comment'));

    db_insert('uc_payment_check')
      ->fields(array(
        'order_id' => $form_state->getValue('order_id'),
        'clear_date' => mktime(12, 0, 0, $form_state->getValue('clear_month'), $form_state->getValue('clear_day'), $form_state->getValue('clear_year')),
      ))
      ->execute();

    drupal_set_message($this->t('Check received, expected clear date of @date.', ['@date' => uc_date_format($form_state->getValue('clear_month'), $form_state->getValue('clear_day'), $form_state->getValue('clear_year'))]));

    $form_state->setRedirect('uc_order.admin_view', ['uc_order' => $form_state->getValue('order_id')]);
  }
}
