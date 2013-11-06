<?php

/**
 * @file
 * Contains \Drupal\uc_quote\Form\ShippingQuoteMethodsForm.
 */

namespace Drupal\uc_quote\Form;

use Drupal\Core\Form\FormBase;

/**
 * Settings for the shipping quote methods.
 */
class ShippingQuoteMethodsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'uc_quote_method_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['methods'] = array(
      '#type' => 'table',
      '#header' => array(t('Shipping method'), t('Details'), t('List position'), t('Operations')),
      '#tabledrag' => array(
        array('order', 'sibling', 'uc-quote-method-weight'),
      ),
      '#empty' => t('No shipping quotes have been configured yet.'),
    );

    foreach (uc_quote_methods(TRUE) as $method) {
      if (isset($method['quote'])) {
        $id = $method['id'];

        // Build a list of operations links.
        $operations = isset($method['operations']) ? $method['operations'] : array();
//        $operations += array('conditions' => array(
//          'title' => t('conditions'),
//          'href' => 'admin/store/settings/quotes/manage/get_quote_from_' . $id,
//          'weight' => 5,
//        ));

        // Ensure "delete" comes towards the end of the list.
        if (isset($operations['delete'])) {
          $operations['delete']['weight'] = 10;
        }
        uasort($operations, 'drupal_sort_weight');

        $form['methods'][$id]['status'] = array(
          '#type' => 'checkbox',
          '#title' => check_plain($method['title']),
          '#default_value' => $method['enabled'],
        );
        $form['methods'][$id]['description'] = array(
          '#markup' => isset($method['description']) ? $method['description'] : '',
        );
        $form['methods'][$id]['weight'] = array(
          '#type' => 'weight',
          '#default_value' => $method['weight'],
          '#attributes' => array('class' => array('uc-quote-method-weight')),
        );
        $form['methods'][$id]['operations'] = array(
          '#type' => 'operations',
          '#links' => $operations,
        );
      }
    }

    $shipping_types = uc_quote_shipping_type_options();
    if (is_array($shipping_types)) {
      $form['uc_quote_type_weight'] = array(
        '#type' => 'details',
        '#title' => t('List position'),
        '#description' => t('Determines which shipping methods are quoted at checkout when products of different shipping types are ordered. Larger values take precedence.'),
        '#collapsible' => TRUE,
        '#tree' => TRUE,
      );
      $weight = variable_get('uc_quote_type_weight', array());
      $shipping_methods = module_invoke_all('uc_shipping_method');
      $method_types = array();
      foreach ($shipping_methods as $method) {
        // Get shipping method types from shipping methods that provide quotes
        if (isset($method['quote'])) {
          $method_types[$method['quote']['type']][] = $method['title'];
        }
      }
      if (isset($method_types['order']) && is_array($method_types['order'])) {
        $count = count($method_types['order']);
        $form['uc_quote_type_weight']['#description'] .= format_plural($count, '<br />The %list method is compatible with any shipping type.', '<br />The %list methods are compatible with any shipping type.', array('%list' => implode(', ', $method_types['order'])));
      }
      foreach ($shipping_types as $id => $title) {
        $form['uc_quote_type_weight'][$id] = array(
          '#type' => 'weight',
          '#title' => $title . (isset($method_types[$id]) && is_array($method_types[$id]) ? ' (' . implode(', ', $method_types[$id]) . ')' : ''),
          '#delta' => 5,
          '#default_value' => isset($weight[$id]) ? $weight[$id] : 0,
        );
      }
    }
    $form['uc_store_shipping_type'] = array(
      '#type' => 'select',
      '#title' => t('Default order fulfillment type for products'),
      '#options' => $shipping_types,
      '#default_value' => variable_get('uc_store_shipping_type', 'small_package'),
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save configuration') );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $enabled = array();
    $method_weight = array();
    foreach ($form_state['values']['methods'] as $id => $method) {
      $enabled[$id] = $method['status'];
      $method_weight[$id] = $method['weight'];
    }

    variable_set('uc_quote_enabled', $enabled);
    variable_set('uc_quote_method_weight', $method_weight);
    variable_set('uc_quote_type_weight', $form_state['values']['uc_quote_type_weight']);
    variable_set('uc_store_shipping_type', $form_state['values']['uc_store_shipping_type']);
    drupal_set_message(t('The configuration options have been saved.'));
  }

}
