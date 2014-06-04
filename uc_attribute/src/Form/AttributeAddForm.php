<?php

/**
 * @file
 * Contains \Drupal\uc_attribute\Form\AttributeAddForm.
 */

namespace Drupal\uc_attribute\Form;

/**
 * Defines the attribute add form.
 */
class AttributeAddForm extends AttributeFormBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    drupal_write_record('uc_attributes', $form_state['values']);
    $form_state['redirect'] = 'admin/store/products/attributes/' . $form_state['values']['aid'] . '/options';
  }

}
