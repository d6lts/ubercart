<?php

/**
 * @file
 * Contains \Drupal\uc_payment\Tests\PaymentPaneTest.
 */

namespace Drupal\uc_payment\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Ubercart payment related tests.
 */
class PaymentPaneTest extends UbercartTestBase {

  public static $modules = array('uc_payment', 'uc_payment_pack');

  public static function getInfo() {
    return array(
      'name' => 'Payment checkout pane',
      'description' => 'Ensures that the payment pane functions properly during checkout.',
      'group' => 'Ubercart',
    );
  }

  /**
   * Overrides WebTestBase::setUp().
   */
  public function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
    $this->addToCart($this->product);
  }

  /**
   * Verifies checkout page presents all enabled payment methods.
   */
  public function testPaymentMethodOptions() {
    // No payment methods.
    $edit = array('methods[check][status]' => FALSE);
    $this->drupalPostForm('admin/store/settings/payment', $edit, 'Save configuration');
    $this->drupalGet('cart/checkout');
    $this->assertText('Checkout cannot be completed without any payment methods enabled. Please contact an administrator to resolve the issue.');

    // Single payment method.
    $edit = array('methods[check][status]' => TRUE);
    $this->drupalPostForm('admin/store/settings/payment', $edit, 'Save configuration');
    $this->drupalGet('cart/checkout');
    $this->assertNoText('Select a payment method from the following options.');
    $this->assertFieldByXPath("//input[@name='panes[payment][payment_method]' and @disabled='disabled']");

    // Multiple payment methods.
    $edit = array('methods[other][status]' => TRUE);
    $this->drupalPostForm('admin/store/settings/payment', $edit, 'Save configuration');
    $this->drupalGet('cart/checkout');
    $this->assertText('Select a payment method from the following options.');
    $this->assertNoFieldByXPath("//input[@name='panes[payment][payment_method]' and @disabled='disabled']");
  }

  /**
   * Tests operation of uc_payment_show_order_total_preview variable.
   */
  public function testOrderTotalPreview() {
    variable_set('uc_payment_show_order_total_preview', TRUE);
    $this->drupalGet('cart/checkout');
    $this->assertText('Order total:');

    variable_set('uc_payment_show_order_total_preview', FALSE);
    $this->drupalGet('cart/checkout');
    $this->assertNoText('Order total:');
  }

  /**
   * Tests free orders.
   */
  public function testFreeOrders() {
    $free_product = $this->createProduct(array('sell_price' => 0));
    $edit = array('methods[check][status]' => TRUE);
    $this->drupalPostForm('admin/store/settings/payment', $edit, 'Save configuration');

    // Check that paid products cannot be purchased for free.
    $this->drupalGet('cart/checkout');
    $this->assertText('Check or money order');
    $this->assertNoText('No payment required');
    $this->assertNoText('Subtotal: $0.00');

    // Check that a mixture of free and paid products cannot be purchased for free.
    $this->addToCart($free_product);
    $this->drupalGet('cart/checkout');
    $this->assertText('Check or money order');
    $this->assertNoText('No payment required');
    $this->assertNoText('Subtotal: $0.00');

    // Check that free products can be purchased successfully with no payment.
    $this->drupalPostForm('cart', array(), t('Remove'));
    $this->drupalPostForm('cart', array(), t('Remove'));
    $this->addToCart($free_product);
    $this->drupalGet('cart/checkout');
    $this->assertNoText('Check or money order');
    $this->assertText('No payment required');
    $this->assertText('Subtotal: $0.00');

    // Check that this is the only available payment method.
    $this->assertNoText('Select a payment method from the following options.');
    $this->assertFieldByXPath("//input[@name='panes[payment][payment_method]' and @disabled='disabled']");
  }
}
