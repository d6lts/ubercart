<?php

/**
 * @file
 * Contains \Drupal\uc_stock\Form\StockSettingsForm.
 */

namespace Drupal\uc_stock\Form;

use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure stock settings for this site.
 */
class StockSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'uc_stock_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['uc_stock_threshold_notification'] = array(
      '#type' => 'checkbox',
      '#title' => t('Send email notification when stock level reaches its threshold value'),
      '#default_value' => variable_get('uc_stock_threshold_notification', FALSE),
    );

    $form['uc_stock_threshold_notification_recipients'] = array(
      '#type' => 'textfield',
      '#title' => t('Notification recipients'),
      '#default_value' => variable_get('uc_stock_threshold_notification_recipients', uc_store_email()),
      '#description' => t('The list of comma-separated email addresses that will receive the notification.'),
    );

    $form['uc_stock_threshold_notification_subject'] = array(
      '#type' => 'textfield',
      '#title' => t('Message subject'),
      '#default_value' => variable_get('uc_stock_threshold_notification_subject', uc_get_message('uc_stock_threshold_notification_subject')),
    );

    $form['uc_stock_threshold_notification_message'] = array(
      '#type' => 'textarea',
      '#title' => t('Message text'),
      '#default_value' => variable_get('uc_stock_threshold_notification_message', uc_get_message('uc_stock_threshold_notification_message')),
      '#description' => t('The message the user receives when the stock level reaches its threshold value.'),
      '#rows' => 10,
    );

    if (module_exists('token')) {
      $form['token_tree'] = array(
        '#markup' => theme('token_tree', array('token_types' => array('uc_order', 'uc_stock', 'node', 'site', 'store'))),
      );
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    variable_set('uc_stock_threshold_notification', $form_state['values']['uc_stock_threshold_notification']);
    variable_set('uc_stock_threshold_notification_recipients', $form_state['values']['uc_stock_threshold_notification_recipients']);
    variable_set('uc_stock_threshold_notification_subject', $form_state['values']['uc_stock_threshold_notification_subject']);
    variable_set('uc_stock_threshold_notification_message', $form_state['values']['uc_stock_threshold_notification_message']);

    parent::submitForm($form, $form_state);
  }

}
