<?php

/**
 * @file
 * Contains \Drupal\uc_order\UcOrderFormController.
 */

namespace Drupal\uc_order;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\uc_order\UcOrderInterface;

/**
 * Form controller for the Ubercart order form.
 */
class UcOrderForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $order = $this->entity;

    $form['#order'] = $order;
    $form['order_id'] = array('#type' => 'hidden', '#value' => $order->id());
    $form['order_uid'] = array('#type' => 'hidden', '#value' => $order->getUserId());

    $modified = isset($form_state['values']['order_modified']) ? $form_state['values']['order_modified'] : $order->modified->value;
    $form['order_modified'] = array('#type' => 'hidden', '#value' => $modified);

    $panes = _uc_order_pane_list('edit');
    foreach ($panes as $pane) {
      if (in_array('edit', $pane['show'])) {
        $func = $pane['callback'];
        if (function_exists($func)) {
          $func('edit-form', $order, $form, $form_state);
        }
      }
    }

    $form = parent::form($form, $form_state);

    form_load_include($form_state, 'inc', 'uc_store', 'includes/uc_ajax_attach');
    $form['#process'][] = 'uc_ajax_process_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, array &$form_state) {
    $element = parent::actions($form, $form_state);
    $element['submit']['#value'] = $this->t('Save changes');
    $element['delete']['#access'] = $this->entity->access('delete');
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, array &$form_state) {
    $order = $this->buildEntity($form, $form_state);

    if ($form_state['values']['order_modified'] != $order->modified->value) {
      form_set_error('order_modified', $form_state, t('This order has been modified by another user, changes cannot be saved.'));
    }

    parent::validate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $order = parent::submit($form, $form_state);
    $original = clone $order;

    // Build list of changes to be applied.
    $panes = _uc_order_pane_list();
    foreach ($panes as $pane) {
      if (in_array('edit', $pane['show'])) {
        $pane['callback']('edit-process', $order, $form, $form_state);
      }
    }

    $log = array();

    foreach (array_keys($order->getFieldDefinitions()) as $key) {
      if ($original->$key->value !== $order->$key->value) {
        if (!is_array($order->$key->value)) {
          $log[$key] = array('old' => $original->$key->value, 'new' => $order->$key->value);
        }
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
    $order->line_items = $order->getLineItems();

    $order->logChanges($log);

    $order->save();

    drupal_set_message(t('Order changes saved.'));

    return $order;
  }

  /**
   * Form submission handler for the 'delete' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   A reference to a keyed array containing the current state of the form.
   */
  public function delete(array $form, array &$form_state) {
    $form_state['redirect'] = 'admin/store/orders/' . $this->entity->id() . '/delete';
  }

}
