<?php

/**
 * @file
 * Contains \Drupal\uc_reports\Form\Reports;
 */

namespace Drupal\uc_reports\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Reports {

  /**
   * Form builder for the custom product report.
   *
   * @see uc_reports_products_custom_form_validate()
   * @see uc_reports_products_custom_form_submit()
   * @ingroup forms
   */
  public static function products_custom_form($form, FormStateInterface $form_state, $values) {
    $form['search'] = array(
      '#type' => 'details',
      '#title' => t('Customize product report parameters'),
      '#description' => t('Adjust these values and update the report to build your custom product report. Once submitted, the report may be bookmarked for easy reference in the future.'),
    );

    $form['search']['start_date'] = array(
      '#type' => 'date',
      '#title' => t('Start date'),
      '#default_value' => array(
        'month' => \Drupal::service('date.formatter')->format($values['start_date'], 'custom', 'n'),
        'day' => \Drupal::service('date.formatter')->format($values['start_date'], 'custom', 'j'),
        'year' => \Drupal::service('date.formatter')->format($values['start_date'], 'custom', 'Y'),
      ),
    );
    $form['search']['end_date'] = array(
      '#type' => 'date',
      '#title' => t('End date'),
      '#default_value' => array(
        'month' => \Drupal::service('date.formatter')->format($values['end_date'], 'custom', 'n'),
        'day' => \Drupal::service('date.formatter')->format($values['end_date'], 'custom', 'j'),
        'year' => \Drupal::service('date.formatter')->format($values['end_date'], 'custom', 'Y'),
      ),
    );

    $form['search']['status'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Order statuses'),
      '#description' => t('Only orders with selected statuses will be included in the report.'),
      '#options' => uc_order_status_options_list(),
      '#default_value' => $values['status'],
    );

    $form['search']['actions'] = array('#type' => 'actions');
    $form['search']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Update report'),
    );

    return $form;
  }

  /**
   * Validation handler for the custom product report.
   *
   * @see uc_reports_products_custom_form()
   * @see uc_reports_products_custom_form_submit()
   */
  public static function products_custom_form_validate($form, FormStateInterface $form_state) {
    if ($form_state->isValueEmpty('status')) {
      $form_state->setErrorByName('status', t('You must select at least one order status.'));
    }
  }

  /**
   * Submission handler for the custom product report.
   *
   * @see uc_reports_products_custom_form()
   * @see uc_reports_products_custom_form_submit()
   */
  public static function products_custom_form_submit($form, FormStateInterface $form_state) {
    $start_date = mktime(0, 0, 0, $form_state->getValue(['start_date', 'month']), $form_state->getValue(['start_date', 'day']), $form_state->getValue(['start_date', 'year']));
    $end_date = mktime(23, 59, 59, $form_state->getValue(['end_date', 'month']), $form_state->getValue(['end_date', 'day']), $form_state->getValue(['end_date', 'year']));

    $args = array(
      $start_date,
      $end_date,
      implode(',', array_keys(array_filter($form_state->getValue('status')))),
    );

    $form_state['redirect'] = array('admin/store/reports/products/custom/' . implode('/', $args));
  }

  /**
   * Return a themed table for product reports.
   *
   * Straight duplication of theme_table, but our row handling is different.
   *
   * @see theme_table()
   * @ingroup themeable
   */
  public static function theme_uc_reports_product_table(array $variables) {
    $header = $variables['header'];
    $rows = $variables['rows'];
    $attributes = $variables['attributes'];
    $caption = $variables['caption'];
    $colgroups = $variables['colgroups'];
    $sticky = $variables['sticky'];
    $empty = $variables['empty'];

    // Add sticky headers, if applicable.
    if (count($header) && $sticky) {
      drupal_add_js('misc/tableheader.js');
      // Add 'sticky-enabled' class to the table to identify it for JS.
      // This is needed to target tables constructed by this function.
      $attributes['class'][] = 'sticky-enabled';
    }

    $output = '<table' . $attributes . ">\n";

    if (isset($caption)) {
      $output .= '<caption>' . $caption . "</caption>\n";
    }

    // Format the table columns:
    if (count($colgroups)) {
      foreach ($colgroups as $colgroup) {
        $attributes = array();

        // Check if we're dealing with a simple or complex column
        if (isset($colgroup['data'])) {
          foreach ($colgroup as $key => $value) {
            if ($key == 'data') {
              $cols = $value;
            }
            else {
              $attributes[$key] = $value;
            }
          }
        }
        else {
          $cols = $colgroup;
        }

        // Build colgroup
        if (is_array($cols) && count($cols)) {
          $output .= ' <colgroup' . new Attribute($attributes) . '>';
          foreach ($cols as $col) {
            $output .= ' <col' . new Attribute($col) . ' />';
          }
          $output .= " </colgroup>\n";
        }
        else {
          $output .= ' <colgroup' . new Attribute($attributes) . " />\n";
        }
      }
    }

    // Add the 'empty' row message if available.
    if (!count($rows) && $empty) {
      $header_count = 0;
      foreach ($header as $header_cell) {
        if (is_array($header_cell)) {
          $header_count += isset($header_cell['colspan']) ? $header_cell['colspan'] : 1;
        }
        else {
          $header_count++;
        }
      }
      $rows[] = array(array('data' => $empty, 'colspan' => $header_count, 'class' => array('empty', 'message')));
    }

    // Format the table header:
    if (count($header)) {
      $ts = tablesort_init($header);
      // HTML requires that the thead tag has tr tags in it follwed by tbody
      // tags. Using ternary operator to check and see if we have any rows.
      $output .= (count($rows) ? ' <thead><tr>' : ' <tr>');
      foreach ($header as $cell) {
        $cell = tablesort_header($cell, $header, $ts);
        $output .= _theme_table_cell($cell, TRUE);
      }
      // Using ternary operator to close the tags based on whether or not there are rows
      $output .= (count($rows) ? " </tr></thead>\n" : "</tr>\n");
    }
    else {
      $ts = array();
    }

    // Format the table rows:
    if (count($rows)) {
      $output .= "<tbody>\n";
      $flip = array('even' => 'odd', 'odd' => 'even');
      $class = 'even';
      foreach ($rows as $row) {
        $attributes = array();

        // Check if we're dealing with a simple or complex row
        if (isset($row['data'])) {
          foreach ($row as $key => $value) {
            if ($key == 'data') {
              $cells = $value;
            }
            // The following elseif clause is where we differ from theme_table()
            elseif ($key == 'primary') {
              $class = $flip[$class];
            }
            else {
              $attributes[$key] = $value;
            }
          }
        }
        else {
          $cells = $row;
        }
        if (count($cells)) {
          // Add odd/even class
          // We don't flip here like theme_table(), because we did that above.
          $attributes['class'][] = $class;

          // Build row
          $output .= ' <tr' . new Attribute($attributes) . '>';
          $i = 0;
          foreach ($cells as $cell) {
            $cell = tablesort_cell($cell, $header, $ts, $i++);
            $output .= _theme_table_cell($cell);
          }
          $output .= " </tr>\n";
        }
      }
      $output .= "</tbody>\n";
    }

    $output .= "</table>\n";
    return $output;
  }

  /**
   * Form to specify a year for the yearly sales report.
   *
   * @see uc_reports_sales_year_form_submit()
   * @ingroup forms
   */
  public static function sales_year_form($form, FormStateInterface $form_state, $year) {
    $form['year'] = array(
      '#type' => 'textfield',
      '#title' => t('Sales year'),
      '#default_value' => $year,
      '#maxlength' => 4,
      '#size' => 4,
      '#prefix' => '<div class="sales-year">',
      '#suffix' => '</div>',
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('View'),
      '#prefix' => '<div class="sales-year">',
      '#suffix' => '</div>',
    );

    return $form;
  }

  /**
   * Submit handler for uc_reports_sales_year_form().
   *
   * @see uc_reports_sales_year_form()
   */
  public static function sales_year_form_submit($form, FormStateInterface $form_state) {
    $form_state['redirect'] = 'admin/store/reports/sales/year/' . $form_state->getValue('year');
  }

  /**
   * Form builder for the custom sales report.
   *
   * @see uc_reports_sales_custom_form_validate()
   * @see uc_reports_sales_custom_form_submit()
   * @ingroup forms
   */
  public static function sales_custom_form($form, FormStateInterface $form_state, $values, $statuses) {
    $form['search'] = array(
      '#type' => 'details',
      '#title' => t('Customize sales report parameters'),
      '#description' => t('Adjust these values and update the report to build your custom sales summary. Once submitted, the report may be bookmarked for easy reference in the future.'),
    );

    $form['search']['start_date'] = array(
      '#type' => 'date',
      '#title' => t('Start date'),
      '#default_value' => array(
        'month' => \Drupal::service('date.formatter')->format($values['start_date'], 'custom', 'n'),
        'day' => \Drupal::service('date.formatter')->format($values['start_date'], 'custom', 'j'),
        'year' => \Drupal::service('date.formatter')->format($values['start_date'], 'custom', 'Y'),
      ),
    );
    $form['search']['end_date'] = array(
      '#type' => 'date',
      '#title' => t('End date'),
      '#default_value' => array(
        'month' => \Drupal::service('date.formatter')->format($values['end_date'], 'custom', 'n'),
        'day' => \Drupal::service('date.formatter')->format($values['end_date'], 'custom', 'j'),
        'year' => \Drupal::service('date.formatter')->format($values['end_date'], 'custom', 'Y'),
      ),
    );

    $form['search']['length'] = array(
      '#type' => 'select',
      '#title' => t('Results breakdown'),
      '#description' => t('Large daily reports may take a long time to display.'),
      '#options' => array(
        'day' => t('daily'),
        'week' => t('weekly'),
        'month' => t('monthly'),
        'year' => t('yearly'),
      ),
      '#default_value' => $values['length'],
    );

    if ($statuses === FALSE) {
      $statuses = uc_reports_order_statuses();
    }

    $form['search']['status'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Order statuses'),
      '#description' => t('Only orders with selected statuses will be included in the report.'),
      '#options' => uc_order_status_options_list(),
      '#default_value' => $statuses,
    );

    $form['search']['detail'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show a detailed list of products ordered.'),
      '#default_value' => $values['detail'],
    );

    $form['search']['actions'] = array('#type' => 'actions');
    $form['search']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Update report'),
    );

    return $form;
  }

  /**
   * Ensure an order status was selected.
   *
   * @see uc_reports_sales_custom_form()
   * @see uc_reports_sales_custom_form_submit()
   */
  public static function sales_custom_form_validate($form, FormStateInterface $form_state) {
    if ($form_state->isValueEmpty('status')) {
      $form_state->setErrorByName('status', t('You must select at least one order status.'));
    }
  }

  /**
   * Submission handler for uc_reports_sales_custom_form().
   *
   * @see uc_reports_sales_custom_form()
   * @see uc_reports_sales_custom_form_validate()
   */
  public static function sales_custom_form_submit($form, FormStateInterface $form_state) {
    // Build the start and end dates from the form.
    $start_date = mktime(0, 0, 0, $form_state->getValue(['start_date', 'month']), $form_state->getValue(['start_date', 'day']), $form_state->getValue(['start_date', 'year']));
    $end_date = mktime(23, 59, 59, $form_state->getValue(['end_date', 'month']), $form_state->getValue(['end_date', 'day']), $form_state->getValue(['end_date', 'year']));

    $args = array(
      $start_date,
      $end_date,
      $form_state->getValue('length'),
      implode(',', array_keys(array_filter($form_state->getValue('status')))),
      $form_state->getValue('detail'),
    );

    $form_state['redirect'] = 'admin/store/reports/sales/custom/' . implode('/', $args);
  }
}
