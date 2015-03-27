<?php

/**
 * @file
 * Contains \Drupal\uc_tax\Form\TaxRateEditForm.
 */

namespace Drupal\uc_tax\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the tax rate edit form.
 */
class TaxRateEditForm extends TaxRateFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $tax_rate = NULL) {
    $rate = uc_tax_rate_load($tax_rate);

    $form = parent::buildForm($form, $form_state);

    $form['#title'] = SafeMarkup::checkPlain($rate->name);

    $form['id'] = array('#type' => 'value', '#value' => $tax_rate);
    $form['name']['#default_value'] = $rate->name;
    $form['rate']['#default_value'] = ($rate->rate * 100) . '%';
    $form['shippable']['#default_value'] = $rate->shippable;
    $form['taxed_product_types']['#default_value'] = $rate->taxed_product_types;
    $form['taxed_line_items']['#default_value'] = $rate->taxed_line_items;
    $form['weight']['#default_value'] = $rate->weight;
    $form['display_include']['#default_value'] = $rate->display_include;
    $form['inclusion_text']['#default_value'] = $rate->inclusion_text;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $rate = parent::submitForm($form, $form_state);

    drupal_set_message(t('Tax rate %name saved.', ['%name' => $rate->name]));

    $form_state->setRedirect('uc_tax.overview');
  }

}