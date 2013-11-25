<?php

/**
 * @file
 * Contains \Drupal\uc_order\Form\OrderStatusAddForm.
 */

namespace Drupal\uc_order\Form;

use Drupal\Core\Form\FormBase;

/**
 * Presents the form to create a custom order status.
 */
class OrderStatusAddForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_order_status_create_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['status_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Order status ID'),
      '#description' => t('Must be a unique ID with no spaces.'),
      '#size' => 32,
      '#maxlength' => 32,
      '#required' => TRUE,
    );

    $form['status_title'] = array(
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#description' => t('The order status title displayed to users.'),
      '#size' => 32,
      '#maxlength' => 48,
      '#required' => TRUE,
    );

    // Build the state option array for the order status table.
    $options = array();
    foreach (uc_order_state_list() as $state) {
      $options[$state['id']] = $state['title'];
    }
    $form['status_state'] = array(
      '#type' => 'select',
      '#title' => t('Order state'),
      '#description' => t('Set which order state this status is for.'),
      '#options' => $options,
      '#default_value' => 'post_checkout',
    );

    $form['status_weight'] = array(
      '#type' => 'weight',
      '#title' => t('List position'),
      '#delta' => 20,
      '#default_value' => 0,
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['create'] = array(
      '#type' => 'submit',
      '#value' => t('Create'),
    );
    $form['actions']['cancel'] = array(
      '#markup' => l(t('Cancel'), 'admin/store/settings/orders/workflow'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    $new_status = strtolower(trim($form_state['values']['status_id']));
    if (strpos($new_status, ' ') !== FALSE || $new_status == 'all') {
      form_set_error('status_id', $form_state, t('You have entered an invalid status ID.'));
    }

    $statuses = uc_order_status_list();
    foreach ($statuses as $status) {
      if ($new_status == $status['id']) {
        form_set_error('status_id', $form_state, t('This ID is already in use.  Please specify a unique ID.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    db_insert('uc_order_statuses')
      ->fields(array(
        'order_status_id' => $form_state['values']['status_id'],
        'title' => $form_state['values']['status_title'],
        'state' => $form_state['values']['status_state'],
        'weight' => $form_state['values']['status_weight'],
        'locked' => 0,
      ))
      ->execute();

    drupal_set_message(t('Custom order status created.'));

    $form_state['redirect'] = 'admin/store/settings/orders';
  }

}
