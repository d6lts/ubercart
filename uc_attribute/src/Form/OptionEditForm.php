<?php

/**
 * @file
 * Contains \Drupal\uc_attribute\Form\OptionEditForm.
 */

namespace Drupal\uc_attribute\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the attribute option edit form.
 */
class OptionEditForm extends OptionFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $aid = NULL, $oid = NULL) {
    $option = uc_attribute_option_load($oid);

    $form = parent::buildForm($form, $form_state, $aid);

    $form['#title'] = $this->t('Edit option: %name', array('%name' => $option->name));

    $form['oid'] = array('#type' => 'value', '#value' => $option->oid);
    $form['name']['#default_value'] = $option->name;
    $form['ordering']['#default_value'] = $option->ordering;
    $form['cost']['#default_value'] = $option->cost;
    $form['price']['#default_value'] = $option->price;
    $form['weight']['#default_value'] = $option->weight;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_write_record('uc_attribute_options', $form_state['values'], array('aid', 'oid'));
    drupal_set_message(t('Updated option %option.', array('%option' => $form_state['values']['name'])));
    watchdog('uc_attribute', 'Updated option %option.', array('%option' => $form_state['values']['name']), WATCHDOG_NOTICE, 'admin/store/products/attributes/' . $form_state['values']['aid'] . '/options/' . $form_state['values']['oid']);
    $form_state->setRedirect('uc_attribute.options', array('aid' => $form_state->getValue('aid')));
  }

}
