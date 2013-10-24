<?php

/**
 * @file
 * Attribute administration menu items.
 */

/**
 * Formats an attribute and its options.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @ingroup themeable
 */
function theme_uc_attribute_options_form($variables) {
  $form = $variables['form'];

  $header = array(t('Name'), t('Default cost'), t('Default price'), t('Default weight'), array('data' => t('List position'), 'sort' => 'asc'), array('data' => t('Operations'), 'colspan' => 2));

  $rows = array();
  foreach (element_children($form['options']) as $oid) {
    $rows[] = array(
      'data' => array(
        drupal_render($form['options'][$oid]['name']),
        drupal_render($form['options'][$oid]['cost']),
        drupal_render($form['options'][$oid]['price']),
        drupal_render($form['options'][$oid]['weight']),
        drupal_render($form['options'][$oid]['ordering']),
        drupal_render($form['options'][$oid]['edit']),
        drupal_render($form['options'][$oid]['delete']),
      ),
      'class' => array('draggable'),
    );
  }

  drupal_add_tabledrag('uc-attribute-option-table', 'order', 'sibling', 'uc-attribute-option-table-ordering');

  $output = theme('table', array(
    'header' => $header,
    'rows' => $rows,
    'attributes' => array('id' => 'uc-attribute-option-table'),
    'empty' => t('No options for this attribute have been added yet.'),
  ));

  $output .= drupal_render_children($form);

  return $output;
}

/**
 * Displays the formatted attribute form.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @see uc_object_attributes_form()
 * @ingroup themeable
 */
function theme_uc_object_attributes_form($variables) {
  $form = $variables['form'];

  $output = '';

  if ($form['view']['#value'] == 'overview') {
    $header = array(t('Remove'), t('Name'), t('Label'), t('Default'), t('Required'), t('List position'), t('Display'));

    $rows = array();
    foreach (element_children($form['attributes']) as $aid) {
      $rows[] = array(
        'data' => array(
          drupal_render($form['attributes'][$aid]['remove']),
          drupal_render($form['attributes'][$aid]['name']),
          drupal_render($form['attributes'][$aid]['label']),
          drupal_render($form['attributes'][$aid]['option']),
          drupal_render($form['attributes'][$aid]['required']),
          drupal_render($form['attributes'][$aid]['ordering']),
          drupal_render($form['attributes'][$aid]['display']),
        ),
        'class' => array('draggable'),
      );
    }

    drupal_add_tabledrag('uc-attribute-table', 'order', 'sibling', 'uc-attribute-table-ordering');

    $output = theme('table', array(
      'header' => $header,
      'rows' => $rows,
      'attributes' => array('id' => 'uc-attribute-table'),
      'empty' => t('No attributes available.'),
    ));
  }
  else {
    $output = '<div class="uc-attributes-add-link">';
    $output .= t('You may add more attributes <a href="!url">here</a>.', array('!url' => url('admin/store/products/attributes/add')));
    $output .= '</div>';
  }

  $output .= drupal_render_children($form);

  return $output;
}

/**
 * Displays the option form.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @see uc_object_options_form()
 * @ingroup themeable
 */
function theme_uc_object_options_form($variables) {
  $form = $variables['form'];
  $output = '';

  drupal_add_js('misc/tableselect.js');
  $header = array(array('data' => '&nbsp;&nbsp;' . t('Options'), 'class' => array('select-all')), t('Default'), t('Cost'), t('Price'), t('Weight'), t('List position'));
  $tables = 0;

  if (isset($form['attributes'])) {
    foreach (element_children($form['attributes']) as $key) {
      $rows = array();
      foreach (element_children($form['attributes'][$key]['options']) as $oid) {
        $rows[] = array(
          'data' => array(
            drupal_render($form['attributes'][$key]['options'][$oid]['select']),
            drupal_render($form['attributes'][$key]['default'][$oid]),
            drupal_render($form['attributes'][$key]['options'][$oid]['cost']),
            drupal_render($form['attributes'][$key]['options'][$oid]['price']),
            drupal_render($form['attributes'][$key]['options'][$oid]['weight']),
            drupal_render($form['attributes'][$key]['options'][$oid]['ordering']),
          ),
          'class' => array('draggable'),
        );
      }

      $table_id = 'uc-attribute-option-table-' . $tables++;
      drupal_add_tabledrag($table_id, 'order', 'sibling', 'uc-attribute-option-table-ordering');

      $output .= theme('table', array(
        'header' => $header,
        'rows' => $rows,
        'attributes' => array(
          'class' => array('product_attributes'),
          'id' => $table_id,
        ),
        'caption' => '<h2>' . drupal_render($form['attributes'][$key]['name']) . '</h2>',
        'empty' => t('This attribute does not have any options.'),
      ));
    }
  }

  if (!$tables) {
    drupal_set_message(t('No attributes available.'), 'warning');
  }

  $output .= drupal_render_children($form);

  return $output;
}

/**
 * Returns a themed set of attribute options for use in order displays.
 *
 * @param $variables
 *   An associative array containing:
 *   - attributes: An associative array containing the set of attributes,
 *     with each element keyed by attribute ID:
 *     - <aid>: An associative array containing:
 *       - #attribute_name: Attribute name.
 *       - #options: Array of option names.
 *
 * @return
 *   Themed set of attribute options.
 *
 * @ingroup themeable
 */
function theme_uc_product_attributes($variables) {
  $attributes = $variables['attributes'];

  $option_rows = array();

  foreach (element_children($attributes) as $key) {
    $optionstr = '';

    foreach ((array)$attributes[$key]['#options'] as $option) {
      // We only need to allow translation from the second option onward
      if (empty($optionstr)) {
        $optionstr .= $option;
      }
      else {
        $optionstr .= t(', !option', array('!option' => $option));
      }
    }

    if ($optionstr != '') {
      $option_rows[$key] = t('@attribute: @option', array('@attribute' => $attributes[$key]['#attribute_name'], '@option' => $optionstr));
    }
  }

  if (!empty($option_rows)) {
    return theme('item_list', array('items' => array_values($option_rows), 'attributes' => array('class' => array('product-description'))));
  }

  return '';
}
