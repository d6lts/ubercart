<?php

/**
 * @file
 * Product administration menu items.
 */

use Drupal\Core\Database\Query\Merge;

/**
 * Displays the product features tab on a product node edit form.
 */
function uc_product_features($node, $fid = NULL, $pfid = NULL) {
  drupal_set_title($node->label());

  $header = array(t('Type'), t('Description'), t('Operations'));
  $rows = array();

  $features = uc_product_feature_load_multiple($node->id());
  foreach ($features as $feature) {
    $operations = array(
      'edit' => array('title' => t('edit'), 'href' => 'node/' . $node->id() . '/edit/features/' . $feature->fid . '/' . $feature->pfid),
      'delete' => array('title' => t('delete'), 'href' => 'node/' . $node->id() . '/edit/features/' . $feature->fid . '/' . $feature->pfid . '/delete'),
    );
    $rows[] = array(
      array('data' => uc_product_feature_data($feature->fid, 'title')),
      array('data' => $feature->description),
      theme('links', array(
        'links' => $operations,
        'attributes' => array('class' => array('links', 'inline')),
      )),
    );
  }

  $build['features'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#attributes' => array('class' => array('uc-product-features')),
    '#empty' => t('No features found for this product.'),
  );
  $build['add_form'] = drupal_get_form('uc_product_feature_add_form', $node);

  return $build;
}

/**
 * Handles adding or editing product features.
 */
function uc_product_feature_edit($node, $fid, $pfid) {
  $func = uc_product_feature_data($fid, 'callback');

  if (function_exists($func)) {
    $form_state = array(
      'build_info' => array(
        'args' => array(
          $node,
        ),
      ),
      'wrapper_callback' => 'uc_product_feature_form',
    );

    if ($pfid == 'add') {
      $form_state['build_info']['args'][] = array();
      $build = drupal_build_form($func, $form_state);
    }
    elseif (intval($pfid) > 0) {
      $feature = uc_product_feature_load($pfid);

      if (isset($feature)) {
        $form_state['build_info']['args'][] = $feature;
        $build = drupal_build_form($func, $form_state);
      }
    }
    else {
      drupal_goto('node/' . $node->id() . '/edit/features');
    }
  }
  else {
    drupal_set_message(t('Error: Attempted to add a non-existent product feature type.'), 'error');
    drupal_goto('node/' . $node->id() . '/edit/features');
  }

  if (empty($build)) {
    drupal_set_message(t('Error: No form data was returned for that operation.'), 'error');
    drupal_goto('node/' . $node->id() . '/edit/features');
  }

  return $build;
}

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
 * Theme function for uc_product_feature_add_form().
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @see uc_product_feature_add_form()
 * @ingroup themeable
 */
function theme_uc_product_feature_add_form($variables) {
  return '<table class="add-feature"><tr><td>' . drupal_render_children($variables['form']) . '</td></tr></table>';
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

/**
 * Confirmation form to delete a product feature.
 *
 * @see uc_product_feature_confirm_delete_submit()
 * @ingroup forms
 */
function uc_product_feature_confirm_delete($form, &$form_state, $node, $fid, $feature) {
  $description = t('Are you sure you wish to delete this %feature?', array('%feature' => uc_product_feature_data($fid, 'title')))
    . '<div><b>' . t('Description') . ':</b><br />' . $feature['description'] . '</div><br />';

  return confirm_form($form, check_plain($node->label()), 'node/' . $node->id() . '/edit/features', $description, t('Delete'), t('Cancel'), 'pf_delete');
}

/**
 * Form submission handler for uc_product_feature_confirm_delete().
 *
 * @see uc_product_feature_confirm_delete()
 */
function uc_product_feature_confirm_delete_submit($form, &$form_state) {
  $node = $form_state['build_info']['args'][0];
  $feature = $form_state['build_info']['args'][2];

  if ($form_state['values']['pf_delete']) {
    uc_product_feature_delete($feature['pfid']);

    drupal_set_message(t('The product feature has been deleted.'));
  }

  $form_state['redirect'] = 'node/' . $node->id() . '/edit/features';
}

/**
 * Sets up image field for products.
 *
 * @see uc_product_uc_store_status()
 */
function uc_product_image_defaults() {
  uc_product_add_default_image_field();

  drupal_set_message(t('Default image support configured for Ubercart products.'));

  drupal_goto('admin/store');
}
