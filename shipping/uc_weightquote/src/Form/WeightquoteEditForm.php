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
    if ($mid && ($method = db_query("SELECT * FROM {uc_weightquote_methods} WHERE mid = :mid", [':mid' => $mid])->fetchObject())) {
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
      '#title' => $this->t('Shipping method title'),
      '#description' => $this->t('The name shown to administrators distinguish this method from other weight quote methods.'),
      '#default_value' => $method->title,
      '#required' => TRUE,
    );
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Line item label'),
      '#description' => $this->t('The name shown to the customer when they choose a shipping method at checkout.'),
      '#default_value' => $method->label,
      '#required' => TRUE,
    );
    $form['base_rate'] = array(
      '#type' => 'uc_price',
      '#title' => $this->t('Base price'),
      '#description' => $this->t('The starting price for weight-based shipping costs.'),
      '#default_value' => $method->base_rate,
      '#required' => TRUE,
    );
    $unit = $this->config('uc_store.settings')->get('weight.units');
    $form['product_rate'] = array(
      '#type' => 'uc_price',
      '#title' => $this->t('Default cost adjustment per @unit', ['@unit' => $unit]),
      '#description' => $this->t('The amount per weight unit to add to the shipping cost for an item.'),
      '#default_value' => $method->product_rate,
      '#field_suffix' => $this->t('per @unit', ['@unit' => $unit]),
      '#required' => TRUE,
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    );

    if (isset($form['mid'])) {
      $form['actions']['delete'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Delete'),
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
    $form_state->cleanValues();
    if ($form_state->hasValue('mid')) {
      db_merge('uc_weightquote_methods')
        ->key(array('mid' => $form_state->getValue('mid')))
        ->fields($form_state->getValues())
        ->execute();
      drupal_set_message($this->t('Weight quote shipping method was updated.'));
      $form_state->setRedirect('uc_quote.methods');
    }
    else {
      db_insert('uc_weightquote_methods')
        ->fields($form_state->getValues())
        ->execute();

      // Ensure Rules picks up the new condition.
      // entity_flush_caches();

      drupal_set_message($this->t('Created and enabled new weight quote shipping method.'));
      $form_state->setRedirect('uc_quote.methods');
      //$form_state['redirect'] = 'admin/store/config/quotes/manage/get_quote_from_weightquote_' . $form_state->getValue('mid');
    }
  }

  /**
   * Helper function to delete a weight quote method.
   */
  public function delete(&$form, FormStateInterface $form_state) {
    $form_state->setRedirect('uc_weightquote.delete', ['mid' => $form_state->getValue('mid')]);
  }

}
