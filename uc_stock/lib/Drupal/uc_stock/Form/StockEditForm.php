<?php

/**
 * @file
 * Contains \Drupal\uc_stock\Form\StockEditForm.
 */

namespace Drupal\uc_stock\Form;

use Drupal\Core\Form\FormBase;
use Drupal\node\NodeInterface;

/**
 * Defines the stock edit form.
 */
class StockEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_stock_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, NodeInterface $node = NULL) {
    $form['stock'] = array('#tree' => TRUE);

    $skus = uc_product_get_models($node->id());

    // Remove 'Any'.
    unset($skus[NULL]);

    if (!$skus) {
      drupal_set_message(t('No SKU found.'), 'error');
    }
    else {
      foreach (array_values($skus) as $id => $sku) {
        $stock = db_query("SELECT * FROM {uc_product_stock} WHERE sku = :sku", array(':sku' => $sku))->fetchAssoc();

        $form['stock'][$id]['sku'] = array(
          '#type' => 'value',
          '#value' => $sku,
        );

        // Checkbox to mark this as active.
        $form['stock'][$id]['active'] = array(
          '#type' => 'checkbox',
          '#default_value' => !empty($stock['active']) ? $stock['active'] : 0,
        );

        // Sanitized version of the SKU for display.
        $form['stock'][$id]['display_sku'] = array(
          '#markup' => check_plain($sku),
        );

        // Textfield for entering the stock level.
        $form['stock'][$id]['stock'] = array(
          '#type' => 'textfield',
          '#default_value' => !empty($stock['stock']) ? $stock['stock'] : 0,
          '#maxlength' => 9,
          '#size' => 9,
        );

        // Textfield for entering the threshold level.
        $form['stock'][$id]['threshold'] = array(
          '#type' => 'textfield',
          '#default_value' => !empty($stock['threshold']) ? $stock['threshold'] : 0,
          '#maxlength' => 9,
          '#size' => 9,
        );
      }
    }

    $form['nid'] = array(
      '#type' => 'value',
      '#value' => $node->id(),
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['save'] = array(
      '#type' => 'submit',
      '#value' => t('Save changes'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    foreach (element_children($form_state['values']['stock']) as $id) {
      $stock = $form_state['values']['stock'][$id];

      db_merge('uc_product_stock')
        ->key(array('sku' => $stock['sku']))
        ->updateFields(array(
          'active' => $stock['active'],
          'stock' => $stock['stock'],
          'threshold' => $stock['threshold'],
        ))
        ->insertFields(array(
          'sku' => $stock['sku'],
          'active' => $stock['active'],
          'stock' => $stock['stock'],
          'threshold' => $stock['threshold'],
          'nid' => $form_state['values']['nid'],
        ))
        ->execute();
    }

    drupal_set_message(t('Stock settings saved.'));
  }

}
