<?php

/**
 * @file
 * Contains \Drupal\uc_payment_pack\Tests\PaymentPackTest.
 */

namespace Drupal\uc_payment_pack\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Payment method pack tests.
 */
class PaymentPackTest extends UbercartTestBase {

  public static $modules = array('uc_payment', 'uc_payment_pack');

  public static function getInfo() {
    return array(
      'name' => 'Payment method pack',
      'description' => 'Ensures that the payment pack methods function correctly.',
      'group' => 'Ubercart',
    );
  }

  public function setUp() {
    parent::setUp();

    // Log in and add a product to the cart for testing.
    $this->drupalLogin($this->adminUser);
    $this->addToCart($this->product);

    // Disable address panes during checkout.
    variable_set('uc_pane_delivery_enabled', FALSE);
    variable_set('uc_pane_billing_enabled', FALSE);
  }

  public function testCheck() {
    $this->drupalGet('admin/store/settings/payment');
    $this->assertText('Check', 'Check payment method found.');
    $this->assertFieldByName('methods[check][status]', 1, 'Check payment method is enabled by default.');

    $this->drupalGet('admin/store/settings/payment/method/check');
    $this->assertTitle('Check settings | Drupal');
    // @todo: Fix and test the settings page

    $this->drupalGet('cart/checkout');
    $this->assertFieldByName('panes[payment][payment_method]', 'check', 'Check payment method is selected at checkout.');
    $this->assertText('Checks should be made out to:');
    // @todo: Test the settings
    // $this->assertText('Personal and business checks will be held for up to 10 business days to ensure payment clears before an order is shipped.');

    $this->drupalPostForm(NULL, array(), 'Review order');
    $this->assertText('Check', 'Check payment method found on review page.');
    $this->assertText('Mail to', 'Check payment method help text found on review page.');
    // @todo: Test the settings

    $this->drupalPostForm(NULL, array(), 'Submit order');

    $order = entity_load('uc_order', 1);
    $this->assertEqual($order->getPaymentMethodId(), 'check', 'Order has check payment method.');

    $this->drupalGet('user/' . $order->getUserId() . '/orders/' . $order->id());
    $this->assertText('Method: Check', 'Check payment method displayed.');

    $this->drupalGet('admin/store/orders/' . $order->id());
    $this->assertText('Method: Check', 'Check payment method displayed.');
    $this->assertLink('Receive Check');

    $this->clickLink('Receive Check');
    $this->assertFieldByName('amount', number_format($order->getTotal(), 2, '.', ''), 'Amount field defaults to order total.');

    $edit = array(
      'comment' => $this->randomString(),
      'clear_month' => mt_rand(1, 12),
      'clear_day' => mt_rand(1, 28),
      'clear_year' => date('Y') + mt_rand(0, 1),
    );
    $formatted = sprintf('%02d-%02d-%d', $edit['clear_month'], $edit['clear_day'], $edit['clear_year']);
    $this->drupalPostForm(NULL, $edit, 'Receive check');

    $this->assertNoLink('Receive Check');
    $this->assertText('Clear Date: ' . $formatted, 'Check clear date found.');

    $this->drupalGet('user/' . $order->getUserId() . '/orders/' . $order->id());
    $this->assertText('Check received');
    $this->assertText('Expected clear date:');
    $this->assertText($formatted, 'Check clear date found.');
  }

  public function testCashOnDelivery() {
    $this->drupalGet('admin/store/settings/payment');
    $this->assertText('Cash on delivery', 'COD payment method found.');
    $this->assertFieldByName('methods[cod][status]', 0, 'COD payment method is disabled by default.');
    $edit = array(
      'methods[cod][status]' => 1,
      'methods[cod][weight]' => -10,
    );
    $this->drupalPostForm(NULL, $edit, 'Save configuration');

    $this->drupalGet('admin/store/settings/payment/method/cod');
    $this->assertTitle('Cash on delivery settings | Drupal');
    // @todo: Fix and test the settings page

    $this->drupalGet('cart/checkout');
    $this->assertFieldByName('panes[payment][payment_method]', 'cod', 'COD payment method is selected at checkout.');
    // @todo: Test the settings
    // $this->assertText('Full payment is expected upon delivery or prior to pick-up.');

    $this->drupalPostForm(NULL, array(), 'Review order');
    $this->assertText('Cash on delivery', 'COD payment method found on review page.');
    // @todo: Test the settings

    $this->drupalPostForm(NULL, array(), 'Submit order');

    $order = entity_load('uc_order', 1);
    $this->assertEqual($order->getPaymentMethodId(), 'cod', 'Order has COD payment method.');

    $this->drupalGet('user/' . $order->getUserId() . '/orders/' . $order->id());
    $this->assertText('Method: Cash on delivery', 'COD payment method displayed.');

    $this->drupalGet('admin/store/orders/' . $order->id());
    $this->assertText('Method: Cash on delivery', 'COD payment method displayed.');
  }

  public function testOther() {
    $this->drupalGet('admin/store/settings/payment');
    $this->assertText('Other', 'Other payment method found.');
    $this->assertFieldByName('methods[other][status]', 0, 'Other payment method is disabled by default.');
    $edit = array(
      'methods[other][status]' => 1,
      'methods[other][weight]' => -10,
    );
    $this->drupalPostForm(NULL, $edit, 'Save configuration');

    $this->drupalGet('cart/checkout');
    $this->assertFieldByName('panes[payment][payment_method]', 'other', 'Other payment method is selected at checkout.');

    $this->drupalPostForm(NULL, array(), 'Review order');
    $this->assertText('Other', 'Other payment method found on review page.');

    $this->drupalPostForm(NULL, array(), 'Submit order');

    $order = entity_load('uc_order', 1);
    $this->assertEqual($order->getPaymentMethodId(), 'other', 'Order has other payment method.');

    $this->drupalGet('admin/store/orders/' . $order->id() . '/edit');
    $this->assertFieldByName('payment_method', 'other', 'Other payment method is selected in the order edit form.');
    $edit = array(
      'payment_details[description]' => $this->randomString(),
    );
    $this->drupalPostForm(NULL, array(), 'Save changes');
    // @todo: Fix storage of payment details.

    $this->drupalGet('user/' . $order->getUserId() . '/orders/' . $order->id());
    $this->assertText('Method: Other', 'Other payment method displayed.');

    $this->drupalGet('admin/store/orders/' . $order->id());
    $this->assertText('Method: Other', 'Other payment method displayed.');
  }

}
