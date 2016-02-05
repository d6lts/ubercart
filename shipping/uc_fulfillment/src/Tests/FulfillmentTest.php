<?php

/**
 * @file
 * Contains \Drupal\uc_fulfillment\Tests\FulfillmentTest.
 */

namespace Drupal\uc_fulfillment\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Tests fulfillment backend functionality.
 *
 * @group Ubercart
 */
class FulfillmentTest extends UbercartTestBase {

  public static $modules = array('uc_payment', 'uc_payment_pack', 'uc_fulfillment');
  public static $adminPermissions = array('fulfill orders');

  /**
   * Tests packaging and shipping a simple order with the "Manual" plugin.
   */
  public function testFulfillmentProcess() {
    // Log on as administrator to fulfill order.
    $this->drupalLogin($this->adminUser);

    // A payment method for the order.
    $method = $this->createPaymentMethod('other');

    // Create an anonymous, shippable order.
    $order = $this->createOrder([
      'uid' => 0,
      'payment_method' => $method['id'],
    ]);
    $order->products[1]->data->shippable = 1;
    $order->save();

    // Check out with the test product.
    uc_payment_enter($order->id(), $method['id'], $order->getTotal());

    // Check for Packages tab and Shipments tab. BOTH should
    // redirect us to $order->id()/packages/new at this point,
    // because we have no packages or shipments yet.

    // Test Packages tab.
    $this->drupalGet('admin/store/orders/' . $order->id());
    // Test presence of tab to package products.
    $this->assertLinkByHref('admin/store/orders/' . $order->id() . '/packages');
    // Go to packages tab.
    $this->clickLink(t('Packages'));
    $this->assertResponse(200);
    // Check redirected path.
    $this->assertUrl('admin/store/orders/' . $order->id() . '/packages/new');
    $this->assertText(
      'This order\'s products have not been organized into packages.',
      'Packages tab found.'
    );

    // Test Shipments tab.
    $this->drupalGet('admin/store/orders/' . $order->id());
    // Test presence of tab to make shipments.
    $this->assertLinkByHref('admin/store/orders/' . $order->id() . '/shipments');
    // Go to Shipments tab.
    $this->clickLink(t('Shipments'));
    $this->assertResponse(200);
    // Check redirected path.
    $this->assertUrl('admin/store/orders/' . $order->id() . '/packages/new');
    $this->assertText(
      'This order\'s products have not been organized into packages.',
      'Shipments tab found.'
    );

    // Now package the products in this order.
    $this->drupalGet('admin/store/orders/' . $order->id() . '/packages');
    $this->assertText(
      $order->products[1]->title->value,
      'Product title found.'
    );
    $this->assertText(
      $order->products[1]->model->value,
      'Product sku found.'
    );
    $this->assertFieldByName(
      'shipping_types[small_package][table][' . $order->id() . '][checked]',
      0,
      'Product is available for packaging.'
    );

    // Select product and create one package.
    $this->drupalPostForm(
      NULL,
      array('shipping_types[small_package][table][' . $order->id() . '][checked]' => 1),
      t('Create one package')
    );
    // Check that we're now on the package list page.
    $this->assertUrl('admin/store/orders/' . $order->id() . '/packages');
    $this->assertText(
      $order->products[1]->qty->value . ' x ' . $order->products[1]->model->value,
      'Product quantity x SKU found.'
    );

    // Test the Shipments tab.
    $this->drupalGet('admin/store/orders/' . $order->id());
    $this->clickLink(t('Shipments'));
    $this->assertResponse(200);
    // Check redirected path.
    $this->assertUrl('admin/store/orders/' . $order->id() . '/shipments/new');
    $this->assertText(
      'No shipments have been made for this order.',
      'New shipments page reached.'
    );
    $this->assertText(
      $order->products[1]->qty->value . ' x ' . $order->products[1]->model->value,
      'Product quantity x SKU found.'
    );
    $this->assertFieldByName(
      'method',
      'manual',
      'Manual shipping method selected.'
    );

    // Select all packages and create shipment using the default "Manual" method..
    $this->drupalPostForm(
      NULL,
      array('shipping_types[small_package][table][' . $order->id() . '][checked]' => 1),
      t('Ship packages')
    );
    // Check that we're now on the shipment details page.
    $this->assertUrl('admin/store/orders/' . $order->id() . '/ship?method_id=manual&0=1');
    $this->assertText(
      'Origin address',
      'Origin address pane found.'
    );
    $this->assertText(
      'Destination address',
      'Destination address pane found.'
    );
    $this->assertText(
      'Package 1',
      'Packages data pane found.'
    );
    $this->assertText(
      'Shipment data',
      'Shipment data pane found.'
    );

    // Fill in the details and make the shipment.
    // If we filled the addresses in when we created the order,
    // those values should already be set here.
//    $this->drupalPostForm(
//      NULL,
//      array(
//        'pickup_address[pickup_address][first_name]' =>
//        'pickup_address[pickup_address][last_name]' =>
//        'pickup_address[pickup_address][company]' =>
//        'pickup_address[pickup_address][street1]' =>
//        'pickup_address[pickup_address][street2]' =>
//        'pickup_address[pickup_address][city]' =>
//        'pickup_address[pickup_address][zone]' =>
//        'pickup_address[pickup_address][country]' =>
//        'pickup_address[pickup_address][postal_code]' =>
//        'delivery_address[pickup_address][first_name]' =>
//        'delivery_address[pickup_address][last_name]' =>
//        'delivery_address[pickup_address][company]' =>
//        'delivery_address[pickup_address][street1]' =>
//        'delivery_address[pickup_address][street2]' =>
//        'delivery_address[pickup_address][city]' =>
//        'delivery_address[pickup_address][zone]' =>
//        'delivery_address[pickup_address][country]' =>
//        'delivery_address[pickup_address][postal_code]' =>
//        'packages[1][pkg_type]' =>
//        'packages[1][declared_value]' =>
//        'packages[1][tracking_number]' =>
//        'packages[1][weight][weight]' =>
//        'packages[1][weight][units]' =>
//        'packages[1][dimensions][length]' =>
//        'packages[1][dimensions][width]' =>
//        'packages[1][dimensions][height]' =>
//        'carrier' => 'FedEx',
//        'accessorials' => 'Standard Overnight',
//        'transaction_id' =>
//        'tracking_number' => '1234567890ABCD',
//        'ship_date[date]' => '1985-10-26',
//        'expected_deliver[date]' => '2015-10-21',
//        'cost' => '12.34',
//      ),
//      t('Save shipment')
//    );
    // Check that we're now on the ? page
    //$this->assertUrl('admin/store/orders/' . $order->id() . '/ship?method_id=manual&0=1');
    //$this->assertText(
    //  'Shipment data',
    //  'Shipment data pane found.'
    //);

    // Check View, Edit, Delete, Print, and Packing Slip actions and tabs.

    // Check for "Tracking" order pane after this order has
    // been shipped and a tracking number entered.
    $this->drupalGet('admin/store/orders/' . $order->id());
    $this->assertText(
      t('Tracking numbers:'),
      'Fulfillment order pane found.'
    );
    //$this->assertText(
    //  '1234567890ABCD',
    //  'Tracking number found.'
    //);
  }

}
