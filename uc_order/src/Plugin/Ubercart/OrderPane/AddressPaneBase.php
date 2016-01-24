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

    $output = '<div class="order-pane-icons">';
    $output .= ' <img src="' . base_path() . drupal_get_path('module', 'uc_store')
      . '/images/address_book.gif" alt="' . t('Select from address book.') . '" '
      . 'title="' . t('Select from address book.') . '" onclick="load_address_select(' . $order->getOwnerId() . ', \'#' . $pane .'_address_select\', \'' . $pane . '\');" />';
    $output .= ' <img src="' . base_path() . drupal_get_path('module', 'uc_store')
      . '/images/copy.gif" alt="' . t('Copy billing information.') . '" title="'
      . t('Copy billing information.') . '" onclick="uc_order_copy_billing_to_shipping();" />';
    $output .= '</div>';
    $output .= '<div id="' . $pane . '_address_select"></div>';

    $form['icons'] = array(
      '#type' => 'markup',
      '#markup' => $output,
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
