<?php

/**
 * @file
 * Contains \Drupal\uc_order\OrderViewsData.
 */

namespace Drupal\uc_order;

use Drupal\Component\Utility\Unicode;
use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the uc_order entity type.
 */
class OrderViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['uc_orders']['order_status']['filter']['id'] = 'uc_order_status';

    $data['uc_orders']['uid']['help'] = t('The user ID that the order belongs to.');
    $data['uc_orders']['uid']['filter']['id'] = 'user_name';
    $data['uc_orders']['uid']['relationship']['title'] = t('Customer');
    $data['uc_orders']['uid']['relationship']['help'] = t('Relate an order to the user who placed it.');
    $data['uc_orders']['uid']['relationship']['label'] = t('customer');

    $data['uc_orders']['order_total']['field']['id'] = 'uc_price';

    $data['uc_orders']['actions'] = array(
      'title' => t('Actions'),
      'help' => t('Clickable links to actions a user may perform on an order.'),
      'field' => array(
        'id' => 'uc_order_actions',
        'real field' => 'order_id',
        'click sortable' => FALSE,
      ),
    );

    $data['uc_orders']['billing_country']['filter']['id'] = 'in_operator';
    $data['uc_orders']['billing_country']['filter']['options callback'] = 'Drupal\uc_country\Controller\CountryController::countryOptionsCallback';
    $data['uc_orders']['delivery_country']['filter']['id'] = 'in_operator';
    $data['uc_orders']['delivery_country']['filter']['options callback'] = 'Drupal\uc_country\Controller\CountryController::countryOptionsCallback';

    $data['uc_orders']['billing_country_name'] = array(
      'title' => t('Billing country name'),
      'help' =>  t('The country name where the bill will be sent.'),
      'field' => array(
        'id' => 'uc_country',
        'real field' => 'billing_country',
      ),
    );

    $data['uc_orders']['delivery_country_name'] = array(
      'title' => t('Delivery country name'),
      'help' =>  t('The country name of the delivery location.'),
      'field' => array(
        'id' => 'uc_country',
        'real field' => 'delivery_country',
      ),
    );

    $data['uc_orders']['billing_zone']['filter']['id'] = 'in_operator';
    $data['uc_orders']['billing_zone']['filter']['options callback'] = 'Drupal\uc_country\Controller\CountryController::zoneOptionsCallback';
    $data['uc_orders']['delivery_zone']['filter']['id'] = 'in_operator';
    $data['uc_orders']['delivery_zone']['filter']['options callback'] = 'Drupal\uc_country\Controller\CountryController::zoneOptionsCallback';

    $data['uc_orders']['billing_zone_name'] = array(
      'title' => t('Billing state/province name'),
      'help' =>  t('The state/zone/province ID where the bill will be sent.'),
      'field' => array(
        'id' => 'uc_zone',
        'real field' => 'billing_zone',
        'additional fields' => array(
          'country' => array(
            'field' => 'billing_country'
          ),
        ),
      ),
    );

    $data['uc_orders']['delivery_zone_name'] = array(
      'title' => t('Delivery state/province name'),
      'help' =>  t('The state/zone/province ID of the delivery location.'),
      'field' => array(
        'id' => 'uc_zone',
        'real field' => 'delivery_zone',
        'additional fields' => array(
          'country' => array(
            'field' => 'delivery_country'
          ),
        ),
      ),
    );

    $data['uc_orders']['billing_full_name'] = array(
      'title' => t('Billing full name'),
      'help' => t('The full name of the person paying for the order.'),
      'field' => array(
        'id' => 'uc_order_full_name',
        'real field' => 'billing_first_name',
        'additional fields' => array(
          'last_name' => array(
            'field' => 'billing_last_name'
          ),
        ),
      ),
    );

    $data['uc_orders']['delivery_full_name'] = array(
      'title' => t('Delivery full name'),
      'help' => t('The full name of the person receiving shipment.'),
      'field' => array(
        'id' => 'uc_order_full_name',
        'real field' => 'delivery_first_name',
        'additional fields' => array(
          'last_name' => array(
            'field' => 'delivery_last_name'
          ),
        ),
      ),
    );

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

    // Expose the uid as a relationship to users.
    $data['users_field_data']['uc_orders'] = array(
      'title' => t('Orders'),
      'help' => t('Relate a user to the orders they have placed. This relationship will create one record for each order placed by the user.'),
      'relationship' => array(
        'title' => t('Order'),
        'label' => t('Order'),
        'base' => 'uc_orders',
        'base field' => 'uid',
        'relationship field' => 'uid',
        'id' => 'standard',
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
        'title' => t('@line_item title', ['@line_item' => $line_item_desc]),
        'help' => t('@line_item order line item', ['@line_item' => $line_item_desc]),
        'field' => array(
          'id' => 'standard',
          'click sortable' => TRUE,
        ),
        'filter' => array(
          'id' => 'string',
        ),
      );

      $data['uc_order_line_items_' . $line_item_id]['amount'] = array(
        'title' => t('@line_item amount', ['@line_item' => $line_item_desc]),
        'help' => t('@line_item order line item', ['@line_item' => $line_item_desc]),
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
}
