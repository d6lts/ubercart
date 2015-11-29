<?php

/**
 * @file
 * Contains \Drupal\uc_tax_report\Form\ParametersForm.
 */

namespace Drupal\uc_tax_report\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\Entity\OrderStatus;

/**
 * Form to customize parameters on the tax report.
 */
class ParametersForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_tax_report_params_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $values = NULL) {
    $form['params'] = array(
      '#type' => 'fieldset',
      '#title' => t('Customize tax report parameters'),
      '#description' => t('Adjust these values and update the report to build your sales tax report. Once submitted, the report may be bookmarked for easy reference in the future.'),
    );

    $form['params']['start_date'] = array(
      '#type' => 'date',
      '#title' => t('Start date'),
      '#default_value' => array(
        'month' => \Drupal::service('date.formatter')->format($values['start_date'], 'custom', 'n'),
        'day' => \Drupal::service('date.formatter')->format($values['start_date'], 'custom', 'j'),
        'year' => \Drupal::service('date.formatter')->format($values['start_date'], 'custom', 'Y'),
      ),
    );
    $form['params']['end_date'] = array(
      '#type' => 'date',
      '#title' => t('End date'),
      '#default_value' => array(
        'month' => \Drupal::service('date.formatter')->format($values['end_date'], 'custom', 'n'),
        'day' => \Drupal::service('date.formatter')->format($values['end_date'], 'custom', 'j'),
        'year' => \Drupal::service('date.formatter')->format($values['end_date'], 'custom', 'Y'),
      ),
    );

    $stat = $values['status'];
    if ($stat === FALSE) {
      $stat = uc_report_order_statuses();
    }

    $form['params']['status'] = array(
      '#type' => 'select',
      '#title' => t('Order statuses'),
      '#description' => t('Only orders with selected statuses will be included in the report.') . '<br />' . t('Hold Ctrl + click to select multiple statuses.'),
      '#options' => OrderStatus::getOptionsList(),
      '#default_value' => $stat,
      '#multiple' => TRUE,
      '#size' => 5,
    );

    $form['params']['actions'] = array('#type' => 'actions');
    $form['params']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Update report'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->isValueEmpty('status')) {
      $form_state->setErrorByName('status', t('You must select at least one order status.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Build the start and end dates from the form.
    $start_date = mktime(0, 0, 0, $form_state->getValue(['start_date', 'month']), $form_state->getValue(['start_date', 'day']), $form_state->getValue(['start_date', 'year']));
    $end_date = mktime(23, 59, 59, $form_state->getValue(['end_date', 'month']), $form_state->getValue(['end_date', 'day']), $form_state->getValue(['end_date', 'year']));

    $args = array(
      'start_date' => $start_date,
      'end_date' => $end_date,
      'status' => implode(',', array_keys($form_state->getValue('status'))),
    );

    $form_state->setRedirect('uc_tax_report.reports', $args);
  }
}
