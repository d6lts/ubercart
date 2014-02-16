<?php

/**
 * @file
 * Views hooks and callback registries.
 */

use Drupal\Component\Utility\Unicode;

/**
 * Implements hook_views_data().
 */
function uc_order_views_data() {
  // Orders table.
  $data['uc_orders']['table']['group'] = t('Order');
  $data['uc_orders']['table']['base'] = array(
    'field' => 'order_id',
    'title' => t('Orders'),
    'help' => t('Orders placed in your Ubercart store.'),
  );

  // Order ID field.
  $data['uc_orders']['order_id'] = array(
    'title' => t('Order ID'),
    'help' => t('The order ID.'),
    'field' => array(
      'id' => 'uc_order',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'numeric',
      'name field' => 'title',
      'numeric' => TRUE,
      'validate type' => 'order_id',
    ),
  );

  // Order status field.
  $data['uc_orders']['order_status'] = array(
    'title' => t('Order status'),
    'help' => t('The order status.'),
    'field' => array(
      'id' => 'uc_order_status',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'uc_order_status',
    ),
  );

  $data['uc_orders']['uid'] = array(
    'title' => t('Uid'),
    'help' => t('The user ID that the order belongs to.'),
    'field' => array(
      'id' => 'user',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'id' => 'user_uid',
      'name field' => 'name', // display this field in the summary
    ),
    'filter' => array(
      'title' => t('Name'),
      'id' => 'user_name',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'relationship' => array(
      'title' => t('Customer'),
      'help' => t('Relate an order to the user who placed it.'),
      'base' => 'users',
      'field' => 'uid',
      'id' => 'standard',
      'label' => t('customer'),
    ),
  );

  // Expose the uid as a relationship to users.
  $data['users']['uc_orders'] = array(
    'title' => t('Orders'),
    'help' => t('Relate a user to the orders they have placed. This relationship will create one record for each order placed by the user.'),
    'relationship' => array(
      'base' => 'uc_orders',
      'base field' => 'uid',
      'relationship field' => 'uid',
      'id' => 'standard',
      'label' => t('orders'),
    ),
  );

  // Changed field handler to display as a price
  $data['uc_orders']['order_total'] = array(
    'title' => t('Order total'),
    'help' => t('The total amount to be paid for the order.'),
    'field' => array(
      'id' => 'uc_price',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
  );

  $data['uc_orders']['product_count'] = array(
    'title' => t('Product count'),
    'help' => t('The total number of products in the order.'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
  );

  $data['uc_orders']['created'] = array(
    'title' => t('Creation date'),
    'help' => t('The date and time the order was created.'),
    'field' => array(
      'id' => 'date',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'date'
    ),
    'filter' => array(
      'id' => 'date',
    ),
  );

  $data['uc_orders']['modified'] = array(
    'title' => t('Last modified'),
    'help' => t('The time the order was last modified.'),
    'field' => array(
      'id' => 'date',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'date'
    ),
    'filter' => array(
      'id' => 'date',
    ),
  );

  $data['uc_orders']['actions'] = array(
    'title' => t('Actions'),
    'help' => t('Clickable links to actions a user may perform on an order.'),
    'field' => array(
      'id' => 'uc_order_actions',
      'real field' => 'order_id',
      'click sortable' => FALSE,
    ),
  );

  $data['uc_orders']['primary_email'] = array(
    'title' => t('Email address'),
    'help' => t('The email address of the customer.'),
    'field' => array(
      'id' => 'user_mail',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
  );

  $addresses = array(
    'billing' => t('Billing address'),
    'delivery' => t('Delivery address'),
  );

  $fields = array(
    'first_name' => t('First name'),
    'last_name' => t('Last name'),
    'phone' => t('Phone number'),
    'company' => t('Company'),
    'street1' => t('Street address 1'),
    'street2' => t('Street address 2'),
    'city' => t('City'),
    'postal_code' => t('Postal code'),
  );

  foreach ($addresses as $prefix => $address) {
    $group = t('Order') . ': ' . $address;

    foreach ($fields as $field => $label) {
      $data['uc_orders'][$prefix . '_' . $field] = array(
        'group' => $group,
        'title' => $label,
        'help' => t('The !field of the !address of the order.', array('!field' => Unicode::strtolower($label), '!address' => Unicode::strtolower($address))),
        'field' => array(
          'id' => 'standard',
          'click sortable' => TRUE,
        ),
        'sort' => array(
          'id' => 'standard',
        ),
        'filter' => array(
          'id' => 'string',
        ),
      );
    }

    $data['uc_orders'][$prefix . '_full_name'] = array(
      'group' => $group,
      'title' => t('Full name'),
      'help' => t('The !field of the !address of the order.', array('!field' => t('full name'), '!address' => Unicode::strtolower($address))),
      'field' => array(
        'id' => 'uc_order_full_name',
        'real field' => $prefix . '_first_name',
        'additional fields' => array(
          'last_name' => array(
            'field' => $prefix . '_last_name'
          ),
        ),
      ),
    );

    $data[$prefix . '_countries']['table']['group'] = $group;
    $data[$prefix . '_countries']['table']['join']['uc_orders'] = array(
      'table' => 'uc_countries',
      'left_field' => $prefix . '_country',
      'field' => 'country_id',
    );
    $data[$prefix . '_countries']['country_id'] = array(
      'title' => t('ISO country code (numeric)'),
      'help' => t('The !field of the !address of the order.', array('!field' => t('numeric ISO country code'), '!address' => Unicode::strtolower($address))),
      'argument' => array(
        'id' => 'numeric',
        'name field' => 'country_iso_code_2',
        'numeric' => TRUE,
        'validate type' => 'country_id',
      ),
      'filter' => array(
        'id' => 'numeric',
      ),
    );
    $data[$prefix . '_countries']['country_name'] = array(
      'title' => t('Country'),
      'help' => t('The !field of the !address of the order.', array('!field' => t('country name'), '!address' => Unicode::strtolower($address))),
      'field' => array(
        'id' => 'standard',
        'click sortable' => TRUE,
      ),
      'sort' => array(
        'id' => 'standard',
      ),
      'filter' => array(
        'id' => 'in_operator',
        'real field' => 'country_id',
        'options callback' => 'uc_country_option_list',
      ),
    );
    $data[$prefix . '_countries']['country_iso_code_2'] = array(
      'title' => t('ISO country code (2 characters)'),
      'help' => t('The !field of the !address of the order.', array('!field' => t('ISO country code'), '!address' => Unicode::strtolower($address))),
      'field' => array(
        'id' => 'standard',
        'click sortable' => TRUE,
      ),
      'sort' => array(
        'id' => 'standard',
      ),
      'filter' => array(
        'id' => 'string',
      ),
    );
    $data[$prefix . '_countries']['country_iso_code_3'] = array(
      'title' => t('ISO country code (3 characters)'),
      'help' => t('The !field of the !address of the order.', array('!field' => t('ISO country code'), '!address' => Unicode::strtolower($address))),
      'field' => array(
        'id' => 'standard',
        'click sortable' => TRUE,
      ),
      'sort' => array(
        'id' => 'standard',
      ),
      'filter' => array(
        'id' => 'string',
      ),
    );

    $data[$prefix . '_zones']['table']['group'] = $group;
    $data[$prefix . '_zones']['table']['join']['uc_orders'] = array(
      'table' => 'uc_zones',
      'left_field' => $prefix . '_zone',
      'field' => 'zone_id',
    );
    $data[$prefix . '_zones']['zone_name'] = array(
      'title' => t('State/Province'),
      'help' => t('The !field of the !address of the order.', array('!field' => t('state or province'), '!address' => Unicode::strtolower($address))),
      'field' => array(
        'id' => 'standard',
        'click sortable' => TRUE,
      ),
      'sort' => array(
        'id' => 'standard',
      ),
      'filter' => array(
        'id' => 'in_operator',
        'real field' => 'zone_code',
        'options callback' => 'uc_zone_option_list',
      ),
    );
    $data[$prefix . '_zones']['zone_code'] = array(
      'title' => t('State/Province code'),
      'help' => t('The !field of the !address of the order.', array('!field' => t('state or province code'), '!address' => Unicode::strtolower($address))),
      'field' => array(
        'id' => 'standard',
        'click sortable' => TRUE,
      ),
      'sort' => array(
        'id' => 'standard',
      ),
      'filter' => array(
        'id' => 'string',
      ),
    );
  }

  $data['uc_orders']['total_weight'] = array(
    'title' => t('Total weight'),
    'help' => t('The physical weight of all the products (weight * quantity) in the order.'),
    'real field' => 'weight',
    'field' => array(
      'handler' => 'uc_order_handler_field_order_weight_total',
      'additional fields' => array(
        'order_id' => 'order_id',
      ),
    ),
  );

  // Ordered products.
  // Get the standard EntityAPI Views data table.
  // $data['uc_order_products'] =  entity_views_table_definition('uc_order_product');
  // // Remove undesirable fields
  // foreach(array('data') as $bad_field) {
  //   if (isset($data['uc_order_products'][$bad_field])) {
  //     unset($data['uc_order_products'][$bad_field]);
  //   }
  // }
  // // Fix incomplete fields
  // $data['uc_order_products']['weight_units']['title'] = t('Weight units');

  $data['uc_order_products']['table']['group'] = t('Ordered product');
  $data['uc_order_products']['table']['base'] = array(
    'field' => 'order_product_id',
    'title' => t('Ordered products'),
    'help' => t('Products that have been ordered in your Ubercart store.'),
  );

  // Expose products to their orders as a relationship.
  $data['uc_orders']['products'] = array(
    'relationship' => array(
      'title' => t('Products'),
      'help' => t('Relate products to an order. This relationship will create one record for each product ordered.'),
      'id' => 'standard',
      'base' => 'uc_order_products',
      'base field' => 'order_id',
      'relationship field' => 'order_id',
      'label' => t('products'),
    ),
  );

  // Expose nodes to ordered products as a relationship.
  $data['uc_order_products']['nid'] = array(
    'title' => t('Nid'),
    'help' => t('The nid of the ordered product. If you need more fields than the nid: Node relationship'),
    'relationship' => array(
      'title' => t('Node'),
      'help' => t('Relate product to node.'),
      'id' => 'standard',
      'base' => 'node',
      'field' => 'nid',
      'label' => t('node'),
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'node_nid',
    ),
    'field' => array(
      'id' => 'node',
    ),
  );

  // Expose orders to ordered products as a relationship.
  $data['uc_order_products']['order_id'] = array(
    'title' => t('Order ID'),
    'help' => t('The order ID of the ordered product. If you need more fields than the order ID: Order relationship'),
    'relationship' => array(
      'title' => t('Order'),
      'help' => t('Relate product to order.'),
      'id' => 'standard',
      'base' => 'uc_orders',
      'field' => 'order_id',
      'label' => t('order'),
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'field' => array(
      'id' => 'uc_order',
    ),
  );

  $data['uc_order_products']['model'] = array(
    'title' => t('SKU'),
    'help' => t('The product model/SKU.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['uc_order_products']['qty'] = array(
    'title' => t('Quantity'),
    'help' => t('The quantity ordered.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
  );

  $data['uc_order_products']['price'] = array(
    'title' => t('Price'),
    'help' => t('The price paid for one product.'),
    'field' => array(
      'id' => 'uc_price',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
  );

  $data['uc_order_products']['total_price'] = array(
    'title' => t('Total price'),
    'help' => t('The price paid for all the products (price * quantity).'),
    'real field' => 'price',
    'field' => array(
      'handler' => 'uc_order_handler_field_money_total',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'uc_order_handler_sort_total',
    ),
    'filter' => array(
      'handler' => 'uc_order_handler_filter_total',
    ),
  );

  $data['uc_order_products']['cost'] = array(
    'title' => t('Cost'),
    'help' => t('The cost to the store for one product.'),
    'field' => array(
      'id' => 'uc_price',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
  );

  $data['uc_order_products']['total_cost'] = array(
    'title' => t('Total cost'),
    'help' => t('The cost to the store for all the products (cost * quantity).'),
    'real field' => 'cost',
    'field' => array(
      'handler' => 'uc_order_handler_field_money_total',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'uc_order_handler_sort_total',
    ),
    'filter' => array(
      'handler' => 'uc_order_handler_filter_total',
    ),
  );

  $data['uc_order_products']['weight'] = array(
    'title' => t('Weight'),
    'help' => t('The physical weight of one product.'),
    'field' => array(
      'additional fields' => array(
        'weight_units' => array(
          'field' => 'weight_units',
        ),
      ),
      'id' => 'uc_weight',
    ),
  );

  $data['uc_order_products']['total_weight'] = array(
    'title' => t('Total weight'),
    'help' => t('The physical weight of all the products (weight * quantity).'),
    'real field' => 'weight',
    'field' => array(
      'additional fields' => array(
        'weight_units' => array(
          'field' => 'weight_units',
        ),
      ),
      'handler' => 'uc_order_handler_field_weight_total',
    ),
  );

  $data['uc_order_products']['title'] = array(
    'title' => t('Title'),
    'help' => t('The title of the product.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  // Order comments table.
  // TODO: refactor this into a groupwise max relationship.
  $data['uc_order_comments']['table']['group'] = t('Order comments');
  $data['uc_order_comments']['table']['join'] = array(
    'uc_orders' => array(
      'left_field' => 'order_id',
      'field' => 'order_id',
    ),
    'uc_order_products' => array(
      'left_table' => 'uc_orders',
      'left_field' => 'order_id',
      'field' => 'order_id',
    ),
  );

  $data['uc_order_comments']['message'] = array(
    'title' => t('Comment'),
    'help' => t('The comment body.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
  );

  // Support for any module's line item, if new modules defines other line items
  // the views cache will have to be rebuilt
  // Although new line items views support should be defined on each module,
  // I don't think this wider apporach would harm. At most, it will duplicate
  // line items
  $line_items = array();
  foreach (_uc_line_item_list() as $line_item) {
    if (!in_array($line_item['id'], array('subtotal', 'tax_subtotal', 'total', 'generic')) && $line_item['stored']) {
      $line_items[$line_item['id']] = $line_item['title'];
    }
  }
  foreach ($line_items as $line_item_id => $line_item_desc) {
    $data['uc_order_line_items_' . $line_item_id]['table']['join']['uc_orders'] = array(
      'table' => 'uc_order_line_items',
      'left_field' => 'order_id',
      'field' => 'order_id',
      'extra' => array(
        array(
          'field' => 'type',
          'value' => $line_item_id,
        ),
      ),
    );
    $data['uc_order_line_items_' . $line_item_id]['table']['join']['uc_order_products'] = $data['uc_order_line_items_' . $line_item_id]['table']['join']['uc_orders'];
    $data['uc_order_line_items_' . $line_item_id]['table']['join']['uc_order_products']['left_table'] = 'uc_orders';

    $data['uc_order_line_items_' . $line_item_id]['table']['group'] = t('Order: Line item');
    $data['uc_order_line_items_' . $line_item_id]['title'] = array(
      'title' => t('!line_item_desc title', array('!line_item_desc' => $line_item_desc)),
      'help' => t('!line_item_desc order line item', array('!line_item_desc' => $line_item_desc)),
      'field' => array(
        'id' => 'standard',
        'click sortable' => TRUE,
      ),
      'filter' => array(
        'id' => 'string',
      ),
    );

    $data['uc_order_line_items_' . $line_item_id]['amount'] = array(
      'title' => t('!line_item_desc amount', array('!line_item_desc' => $line_item_desc)),
      'help' => t('!line_item_desc order line item', array('!line_item_desc' => $line_item_desc)),
      'field' => array(
        'id' => 'uc_price',
        'click sortable' => TRUE,
      ),
      'filter' => array(
        'id' => 'numeric',
      ),
    );
  }

  return $data;
}
