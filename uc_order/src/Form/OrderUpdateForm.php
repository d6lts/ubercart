<?php

/**
 * @file
 * Contains \Drupal\uc_order\Form\OrderUpdateForm.
 */

namespace Drupal\uc_order\Form;

use Drupal\Core\Form\FormBase;
use Drupal\uc_order\UcOrderInterface;

/**
 * Updates an order's status and optionally adds comments.
 */
class OrderUpdateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_order_view_update_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, UcOrderInterface $order = NULL) {
    $form['order_comment_field'] = array(
      '#type' => 'details',
      '#title' => t('Add an order comment'),
    );
    $form['order_comment_field']['order_comment'] = array(
      '#type' => 'textarea',
      '#description' => t('Order comments are used primarily to communicate with the customer.'),
    );

    $form['admin_comment_field'] = array(
      '#type' => 'details',
      '#title' => t('Add an admin comment'),
    );
    $form['admin_comment_field']['admin_comment'] = array(
      '#type' => 'textarea',
      '#description' => t('Admin comments are only seen by store administrators.'),
    );

    $form['current_status'] = array(
      '#type' => 'value',
      '#value' => $order->getStatusId(),
    );

    $form['order_id'] = array(
      '#type' => 'value',
      '#value' => $order->id(),
    );

    $form['controls'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('uc-inline-form')),
      '#weight' => 10,
    );
    $form['controls']['status'] = array(
      '#type' => 'select',
      '#title' => t('Order status'),
      '#default_value' => $order->getStatusId(),
      '#options' => uc_order_status_options_list(),
    );
    $form['controls']['notify'] = array(
      '#type' => 'checkbox',
      '#title' => t('Send e-mail notification on update.'),
    );

    $form['controls']['actions'] = array('#type' => 'actions');
    $form['controls']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Update'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $uid = \Drupal::currentUser()->id();

    if (!empty($form_state['values']['order_comment'])) {
      uc_order_comment_save($form_state['values']['order_id'], $uid, $form_state['values']['order_comment'], 'order', $form_state['values']['status'], $form_state['values']['notify']);
    }

    if (!empty($form_state['values']['admin_comment'])) {
      uc_order_comment_save($form_state['values']['order_id'], $uid, $form_state['values']['admin_comment']);
    }

    if ($form_state['values']['status'] != $form_state['values']['current_status']) {
      entity_load('uc_order', $form_state['values']['order_id'])
        ->setStatusId($form_state['values']['status'])
        ->save();

      if (empty($form_state['values']['order_comment'])) {
        uc_order_comment_save($form_state['values']['order_id'], $uid, '-', 'order', $form_state['values']['status'], $form_state['values']['notify']);
      }
    }

    // Let Rules send email if requested.
    // if ($form_state['values']['notify']) {
    //   $order = uc_order_load($form_state['values']['order_id']);
    //   rules_invoke_event('uc_order_status_email_update', $order);
    // }

    drupal_set_message(t('Order updated.'));
  }

}
