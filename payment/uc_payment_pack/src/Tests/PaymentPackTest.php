<?php

/**
 * @file
 * Contains \Drupal\uc_payment_pack\Tests\PaymentPackTest.
 */

namespace Drupal\uc_payment_pack\Tests;

use Drupal\uc_order\Entity\Order;
use Drupal\uc_store\Tests\UbercartTestBase;
use Drupal\uc_store\Address;

/**
 * Tests the payment method pack.
 *
 * @group Ubercart
 */
class PaymentPackTest extends UbercartTestBase {

  public static $modules = array('uc_payment', 'uc_payment_pack');

  public function setUp() {
    parent::setUp();

    // Log in and add a product to the cart for testing.
    $this->drupalLogin($this->adminUser);
    $this->addToCart($this->product);

    // Disable address panes during checkout.
    $edit = array(
      'panes[delivery][status]' => FALSE,
      'panes[billing][status]' => FALSE,
    );
    $this->drupalPostForm('admin/store/config/checkout', $edit, t('Save configuration'));
  }

  /**
   * Tests for Check payment method.
   */
  public function testCheck() {
    $this->drupalGet('admin/store/config/payment/add/check');
    $this->assertText('Check');
    $this->assertFieldByName('settings[policy]', 'Personal and business checks will be held for up to 10 business days to ensure payment clears before an order is shipped.', 'Default check payment policy found.');

    $edit = [
      'id' => strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
      'settings[policy]' => $this->randomString(),
    ];

    // Fill in and save the check address settings.
    $address = new Address();
    $address->first_name = $this->randomMachineName(6);
    $address->company = $this->randomMachineName(10);
    $address->street1 = mt_rand(100, 1000) . ' ' . $this->randomMachineName(10);
    $address->street2 = 'Suite ' . mt_rand(100, 999);
    $address->city = $this->randomMachineName(10);
    $address->postal_code = mt_rand(10000, 99999);
    $country_id = array_rand(\Drupal::service('country_manager')->getEnabledList());
    $address->country = $country_id;
    $this->drupalPostAjaxForm(NULL, ['settings[address][country]' => $address->country], 'settings[address][country]');

    $edit += array(
      'settings[name]' => $address->first_name,
      'settings[address][company]' => $address->company,
      'settings[address][street1]' => $address->street1,
      'settings[address][street2]' => $address->street2,
      'settings[address][city]' => $address->city,
      'settings[address][country]' => $address->country,
      'settings[address][postal_code]' => $address->postal_code,
    );
    // Don't try to set the zone unless the country has zones!
    $zone_list = \Drupal::service('country_manager')->getZoneList($country_id);
    if (!empty($zone_list)) {
      $address->zone = array_rand($zone_list);
      $edit += array(
        'settings[address][zone]' => $address->zone,
      );
    }

    $this->drupalPostForm(NULL, $edit, 'Save');

    // Test that check settings show up on checkout page.
    $this->drupalGet('cart/checkout');
    $this->assertFieldByName('panes[payment][payment_method]', $edit['id'], 'Check payment method is selected at checkout.');
    $this->assertText('Checks should be made out to:');
    $this->assertRaw((string) $address, 'Properly formatted check mailing address found.');
    $this->assertEscaped($edit['settings[policy]'], 'Check payment policy found at checkout.');

    // Test that check settings show up on review order page.
    $this->drupalPostForm(NULL, array(), 'Review order');
    $this->assertText('Check', 'Check payment method found on review page.');
    $this->assertText('Mail to', 'Check payment method help text found on review page.');
    $this->assertRaw((string) $address, 'Properly formatted check mailing address found.');
    $this->drupalPostForm(NULL, array(), 'Submit order');

    // Test user order view
    $order = Order::load(1);
    $this->assertEqual($order->getPaymentMethodId(), $edit['id'], 'Order has check payment method.');

    $this->drupalGet('user/' . $order->getOwnerId() . '/orders/' . $order->id());
    $this->assertText('Method: Check', 'Check payment method displayed.');

    // Test admin order view - receive check
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

    // Test that user order view shows check received
    $this->drupalGet('user/' . $order->getOwnerId() . '/orders/' . $order->id());
    $this->assertText('Check received');
    $this->assertText('Expected clear date:');
    $this->assertText($formatted, 'Check clear date found.');
  }

  /**
   * Tests for Cash on Delivery payment method.
   */
  public function testCashOnDelivery() {
    $this->drupalGet('admin/store/config/payment/add/cod');
    $this->assertFieldByName('settings[policy]', 'Full payment is expected upon delivery or prior to pick-up.', 'Default COD policy found.');

    $cod = $this->createPaymentMethod('cod', [
      'settings[policy]' => $this->randomString(),
    ]);
    // @todo: Test enabling delivery date on settings page

    // Test checkout page
    $this->drupalGet('cart/checkout');
    $this->assertFieldByName('panes[payment][payment_method]', $cod['id'], 'COD payment method is selected at checkout.');
    $this->assertEscaped($cod['settings[policy]'], 'COD policy found at checkout.');

    // Test review order page
    $this->drupalPostForm(NULL, array(), 'Review order');
    $this->assertText('Cash on delivery', 'COD payment method found on review page.');
    $this->drupalPostForm(NULL, array(), 'Submit order');

    // Test user order view
    $order = Order::load(1);
    $this->assertEqual($order->getPaymentMethodId(), $cod['id'], 'Order has COD payment method.');

    $this->drupalGet('user/' . $order->getOwnerId() . '/orders/' . $order->id());
    $this->assertText('Method: Cash on delivery', 'COD payment method displayed.');

    // Test admin order view
    $this->drupalGet('admin/store/orders/' . $order->id());
    $this->assertText('Method: Cash on delivery', 'COD payment method displayed.');
  }

  /**
   * Tests for Other payment method.
   */
  public function testOther() {
    $other = $this->createPaymentMethod('other');

    // Test checkout page
    $this->drupalGet('cart/checkout');
    $this->assertFieldByName('panes[payment][payment_method]', $other['id'], 'Other payment method is selected at checkout.');

    // Test review order page
    $this->drupalPostForm(NULL, array(), 'Review order');
    $this->assertText('Other', 'Other payment method found on review page.');
    $this->drupalPostForm(NULL, array(), 'Submit order');

    // Test user order view
    $order = Order::load(1);
    $this->assertEqual($order->getPaymentMethodId(), $other['id'], 'Order has other payment method.');

    $this->drupalGet('user/' . $order->getOwnerId() . '/orders/' . $order->id());
    $this->assertText('Method: Other', 'Other payment method displayed.');

    // Test admin order view
    $this->drupalGet('admin/store/orders/' . $order->id());
    $this->assertText('Method: Other', 'Other payment method displayed.');

    $this->drupalGet('admin/store/orders/' . $order->id() . '/edit');
    $this->assertFieldByName('payment_method', $other['id'], 'Other payment method is selected in the order edit form.');
    $edit = array(
      'payment_details[description]' => $this->randomString(),
    );
    $this->drupalPostForm(NULL, array(), 'Save changes');
    // @todo: Test storage of payment details.
  }

}
