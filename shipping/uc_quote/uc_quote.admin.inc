<?php

/**
 * @file
 * Shipping quotes administration menu items.
 */

/**
 * Displays a formatted list of shipping quote methods and form elements.
 *
 * @see uc_quote_method_settings()
 * @ingroup themeable
 */
function theme_uc_quote_method_settings($variables) {
  $form = $variables['form'];

  drupal_add_tabledrag('uc-quote-methods', 'order', 'sibling', 'uc-quote-method-weight');

  $header = array(t('Shipping method'), t('Details'),  array('data' => t('List position'), 'sort' => 'asc'), t('Operations'));
  $rows = array();
  foreach (element_children($form['methods']) as $method) {
    $row = array(
      drupal_render($form['methods'][$method]['uc_quote_enabled']),
      drupal_render($form['methods'][$method]['description']),
      drupal_render($form['methods'][$method]['uc_quote_method_weight']),
      drupal_render($form['methods'][$method]['operations']),
    );

    $rows[] = array(
      'data' => $row,
      'class' => array('draggable'),
    );
  }

  $output = theme('table', array(
    'header' => $header,
    'rows' => $rows,
    'attributes' => array('id' => 'uc-quote-methods'),
    'empty' => t('No shipping quotes have been configured yet.'),
  ));
  $output .= drupal_render_children($form);
  return $output;
}
