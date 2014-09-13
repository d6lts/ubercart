<?php

/**
 * @file
 * Contains \Drupal\uc_attribute\Form\OptionAddForm.
 */

namespace Drupal\uc_attribute\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the attribute option add form.
 */
class OptionAddForm extends OptionFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $aid = NULL) {
    $attribute = uc_attribute_load($aid);

    $form = parent::buildForm($form, $form_state, $aid);

    $form['#title'] = $this->t('Options for %name', array('%name' => $attribute->name));

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_write_record('uc_attribute_options', $form_state->getValues());
    drupal_set_message(t('Created new option %option.', array('%option' => $form_state->getValue('name'))));
    watchdog('uc_attribute', 'Created new option %option.', array('%option' => $form_state->getValue('name')), WATCHDOG_NOTICE, 'admin/store/products/attributes/' . $form_state->getValue('aid') . '/options/add');
    $form_state->setRedirect('uc_attribute.option_add', array('aid' => $form_state->getValue('aid')));
  }

}
