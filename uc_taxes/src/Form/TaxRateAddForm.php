<?php

/**
 * @file
 * Contains \Drupal\uc_taxes\Form\TaxRateAddForm.
 */

namespace Drupal\uc_taxes\Form;

/**
 * Defines the new tax rate form.
 */
class TaxRateAddForm extends TaxRateFormBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $form_state['values']['id'] = 0;
    $rate = parent::submitForm($form, $form_state);

    drupal_set_message(t('Tax rate %name created.', array('%name' => $rate->name)));

    //$form_state['redirect'] = 'admin/store/settings/taxes/manage/uc_taxes_' . $rate->id;
    $form_state['redirect'] = 'admin/store/settings/taxes';
  }

}
