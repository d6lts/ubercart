<?php

/**
 * @file
 * Contains \Drupal\uc_cart\Plugin\Ubercart\CheckoutPane\AddressPaneBase.
 */

namespace Drupal\uc_cart\Plugin\Ubercart\CheckoutPane;

use Drupal\Component\Utility\String;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_cart\CheckoutPanePluginBase;
use Drupal\uc_order\UcOrderInterface;
use Drupal\uc_store\Address;

/**
 * Provides a generic address pane that can be extended as required.
 */
abstract class AddressPaneBase extends CheckoutPanePluginBase {

  /**
   * Source pane for "copy address" checkbox.
   */
  protected static $sourcePaneId;

  /**
   * Returns additional text to display as a description.
   *
   * @return string
   *   The fieldset description.
   */
  abstract protected function getDescription();

  /**
   * Returns text to display for the 'copy address' field.
   *
   * @return string
   *   The text to display.
   */
  abstract protected function getCopyAddressText();

  /**
   * {@inheritdoc}
   */
  public function view(UcOrderInterface $order, array $form, FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $cart_config = \Drupal::config('uc_cart.settings');
    $pane = $this->pluginDefinition['id'];
    $source = $this->sourcePaneId();

    $contents['#description'] = $this->getDescription();

    if ($source != $pane) {
      $contents['copy_address'] = array(
        '#type' => 'checkbox',
        '#title' => $this->getCopyAddressText(),
        '#default_value' => $cart_config->get('default_same_address'),
        '#ajax' => array(
          'callback' => array($this, 'ajaxRender'),
          'wrapper' => $pane . '-address-pane',
          'progress' => array(
            'type' => 'throbber',
          ),
        ),
      );
    }

    if ($user->isAuthenticated() && $addresses = uc_select_addresses($user->id(), $pane)) {
      $contents['select_address'] = array(
        '#type' => 'select',
        '#title' => t('Saved addresses'),
        '#options' => $addresses['#options'],
        '#ajax' => array(
          'callback' => array($this, 'ajaxRender'),
          'wrapper' => $pane . '-address-pane',
          'progress' => array(
            'type' => 'throbber',
          ),
        ),
        '#states' => array(
          'invisible' => array(
            'input[name="panes[' . $pane . '][copy_address]"]' => array('checked' => TRUE),
          ),
        ),
      );
    }

    $contents['address'] = array(
      '#type' => 'uc_address',
      '#default_value' => $order->getAddress($pane),
      '#prefix' => '<div id="' . $pane . '-address-pane">',
      '#suffix' => '</div>',
    );

    if ($form_state->hasValue(['panes', $pane, 'copy_address'])) {
      $contents['address']['#hidden'] = !$form_state->isValueEmpty(['panes', $pane, 'copy_address']);
    }
    elseif (isset($contents['copy_address'])) {
      $contents['address']['#hidden'] = $cart_config->get('default_same_address');
    }

    if ($element = $form_state->getTriggeringElement()) {
      $input = $form_state->getUserInput();

      if ($element['#name'] == "panes[$pane][copy_address]") {
        $address = &$form_state->getValue(['panes', $source]);
        foreach ($address as $field => $value) {
          if (substr($field, 0, strlen($source)) == $source) {
            $field = str_replace($source, $pane, $field);
            $input['panes'][$pane][$field] = $value;
            $order->$field = $value;
          }
        }
      }

      if ($element['#name'] == "panes[$pane][select_address]") {
        $address = $addresses[$element['#value']];
        foreach ($address as $field => $value) {
          $input['panes'][$pane][$pane . '_' . $field] = $value;
          $order->{$pane . '_' . $field} = $value;
        }
      }

      $form_state->setUserInput($input);

      // Forget any previous Ajax submissions, as we send new default values.
      $form_state->set('uc_address', NULL);
    }

    return $contents;
  }

  /**
   * {@inheritdoc}
   */
  public function process(UcOrderInterface $order, array $form, FormStateInterface $form_state) {
    $pane = $this->pluginDefinition['id'];
    $source = $this->sourcePaneId();

    $address = new Address;
    $panes = &$form_state->getValue('panes');
    foreach ($panes[$pane] as $field => $value) {
      if (isset($address->$field)) {
        if (!empty($panes[$pane]['copy_address'])) {
          $address->$field = $panes[$source][$field];
        }
        else {
          $address->$field = $value;
        }
      }
    }
    $order->setAddress($pane, $address);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function review(UcOrderInterface $order) {
    $pane = $this->pluginDefinition['id'];
    $address = $order->getAddress($pane);
    $review[] = array('title' => t('Address'), 'data' => $address);
    if (uc_address_field_enabled('phone') && !empty($address->phone)) {
      $review[] = array('title' => t('Phone'), 'data' => String::checkPlain($address->phone));
    }
    return $review;
  }

  /**
   * Returns the ID of the source (first) address pane for copying.
   */
  protected function sourcePaneId() {
    if (!isset(self::$sourcePaneId)) {
      self::$sourcePaneId = $this->pluginDefinition['id'];
    }
    return self::$sourcePaneId;
  }

  /**
   * Ajax callback to re-render the full address element.
   */
  public function ajaxRender($form, FormStateInterface $form_state) {
    $element = &$form;
    $triggering_element = $form_state->getTriggeringElement();
    foreach (array_slice($triggering_element['#array_parents'], 0, -1) as $field) {
      $element = &$element[$field];
    }
    return $element['address'];
  }

}
