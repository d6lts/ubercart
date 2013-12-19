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
    $form['stock'] = array(
      '#type' => 'table',
      '#header' => array(
        array('data' => '&nbsp;&nbsp;' . $this->t('Active'), 'class' => array('select-all')),
        $this->t('SKU'),
        $this->t('Stock'),
        $this->t('Threshold'),
      ),
    );
    $form['#attached']['library'][] = array('system', 'drupal.tableselect');

    $skus = uc_product_get_models($node->id(), FALSE);
    foreach ($skus as $sku) {
      $stock = db_query("SELECT * FROM {uc_product_stock} WHERE sku = :sku", array(':sku' => $sku))->fetchAssoc();

      $form['stock'][$sku]['active'] = array(
        '#type' => 'checkbox',
        '#default_value' => !empty($stock['active']) ? $stock['active'] : 0,
      );
      $form['stock'][$sku]['sku'] = array(
        '#markup' => check_plain($sku),
      );
      $form['stock'][$sku]['stock'] = array(
        '#type' => 'textfield',
        '#default_value' => !empty($stock['stock']) ? $stock['stock'] : 0,
        '#maxlength' => 9,
        '#size' => 9,
      );
      $form['stock'][$sku]['threshold'] = array(
        '#type' => 'textfield',
        '#default_value' => !empty($stock['threshold']) ? $stock['threshold'] : 0,
        '#maxlength' => 9,
        '#size' => 9,
      );
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
    foreach (element_children($form_state['values']['stock']) as $sku) {
      $stock = $form_state['values']['stock'][$sku];

      db_merge('uc_product_stock')
        ->key(array('sku' => $sku))
        ->updateFields(array(
          'active' => $stock['active'],
          'stock' => $stock['stock'],
          'threshold' => $stock['threshold'],
        ))
        ->insertFields(array(
          'sku' => $sku,
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
