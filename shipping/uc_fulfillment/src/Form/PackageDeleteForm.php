<?php

/**
 * @file
 * Contains \Drupal\uc_fulfillment\Form\PackageDeleteForm.
 */

namespace Drupal\uc_fulfillment\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_order\OrderInterface;

/**
 * Decides to unpackage products.
 */
class PackageDeleteForm extends ConfirmFormBase {

  /**
   * The order id.
   */
  protected $order_id;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_fulfillment_package_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'uc_fulfillment.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this package?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('The products it contains will be available for repackaging.');
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
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('uc_fulfillment.packages', ['uc_order' => $this->order_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $uc_order = NULL, $package_id = NULL) {
    $this->order_id = $uc_order->id();

    $form['package_id'] = array(
      '#type' => 'value',
      '#value' => $package_id,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    uc_fulfillment_package_delete($form_state->getValue('package_id'));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
