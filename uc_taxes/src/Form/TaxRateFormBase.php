<?php

/**
 * @file
 * Contains \Drupal\uc_taxes\Form\TaxRateFormBase.
 */

namespace Drupal\uc_taxes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the tax rate add/edit form.
 */
abstract class TaxRateFormBase extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_taxes_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#description' => t('This name will appear to the customer when this tax is applied to an order.'),
      '#required' => TRUE,
    );

    $form['rate'] = array(
      '#type' => 'textfield',
      '#title' => t('Rate'),
      '#description' => t('The tax rate as a percent or decimal. Examples: 6%, .06'),
      '#size' => 15,
      '#required' => TRUE,
    );

    $form['shippable'] = array(
      '#type' => 'radios',
      '#title' => t('Taxed products'),
      '#options' => array(
        t('Apply tax to any product regardless of its shippability.'),
        t('Apply tax to shippable products only.'),
      ),
      '#default_value' => 0,
    );

    // TODO: Remove the need for a special case for product kit module.
    $options = array();
    foreach (node_type_get_names() as $type => $name) {
      if ($type != 'product_kit' && uc_product_is_product($type)) {
        $options[$type] = $name;
      }
    }
    $options['blank-line'] = t('"Blank line" product');

    $form['taxed_product_types'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Taxed product types'),
      '#description' => t('Apply taxes to the specified product types/classes.'),
      '#options' => $options,
    );

    $options = array();
    foreach (_uc_line_item_list() as $id => $line_item) {
      if (!in_array($id, array('subtotal', 'tax_subtotal', 'total', 'tax_display'))) {
        $options[$id] = $line_item['title'];
      }
    }

    $form['taxed_line_items'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Taxed line items'),
      '#description' => t('Adds the checked line item types to the total before applying this tax.'),
      '#options' => $options,
    );

    $form['weight'] = array(
      '#type' => 'weight',
      '#title' => t('Weight'),
      '#description' => t('Taxes are sorted by weight and then applied to the order sequentially. This value is important when taxes need to include other tax line items.'),
    );

    $form['display_include'] = array(
      '#type' => 'checkbox',
      '#title' => t('Include this tax when displaying product prices.'),
    );

    $form['inclusion_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Tax inclusion text'),
      '#description' => t('This text will be displayed near the price to indicate that it includes tax.'),
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#suffix' => l(t('Cancel'), 'admin/store/settings/taxes'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!empty($form_state['values']['rate']) && (floatval($form_state['values']['rate']) < 0)) {
      $form_state->setErrorByName('rate', t('Rate must be a positive number. No commas and only one decimal point.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Determine the decimal rate value.
    if (strpos($form_state['values']['rate'], '%')) {
      $form_state['values']['rate'] = floatval($form_state['values']['rate']) / 100;
    }
    else {
      $form_state['values']['rate'] = floatval($form_state['values']['rate']);
    }

    // Build the rate object based on the form values and save it.
    $rate = (object) array(
      'id' => $form_state['values']['id'],
      'name' => $form_state['values']['name'],
      'rate' => $form_state['values']['rate'],
      'taxed_product_types' => array_filter($form_state['values']['taxed_product_types']),
      'taxed_line_items' => array_filter($form_state['values']['taxed_line_items']),
      'weight' => $form_state['values']['weight'],
      'shippable' => $form_state['values']['shippable'],
      'display_include' => $form_state['values']['display_include'],
      'inclusion_text' => $form_state['values']['inclusion_text'],
    );
    return uc_taxes_rate_save($rate);

    // Update the name of the associated conditions.
    // $conditions = rules_config_load('uc_taxes_' . $form_state['values']['id']);
    // if ($conditions) {
    //   $conditions->label = $form_state['values']['name'];
    //   $conditions->save();
    // }
  }

}
