<?php

/**
 * @file
 * Contains \Drupal\uc_payment\Form\PaymentDeleteForm.
 */

namespace Drupal\uc_payment\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_order\OrderInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Confirmation form to delete a payment from an order.
 */
class PaymentDeleteForm extends ConfirmFormBase {

  /**
   * The payment to be deleted.
   */
  protected $payment;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this payment?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('uc_payments.order_payments', ['uc_order' => $this->payment->order_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_payment_delete_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $uc_order = NULL, $payment = NULL) {
    $this->payment = uc_payment_load($payment);

    // Make sure the payment is for the specified order.
    if ($this->payment->order_id != $uc_order->id()) {
      throw new NotFoundHttpException();
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    uc_payment_delete($this->payment->receipt_id);
    drupal_set_message($this->t('Payment deleted.'));
    $form_state->setRedirect('uc_payments.order_payments', ['uc_order' => $this->payment->order_id]);
  }

}
