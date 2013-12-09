<?php

/**
 * @file
 * Contains \Drupal\uc_cart\Form\CheckoutForm.
 */

namespace Drupal\uc_cart\Form;

use Drupal\Core\Form\FormBase;

/**
 * The checkout form built up from the enabled checkout panes.
 */
class CheckoutForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_cart_checkout_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $order= NULL) {
    if ($processed = isset($form_state['storage']['order'])) {
      $order = $form_state['storage']['order'];
    }
    else {
      $form_state['storage']['order'] = $order;
      $form_state['storage']['base_path'] = implode('/', array_slice(arg(), 0, -1));
    }

    $form['#attributes']['class'][] = 'uc-cart-checkout-form';
    $form['#attached']['css'][] = drupal_get_path('module', 'uc_cart') . '/css/uc_cart.css';
    $form['panes'] = array('#tree' => TRUE);
    $panes = _uc_checkout_pane_list();

    // If the order isn't shippable, remove panes with shippable == TRUE.
    if (!$order->isShippable() && variable_get('uc_cart_delivery_not_shippable', TRUE)) {
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
      $order->save();
    }

    foreach ($panes as $id => $pane) {
      if ($pane['enabled']) {
        $return = $pane['callback']('view', $order, $form, $form_state);

        // Add the pane if any display data is returned from the callback.
        if (is_array($return) && (!empty($return['description']) || !empty($return['contents']))) {
          // Create the fieldset for the pane.
          $form['panes'][$id] = array(
            '#type' => 'details',
            '#title' => check_plain($pane['title']),
            '#description' => !empty($return['description']) ? $return['description'] : '',
            '#id' => $id . '-pane',
            '#theme' => isset($return['theme']) ? $return['theme'] : NULL,
          );

          // Add the contents of the fieldset if any were returned.
          if (!empty($return['contents'])) {
            $form['panes'][$id] = array_merge($form['panes'][$id], $return['contents']);
          }
        }
      }
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['cancel'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#validate' => array(),
      '#limit_validation_errors' => array(),
      '#submit' => array(array($this, 'cancel')),
    );
    $form['actions']['continue'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Review order'),
      '#button_type' => 'primary',
    );

    form_load_include($form_state, 'inc', 'uc_store', 'includes/uc_ajax_attach');
    $form['#process'][] = 'uc_ajax_process_form';

    unset($_SESSION['uc_checkout'][$order->id()]);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
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
          $form_state['checkout_valid'] = FALSE;
        }
      }
    }

    // Reload line items and save order.
    $order->line_items = uc_order_load_line_items($order);
    $order->save();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
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
   * Submit handler for the "Cancel" button on the checkout form.
   */
  public function cancel(array &$form, array &$form_state) {
    $order = $form_state['storage']['order'];
    if (isset($_SESSION['cart_order']) && $_SESSION['cart_order'] == $order->id()) {
      uc_order_comment_save($_SESSION['cart_order'], 0, $this->t('Customer canceled this order from the checkout form.'));
      unset($_SESSION['cart_order']);
    }

    unset($_SESSION['uc_checkout'][$order->id()]);
    $form_state['redirect'] = $form_state['storage']['base_path'];
  }

}
