<?php

/**
 * @file
 * Contains \Drupal\uc_attribute\Form\AttributeOptionsForm.
 */

namespace Drupal\uc_attribute\Form;

use Drupal\Core\Form\FormBase;

/**
 * Displays options and the modifications to products they represent.
 */
class AttributeOptionsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_attribute_options_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $aid = NULL) {
    $attribute = uc_attribute_load($aid);

    drupal_set_title(t('Options for %name', array('%name' => $attribute->name)), PASS_THROUGH);

    $form['options'] = array();
    foreach ($attribute->options as $key => $data) {
      $form['options'][$key] = array(
        'name' => array(
          '#markup' => check_plain($data->name),
        ),
        'cost' => array(
          '#theme' => 'uc_price',
          '#price' => $data->cost,
        ),
        'price' => array(
          '#theme' => 'uc_price',
          '#price' => $data->price,
        ),
        'weight' => array(
          '#markup' => (string)$data->weight,
        ),
        'ordering' => array(
          '#type' => 'weight',
          '#delta' => 50,
          '#default_value' => $data->ordering,
          '#attributes' => array('class' => array('uc-attribute-option-table-ordering')),
        ),
        'edit' => array('#markup' => l(t('edit'), 'admin/store/products/attributes/' . $attribute->aid . '/options/' . $key . '/edit')),
        'delete' => array('#markup' => l(t('delete'), 'admin/store/products/attributes/' . $attribute->aid . '/options/' . $key . '/delete')),
      );
    }

    if (count($form['options'])) {
      $form['options']['#tree'] = TRUE;

      $form['actions'] = array('#type' => 'actions');
      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Save changes'),
        '#weight' => 10,
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    foreach ($form_state['values']['options'] as $oid => $option) {
      db_update('uc_attribute_options')
        ->fields(array(
          'ordering' => $option['ordering'],
        ))
        ->condition('oid', $oid)
        ->execute();
    }

    drupal_set_message(t('The changes have been saved.'));
  }

}
