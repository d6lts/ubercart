<?php

/**
 * @file
 * Cart menu items.
 */

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

  drupal_add_css(drupal_get_path('module', 'uc_cart') . '/css/uc_cart.css');

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

/**
 * Gives customers the option to finish checkout or go revise their information.
 *
 * @see uc_cart_checkout_review_form_back()
 * @see uc_cart_checkout_review_form_submit()
 * @ingroup forms
 */
function uc_cart_checkout_review_form($form, &$form_state, $order) {

  if (!isset($form_state['uc_order'])) {
    $form_state['uc_order'] = $order;
    $form_state['storage']['base_path'] = implode('/', array_slice(arg(), 0, -2));
  }

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['back'] = array(
    '#type' => 'submit',
    '#value' => t('Back'),
    '#submit' => array('uc_cart_checkout_review_form_back'),
  );
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit order'),
  );

  return $form;
}

/**
 * Returns the customer to the checkout page to edit their information.
 *
 * @see uc_cart_checkout_review_form()
 */
function uc_cart_checkout_review_form_back($form, &$form_state) {
  $form_state['redirect'] = $form_state['storage']['base_path'] . '/checkout';
}

/**
 * Final checks to make sure the order can be completed.
 *
 * @see uc_cart_checkout_review_form()
 */
function uc_cart_checkout_review_form_submit($form, &$form_state) {
  // Invoke hook_uc_order($op = 'submit') to test to make sure the order can
  // be completed... used for auto payment in uc_credit.module.
  $order = $form_state['uc_order'];
  $error = FALSE;

  // Invoke it on a per-module basis instead of all at once.
  foreach (module_implements('uc_order') as $module) {
    $function = $module . '_uc_order';
    if (function_exists($function)) {
      // $order must be passed by reference.
      $result = $function('submit', $order, NULL);

      $msg_type = 'status';
      if ($result[0]['pass'] === FALSE) {
        $error = TRUE;
        $msg_type = 'error';
      }
      if (!empty($result[0]['message'])) {
        drupal_set_message($result[0]['message'], $msg_type);
      }

      // Stop invoking the hooks if there was an error.
      if ($error) {
        break;
      }
    }
  }

  if ($error) {
    $form_state['redirect'] = $form_state['storage']['base_path'] . '/checkout/review';
  }
  else {
    unset($_SESSION['uc_checkout'][$order->id()]['do_review']);
    $_SESSION['uc_checkout'][$order->id()]['do_complete'] = TRUE;
    $form_state['redirect'] = $form_state['storage']['base_path'] . '/checkout/complete';
  }
}
