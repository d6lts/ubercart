<?php

/**
 * @file
 * Contains \Drupal\uc_reports\Form\YearlySalesReport;
 */

namespace Drupal\uc_reports\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class YearlySalesReport extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $year) {
    $form['year'] = array(
      '#type' => 'textfield',
      '#title' => t('Sales year'),
      '#default_value' => $year,
      '#maxlength' => 4,
      '#size' => 4,
      '#prefix' => '<div class="sales-year">',
      '#suffix' => '</div>',
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('View'),
      '#prefix' => '<div class="sales-year">',
      '#suffix' => '</div>',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('uc_reports.yearly.sales', ['year' => $form_state->getValue('year')]);
  }
}
