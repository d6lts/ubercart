<?php

/**
 * @file
 * Contains \Drupal\uc_product\Form\BuyItNowForm.
 */

namespace Drupal\uc_product\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\uc_cart\Controller\Cart;

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
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    $query = \Drupal::request()->query->all();
    $form['#action'] = Url::fromRoute('<current>')->setOptions(['query' => $query])->toString();

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
      '#value' => $this->t('Add to cart'),
      '#id' => 'edit-submit-' . $node->id(),
    );

    uc_form_alter($form, $form_state, $this->getFormId());

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getRedirect()) {
      $data = \Drupal::moduleHandler()->invokeAll('uc_add_to_cart_data', array($form_state->getValues()));
      $msg = $this->config('uc_cart.settings')->get('add_item_msg');
      $cart = Cart::create(\Drupal::getContainer());
      $redirect = $cart->addItem($form_state->getValue('nid'), $form_state->getValue('qty'), $data, NULL, $msg);
      if (isset($redirect)) {
        $form_state->setRedirectUrl($redirect);
      }
    }
  }

}
