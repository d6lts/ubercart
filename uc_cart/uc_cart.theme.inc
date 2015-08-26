<?php

/**
 * @file
 * Theme functions for the uc_cart module.
 */

/**
 * Formats the cart contents table on the checkout page.
 *
 * @param array $variables
 *   An associative array containing:
 *   - show_subtotal: TRUE or FALSE indicating if you want a subtotal row
 *     displayed in the table.
 *   - items: An associative array of cart item information containing:
 *     - qty: Quantity in cart.
 *     - title: Item title.
 *     - price: Item price.
 *     - desc: Item description.
 *
 * @return
 *   The HTML output for the cart review table.
 *
 * @ingroup themeable
 */
function theme_uc_cart_review_table(array $variables) {
  $items = $variables['items'];
  $show_subtotal = $variables['show_subtotal'];

  $subtotal = 0;

  // Set up table header.
  $header = array(
    array('data' => t('Quantity'), 'class' => array('qty')),
    array('data' => t('Products'), 'class' => array('products')),
    array('data' => t('Price'), 'class' => array('price')),
  );

  // Set up table rows.
  // @todo Replace with Views.
  foreach ($items as $item) {
    $subtotal += $item->price->value * $item->qty->value;
    $rows[] = array(
      array(
        'data' => array(
          '#theme' => 'uc_qty',
          '#qty' => $item->qty->value,
        ),
        'class' => array('qty'),
      ),
      array(
        'data' => $item->title->value . uc_product_get_description($item),
        'class' => array('products'),
      ),
      array(
        'data' => array(
          '#theme' => 'uc_price',
          '#price' => $item->price->value * $item->qty->value,
        ),
        'class' => array('price'),
      ),
    );
  }

  // Add the subtotal as the final row.
  if ($show_subtotal) {
    $rows[] = array(
      'data' => array(
        // One cell
        array(
          'data' => array(
            '#theme' => 'uc_price',
            '#prefix' => '<span id="subtotal-title">' . t('Subtotal:') . '</span> ',
            '#price' => $subtotal,
          ),
          // Cell attributes
          'colspan' => 3,
          'class' => array('subtotal'),
        ),
      ),
      // Row attributes
      'class' => array('subtotal'),
    );
  }

  $table = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#attributes' => array('class' => array('cart-review')),
  );

  return drupal_render($table);
}

/**
 * Themes the checkout review order page.
 *
 * @param array $variables
 *   An associative array containing:
 *   - form: A render element representing the form, that by default includes
 *     the 'Back' and 'Submit order' buttons at the bottom of the review page.
 *   - panes: An associative array for each checkout pane that has information
 *     to add to the review page, keyed by the pane title:
 *     - <pane title>: The data returned for that pane or an array of returned
 *       data.
 *
 * @return
 *   A string of HTML for the page contents.
 *
 * @ingroup themeable
 */
function theme_uc_cart_checkout_review(array $variables) {
  $panes = $variables['panes'];
  $form = $variables['form'];

  $output = '<p>' . t("Your order is almost complete. Please review the details below and click 'Submit order' if all the information is correct.  You may use the 'Back' button to make changes to your order if necessary.") . '</p>';

  $output .= '<table class="order-review-table">';

  foreach ($panes as $title => $data) {
    $output .= '<tr class="pane-title-row">';
    $output .= '<td colspan="2">' . $title . '</td>';
    $output .= '</tr>';
    foreach ($data as $row) {
      if (isset($row['data'])) {
        if (is_array($row['data'])) {
          $row['data'] = drupal_render($row['data']);
        }
        if (isset($row['border'])) {
          $border = ' class="row-border-' . $row['border'] . '"';
        }
        else {
          $border = '';
        }
        $output .= '<tr' . $border . '>';
        $output .= '<td class="title-col">' . $row['title'] . ':</td>';
        $output .= '<td class="data-col">' . $row['data'] . '</td>';
        $output .= '</tr>';
      }
      else {
        $output .= '<tr><td colspan="2">' . drupal_render($row) . '</td></tr>';
      }
    }
  }

  $output .= '<tr class="review-button-row">';
  $output .= '<td colspan="2">' . drupal_render($form) . '</td>';
  $output .= '</tr>';

  $output .= '</table>';

  return $output;
}
