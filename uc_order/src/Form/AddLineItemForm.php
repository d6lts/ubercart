<?php

/**
 * @file
 * Contains \Drupal\uc_order\Form\AddLineItemForm.
 */

namespace Drupal\uc_order\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;

/**
 * Form to add a line item to an order.
 */
class AddLineItemForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_order_add_line_item_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $order = NULL, $line_item_id = '') {
    $func = _uc_line_item_data($line_item_id, 'callback');

    if (!function_exists($func) || ($form = $func('form', $order->id())) == NULL) {
      $form['title'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Line item title'),
        '#description' => $this->t('Display title of the line item.'),
        '#size' => 32,
        '#maxlength' => 128,
        '#default_value' => _uc_line_item_data($line_item_id, 'title'),
      );
      $form['amount'] = array(
        '#type' => 'uc_price',
        '#title' => $this->t('Line item amount'),
        '#allow_negative' => TRUE,
      );
    }

    $form['order_id'] = array(
      '#type' => 'hidden',
      '#value' => $order->id(),
    );
    $form['line_item_id'] = array(
      '#type' => 'hidden',
      '#value' => $line_item_id,
    );
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add line item'),
      '#suffix' => Link::createFromRoute($this->t('Cancel'), 'entity.uc_order.edit_form', ['uc_order' => $order->id()])->toString(),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $func = _uc_line_item_data($form_state->getValue('line_item_id'), 'callback');
    if (function_exists($func) && ($form = $func('form', $form_state->getValue('order_id'))) != NULL) {
      $func('validate', $form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $func = _uc_line_item_data($form_state->getValue('line_item_id'), 'callback');
    if (function_exists($func) && ($form = $func('form', $form_state->getValue('order_id'))) != NULL) {
      $func('submit', $form, $form_state);
    }
    else {
      uc_order_line_item_add($form_state->getValue('order_id'), $form_state->getValue('line_item_id'), $form_state->getValue('title'), $form_state->getValue('amount'));
      drupal_set_message($this->t('Line item added to order.'));
    }

    $form_state->setRedirect('entity.uc_order.edit_form', ['uc_order' => $form_state->getValue('order_id')]);
  }

}
