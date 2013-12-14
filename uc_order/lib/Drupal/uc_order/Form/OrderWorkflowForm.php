<?php

/**
 * @file
 * Contains \Drupal\uc_order\Form\OrderWorkflowForm.
 */

namespace Drupal\uc_order\Form;

use Drupal\Core\Form\FormBase;

/**
 * Displays the order workflow form for order state and status customization.
 */
class OrderWorkflowForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_order_workflow_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $states = uc_order_state_list();
    $statuses = uc_order_status_list();

    $form['order_states'] = array(
      '#type' => 'details',
      '#title' => t('Order states'),
      '#collapsed' => TRUE,
    );
    $form['order_states']['order_states'] = array(
      '#type' => 'table',
      '#header' => array(t('State'), t('Default order status')),
    );

    foreach ($states as $state_id => $state) {
      $form['order_states']['order_states'][$state_id]['title'] = array(
        '#markup' => $state['title'],
      );

      // Create the select box for specifying a default status per order state.
      $options = array();
      foreach ($statuses as $status) {
        if ($status['state'] == $state_id) {
          $options[$status['id']] = $status['title'];
        }
      }
      if (empty($options)) {
        $form['order_states']['order_states'][$state_id]['default'] = array(
          '#markup' => t('- N/A -'),
        );
      }
      else {
        $form['order_states']['order_states'][$state_id]['default'] = array(
          '#type' => 'select',
          '#options' => $options,
          '#default_value' => uc_order_state_default($state_id),
        );
      }
    }

    $form['order_statuses'] = array(
      '#type' => 'details',
      '#title' => t('Order statuses'),
      '#collapsible' => FALSE,
    );
    $form['order_statuses']['order_statuses'] = array(
      '#type' => 'table',
      '#header' => array(t('ID'), t('Title'), t('List position'), t('State'), t('Remove')),
    );

    // Build the state option array for the order status table.
    $options = array();
    foreach ($states as $state_id => $state) {
      $options[$state_id] = $state['title'];
    }

    foreach ($statuses as $status) {
      $form['#locked'][$status['id']] = $status['locked'];

      $form['order_statuses']['order_statuses'][$status['id']]['id'] = array(
        '#markup' => $status['id'],
      );
      $form['order_statuses']['order_statuses'][$status['id']]['title'] = array(
        '#type' => 'textfield',
        '#default_value' => $status['title'],
        '#size' => 32,
        '#required' => TRUE,
      );
      $form['order_statuses']['order_statuses'][$status['id']]['weight'] = array(
        '#type' => 'weight',
        '#delta' => 20,
        '#default_value' => $status['weight'],
      );
      if ($status['locked']) {
        $form['order_statuses']['order_statuses'][$status['id']]['state'] = array(
          '#markup' => $states[$status['state']]['title'],
        );
      }
      else {
        $form['order_statuses']['order_statuses'][$status['id']]['state'] = array(
          '#type' => 'select',
          '#options' => $options,
          '#default_value' => $status['state'],
        );
        $form['order_statuses']['order_statuses'][$status['id']]['remove'] = array(
          '#type' => 'checkbox',
        );
      }
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit changes'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    foreach ($form_state['values']['order_states'] as $key => $value) {
      variable_set('uc_state_' . $key . '_default', $value['default']);
    }

    foreach ($form_state['values']['order_statuses'] as $key => $value) {
      if (!$form['#locked'][$key] && $value['remove']) {
        db_delete('uc_order_statuses')
          ->condition('order_status_id', $key)
          ->execute();
        drupal_set_message(t('Order status %status removed.', array('%status' => $key)));
      }
      else {
        $fields = array(
          'title' => $value['title'],
          'weight' => $value['weight'],
        );

        // The state cannot be changed if the status is locked.
        if (!$form['#locked'][$key]) {
          $fields['state'] = $value['state'];
        }

        $query = db_update('uc_order_statuses')
          ->fields($fields)
          ->condition('order_status_id', $key)
          ->execute();
      }
    }

    drupal_set_message(t('Order workflow information saved.'));
  }

}
