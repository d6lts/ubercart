<?php

/**
 * @file
 * Contains \Drupal\uc_attribute\Form\AttributeAddForm.
 */

namespace Drupal\uc_attribute\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the attribute add form.
 */
class AttributeAddForm extends AttributeFormBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_write_record('uc_attributes', $form_state['values']);
    $form_state->setRedirect('uc_attribute.options', array('aid' => $form_state->getValue('aid')));
  }

}
