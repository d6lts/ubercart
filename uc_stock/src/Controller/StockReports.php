<?php

/**
 * @file
 * Contains \Drupal\uc_stock\Controller\StockReports.
 */

namespace Drupal\uc_stock\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\uc_report\Controller\Reports;

/**
 * Displays a stock report for products with stock tracking enabled.
 */
class StockReports extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function report() {

    //$page_size = (isset($_GET['nopage'])) ? UC_REPORT_MAX_RECORDS : variable_get('uc_report_table_size', 30);
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


    // @todo: Replace arg()
    //if (arg(4) == 'threshold') {
    //  $query->where('threshold >= stock');
    //}

    $result = $query->execute();
    foreach ($result as $stock) {
      $op = array();
      if ($this->currentUser()->hasPermission('administer product stock')) {
        $op[] = Link::createFromRoute($this->t('edit'), 'uc_stock.edit', ['node' => $stock->nid], ['query' => ['destination' => 'admin/store/reports/stock']])->toString();
      }

      // Add the data to a table row for display.
      $rows[] = array(
        'data' => array(
          array('data' => $stock->sku),
          array('data' => Link::createFromRoute($stock->title, 'uc_stock.edit', ['node' => $stock->nid])->toString()),
          array('data' => $stock->stock),
          array('data' => $stock->threshold),
          array('data' => implode(' ', $op)),
        ),
        'class' => array(($stock->threshold >= $stock->stock) ? 'uc-stock-below-threshold' : 'uc-stock-above-threshold'),
      );

      // Add the data to the CSV contents for export.
      $csv_rows[] = array($stock->sku, $stock->title, $stock->stock, $stock->threshold);
    }

    // Cache the CSV export.
    $controller = new Reports();
    $csv_data = $controller->store_csv('uc_stock', $csv_rows);

    $build['form'] = $this->formBuilder()->getForm('\Drupal\uc_stock\Form\StockReportForm');
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
      '#markup' => Link::createFromRoute($this->t('Export to CSV file'), 'uc_report.getcsv', ['report_id' => $csv_data['report'], 'user_id' => $csv_data['user']])->toString(),
      '#suffix' => '&nbsp;&nbsp;&nbsp;',
    );

//    if (isset($_GET['nopage'])) {
//      $build['links']['toggle_pager'] = array(
//        '#markup' => Link::createFromRoute($this->t('Show paged records'), 'uc_stock.reports')->toString(),
//      );
//    }
//    else {
      $build['links']['toggle_pager'] = array(
        '#markup' => Link::createFromRoute($this->t('Show all records'), 'uc_stock.reports', [], ['query' => ['nopage' => '1']])->toString(),
      );
//    }

    return $build;
  }
}
