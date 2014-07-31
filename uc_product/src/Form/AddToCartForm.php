<?php

/**
 * @file
 * Contains \Drupal\uc_product\Form\AddToCartForm.
 */

namespace Drupal\uc_product\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

/**
 * Defines a complex form for adding a product to the cart.
 */
class AddToCartForm extends BuyItNowForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_product_add_to_cart_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    $form['node'] = array(
      '#type' => 'value',
      '#value' => isset($form_state['storage']['variant']) ? $form_state['storage']['variant'] : $node,
    );

    $form = parent::buildForm($form, $form_state, $node);

    if ($node->default_qty > 0) {
      if (\Drupal::config('uc_product.settings')->get('add_to_cart_qty')) {
        $form['qty'] = array(
          '#type' => 'uc_quantity',
          '#title' => t('Quantity'),
          '#default_value' => $node->default_qty,
        );
      }
      else {
        $form['qty']['#value'] = $node->default_qty;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $data = \Drupal::moduleHandler()->invokeAll('uc_add_to_cart_data', array($form_state['values']));
    $form_state['storage']['variant'] = uc_product_load_variant($form_state['values']['nid'], $data);
  }

}
