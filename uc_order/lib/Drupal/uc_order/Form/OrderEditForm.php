<?php

/**
 * @file
 * Contains \Drupal\uc_order\Form\OrderEditForm.
 */

namespace Drupal\uc_order\Form;

use Drupal\Core\Form\FormBase;
use Drupal\uc_order\UcOrderInterface;

/**
 * Displays the order edit screen, constructed via hook_uc_order_pane().
 */
class OrderEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_order_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, UcOrderInterface $uc_order = NULL) {
    if (isset($form_state['order'])) {
      $order = $form_state['order'];
    }
    else {
      $form_state['order'] = $order = $uc_order;
    }
    $form['#order'] = $order;
    $form['order_id'] = array('#type' => 'hidden', '#value' => $order->id());
    $form['order_uid'] = array('#type' => 'hidden', '#value' => $order->getUserId());

    $modified = isset($form_state['values']['order_modified']) ? $form_state['values']['order_modified'] : $order->modified->value;
    $form['order_modified'] = array('#type' => 'hidden', '#value' => $modified);

    $panes = _uc_order_pane_list('edit');
    foreach ($panes as $id => $pane) {
      if (in_array('edit', $pane['show'])) {
        $func = $pane['callback'];
        if (function_exists($func)) {
          $func('edit-form', $order, $form, $form_state);
        }
      }
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit-changes'] = array(
      '#type' => 'submit',
      '#value' => t('Submit changes'),
    );
    $form['actions']['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete'),
      '#submit' => array('uc_order_edit_form_delete'),
      '#access' => $order->access('delete'),
    );

    $form_state['form_display'] = entity_get_form_display('uc_order', 'uc_order', 'default');
    field_attach_form($order, $form, $form_state);

    form_load_include($form_state, 'inc', 'uc_store', 'includes/uc_ajax_attach');
    $form['#process'][] = 'uc_ajax_process_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    $order = uc_order_load($form_state['values']['order_id']);
    if ($form_state['values']['order_modified'] != $order->modified->value) {
      form_set_error('order_modified', $form_state, t('This order has been modified by another user, changes cannot be saved.'));
    }

    field_attach_form_validate($order, $form, $form_state);

    // Build list of changes to be applied.
    $panes = _uc_order_pane_list();
    foreach ($panes as $id => $pane) {
      if (in_array('edit', $pane['show'])) {
        $func = $pane['callback'];
        if (function_exists($func)) {
          if (($changes = $func('edit-process', $form_state['order'], $form, $form_state)) != NULL) {
            foreach ($changes as $key => $value) {
              //$form_state['order']->$key->value = $value;
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $order = uc_order_load($form_state['values']['order_id']);
    $log = array();

    foreach (array_keys($form_state['order']->getPropertyDefinitions()) as $key) {
      $value = $form_state['order']->$key->value;
      if ($order->$key->value !== $value) {
        if (!is_array($value)) {
          $log[$key] = array('old' => $order->$key->value, 'new' => $value);
        }
        $order->$key->value = $value;
      }
    }

    if (module_exists('uc_stock')) {
      $qtys = array();
      foreach ($order->products as $product) {
        $qtys[$product->order_product_id] = $product->qty;
      }
    }

    if (isset($form_state['values']['products']) && is_array($form_state['values']['products'])) {
      foreach ($form_state['values']['products'] as $product) {
        if (!isset($product['remove']) && intval($product['qty']) > 0) {
          foreach (array('qty', 'title', 'model', 'weight', 'weight_units', 'cost', 'price') as $field) {
            $order->products[$product['order_product_id']]->$field = $product[$field];
          }

          if (module_exists('uc_stock')) {
            $product = (object)$product;
            $temp = $product->qty;
            $product->qty = $product->qty - $qtys[$product->order_product_id];
            uc_stock_adjust_product_stock($product, 0, $order);
            $product->qty = $temp;
          }
        }
        else {
          $log['remove_' . $product['nid']] = $product['title'] . ' removed from order.';
        }
      }
    }

    // Load line items again, since some may have been updated by the form.
    $order->line_items = uc_order_load_line_items($order);

    uc_order_log_changes($order->id(), $log);

    field_attach_extract_form_values($order, $form, $form_state);

    $order->save();

    drupal_set_message(t('Order changes saved.'));
  }

}
