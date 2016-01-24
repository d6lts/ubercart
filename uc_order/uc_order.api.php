<?php

use Drupal\uc_order\OrderInterface;

/**
 * @file
 * Hooks provided by the Order module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Defines line items that are attached to orders.
 *
 * A line item is a representation of charges, fees, and totals for an order.
 * Default line items include the subtotal and total line items, the tax line
 * item, and the shipping line item. There is also a generic line item that
 * store admins can use to add extra fees and discounts to manually created
 * orders. Module developers will use this hook to define new types of line
 * items for their stores. An example use would be for a module that allows
 * customers to use coupons and wants to represent an entered coupon as a line
 * item.
 *
 * Once a line item has been defined in hook_uc_line_item, Ubercart will begin
 * interacting with it in various parts of the code. One of the primary ways
 * this is done is through the callback function you specify for the line item.
 *
 * @return
 *   Your hook should return an array of associative arrays. Each item in the
 *   array represents a single line item, keyed by the internal ID of the line
 *   item, and with the following members:
 *   - "title"
 *     - type: string
 *     - value: The title of the line item shown to the user in various
 *       interfaces. Use t().
 *   - "callback"
 *     - type: string
 *     - value: Name of the line item's callback function, called for various
 *       operations.
 *   - "weight"
 *     - type: integer
 *     - value: Display order of the line item in lists; "lighter" items are
 *       displayed first.
 *   - "stored"
 *     - type: boolean
 *     - value: Whether or not the line item will be stored in the database.
 *       Should be TRUE for any line item that is modifiable from the order
 *       edit screen.
 *   - "add_list"
 *     - type: boolean
 *     - value: Whether or not a line item should be included in the "Add a Line
 *       Item" select box on the order edit screen.
 *   - "calculated"
 *     - type: boolean
 *     - value: Whether or not the value of this line item should be added to
 *       the order total. (Ex: would be TRUE for a shipping charge line item but
 *       FALSE for the subtotal line item since the product prices are already
 *       taken into account.)
 *   - "display_only"
 *     - type: boolean
 *     - value: Whether or not this line item is simply a display of information
 *       but not calculated anywhere. (Ex: the total line item uses display to
 *       simply show the total of the order at the bottom of the list of line
 *       items.)
 */
function hook_uc_line_item() {
  $items[] = array(
    'id' => 'generic',
    'title' => t('Empty line'),
    'weight' => 2,
    'default' => FALSE,
    'stored' => TRUE,
    'add_list' => TRUE,
    'calculated' => TRUE,
    'callback' => 'uc_line_item_generic',
  );

  return $items;
}

/**
 * Alters a line item on an order when the order is loaded.
 *
 * @param &$item
 *   The line item array.
 * @param $order
 *   The order object containing the line item.
 */
function hook_uc_line_item_alter(&$item, $order) {
  rules_invoke_event('calculate_line_item_discounts', $item, $order->getOwner());
}

/**
 * Alters the line item definitions declared in hook_uc_line_item().
 *
 * @param &$items
 *   The combined return value of hook_uc_line_item().
 */
function hook_uc_line_item_data_alter(&$items) {
  // Tax amounts are added in to other line items, so the actual tax line
  // items should not be added to the order total.
  $items['tax']['calculated'] = FALSE;

  // Taxes are included already, so the subtotal without taxes doesn't
  // make sense.
  $items['tax_subtotal']['callback'] = NULL;
}


/**
 * Performs actions on orders.
 *
 * An order in Ubercart represents a single transaction. Orders are created
 * during the checkout process where they sit in the database with a status of
 * "In checkout". When a customer completes checkout, the order's status gets
 * updated to show that the sale has gone through. Once an order is created,
 * and even during its creation, it may be acted on by any module to connect
 * extra information to an order. Every time an action occurs to an order,
 * hook_uc_order() gets invoked to let your modules know what's happening and
 * make stuff happen.
 *
 * @param $op
 *   The action being performed.
 * @param $order
 *   This is the order object.
 * @param $arg2
 *   This is variable and is based on the value of $op:
 *   - submit: When a sale is being completed and the customer has clicked the
 *     Submit order button from the checkout screen, the hook is invoked with
 *     this op. This gives modules a chance to determine whether or not the
 *     order should be allowed. An example use of this is the credit module
 *     attempting to process payments when an order is submitted and returning
 *     a failure message if the payment failed.
 *     To prevent an order from passing through, you must return an array
 *     resembling the following one with the failure message:
 *     @code
 *       return array(array(
 *         'pass' => FALSE,
 *         'message' => t('We were unable to process your credit card.'),
 *       ));
 *     @endcode
 */
function hook_uc_order($op, $order, $arg2) {
  switch ($op) {
    case 'save':
      // Do something to save payment info!
      break;
  }
}

/**
 * Adds links to local tasks for orders on the admin's list of orders.
 *
 * @param $order
 *   An order object.
 *
 * @return
 *   An array of operations links. Each link has the following keys:
 *   - title: The title of page being linked.
 *   - href: The link path. Do not use url(), but do use the $order's order_id.
 *   - weight: Sets the display order of operations.
 */
function hook_uc_order_actions($order) {
  $account = \Drupal::currentUser();
  $actions = array();
  if ($account->hasPermission('fulfill orders')) {
    $result = db_query("SELECT COUNT(nid) FROM {uc_order_products} WHERE order_id = :id AND data LIKE :data", array(':id' => $order->id(), ':data' => '%s:9:\"shippable\";s:1:\"1\";%'));
    if ($result->fetchField()) {
      $actions['package'] = array(
        'title' => t('Package'),
        'href' => 'admin/store/orders/' . $order->id() . '/packages',
        'weight' => 12,
      );
      $result = db_query("SELECT COUNT(package_id) FROM {uc_packages} WHERE order_id = :id", array(':id' => $order->id()));
      if ($result->fetchField()) {
        $actions['ship'] = array(
          'title' => t('Ship'),
          'href' => 'admin/store/orders/' . $order->id() . '/shipments',
          'weight' => 13,
        );
      }
    }
  }
  return $actions;
}

/**
 * Allows the local task icons for orders to be altered.
 *
 * @param &$actions
 *   A set of actions as defined in hook_uc_order_actions().
 * @param $order
 *   An order object.
 */
function hook_uc_order_actions_alter(&$actions, $order) {
  $actions['view']['title'] = t('Display');
}

/**
 * Verifies whether an order may be deleted.
 *
 * @param $order
 *   An order object.
 *
 * @return bool
 *   FALSE if the order should not be deleted.
 */
function hook_uc_order_can_delete(OrderInterface $order) {
  if (uc_payment_load_payments($order->id()) !== FALSE) {
    return FALSE;
  }
}

/**
 * Alters order pane plugin definitions.
 *
 * @param array[] $panes
 *   Keys are plugin IDs. Values are plugin definitions.
 */
function hook_uc_order_pane_alter(&$panes) {
  $panes['payment']['title'] = 'Payment information';
}

/**
 * Allows modules to alter order products when they're loaded with an order.
 *
 * @param &$product
 *   The product object as found in the $order object.
 * @param $order
 *   The order object to which the product belongs.
 *
 * @return
 *   Nothing should be returned. Hook implementations should receive the
 *   $product object by reference and alter it directly.
 */
function hook_uc_order_product_alter(\Drupal\uc_order\OrderProductInterface &$product, \Drupal\uc_order\OrderInterface $order) {
  $product->model = 'SKU';
}

/**
 * Acts on order products being loaded from the database.
 *
 * This hook is invoked during order product loading, which is handled by
 * entity_load(), via the EntityCRUDController.
 *
 * @param array $order_products
 *   An array of order product entities being loaded, keyed by id.
 *
 * @see hook_entity_load()
 */
function hook_uc_order_product_load(array $order_products) {
  $result = db_query('SELECT pid, foo FROM {mytable} WHERE pid IN(:ids[])', array(':ids[]' => array_keys($entities)));
  foreach ($result as $record) {
    $entities[$record->pid]->foo = $record->foo;
  }
}

/**
 * Responds when an order product is inserted.
 *
 * This hook is invoked after the order product is inserted into the database.
 *
 * @param object $order_product
 *   The order product that is being inserted.
 *
 * @see hook_entity_insert()
 */
function hook_uc_order_product_insert(object $order_product) {
  db_insert('mytable')
    ->fields(array(
      'id' => entity_id('uc_order_product', $order_product),
      'extra' => print_r($order_product, TRUE),
    ))
    ->execute();
}

/**
 * Acts on an order product being inserted or updated.
 *
 * This hook is invoked before the order product is saved to the database.
 *
 * @param object $order_product
 *   The order product that is being inserted or updated.
 *
 * @see hook_entity_presave()
 */
function hook_uc_order_product_presave(object $order_product) {
  $order_product->name = 'foo';
}

/**
 * Responds to an order product being updated.
 *
 * This hook is invoked after the order product has been updated in the database.
 *
 * @param object $order_product
 *   The order product that is being updated.
 *
 * @see hook_entity_update()
 */
function hook_uc_order_product_update(object $order_product) {
  db_update('mytable')
    ->fields(array('extra' => print_r($order_product, TRUE)))
    ->condition('opid', entity_id('uc_order_product', $order_product))
    ->execute();
}

/**
 * Responds after order product deletion.
 *
 * This hook is invoked after the order product has been removed from the
 * database.
 *
 * @param object $order_product
 *   The order product that is being deleted.
 *
 * @see hook_entity_delete()
 * @see hook_uc_order_edit_form_product_remove()
 */
function hook_uc_order_product_delete(object $order_product) {
  db_delete('mytable')
    ->condition('opid', entity_id('uc_order_product', $order_product))
    ->execute();
}

/**
 * Allow modules to specify whether a product is shippable.
 *
 * @param \Drupal\uc_order\OrderProductInterface|\Drupal\uc_cart\CartItemInterface $product
 *   The product to check. May be a cart item or an order product.
 * @return bool
 *   TRUE to specify that this product is shippable.
 */
function hook_uc_order_product_can_ship($product) {
  $roles = db_query('SELECT * FROM {uc_roles_products} WHERE nid = :nid', [':nid' => $product->nid->target_id]);
  foreach ($roles as $role) {
    // If the model is empty, keep looking. (Everyone needs a role model...)
    if (empty($role->model)) {
      continue;
    }

    // If there's an adjusted SKU, use it... otherwise use the node SKU.
    $sku = (empty($product->data['model'])) ? $product->model->value : $product->data['model'];

    // Keep looking if it doesn't match.
    if ($sku != $role->model) {
      continue;
    }

    return $role->shippable;
  }
}

/**
 * @} End of "addtogroup hooks".
 */
