<?php

/**
 * @file
 * Contains \Drupal\uc_flatrate\Form\FlatrateEditForm.
 */

namespace Drupal\uc_flatrate\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configures the store default flat rate shipping rates.
 */
class FlatrateEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_flatrate_admin_method_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $mid = NULL) {
    if ($mid && ($method = db_query("SELECT * FROM {uc_flatrate_methods} WHERE mid = :mid", array(':mid' => $mid))->fetchObject())) {
      $form['mid'] = array(
        '#type' => 'value',
        '#value' => $mid,
      );
    }
    else {
      $method = (object) array(
        'title' => '',
        'label' => '',
        'base_rate' => '',
        'product_rate' => '',
      );
    }

    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Shipping method title'),
      '#description' => t('The name shown to administrators distinguish this method from other flatrate methods.'),
      '#default_value' => $method->title,
      '#required' => TRUE,
    );
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Line item label'),
      '#description' => t('The name shown to the customer when they choose a shipping method at checkout.'),
      '#default_value' => $method->label,
      '#required' => TRUE,
    );
    $form['base_rate'] = array(
      '#type' => 'uc_price',
      '#title' => t('Base price'),
      '#description' => t('The starting price for shipping costs.'),
      '#default_value' => $method->base_rate,
      '#required' => TRUE,
    );
    $form['product_rate'] = array(
      '#type' => 'uc_price',
      '#title' => t('Default product shipping rate'),
      '#description' => t('Additional shipping cost per product in cart.'),
      '#default_value' => $method->product_rate,
      '#required' => TRUE,
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#button_type' => 'primary',
    );

    if (isset($form['mid'])) {
      $form['actions']['delete'] = array(
        '#type' => 'submit',
        '#value' => t('Delete'),
        '#submit' => array(array($this, 'delete')),
        '#button_type' => 'danger',
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (isset($form_state['values']['mid'])) {
      drupal_write_record('uc_flatrate_methods', $form_state['values'], 'mid');
      drupal_set_message(t('Flat rate shipping method was updated.'));
      $form_state->setRedirect('uc_quote.methods');
    }
    else {
      drupal_write_record('uc_flatrate_methods', $form_state['values']);

      // Ensure Rules picks up the new condition.
      // entity_flush_caches();

      drupal_set_message(t('Created and enabled new flat rate shipping method.'));
      $form_state->setRedirect('uc_quote.methods');
      //$form_state['redirect'] = 'admin/store/settings/quotes/manage/get_quote_from_flatrate_' . $form_state['values']['mid'];
    }
  }

  /**
   * Helper function to delete a flatrate method.
   */
  public function delete(&$form, FormStateInterface $form_state) {
    $form_state->setRedirect('uc_flatrate.delete', array('mid' => $form_state->getValue('mid')));
  }

}
