<?php

/**
 * @file
 * Contains \Drupal\uc_quote\Form\QuoteMethodsForm.
 */

namespace Drupal\uc_quote\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Settings for the shipping quote methods.
 */
class QuoteMethodsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'uc_quote_method_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'uc_quote.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $quote_config = $this->config('uc_quote.settings');
    $form['methods'] = array(
      '#type' => 'table',
      '#header' => array(t('Shipping method'), t('Details'), t('List position'), t('Operations')),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'uc-quote-method-weight',
        ),
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
//          'url' => Url::fromRoute('admin/store/settings/quotes/manage/get_quote_from_', ['id' => $id]),
//          'weight' => 5,
//        ));

        // Ensure "delete" comes towards the end of the list.
        if (isset($operations['delete'])) {
          $operations['delete']['weight'] = 10;
        }
        uasort($operations, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

        $form['methods'][$id]['status'] = array(
          '#type' => 'checkbox',
          '#title' => SafeMarkup::checkPlain($method['title']),
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
        '#tree' => TRUE,
      );
      $weight = $quote_config->get('type_weight');
      $shipping_methods = \Drupal::moduleHandler()->invokeAll('uc_shipping_method');
      $method_types = array();
      foreach ($shipping_methods as $method) {
        // Get shipping method types from shipping methods that provide quotes
        if (isset($method['quote'])) {
          $method_types[$method['quote']['type']][] = $method['title'];
        }
      }
      if (isset($method_types['order']) && is_array($method_types['order'])) {
        $count = count($method_types['order']);
        $form['uc_quote_type_weight']['#description'] .= \Drupal::translation()->formatPlural($count, '<br />The %list method is compatible with any shipping type.', '<br />The %list methods are compatible with any shipping type.', array('%list' => implode(', ', $method_types['order'])));
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
      '#default_value' => $quote_config->get('shipping_type'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $enabled = array();
    $method_weight = array();
    foreach ($form_state->getValue('methods') as $id => $method) {
      $enabled[$id] = $method['status'];
      $method_weight[$id] = $method['weight'];
    }

    $quote_config = $this->config('uc_quote.settings');
    $quote_config
      ->set('enabled', $enabled)
      ->set('method_weight', $method_weight)
      ->set('type_weight', $form_state->getValue('uc_quote_type_weight'))
      ->set('shipping_type', $form_state->getValue('uc_store_shipping_type'))
      ->save();

    return parent::submitForm($form, $form_state);
  }

}
