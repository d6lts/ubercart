<?php

/**
 * @file
 * Contains \Drupal\uc_order\Form\OrderMailInvoiceForm.
 */

namespace Drupal\uc_order\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\UcOrderInterface;

/**
 * Defines a form to set the recipient of an invoice, then mails it.
 */
class OrderMailInvoiceForm extends FormBase {

  /**
   * The order to be emailed.
   */
  protected $order;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_order_mail_invoice_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UcOrderInterface $uc_order = NULL) {
    $this->order = $uc_order;

    $form['email'] = array(
      '#type' => 'email',
      '#title' => t('Recipient e-mail address'),
      '#default_value' => $uc_order->getEmail(),
      '#required' => TRUE,
    );
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit' ] = array(
      '#type' => 'submit',
      '#value' => t('Mail invoice'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $recipient = $form_state['values']['email'];
    $params = array('order' => $this->order);
    drupal_mail('uc_order', 'invoice', $recipient, uc_store_mail_recipient_langcode($recipient), $params, uc_store_email_from());

    $message = t('Invoice e-mailed to @email.', array('@email' => $recipient));
    drupal_set_message($message);
    $this->order->logChanges(array($message));
  }

}
