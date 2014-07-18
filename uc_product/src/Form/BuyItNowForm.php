<?php

/**
 * @file
 * Contains \Drupal\uc_product\Form\BuyItNowForm.
 */

namespace Drupal\uc_product\Form;

use Drupal\Core\Form\FormBase;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Defines a simple form for adding a product to the cart.
 */
class BuyItNowForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_product_buy_it_now_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, NodeInterface $node = NULL) {
    $form['nid'] = array(
      '#type' => 'value',
      '#value' => $node->id(),
    );

    $form['qty'] = array(
      '#type' => 'value',
      '#value' => 1,
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Add to cart'),
      '#id' => 'edit-submit-' . $node->id(),
    );

    uc_form_alter($form, $form_state, $this->getFormId());

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    if (empty($form_state['redirect'])) {
      $data = \Drupal::moduleHandler()->invokeAll('uc_add_to_cart_data', array($form_state['values']));
      $msg = \Drupal::config('uc_cart.settings')->get('add_item_msg');
      $form_state['redirect'] = uc_cart_add_item($form_state['values']['nid'], $form_state['values']['qty'], $data, NULL, $msg);
    }
  }

}
