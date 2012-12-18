<?php

/**
 * @file
 * Views hooks for Ubercart products.
 */

/**
 * Implements hook_views_data().
 */
function uc_product_views_data() {
  $data['uc_products']['table']['group'] = t('Product');

  $data['uc_products']['table']['join'] = array(
    'node' => array(
      'left_field' => 'vid',
      'field' => 'vid',
    ),
    'node_revisions' => array(
      'left_field' => 'vid',
      'field' => 'vid',
    ),
  );

  $data['uc_products']['model'] = array(
    'title' => t('SKU'),
    'help' => t('The model number.'),
    'field' => array(
      'id' => 'node',
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

  $data['uc_products']['list_price'] = array(
    'title' => t('List price'),
    'help' => t("The manufacturer's suggested price."),
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

  $data['uc_products']['cost'] = array(
    'title' => t('Cost'),
    'help' => t('The cost of the product to the store.'),
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

  $data['uc_products']['sell_price'] = array(
    'title' => t('Sell price'),
    'help' => t('The price for which the product is sold.'),
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

  $data['uc_products']['display_price'] = array(
    'title' => t('Display price'),
    'help' => t('The price for which the product is sold, altered for display (e.g. with inclusive taxes).'),
    'field' => array(
      'id' => 'uc_product_display_price',
      'real field' => 'nid',
      'click sortable' => TRUE,
    ),
  );

  $data['uc_products']['weight'] = array(
    'title' => t('Weight'),
    'help' => t('The physical weight.'),
    'field' => array(
      'additional fields' => array(
        'field' => 'weight_units',
      ),
      'handler' => 'uc_product_handler_field_weight',
      'click sortable' => TRUE,
      'float' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
  );

  $data['uc_products']['length'] = array(
    'title' => t('Length'),
    'help' => t('The physical length.'),
    'field' => array(
      'additional fields' => array(
        'field' => 'length_units',
      ),
      'handler' => 'uc_product_handler_field_length',
      'click sortable' => TRUE,
      'float' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
  );

  $data['uc_products']['width'] = array(
    'title' => t('Width'),
    'help' => t('The physical width.'),
    'field' => array(
      'additional fields' => array(
        'field' => 'length_units',
      ),
      'handler' => 'uc_product_handler_field_length',
      'click sortable' => TRUE,
      'float' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
  );
  $data['uc_products']['height'] = array(
    'title' => t('Height'),
    'help' => t('The physical height.'),
    'field' => array(
      'additional fields' => array(
        'field' => 'length_units',
      ),
      'handler' => 'uc_product_handler_field_length',
      'click sortable' => TRUE,
      'float' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
  );

  $data['uc_products']['pkg_qty'] = array(
    'title' => t('Package quantity'),
    'help' => t('The number of items that fit in the largest package.'),
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

  $data['uc_products']['ordering'] = array(
    'title' => t('List position'),
    'help' => t('The default sort criteria in the catalog.'),
    'field' => array(
      'help' => t("The product list position."),
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
  );

  $data['uc_products']['shippable'] = array(
    'title' => t('Shippable'),
    'help' => t('Is the product shippable?'),
    'field' => array(
      'handler' => 'boolean',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'handler' => 'boolean',
    ),
  );

  $data['uc_products']['is_product'] = array(
    'title' => t('Is a product'),
    'help' => t('Check for the fields provided by the Ubercart product module.'),
    'group' => t('Node'),
    'filter' => array(
      'handler' => 'uc_product_handler_filter_product',
      'label' => t('Is a product'),
    ),
  );

  $data['uc_products']['addtocartlink'] = array(
    'title' => t('Add to cart form'),
    'help' => t("Form to put the product in the customer's cart."),
    'group' => t('Product'),
    'field' => array(
      'additional fields' => array(
        'nid' => array(
          'table' => 'node',
          'field' => 'nid',
        ),
        'type' => array(
          'table' => 'node',
          'field' => 'type',
        ),
      ),
      'handler' => 'uc_product_handler_field_addtocart',
      'element type' => 'div',
    ),
  );

  $data['uc_products']['buyitnowbutton'] = array(
    'title' => t('Buy it now button'),
    'help' => t('A button to add a product to the cart without quantity or attribute fields.'),
    'group' => t('Product'),
    'field' => array(
      'table' => 'node',
      'additional fields' => array(
        'nid' => array(
          'table' => 'node',
          'field' => 'nid',
        ),
      ),
      'handler' => 'uc_product_handler_field_buyitnow',
      'element type' => 'div',
    ),
  );

  return $data;
}
