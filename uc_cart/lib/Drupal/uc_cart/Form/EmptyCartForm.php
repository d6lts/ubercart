<?php

/**
 * @file
 * Contains \Drupal\uc_cart\Form\EmptyCartForm.
 */

namespace Drupal\uc_cart\Form;

use Drupal\Core\Form\ConfirmFormBase;

/**
 * Confirm that the customer wants to empty their cart.
 */
class EmptyCartForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to empty your shopping cart?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'uc_cart.cart',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_cart_empty_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    uc_cart_empty();
    $form_state['redirect'] = 'cart';
  }

}
