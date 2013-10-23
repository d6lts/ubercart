<?php

/**
 * @file
 * Contains \Drupal\uc_attribute\Form\ProductAttributesAddForm.
 */

namespace Drupal\uc_attribute\Form;

use Drupal\node\NodeInterface;

/**
 * Defines the product attribute add form.
 */
class ProductAttributesAddForm extends ObjectAttributesAddFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, NodeInterface $node = NULL) {
    $this->attributeTable = 'uc_product_attributes';
    $this->optionTable = 'uc_product_options';
    $this->idField = 'nid';
    $this->idValue = $node->id();

    $attributes = uc_product_get_attributes($node->id());
    return parent::buildForm($form, $form_state, $attributes);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    parent::submitForm($form, $form_state);
    $form_state['redirect'] = 'node/' . $this->idValue . '/edit/attributes';
  }

  /**
   * {@inheritdoc}
   */
  protected function attributesAdded() {
    db_delete('uc_product_adjustments')
      ->condition('nid', $this->idValue)
      ->execute();
  }

}
