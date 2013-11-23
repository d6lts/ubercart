<?php

/**
 * @file
 * Product administration menu items.
 */

use Drupal\Core\Database\Query\Merge;

/**
 * Adds the form for adding a product feature to the features tab.
 *
 * @see uc_product_feature_add_form_submit()
 * @see theme_uc_product_feature_add_form()
 * @ingroup forms
 */
function uc_product_feature_add_form($form, &$form_state, $node) {
  foreach (module_invoke_all('uc_product_feature') as $feature) {
    $options[$feature['id']] = $feature['title'];
  }
  ksort($options);

  $form['feature'] = array(
    '#type' => 'select',
    '#title' => t('Add a new feature'),
    '#options' => $options,
  );

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Add'),
  );

  return $form;
}

/**
 * Form submission handler for uc_product_feature_add_form().
 *
 * @see uc_product_feature_add_form()
 */
function uc_product_feature_add_form_submit($form, &$form_state) {
  $node = $form_state['build_info']['args'][0];

  $form_state['redirect'] = 'node/' . $node->id() . '/edit/features/' . $form_state['values']['feature'] . '/add';
}
