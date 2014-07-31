<?php

/**
 * @file
 * Contains \Drupal\uc_weightquote\Form\WeightquoteEditForm.
 */

namespace Drupal\uc_weightquote\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configures the store default weight-based shipping rates.
 */
class WeightquoteEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_weightquote_admin_method_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $mid = NULL) {
    if ($mid && ($method = db_query("SELECT * FROM {uc_weightquote_methods} WHERE mid = :mid", array(':mid' => $mid))->fetchObject())) {
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
      '#description' => t('The name shown to administrators distinguish this method from other weight quote methods.'),
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
      '#description' => t('The starting price for weight-based shipping costs.'),
      '#default_value' => $method->base_rate,
      '#required' => TRUE,
    );
    $unit = \Drupal::config('uc_store.settings')->get('units.weight');
    $form['product_rate'] = array(
      '#type' => 'uc_price',
      '#title' => t('Default cost adjustment per !unit', array('!unit' => $unit)),
      '#description' => t('The amount per weight unit to add to the shipping cost for an item.'),
      '#default_value' => $method->product_rate,
      '#field_suffix' => t('per @unit', array('@unit' => $unit)),
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
      drupal_write_record('uc_weightquote_methods', $form_state['values'], 'mid');
      drupal_set_message(t('Weight quote shipping method was updated.'));
      $form_state['redirect'] = 'admin/store/settings/quotes';
    }
    else {
      drupal_write_record('uc_weightquote_methods', $form_state['values']);

      // Ensure Rules picks up the new condition.
      // entity_flush_caches();

      drupal_set_message(t('Created and enabled new weight quote shipping method.'));
      $form_state['redirect'] = 'admin/store/settings/quotes';
      //$form_state['redirect'] = 'admin/store/settings/quotes/manage/get_quote_from_weightquote_' . $form_state['values']['mid'];
    }
  }

  /**
   * Helper function to delete a weight quote method.
   */
  public function delete(&$form, FormStateInterface $form_state) {
    $form_state['redirect'] = 'admin/store/settings/quotes/methods/weightquote/' . $form_state['values']['mid'] . '/delete';
  }

}
