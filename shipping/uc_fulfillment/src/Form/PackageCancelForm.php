<?php

/**
 * @file
 * Contains \Drupal\uc_fulfillment\Form\PackageCancelForm.
 */

namespace Drupal\uc_fulfillment\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_fulfillment\PackageInterface;
use Drupal\uc_fulfillment\Shipment;
use Drupal\uc_order\OrderInterface;

/**
 * Confirms cancellation of a package's shipment.
 */
class PackageCancelForm extends ConfirmFormBase {

  /**
   * The order id.
   *
   * @var \Drupal\uc_order\OrderInterface
   */
  protected $order_id;

  /**
   * The package.
   *
   * @var \Drupal\uc_fulfillment\PackageInterface
   */
  protected $package;

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
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $uc_order = NULL, PackageInterface $uc_package = NULL) {
    $this->order_id = $uc_order->id();
    $this->package = $uc_package;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $shipment = Shipment::load($this->package->sid);
    $methods = \Drupal::moduleHandler()->invokeAll('uc_fulfillment_method');
    if (isset($methods[$shipment->shipping_method]['cancel']) &&
        function_exists($methods[$shipment->shipping_method]['cancel'])) {
      $result = call_user_func($methods[$shipment->shipping_method]['cancel'], $shipment->tracking_number, array($this->package->tracking_number));
      if ($result) {
        db_update('uc_packages')
          ->fields(array(
            'sid' => NULL,
            'label_image' => NULL,
            'tracking_number' => NULL,
          ))
          ->condition('package_id', $this->package->package_id)
          ->execute();

        if (isset($this->package->label_image)) {
          file_usage_delete($this->package->label_image, 'uc_fulfillment', 'package', $this->package->package_id);
          file_delete($this->package->label_image);
          unset($this->package->label_image);
        }

        unset($shipment->packages[$this->package->package_id]);
        if (!count($shipment->packages)) {
          $shipment->delete();
        }
      }
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
