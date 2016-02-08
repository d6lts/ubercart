<?php

/**
 * @file
 * Contains \Drupal\uc_fulfillment\Tests\ShipmentTest.
 */

namespace Drupal\uc_fulfillment\Tests;

use Drupal\uc_order\Entity\Order;
use Drupal\uc_order\Entity\OrderProduct;
use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Tests creating new shipments of packaged products.
 *
 * @group Ubercart
 */
class ShipmentTest extends UbercartTestBase {

  public static $modules = array('uc_payment', 'uc_payment_pack', 'uc_fulfillment');
  public static $adminPermissions = array('fulfill orders');

  public function testShipmentsUI() {
    $this->drupalLogin($this->adminUser);
    $method = $this->createPaymentMethod('other');

    // Process an anonymous, shippable order.
    $order = Order::create([
      'uid' => 0,
      'primary_email' => $this->randomString() . '@example.org',
      'payment_method' => $method['id'],
    ]);

    // Add three more products to use for our tests.
    $products = array();
    for ($i = 1; $i <= 4; $i++) {
      $product = $this->createProduct(array('uid' => $this->adminUser->id(), 'promote' => 0));
      $order->products[$i] = OrderProduct::create(array(
        'nid' => $product->nid->target_id,
        'title' => $product->title->value,
        'model' => $product->model,
        'qty' => 1,
        'cost' => $product->cost->value,
        'price' => $product->price->value,
        'weight' => $product->weight,
        'data' => [],
      ));
      $order->products[$i]->data->shippable = 1;
    }
    $order->save();
    $order = Order::load($order->id());
    uc_payment_enter($order->id(), $method['id'], $order->getTotal());

    // Now quickly package all the products in this order.
    $this->drupalGet('admin/store/orders/' . $order->id() . '/packages');
    $this->drupalPostForm(
      NULL,
      array(
        'shipping_types[small_package][table][1][checked]' => 1,
        'shipping_types[small_package][table][2][checked]' => 1,
        'shipping_types[small_package][table][3][checked]' => 1,
        'shipping_types[small_package][table][4][checked]' => 1,
      ),
      t('Create one package')
    );

    // Test "Ship" operations for this package.
    $this->drupalGet('admin/store/orders/' . $order->id() . '/packages');
    $this->assertLink(t('Ship'));
    $this->clickLink(t('Ship'));
    $this->assertUrl('admin/store/orders/' . $order->id() . '/shipments/new?pkgs=1');
    foreach ($order->products as $sequence => $item) {
      $this->assertText(
        $item->qty->value . ' x ' . $item->model->value,
        'Product quantity x SKU found.'
      );
// Test for weight here too? How do we compute this?
    }
    // We're shipping a specific package, so it should already be checked.
    foreach ($order->products as $sequence => $item) {
      $this->assertFieldByName(
        'shipping_types[small_package][table][1][checked]',
        1,
        'Package is available for shipping.'
      );
    }
    $this->assertFieldByName(
      'method',
      'manual',
      'Manual shipping method selected.'
    );

    //
    // Test presence and operation of ship operation on order admin View.
    //
    $this->drupalGet('admin/store/orders/view');
    $this->assertLinkByHref('admin/store/orders/' . $order->id() . '/shipments');
    // Test action.
    $this->clickLink(t('Ship'));
    $this->assertResponse(200);
    $this->assertUrl('admin/store/orders/' . $order->id() . '/shipments/new');
    $this->assertText(
      'No shipments have been made for this order.',
      'Ship action found.'
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

    // Test reaching this through the shipments tab too ...


  }

}
