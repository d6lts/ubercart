<?php

/**
 * @file
 * Stock administration menu items.
 */

/**
 * Displays a stock report for products with stock tracking enabled.
 */
function uc_stock_report() {
  $page_size = (isset($_GET['nopage'])) ? UC_REPORTS_MAX_RECORDS : variable_get('uc_reports_table_size', 30);
  $csv_rows = array();
  $rows = array();

  $header = array(
    array('data' => t('SKU'), 'field' => 'sku', 'sort' => 'asc'),
    array('data' => t('Product'), 'field' => 'title'),
    array('data' => t('Stock'), 'field' => 'stock'),
    array('data' => t('Threshold'), 'field' => 'threshold'),
    array('data' => t('Operations')),
  );

  $csv_rows[] = array(t('SKU'), t('Product'), t('Stock'), t('Threshold'));

  $query = db_select('uc_product_stock', 's')
    ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
    ->extend('Drupal\Core\Database\Query\TableSortExtender')
    ->orderByHeader($header)
    ->limit($page_size)
    ->fields('s', array(
      'nid',
      'sku',
      'stock',
      'threshold',
    ));

  $query->leftJoin('node', 'n', 's.nid = n.nid');
  $query->addField('n', 'title');
  $query->condition('active', 1)
    ->condition('title', '', '<>');


  if (arg(4) == 'threshold') {
    $query->where('threshold >= stock');
  }

  $result = $query->execute();
  foreach ($result as $stock) {
    $op = array();
    if (\Drupal::currentUser()->hasPermission('administer product stock')) {
      $op[] = l(t('edit'), 'node/' . $stock->nid . '/edit/stock', $options = array('query' => array('destination' => 'admin/store/reports/stock')));
    }

    // Add the data to a table row for display.
    $rows[] = array(
      'data' => array(
        array('data' => $stock->sku),
        array('data' => l($stock->title, 'node/' . $stock->nid)),
        array('data' => $stock->stock),
        array('data' => $stock->threshold),
        array('data' => implode(' ', $op)),
      ),
      'class' => array(($stock->threshold >= $stock->stock) ? 'uc-stock-below-threshold' : 'uc-stock-above-threshold'),
    );

    // Add the data to the CSV contents for export.
    $csv_rows[] = array($stock->sku, $stock->title, $stock->stock, $stock->threshold);
  }

  module_load_include('inc', 'uc_reports', 'uc_reports.admin');
  $csv_data = uc_reports_store_csv('uc_stock', $csv_rows);

  $build['form'] = drupal_get_form('uc_stock_report_form');
  $build['report'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#attributes' => array('width' => '100%', 'class' => array('uc-stock-table')),
  );
  $build['pager'] = array(
    '#theme' => 'pager',
  );

  $build['links'] = array(
    '#prefix' => '<div class="uc-reports-links">',
    '#suffix' => '</div>',
  );
  $build['links']['export_csv'] = array(
    '#markup' => l(t('Export to CSV file'), 'admin/store/reports/getcsv/' . $csv_data['report'] . '/' . $csv_data['user']),
    '#suffix' => '&nbsp;&nbsp;&nbsp;',
  );

  if (isset($_GET['nopage'])) {
    $build['links']['toggle_pager'] = array(
      '#markup' => l(t('Show paged records'), 'admin/store/reports/stock'),
    );
  }
  else {
    $build['links']['toggle_pager'] = array(
      '#markup' => l(t('Show all records'), 'admin/store/reports/stock', array('query' => array('nopage' => '1'))),
    );
  }

  return $build;
}

/**
 * Form builder for stock report threshold filter.
 *
 * @see uc_stock_report_form_submit()
 * @ingroup forms
 */
function uc_stock_report_form($form, &$form_state) {
  $form['threshold'] = array(
    '#type' => 'checkbox',
    '#title' => t('Only show SKUs that are below their threshold.'),
    '#default_value' => arg(4) == 'threshold' ? TRUE : FALSE,
    '#attributes' => array('onchange' => 'this.form.submit();'),
  );

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Update'),
    '#attributes' => array('style' => "display:none;"),
  );

  return $form;
}

/**
 * Form submission handler for uc_stock_report_form().
 *
 * @see uc_stock_report_form()
 */
function uc_stock_report_form_submit($form, &$form_state) {
  if ($form_state['values']['threshold']) {
    drupal_goto('admin/store/reports/stock/threshold');
  }
  else {
    drupal_goto('admin/store/reports/stock');
  }
}
