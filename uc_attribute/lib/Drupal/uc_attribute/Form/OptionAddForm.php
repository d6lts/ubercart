<?php

/**
 * @file
 * Contains \Drupal\uc_attribute\Form\OptionAddForm.
 */

namespace Drupal\uc_attribute\Form;

/**
 * Defines the attribute option add form.
 */
class OptionAddForm extends OptionFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $aid = NULL) {
    $attribute = uc_attribute_load($aid);
    drupal_set_title(t('Options for %name', array('%name' => $attribute->name)), PASS_THROUGH);

    return parent::buildForm($form, $form_state, $aid);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    drupal_write_record('uc_attribute_options', $form_state['values']);
    drupal_set_message(t('Created new option %option.', array('%option' => $form_state['values']['name'])));
    watchdog('uc_attribute', 'Created new option %option.', array('%option' => $form_state['values']['name']), WATCHDOG_NOTICE, 'admin/store/products/attributes/' . $form_state['values']['aid'] . '/options/add');
    $form_state['redirect'] = 'admin/store/products/attributes/' . $form_state['values']['aid'] . '/options/add';
  }

}
