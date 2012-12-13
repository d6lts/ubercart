<?php

/**
 * @file
 * Views hooks and callback registries.
 */

/**
 * Implements hook_views_data().
 */
function uc_payment_views_data() {
  $data['uc_orders']['payment_method'] = array(
    'title' => t('Payment method'),
    'help' => t('The method of payment.'),
    'field' => array(
      'id' => 'uc_payment_method',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'uc_payment_method',
    ),
  );

  return $data;
}
