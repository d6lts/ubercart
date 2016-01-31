<?php

/**
 * @file
 * Contains \Drupal\uc_fulfillment\Form\PackageCancelForm.
 */

namespace Drupal\uc_fulfillment\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Confirms cancellation of a package's shipment.
 */
class PackageCancelForm extends ConfirmFormBase {

  /**
   * The order id.
   */
  protected $order_id;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_fulfillment_package_cancel_confirm';
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
    return $this->t('Are you sure you want to cancel the shipment of this package?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('It will be available for reshipment.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Cancel shipment');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Nevermind');
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
  public function buildForm(array $form, FormStateInterface $form_state, $order = NULL, $package = NULL) {
    $this->order_id = $order->id();

    $form['order_id'] = array(
      '#type' => 'value',
      '#value' => $order->id(),
    );
    $form['package_id'] = array(
      '#type' => 'value',
      '#value' => $package->package_id,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $package = uc_fulfillment_package_load($form_state->getValue('package_id'));
    $shipment = uc_fulfillment_shipment_load($package->sid);
    $methods = \Drupal::moduleHandler()->invokeAll('uc_fulfillment_method');
    if (isset($methods[$shipment->shipping_method]['cancel']) &&
        function_exists($methods[$shipment->shipping_method]['cancel'])) {
      $result = call_user_func($methods[$shipment->shipping_method]['cancel'], $shipment->tracking_number, array($package->tracking_number));
      if ($result) {
        db_update('uc_packages')
          ->fields(array(
            'sid' => NULL,
            'label_image' => NULL,
            'tracking_number' => NULL,
          ))
          ->condition('package_id', $package->package_id)
          ->execute();

        if (isset($package->label_image)) {
          file_usage_delete($package->label_image, 'uc_fulfillment', 'package', $package->package_id);
          file_delete($package->label_image);
          unset($package->label_image);
        }

        unset($shipment->packages[$package->package_id]);
        if (!count($shipment->packages)) {
          uc_fulfillment_shipment_delete($shipment->sid);
        }
      }
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}