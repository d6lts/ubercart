<?php

/**
 * @file
 * Theme functions for the uc_cart module.
 */

use Drupal\Core\Template\Attribute;

/**
 * Themes the shopping cart block title.
 *
 * @param $variables
 *   An associative array containing:
 *   - title: The text to use for the title of the block.
 *   - show_icon: TRUE if the cart icon is to be displayed.
 *   - empty: TRUE if the cart is empty.
 *   - collapsible: TRUE if the cart block is collapsible.
 *   - collapsed: TRUE if the cart block is collapsed initially.
 *
 * @ingroup themeable
 */
function theme_uc_cart_block_title($variables) {
  $output = '';

  // Add in the cart image if specified.
  if ($variables['show_icon']) {
    $icon_attributes['title'][] = t('View your shopping cart.');
    $icon_attributes['class'][] = 'cart-block-icon';
    if ($variables['cart_empty']) {
      $icon_attributes['class'][] = 'empty';
    }
    $output .= l('<span ' . new Attribute($icon_attributes) . '></span>', 'cart', array('html' => TRUE));
  }

  $attributes = array();
  if ($variables['collapsible']) {
    $attributes['title'] = t('Show/hide shopping cart contents.');
    $attributes['class'][] = 'cart-block-arrow';
    if ($variables['collapsed']) {
      $attributes['class'][] = 'collapsed';
    }
  }
  $output .= '<span ' . new Attribute($attributes) . '>' . $variables['title'] . '</span>';

  return $output;
}

/**
 * Themes the cart checkout button(s).
 *
 * @param $variables
 *   An associative array containing:
 *   - buttons: A render element representing the form buttons.
 *
 * @see \Drupal\uc_cart\Form\CartForm
 * @ingroup themeable
 */
function theme_uc_cart_checkout_buttons($variables) {
  $output = '';

  if ($buttons = element_children($variables['buttons'])) {
    // Render the first button.
    $button = array_shift($buttons);
    $output = drupal_render($variables['buttons'][$button]);

    // Render any remaining buttons inside a separate container.
    if ($buttons) {
      $output .= '<div class="uc-cart-checkout-button-container clearfix">';

      // Render the second button.
      $output .= '<div class="uc-cart-checkout-button">';
      $output .= '<div class="uc-cart-checkout-button-separator">' . t('- or -') . '</div>';
      $button = array_shift($buttons);
      $output .= drupal_render($variables['buttons'][$button]);
      $output .= '</div>';

      // Render any remaining buttons.
      foreach ($buttons as $button) {
        $output .= '<div class="uc-cart-checkout-button">';
        $output .= drupal_render($variables['buttons'][$button]);
        $output .= '</div>';
      }

      $output .= '</div>';
    }
  }

  return $output;
}

/**
 * Formats the cart contents table on the checkout page.
 *
 * @param $variables
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
function theme_uc_cart_review_table($variables) {
  $items = $variables['items'];
  $show_subtotal = $variables['show_subtotal'];

  $subtotal = 0;

  // Set up table header.
  $header = array(
    array('data' => theme('uc_qty_label'), 'class' => array('qty')),
    array('data' => t('Products'), 'class' => array('products')),
    array('data' => t('Price'), 'class' => array('price')),
  );

  // Set up table rows.
  $display_items = entity_view_multiple($items, 'cart');
  if (!empty($display_items)) {
    foreach (element_children($display_items) as $key) {
      $display_item = $display_items[$key];
      $subtotal += $display_item['total']['#price'];
      $rows[] = array(
        array('data' => $display_item['qty'], 'class' => array('qty')),
        array('data' => $display_item['product'], 'class' => array('products')),
        array('data' => $display_item['total'], 'class' => array('price')),
      );
    }
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

  return theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('class' => array('cart-review'))));
}

/**
 * Themes the checkout review order page.
 *
 * @param $variables
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
function theme_uc_cart_checkout_review($variables) {
  $panes = $variables['panes'];
  $form = $variables['form'];

  $output = '<p>' . t("Your order is almost complete. Please review the details below and click 'Submit order' if all the information is correct.  You may use the 'Back' button to make changes to your order if necessary.") . '</p>';

  $output .= '<table class="order-review-table">';

  foreach ($panes as $title => $data) {
    $output .= '<tr class="pane-title-row">';
    $output .= '<td colspan="2">' . $title . '</td>';
    $output .= '</tr>';
    if (is_array($data)) {
      foreach ($data as $row) {
        if (is_array($row)) {
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
          $output .= '<tr><td colspan="2">' . $row . '</td></tr>';
        }
      }
    }
    else {
      $output .= '<tr><td colspan="2">' . $data . '</td></tr>';
    }
  }

  $output .= '<tr class="review-button-row">';
  $output .= '<td colspan="2">' . drupal_render($form) . '</td>';
  $output .= '</tr>';

  $output .= '</table>';

  return $output;
}
