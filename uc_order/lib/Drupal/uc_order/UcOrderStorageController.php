<?php

/**
 * @file
 * Definition of Drupal\uc_order\UcOrderStorageController.
 */

namespace Drupal\uc_order;

use Drupal\Core\Entity\DatabaseStorageControllerNG;

/**
 * Controller class for orders.
 */
class UcOrderStorageController extends DatabaseStorageControllerNG {

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::create().
   */
  public function create(array $values) {
    $store_config = config('uc_store.settings');

    // Set the primary email address.
    if (empty($values['primary_email']) && !empty($values['uid'])) {
      if ($account = user_load($values['uid'])) {
        $values['primary_email'] = $account->mail;
      }
    }

    // Set the default order status.
    if (empty($values['order_status'])) {
      $values['order_status'] = uc_order_state_default('in_checkout');
    }

    // Set the default currency.
    if (empty($values['currency'])) {
      $values['currency'] = $store_config->get('currency.code');
    }

    // Set the default country codes.
    if (empty($values['billing_country'])) {
      $values['billing_country'] = $store_config->get('address.country');
    }
    if (empty($values['delivery_country'])) {
      $values['delivery_country'] = $store_config->get('address.country');
    }

    // Set the created time to now.
    if (empty($values['created'])) {
      $values['created'] = REQUEST_TIME;
    }

    return parent::create($values)->getBCEntity();
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::attachLoad().
   */
  protected function attachLoad(&$queried_entities, $load_revision = FALSE) {
    $orders = $this->mapFromStorageRecords($queried_entities, $load_revision);

    foreach ($orders as $id => $order) {
      $order = $order->getBCEntity();
      $queried_entities[$id] = $order;

      $order->data = unserialize($order->data);

      $order->products = entity_load_multiple_by_properties('uc_order_product', array('order_id' => $order->id()));
      foreach ($order->products as $product) {
        $product->order = $order;
      }

      uc_order_module_invoke('load', $order, NULL);

      // Load line items... has to be last after everything has been loaded.
      $order->line_items = uc_order_load_line_items($order);

      $fields = array();

      if (($count = uc_order_get_product_count($order)) !== $order->product_count) {
        $fields['product_count'] = $count;
        $order->product_count = $count;
      }

      if (count($fields)) {
        $query = db_update('uc_orders')
          ->fields($fields)
          ->condition('order_id', $order->id())
          ->execute();
      }
    }

//    parent::attachLoad($queried_entities, $load_revision);
  }

  /**
   * {@inheritdoc}
   */
  public function baseFieldDefinitions() {
    $properties['order_id'] = array(
      'label' => t('Order ID'),
      'description' => t('The order ID.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $properties['uid'] = array(
      'label' => t('Customer'),
      'description' => 'The user that placed the order.',
      'type' => 'entity_reference_field',
      'settings' => array('target_type' => 'user'),
    );
    $properties['order_status'] = array(
      'label' => t('Order status'),
      'description' => 'The {uc_order_statuses}.order_status_id indicating the order status.',
      'type' => 'string_field',
    );
    $properties['order_total'] = array(
      'label' => t('Order total'),
      'description' => 'The total amount to be paid for the order.',
      'type' => 'integer_field',
      // 'type' => 'float',
    );
    $properties['product_count'] = array(
      'label' => t('Product count'),
      'description' => 'The total product quantity of the order.',
      'type' => 'integer_field',
    );
    $properties['primary_email'] = array(
      'label' => t('Primary e-mail'),
      'description' => 'The email address of the customer.',
      'type' => 'string_field',
    );
    $properties['delivery_first_name'] = array(
      'label' => t('Delivery first name'),
      'description' => 'The first name of the person receiving shipment.',
      'type' => 'string_field',
    );
    $properties['delivery_last_name'] = array(
      'label' => t('Delivery last name'),
      'description' => 'The last name of the person receiving shipment.',
      'type' => 'string_field',
    );
    $properties['delivery_phone'] = array(
      'label' => t('Delivery phone'),
      'description' => 'The phone number at the delivery location.',
      'type' => 'string_field',
    );
    $properties['delivery_company'] = array(
      'label' => t('Delivery company'),
      'description' => 'The company at the delivery location.',
      'type' => 'string_field',
    );
    $properties['delivery_street1'] = array(
      'label' => t('Delivery street 1'),
      'description' => 'The street address of the delivery location.',
      'type' => 'string_field',
    );
    $properties['delivery_street2'] = array(
      'label' => t('Delivery street 2'),
      'description' => 'The second line of the street address.',
      'type' => 'string_field',
    );
    $properties['delivery_city'] = array(
      'label' => t('Delivery city'),
      'description' => 'The city of the delivery location.',
      'type' => 'string_field',
    );
    $properties['delivery_zone'] = array(
      'label' => t('Delivery state/province'),
      'description' => 'The state/zone/province id of the delivery location.',
      'type' => 'integer_field',
    );
    $properties['delivery_postal_code'] = array(
      'label' => t('Delivery postal code'),
      'description' => 'The postal code of the delivery location.',
      'type' => 'string_field',
    );
    $properties['delivery_country'] = array(
      'label' => t('Delivery country'),
      'description' => 'The country ID of the delivery location.',
      'type' => 'integer_field',
    );
    $properties['billing_first_name'] = array(
      'label' => t('Billing first name'),
      'description' => 'The first name of the person paying for the order.',
      'type' => 'string_field',
    );
    $properties['billing_last_name'] = array(
      'label' => t('Billing last name'),
      'description' => 'The last name of the person paying for the order.',
      'type' => 'string_field',
    );
    $properties['billing_phone'] = array(
      'label' => t('Billing phone'),
      'description' => 'The phone number for the billing address.',
      'type' => 'string_field',
    );
    $properties['billing_company'] = array(
      'label' => t('Billing company'),
      'description' => 'The company of the billing address.',
      'type' => 'string_field',
    );
    $properties['billing_street1'] = array(
      'label' => t('Billing street 1'),
      'description' => 'The street address where the bill will be sent.',
      'type' => 'string_field',
    );
    $properties['billing_street2'] = array(
      'label' => t('Billing street 2'),
      'description' => 'The second line of the street address.',
      'type' => 'string_field',
    );
    $properties['billing_city'] = array(
      'label' => t('Billing city'),
      'description' => 'The city where the bill will be sent.',
      'type' => 'string_field',
    );
    $properties['billing_zone'] = array(
      'label' => t('Billing state/province'),
      'description' => 'The state/zone/province ID where the bill will be sent.',
      'type' => 'integer_field',
    );
    $properties['billing_postal_code'] = array(
      'label' => t('Billing postal code'),
      'description' => 'The postal code where the bill will be sent.',
      'type' => 'string_field',
    );
    $properties['billing_country'] = array(
      'label' => t('Billing country'),
      'description' => 'The country ID where the bill will be sent.',
      'type' => 'integer_field',
    );
    $properties['payment_method'] = array(
      'label' => t('Payment method'),
      'description' => 'The method of payment.',
      'type' => 'string_field',
    );
    $properties['data'] = array(
      'label' => t('Data'),
      'description' => 'A serialized array of extra data.',
      'type' => 'string_field',
    );
    $properties['created'] = array(
      'label' => t('Created'),
      'description' => 'The Unix timestamp indicating when the order was created.',
      'type' => 'integer_field',
    );
    $properties['modified'] = array(
      'label' => t('Modified'),
      'description' => 'The Unix timestamp indicating when the order was last modified.',
      'type' => 'integer_field',
    );
    $properties['host'] = array(
      'label' => t('Host'),
      'description' => 'Host IP address of the person paying for the order.',
      'type' => 'string_field',
    );
    $properties['currency'] = array(
      'label' => t('Currency'),
      'description' => 'The ISO currency code for the order.',
      'type' => 'string_field',
      // 'settings' => array(
      //   'default_value' => '',
      // ),
      // 'property_constraints' => array(
      //   'value' => array('Length' => array('max' => 3)),
      // ),
    );
    return $properties;
  }

}
