<?php

/**
 * @file
 * Contains \Drupal\uc_order\Form\OrderWorkflowForm.
 */

namespace Drupal\uc_order\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Displays the order workflow form for order state and status customization.
 */
class OrderWorkflowForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_order_workflow_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'uc_order.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $states = uc_order_state_options_list();
    $statuses = \Drupal\uc_order\Entity\OrderStatus::loadMultiple();

    $form['order_states'] = array(
      '#type' => 'details',
      '#title' => t('Order states'),
    );
    $form['order_states']['order_states'] = array(
      '#type' => 'table',
      '#header' => array(t('State'), t('Default order status')),
    );

    foreach ($states as $state_id => $title) {
      $form['order_states']['order_states'][$state_id]['title'] = array(
        '#markup' => $title,
      );

      // Create the select box for specifying a default status per order state.
      $options = array();
      foreach ($statuses as $status) {
        if ($status->state == $state_id) {
          $options[$status->id] = $status->name;
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
      '#open' => TRUE,
    );
    $form['order_statuses']['order_statuses'] = array(
      '#type' => 'table',
      '#header' => array(t('ID'), t('Title'), t('List position'), t('State'), t('Remove')),
    );

    foreach ($statuses as $status) {
      $form['#locked'][$status->id] = $status->locked;

      $form['order_statuses']['order_statuses'][$status->id]['id'] = array(
        '#markup' => $status->id,
      );
      $form['order_statuses']['order_statuses'][$status->id]['name'] = array(
        '#type' => 'textfield',
        '#default_value' => $status->name,
        '#size' => 32,
        '#required' => TRUE,
      );
      $form['order_statuses']['order_statuses'][$status->id]['weight'] = array(
        '#type' => 'weight',
        '#delta' => 20,
        '#default_value' => $status->weight,
      );
      if ($status->locked) {
        $form['order_statuses']['order_statuses'][$status->id]['state'] = array(
          '#markup' => $states[$status->state],
        );
      }
      else {
        $form['order_statuses']['order_statuses'][$status->id]['state'] = array(
          '#type' => 'select',
          '#options' => $states,
          '#default_value' => $status->state,
        );
        $form['order_statuses']['order_statuses'][$status->id]['remove'] = array(
          '#type' => 'checkbox',
        );
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('uc_order.settings');
    foreach ($form_state->getValue('order_states') as $key => $value) {
      $config->set("default_state.$key", $value['default']);
    }
    $config->save();

    foreach ($form_state->getValue('order_statuses') as $id => $value) {
      $status = \Drupal\uc_order\Entity\OrderStatus::load($id);
      if (!$form['#locked'][$id] && $value['remove']) {
        $status->delete();
        drupal_set_message(t('Order status %status removed.', ['%status' => $status->name]));
      }
      else {
        $status->name = $value['name'];
        $status->weight = (int) $value['weight'];

        // The state cannot be changed if the status is locked.
        if (!$form['#locked'][$key]) {
          $status->state = $value['state'];
        }

        $status->save();
      }
    }

    parent::submitForm($form, $form_state);
  }

}
