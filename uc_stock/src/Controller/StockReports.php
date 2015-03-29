<?php

/**
 * @file
 * Contains \Drupal\uc_stock\Controller\StockReports.
 */

namespace Drupal\uc_stock\Controller;

use Drupal\Component\Utility\String;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

/**
 * Displays a stock report for products with stock tracking enabled.
 */
class StockReports {

  /**
   * {@inheritdoc}
   */
  public static function report() {

    //$page_size = (isset($_GET['nopage'])) ? UC_REPORTS_MAX_RECORDS : variable_get('uc_reports_table_size', 30);
    $page_size = 30;
    $csv_rows = array();
    $rows = array();

    $header = array(
      array('data' => $this->t('SKU'), 'field' => 'sku', 'sort' => 'asc'),
      array('data' => $this->t('Product'), 'field' => 'title'),
      array('data' => $this->t('Stock'), 'field' => 'stock'),
      array('data' => $this->t('Threshold'), 'field' => 'threshold'),
      array('data' => $this->t('Operations')),
    );

    $csv_rows[] = array($this->t('SKU'), $this->t('Product'), $this->t('Stock'), $this->t('Threshold'));

    $query = db_select('uc_product_stock', 's')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender')
      ->orderByHeader($header)
      ->limit($page_size)
      ->fields('s', array(
        'nid',
        'sku',
        'stock',
        'threshold',
      ));

    $query->leftJoin('node_field_data', 'n', 's.nid = n.nid');
    $query->addField('n', 'title');
    $query->condition('active', 1)
      ->condition('title', '', '<>');


    if (arg(4) == 'threshold') {
      $query->where('threshold >= stock');
    }

    $result = $query->execute();
    foreach ($result as $stock) {
      $op = array();
      if ($this->currentUser()->hasPermission('administer product stock')) {
        $op[] = $this->l($this->t('edit'), new Url('uc_stock.edit', ['node' => $stock->nid], ['query' => ['destination' => 'admin/store/reports/stock']]));
      }

      // Add the data to a table row for display.
      $rows[] = array(
        'data' => array(
          array('data' => $stock->sku),
          array('data' => $this->l($stock->title, new Url('uc_stock.edit', ['node' => $stock->nid]))),
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
      '#type' => 'pager',
    );

    $build['links'] = array(
      '#prefix' => '<div class="uc-reports-links">',
      '#suffix' => '</div>',
    );
    $build['links']['export_csv'] = array(
      '#markup' => $this->l($this->t('Export to CSV file'), new Url('admin/store/reports/getcsv/' . $csv_data['report'] . '/' . $csv_data['user'])),
      '#suffix' => '&nbsp;&nbsp;&nbsp;',
    );

//    if (isset($_GET['nopage'])) {
//      $build['links']['toggle_pager'] = array(
//        '#markup' => $this->l($this->t('Show paged records'), new Url('admin/store/reports/stock')),
//      );
//    }
//    else {
      $build['links']['toggle_pager'] = array(
        '#markup' => $this->l($this->t('Show all records'), new Url('admin/store/reports/stock'), [], ['query' => ['nopage' => '1']]),
      );
//    }

    return $build;
  }
}
