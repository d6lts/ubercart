<?php

/**
 * @file
 * Contains \Drupal\uc_order\Plugin\Ubercart\OrderPane\AddressPaneBase.
 */

namespace Drupal\uc_order\Plugin\Ubercart\OrderPane;

use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\EditableOrderPanePluginBase;
use Drupal\uc_order\OrderInterface;

/**
 * Provides a generic address pane that can be extended as required.
 */
abstract class AddressPaneBase extends EditableOrderPanePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getClasses() {
    return 'pos-left';
  }

  /**
   * {@inheritdoc}
   */
  public function view(OrderInterface $order, $view_mode) {
    $pane = $this->pluginDefinition['id'];
    $address = $order->getAddress($pane);
    return ['#markup' => $address . '<br />' . $address->phone];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $pane = $this->pluginDefinition['id'];

    $form['address-book-image'] = array(
      '#theme' => 'image',
      '#uri' => base_path() . drupal_get_path('module', 'uc_store') . '/images/address_book.gif',
      '#title' => $this->t('Select from address book.'),
      '#alt' => $this->t('Select from address book.'),
      '#attributes' => array('id' => 'las', 'onclick' => '"load_address_select(' . $order->getOwnerId() . ', \'#' . $pane . '_address_select\', \'' . $pane . '\');"'),
      '#prefix' => '<div class="order-pane-icons">',
    );

    $form['copy-address-image'] = array(
      '#theme' => 'image',
      '#uri' => base_path() . drupal_get_path('module', 'uc_store') . '/images/copy.gif',
      // Need to set #title, #alt, and #attributes in derived class.
      '#suffix' => '</div>',
    );

    $form['icons'] = array(
      '#type' => 'markup',
      '#markup' => '<div id="' . $pane . '_address_select"></div>',
    );

    $form['address'] = array(
      '#type' => 'uc_address',
      '#parents' => [$pane],
      '#default_value' => $order->getAddress($pane),
      '#required' => FALSE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(OrderInterface $order, array &$form, FormStateInterface $form_state) {
    $pane = $this->pluginDefinition['id'];
    $address = $order->getAddress($pane);
    foreach ($form_state->getValue($pane) as $key => $value) {
      if (uc_address_field_enabled($key)) {
        $address->$key = $value;
      }
    }
    $order->setAddress($pane, $address);
  }

}
