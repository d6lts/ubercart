<?php

/**
 * @file
 * Contains \Drupal\uc_attribute\Form\ObjectAttributesFormBase.
 */

namespace Drupal\uc_attribute\Form;

use Drupal\Core\Form\FormBase;

/**
 * Defines the class/product attributes overview form.
 */
abstract class ObjectAttributesFormBase extends FormBase {

  /**
   * The attribute table that this form will write to.
   */
  protected $attributeTable;

  /**
   * The option table that this form will write to.
   */
  protected $optionTable;

  /**
   * The identifier field that this form will use.
   */
  protected $idField;

  /**
   * The identifier value that this form will use.
   */
  protected $idValue;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_object_attributes_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $attributes = NULL) {
    $form['#tree'] = TRUE;

    $form['attributes'] = array();

    foreach ($attributes as $attribute) {
      $option = isset($attribute->options[$attribute->default_option]) ? $attribute->options[$attribute->default_option] : NULL;

      $form['attributes'][$attribute->aid] = array(
        'remove' => array(
          '#type' => 'checkbox',
          '#title' => t('Remove'),
          '#title_display' => 'invisible',
          '#default_value' => 0,
        ),
        'name' => array(
          '#markup' => check_plain($attribute->name),
        ),
        'label' => array(
          '#type' => 'textfield',
          '#title' => t('Label'),
          '#title_display' => 'invisible',
          '#default_value' => empty($attribute->label) ? $attribute->name : $attribute->label,
          '#size' => 20,
        ),
        'option' => array(
          '#markup' => $option ? (check_plain($option->name) . ' (' . theme('uc_price', array('price' => $option->price)) . ')' ) : t('n/a'),
        ),
        'required' => array(
          '#type' => 'checkbox',
          '#title' => t('Required'),
          '#title_display' => 'invisible',
          '#default_value' => $attribute->required,
        ),
        'ordering' => array(
          '#type' => 'weight',
          '#title' => t('List position'),
          '#title_display' => 'invisible',
          '#delta' => 25,
          '#default_value' => $attribute->ordering,
          '#attributes' => array('class' => array('uc-attribute-table-ordering')),
        ),
        'display' => array(
          '#type' => 'select',
          '#title' => t('Display'),
          '#title_display' => 'invisible',
          '#default_value' => $attribute->display,
          '#options' => _uc_attribute_display_types(),
        ),
      );
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['save'] = array(
      '#type' => 'submit',
      '#value' => t('Save changes'),
    );

    // @todo Remove when theme_uc_object_attributes_form is removed or refactored.
    $form['view'] = array(
      '#type' => 'value',
      '#value' => 'overview',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $changed = FALSE;

    foreach ($form_state['values']['attributes'] as $aid => $attribute) {
      if ($attribute['remove']) {
        $remove_aids[] = $aid;
      }
      else {
        $attribute['aid'] = $aid;
        $attribute[$this->idField] = $this->idValue;
        drupal_write_record($this->attributeTable, $attribute, array('aid', $this->idField));
        $changed = TRUE;
      }
    }

    if (isset($remove_aids)) {
      $select = db_select('uc_attribute_options', 'ao')
        ->fields('ao', array('oid'))
        ->condition('ao.aid', $remove_aids, 'IN');
      db_delete($this->optionTable)
        ->condition('oid', $select, 'IN')
        ->condition($this->idField, $this->idValue)
        ->execute();

      db_delete($this->attributeTable)
        ->condition($this->idField, $this->idValue)
        ->condition('aid', $remove_aids, 'IN')
        ->execute();

      $this->attributesRemoved();

      drupal_set_message(\Drupal::translation()->formatPlural(count($remove_aids), '1 attribute has been removed.', '@count attributes have been removed.'));
    }

    if ($changed) {
      drupal_set_message(t('The changes have been saved.'));
    }
  }

  /**
   * Called when submission of this form caused attributes to be removed.
   */
  protected function attributesRemoved() {
  }

}
