<?php

/**
 * @file
 * Token hooks for the uc_fulfillment module.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function uc_fulfillment_token_info() {
  $tokens = array();

  $tokens['tracking-number'] = array(
    'name' => t('Shipment tracking number(s)'),
    'description' => t('Tracking number(s) (if applicable) for product shipments.'),
  );

  return array(
    'tokens' => array('uc_order' => $tokens),
  );
}

/**
 * Implements hook_tokens().
 */
function uc_fulfillment_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = array();

  if ($type == 'uc_order' && !empty($data['uc_order'])) {
    $order = $data['uc_order'];
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'tracking-number':
          $result = db_query('SELECT tracking_number FROM {uc_shipments} WHERE order_id = :order_id', array(':order_id' => $order->id()));
          $tracking_numbers = array();
          foreach ($result as $record) {
            if ((isset($record->tracking_number)) && (!empty($record->tracking_number))) {
              $tracking_numbers[] = $record->tracking_number;
            }
          }
          $replacements[$original] = implode(', ', $tracking_numbers);
          break;
      }
    }
  }

  return $replacements;
}
