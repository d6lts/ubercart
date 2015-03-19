<?php

/**
 * @file
 * Contains \Drupal\uc_tax_report\SalesTaxReport.
 */

namespace Drupal\uc_tax_report\Controller;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Displays sales tax report.
 *
 * @return
 *   Renderable form array.
 */
class SalesTaxReport {

  /**
   * Displays the sales tax report form and table.
   */
  public static function report($start_date = NULL, $end_date = NULL, $status = NULL) {
    // Use default report parameters if we don't detect values in the URL.
    if ($start_date == '') {
      $args = array(
        'start_date' => mktime(0, 0, 0, date('n'), 1, date('Y') - 1),
        'end_date' => REQUEST_TIME,
        'status' => FALSE,
      );
    }
    else {
      $args = array(
        'start_date' => $start_date,
        'end_date' => $end_date,
        'status' => explode(',', $status),
      );
    }

    // Pull the order statuses into a SQL friendly array.
    if ($args['status'] === FALSE) {
      $order_statuses = uc_reports_order_statuses();
    }
    else {
      $order_statuses = $args['status'];
    }

    // Build the header for the report table.
    $header = array(t('Tax Name'), t('Jurisdiction'), t('Tax rate'), t('Total taxable amount'), t('Total tax collected'));
    $rows = array();
    $csv_rows = array();
    $csv_rows[] = $header;

    // Query to get the tax line items in this date range

    $result = db_query("SELECT ucoli.amount, ucoli.title, ucoli.data FROM {uc_orders} ucord LEFT JOIN {uc_order_statuses} ON order_status_id = order_status LEFT JOIN {uc_order_line_items} ucoli ON ucord.order_id = ucoli.order_id WHERE :start <= created AND created <= :end AND order_status IN (:statuses[]) AND ucoli.type = :type", array(':start' => $args['start_date'], ':end' => $args['end_date'], ':statuses[]' => $order_statuses, ':type' => 'tax'));

    // add up the amounts by jurisdiction
    $totals = array();
    $no_meta_totals = array();

    foreach ($result as $item) {
      $name = trim($item->title);
      $amount = floatval($item->amount);

      // get the meta-data out of the serialized array
      $data = unserialize($item->data);
      $jurisdiction = trim($data['tax_jurisdiction']);
      $taxable_amount = floatval($data['taxable_amount']);
      $rate = floatval($data['tax_rate']);

      // make a line item in the report for each name/jurisdiction/rate
      $key = strtolower($name) . strtolower($jurisdiction) . number_format($rate, 5);

      if (!empty($jurisdiction) && $amount && $taxable_amount) {
        // we have meta-data
        if (empty($totals[$key])) {
          $totals[$key] = array(
            'name' => $name,
            'jurisdiction' => $jurisdiction,
            'rate' => $rate,
            'taxable_amount' => $taxable_amount,
            'amount' => $amount,
          );
        }
        else {
          $totals[$key]['taxable_amount'] += $taxable_amount;
          $totals[$key]['amount'] += $amount;
        }
      }
      elseif ($amount) {
        // Old data: no meta-data was stored. Just report the amount collected.
        if (empty($no_meta_totals[$key])) {
          $no_meta_totals[$key] = array(
            'name' => $name,
            'amount' => $amount,
          );
        }
        else {
          $no_meta_totals[$key]['amount'] += $amount;
        }
      }
    }

    // sort and make this into a report

    ksort($totals);
    ksort($no_meta_totals);

    $taxable_amount = 0;
    $amount = 0;
    $star_legend = '';

    foreach ($totals as $line) {
      $row = array(
        $line['name'],
        $line['jurisdiction'],
        number_format($line['rate'] * 100, 3) . '%',
        theme('uc_price', array('price' => $line['taxable_amount'])),
        theme('uc_price', array('price' => $line['amount'])),
      );
      $rows[] = $row;
      // Remove HTML for CSV files.
      $row[3] = $line['taxable_amount'];
      $row[4] = $line['amount'];
      $csv_rows[] = $row;
      $taxable_amount += $line['taxable_amount'];
      $amount += $line['amount'];
    }

    foreach ($no_meta_totals as $line) {
      $row = array(
        $line['name'],
        '*',
        '*',
        '*',
        theme('uc_price', array('price' => $line['amount'])),
      );
      $rows[] = $row;
      // Remove HTML for CSV files.
      $row[4] = $line['amount'];
      $csv_rows[] = $row;
      $amount += $line['amount'];
      // We have at least one no-meta-data line. Explain why.
      $star_legend = t('* No information on jurisdiction, tax rate, or taxable amount is available for this line.');
    }

    // Add a totals line.
    $row = array(
      t('Total'),
      '',
      '',
      theme('uc_price', array('price' => $taxable_amount)),
      theme('uc_price', array('price' => $amount)),
    );
    $rows[] = $row;
    // Removes HTML for CSV files.
    $row[3] = $taxable_amount;
    $row[4] = $amount;
    $csv_rows[] = $row;

    // Cache the CSV export.
    module_load_include('inc', 'uc_reports', 'uc_reports.admin');
    $csv_data = uc_reports_store_csv('uc_tax_report', $csv_rows);

    // Build the page output holding the form, table, and CSV export link.
    $build['form'] = \Drupal::formBuilder()->getForm('uc_tax_report_params_form', $args, $args['status']);
    $build['report'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array('width' => '100%', 'class' => array('uc-sales-table')),
    );

    if ($star_legend) {
      $build['legend'] = array(
        '#markup' => $star_legend,
        '#prefix' => '<div class="uc-reports-note"><p>',
        '#suffix' => '</p></div>',
      );
    }

    $build['export_csv'] = array(
      '#markup' => \Drupal::l(t('Export to CSV file.'), new Url('admin/store/reports/getcsv/' . $csv_data['report'] . '/' . $csv_data['user'])),
      '#prefix' => '<div class="uc-reports-links">',
      '#suffix' => '</div>',
    );

    return $build;
  }
}
