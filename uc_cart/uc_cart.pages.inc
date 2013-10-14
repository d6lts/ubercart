<?php

/**
 * @file
 * Cart menu items.
 */

/**
 * Confirm that the customer wants to empty their cart.
 */
function uc_cart_empty_confirm($form, &$form_state) {
  return confirm_form($form, t('Are you sure you want to empty your shopping cart?'), 'cart');
}

/**
 * Submission handler to empty the cart after confirmations.
 */
function uc_cart_empty_confirm_submit($form, &$form_state) {
  uc_cart_empty();
  $form_state['redirect'] = 'cart';
}

/**
 * The checkout form built up from the enabled checkout panes.
 *
 * @param $order
 *   The order that is being checked out.
 *
 * @see uc_cart_checkout_form_process()
 * @see uc_cart_checkout_form_validate()
 * @see uc_cart_checkout_form_submit()
 * @see uc_cart_checkout_review()
 * @see theme_uc_cart_checkout_form()
 * @ingroup forms
 */
function uc_cart_checkout_form($form, &$form_state, $order) {

  if ($processed = isset($form_state['storage']['order'])) {
    $order = $form_state['storage']['order'];
  }
  else {
    $form_state['storage']['order'] = $order;
    $form_state['storage']['base_path'] = implode('/', array_slice(arg(), 0, -1));
  }

  $form['#attributes']['class'][] = 'uc-cart-checkout-form';
  $form['#attached']['js'][] = drupal_get_path('module', 'uc_cart') . '/uc_cart.js';
  $form['#attached']['css'][] = drupal_get_path('module', 'uc_cart') . '/uc_cart.css';

  if ($instructions = variable_get('uc_checkout_instructions', '')) {
    $form['instructions'] = array(
      '#prefix' => '<div id="checkout-instructions">',
      '#markup' => filter_xss_admin($instructions),
      '#suffix' => '</div>',
    );
  }

  $form['panes'] = array('#tree' => TRUE);
  $panes = _uc_checkout_pane_list();

  // If the order isn't shippable, remove panes with shippable == TRUE.
  if (!uc_order_is_shippable($order) && variable_get('uc_cart_delivery_not_shippable', TRUE)) {
    $panes = uc_cart_filter_checkout_panes($panes, array('shippable' => TRUE));
  }

  // Invoke the 'prepare' op of enabled panes, but only if their 'process' ops
  // have not been invoked on this request (i.e. when rebuilding after AJAX).
  foreach ($panes as $id => $pane) {
    if ($pane['enabled'] && empty($form_state['storage']['panes'][$id]['prepared']) && isset($pane['callback']) && function_exists($pane['callback'])) {
      $pane['callback']('prepare', $order, $form, $form_state);
      $form_state['storage']['panes'][$id]['prepared'] = TRUE;
      $processed = FALSE; // Make sure we save the updated order.
    }
  }

  // Load the line items and save the order. We do this after the 'prepare'
  // callbacks of enabled panes have been invoked, because these may have
  // altered the order.
  if (!$processed) {
    $order->line_items = uc_order_load_line_items($order);
    uc_order_save($order);
  }

  foreach ($panes as $id => $pane) {
    if ($pane['enabled']) {
      $pane['prev'] = _uc_cart_checkout_prev_pane($panes, $id);
      $pane['next'] = _uc_cart_checkout_next_pane($panes, $id);

      if (!isset($pane['collapsed'])) {
        $collapsed = ($pane['prev'] === FALSE || empty($displayed[$pane['prev']])) ? FALSE : TRUE;
      }
      if (isset($form_state['expanded_panes']) && in_array($id, $form_state['expanded_panes'])) {
        $collapsed = FALSE;
      }

      $return = $pane['callback']('view', $order, $form, $form_state);

      // Add the pane if any display data is returned from the callback.
      if (is_array($return) && (!empty($return['description']) || !empty($return['contents']))) {
        // Create the fieldset for the pane.
        $form['panes'][$id] = array(
          '#type' => 'details',
          '#title' => check_plain($pane['title']),
          '#description' => !empty($return['description']) ? $return['description'] : '',
          '#collapsible' => $pane['collapsible'] && variable_get('uc_use_next_buttons', FALSE),
          '#collapsed' => variable_get('uc_use_next_buttons', FALSE) ? $collapsed : FALSE,
          '#id' => $id . '-pane',
          '#theme' => isset($return['theme']) ? $return['theme'] : NULL,
        );

        // Add the contents of the fieldset if any were returned.
        if (!empty($return['contents'])) {
          $form['panes'][$id] = array_merge($form['panes'][$id], $return['contents']);
        }

        // Add the 'Next' button if necessary.
        if ((!isset($return['next-button']) || $return['next-button'] !== FALSE) && $pane['next'] !== FALSE &&
            variable_get('uc_use_next_buttons', FALSE) != FALSE) {
          $opt = variable_get('uc_collapse_current_pane', FALSE) ? $id : 'false';
          $form['panes'][$id]['next'] = array(
            '#type' => 'button',
            '#value' => t('Next'),
            '#weight' => 20,
            '#attributes' => array('onclick' => "return uc_cart_next_button_click(this, '" . $pane['next'] . "', '" . $opt . "');"),
            '#prefix' => '<div class="next-button">',
            '#suffix' => '</div>',
          );
        }

        // Log that this pane was actually displayed.
        $displayed[$id] = TRUE;
      }
    }
  }
  unset($form_state['expanded_panes']);

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['cancel'] = array(
    '#type' => 'submit',
    '#value' => t('Cancel'),
    '#validate' => array(), // Disable validation to prevent a new order
                            // from being created.
    '#limit_validation_errors' => array(),
    '#submit' => array('uc_cart_checkout_form_cancel'),
  );
  $form['actions']['continue'] = array(
    '#type' => 'submit',
    '#value' => t('Review order'),
  );

  form_load_include($form_state, 'inc', 'uc_store', 'includes/uc_ajax_attach');
  $form['#process'][] = 'uc_ajax_process_form';

  unset($_SESSION['uc_checkout'][$order->id()]);

  return $form;
}

/**
 * Default theme function for the checkout form.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @see uc_cart_checkout_form()
 * @ingroup themeable
 */
function theme_uc_cart_checkout_form($variables) {
  return drupal_render_children($variables['form']);
}

/**
 * Form validation for uc_cart_checkout_form().
 *
 * @see uc_cart_checkout_form()
 * @see uc_cart_checkout_form_submit()
 */
function uc_cart_checkout_form_validate($form, &$form_state) {
  $order = $form_state['storage']['order'];

  // Update the order "modified" time to prevent timeout on ajax requests.
  $order->modified = REQUEST_TIME;

  // Validate/process the cart panes.  A FALSE value results in failed checkout.
  $form_state['checkout_valid'] = TRUE;
  foreach (element_children($form_state['values']['panes']) as $pane_id) {
    $func = _uc_checkout_pane_data($pane_id, 'callback');
    if (is_string($func) && function_exists($func)) {
      $isvalid = $func('process', $order, $form, $form_state);
      if ($isvalid === FALSE) {
        $form_state['expanded_panes'][] = $pane_id;
        $form_state['checkout_valid'] = FALSE;
      }
    }
  }

  // Reload line items and save order.
  $order->line_items = uc_order_load_line_items($order);
  uc_order_save($order);
}

/**
 * Form submission handler for uc_cart_checkout_form().
 *
 * @see uc_cart_checkout_form()
 * @see uc_cart_checkout_form_validate()
 */
function uc_cart_checkout_form_submit($form, &$form_state) {
  if ($form_state['checkout_valid'] === FALSE) {
    $url = $form_state['storage']['base_path'] . '/checkout';
  }
  else {
    $url = $form_state['storage']['base_path'] . '/checkout/review';
    $_SESSION['uc_checkout'][$form_state['storage']['order']->id()]['do_review'] = TRUE;
  }

  unset($form_state['checkout_valid']);

  $form_state['redirect'] = $url;
}

/**
 * Submit handler for "Cancel" button on uc_cart_checkout_form().
 *
 * @see uc_cart_checkout_form()
 */
function uc_cart_checkout_form_cancel($form, &$form_state) {
  $order = $form_state['storage']['order'];
  if (isset($_SESSION['cart_order']) && $_SESSION['cart_order'] == $order->id()) {
    uc_order_comment_save($_SESSION['cart_order'], 0, t('Customer canceled this order from the checkout form.'));
    unset($_SESSION['cart_order']);
  }

  unset($_SESSION['uc_checkout'][$order->id()]);
  $form_state['redirect'] = $form_state['storage']['base_path'];
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

  drupal_add_css(drupal_get_path('module', 'uc_cart') . '/uc_cart.css');

  $output = '<div id="review-instructions">' . filter_xss_admin(variable_get('uc_checkout_review_instructions', uc_get_message('review_instructions'))) . '</div>';

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
