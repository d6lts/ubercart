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
    $this->drupalGet('admin/store/config/payment');
    $this->assertText('Check', 'Check payment method found.');
    $this->assertFieldByName('methods[check][status]', 1, 'Check payment method is enabled by default.');

    $this->drupalGet('admin/store/config/payment/method/check');
    $this->assertTitle('Check settings | Drupal');
    $this->assertText(\Drupal::config('uc_payment_pack.check.settings')->get('policy'), 'Default check payment policy found.');

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

    //$edit = array(
    //  'uc_check_mailing_country' => $address->country,
    //);
    //$this->drupalPostAjaxForm('admin/store/config/payment/method/check', $edit, 'uc_check_mailing_country');
    $edit = array(
      'uc_check_mailing_name' => $address->first_name,
      'uc_check_mailing_company' => $address->company,
      'uc_check_mailing_street1' => $address->street1,
      'uc_check_mailing_street2' => $address->street2,
      'uc_check_mailing_city' => $address->city,
      'uc_check_mailing_postal_code' => $address->postal_code,
    );
    // Don't try to set the zone unless the store country has zones!
    $zone_list = \Drupal::service('country_manager')->getZoneList($country_id);
    if (!empty($zone_list)) {
      $address->zone = array_rand($zone_list);
      $edit += array(
        'uc_check_mailing_zone' => $address->zone,
      );
    }

    // Fool the Ajax by setting the store default country to our randomly-chosen
    // country before we post the form. Otherwise the zone select won't be
    // populated correctly.
    \Drupal::configFactory()->getEditable('uc_payment_pack.check.settings')->set('mailing_address.country', $country_id)->save();
    $this->drupalPostForm('admin/store/config/payment/method/check', $edit, t('Save configuration'));

    // Test that check settings show up on checkout page.
    $this->drupalGet('cart/checkout');
    $this->assertFieldByName('panes[payment][payment_method]', 'check', 'Check payment method is selected at checkout.');
    $this->assertText('Checks should be made out to:');
    $this->assertRaw((string) $address, 'Properly formatted check mailing address found.');
    $this->assertText(\Drupal::config('uc_payment_pack.check.settings')->get('policy'), 'Check payment policy found at checkout.');

    // Test that check settings show up on review order page.
    $this->drupalPostForm(NULL, array(), 'Review order');
    $this->assertText('Check', 'Check payment method found on review page.');
    $this->assertText('Mail to', 'Check payment method help text found on review page.');
    $this->assertRaw((string) $address, 'Properly formatted check mailing address found.');
    $this->drupalPostForm(NULL, array(), 'Submit order');

    // Test user order view
    $order = Order::load(1);
    $this->assertEqual($order->getPaymentMethodId(), 'check', 'Order has check payment method.');

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
    $this->drupalGet('admin/store/config/payment');
    $this->assertText('Cash on delivery', 'COD payment method found.');
    $this->assertFieldByName('methods[cod][status]', 0, 'COD payment method is disabled by default.');
    $edit = array(
      'methods[cod][status]' => 1,
      'methods[cod][weight]' => -10,
    );
    $this->drupalPostForm(NULL, $edit, 'Save configuration');

    $this->drupalGet('admin/store/config/payment/method/cod');
    $this->assertTitle('Cash on delivery settings | Drupal');
    $this->assertText(\Drupal::config('uc_payment_pack.cod.settings')->get('policy'), 'Default COD policy found.');
    // @todo: Test changing the policy on settings page
    // @todo: Test enabling delivery datae on settings page

    // Test checkout page
    $this->drupalGet('cart/checkout');
    $this->assertFieldByName('panes[payment][payment_method]', 'cod', 'COD payment method is selected at checkout.');
    $this->assertText(\Drupal::config('uc_payment_pack.cod.settings')->get('policy'), 'Default COD policy found at checkout.');

    // Test review order page
    $this->drupalPostForm(NULL, array(), 'Review order');
    $this->assertText('Cash on delivery', 'COD payment method found on review page.');
    $this->drupalPostForm(NULL, array(), 'Submit order');

    // Test user order view
    $order = Order::load(1);
    $this->assertEqual($order->getPaymentMethodId(), 'cod', 'Order has COD payment method.');

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
    $this->drupalGet('admin/store/config/payment');
    $this->assertText('Other', 'Other payment method found.');
    $this->assertFieldByName('methods[other][status]', 0, 'Other payment method is disabled by default.');
    $edit = array(
      'methods[other][status]' => 1,
      'methods[other][weight]' => -10,
    );
    $this->drupalPostForm(NULL, $edit, 'Save configuration');

    // Test checkout page
    $this->drupalGet('cart/checkout');
    $this->assertFieldByName('panes[payment][payment_method]', 'other', 'Other payment method is selected at checkout.');

    // Test review order page
    $this->drupalPostForm(NULL, array(), 'Review order');
    $this->assertText('Other', 'Other payment method found on review page.');
    $this->drupalPostForm(NULL, array(), 'Submit order');

    // Test user order view
    $order = Order::load(1);
    $this->assertEqual($order->getPaymentMethodId(), 'other', 'Order has other payment method.');

    $this->drupalGet('user/' . $order->getOwnerId() . '/orders/' . $order->id());
    $this->assertText('Method: Other', 'Other payment method displayed.');

    // Test admin order view
    $this->drupalGet('admin/store/orders/' . $order->id());
    $this->assertText('Method: Other', 'Other payment method displayed.');

    $this->drupalGet('admin/store/orders/' . $order->id() . '/edit');
    $this->assertFieldByName('payment_method', 'other', 'Other payment method is selected in the order edit form.');
    $edit = array(
      'payment_details[description]' => $this->randomString(),
    );
    $this->drupalPostForm(NULL, array(), 'Save changes');
    // @todo: Test storage of payment details.
  }

}
