<?php

/**
 * @file
 * Contains the callbacks for the default line items for orders and
 * the various functions that make line items work.
 *
 * Line items are defined using hook_uc_line_item() and use a callback to
 * handle the different processes involved in line item
 * viewing/editing/calculating. The default line items are defined in
 * uc_order_uc_line_item() in uc_order.module.
 */

/**
 * Handles the subtotal line item.
 */
function uc_line_item_subtotal($op, $order) {
  switch ($op) {
    case 'load':
      $lines[] = array(
        'id' => 'subtotal',
        'title' => t('Subtotal'),
        'amount' => $order->getSubtotal(),
      );
      return $lines;
  }
}

/**
 * Handles the total line item.
 */
function uc_line_item_total($op, $order) {
  switch ($op) {
    case 'display':
      $lines[] = array(
        'id' => 'total',
        'title' => t('Order total'),
        'amount' => $order->getTotal(),
      );
      return $lines;
  }
}

/**
 * Updates a line item.
 */
function uc_order_update_line_item($id, $title, $amount, $data = NULL) {
  $fields = array(
    'title' => $title,
    'amount' => $amount,
  );

  if (!is_null($data)) {
    $fields['data'] = serialize($data);
  }

  db_update('uc_order_line_items')
    ->fields($fields)
    ->condition('line_item_id', $id)
    ->execute();

  return TRUE;
}

/**
 * Deletes a specific line item, or every line item in an order.
 *
 * @param int $id
 *   The line item ID, or order ID.
 * @param bool $order
 *   If FALSE, deletes the line item with the specified ID (default).
 *   If TRUE, deletes all line items on the order with the specified ID.
 *
 * @return bool
 *   Always TRUE.
 */
function uc_order_delete_line_item($id, $order = FALSE) {
  if ($order === FALSE) {
    db_delete('uc_order_line_items')
      ->condition('line_item_id', $id)
      ->execute();
  }
  else {
    db_delete('uc_order_line_items')
      ->condition('order_id', $id)
      ->execute();
  }

  return TRUE;
}

/**
 * Adds a line item to an order.
 */
function uc_order_line_item_add($order_id, $type, $title, $amount, $weight = NULL, $data = NULL) {
  if (is_null($weight)) {
    $weight = _uc_line_item_data($type, 'weight');
  }

  $line_item = array(
    'order_id' => $order_id,
    'type' => $type,
    'title' => $title,
    'amount' => $amount,
    'weight' => $weight,
    'data' => serialize($data),
  );

  $line_item['line_item_id'] = db_insert('uc_order_line_items')
    ->fields($line_item)
    ->execute();

  $line_item['data'] = $data;

  return $line_item;
}

/**
 * Builds a list of line items defined in the enabled modules.
 */
function _uc_line_item_list($action = NULL) {
  static $items;

  if (count($items) > 0 && $action !== 'rebuild') {
    return $items;
  }

  $items = array();
  foreach (\Drupal::moduleHandler()->invokeAll('uc_line_item') as $id => $item) {
    // Preserve backward compatibility for methods with no key specified.
    if (is_numeric($id)) {
      $id = $item['id'];
    }

    // Set defaults.
    $item += array(
      'id' => $id,
      'enabled' => TRUE,
      'weight' => 1,
    );

    // Merge in any settings.
    $items[$id] = array_merge($item, array(
//      @todo: Is something needed here to replace variable_get ?
//      'enabled' => variable_get('uc_li_' . $id . '_enabled', $item['enabled']),
//      'weight' => variable_get('uc_li_' . $id . '_weight', $item['weight']),
      'enabled' => $item['enabled'],
      'weight' => $item['weight'],
    ));
  }

  \Drupal::moduleHandler()->alter('uc_line_item_data', $items);

  uasort($items, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

  return $items;
}

/**
 * Returns data from a line item by ID and the array key.
 */
function _uc_line_item_data($item_id, $key) {
  $items = _uc_line_item_list();
  return isset($items[$item_id][$key]) ? $items[$item_id][$key] : NULL;
}
