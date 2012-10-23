<?php

/**
 * @file
 * Definition of Drupal\uc_taxes\Tests\UbercartStoredTaxesTest.
 */

namespace Drupal\uc_taxes\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Tests stored tax data.
 */
class UbercartStoredTaxesTest extends UbercartTestBase {

  public static $modules = array('uc_cart', 'uc_payment', 'uc_payment_pack', 'uc_taxes');
  public static $adminPermissions = array('administer rules', 'configure taxes');

  public static function getInfo() {
    return array(
      'name' => 'Stored tax data',
      'description' => 'Ensures that historical tax data is stored correctly, and that the proper amount is displayed.',
      'group' => 'Ubercart',
    );
  }

  function loadTaxLine($order_id) {
    $order = uc_order_load($order_id, TRUE);
    foreach ($order->line_items as $line) {
      if ($line['type'] == 'tax') {
        return $line;
      }
    }
    return FALSE;
  }

  function assertTaxLineCorrect($line, $rate, $when) {
    $this->assertTrue($line, t('The tax line item was saved to the order ' . $when));
    $this->assertTrue(number_format($rate * $this->product->sell_price, 2) == number_format($line['amount'], 2), t('Stored tax line item has the correct amount ' . $when));
    $this->assertFieldByName('line_items[' . $line['line_item_id'] . '][li_id]', $line['line_item_id'], t('Found the tax line item ID ' . $when));
    $this->assertText($line['title'], t('Found the tax title ' . $when));
    $this->assertText(uc_currency_format($line['amount']), t('Tax display has the correct amount ' . $when));
  }

  function testTaxDisplay() {
    $this->drupalLogin($this->adminUser);

    // Enable a payment method for the payment preview checkout pane.
    $edit = array('uc_payment_method_check_checkout' => 1);
    $this->drupalPost('admin/store/settings/payment', $edit, t('Save configuration'));

    // Create a 20% inclusive tax rate.
    $rate = (object) array(
      'id' => 0, // TODO: should not have to set this
      'name' => $this->randomName(8),
      'rate' => 0.2,
      'taxed_product_types' => array('product'),
      'taxed_line_items' => array(),
      'weight' => 0,
      'shippable' => 0,
      'display_include' => 1,
      'inclusion_text' => '',
    );
    uc_taxes_rate_save($rate);

    $this->drupalGet('admin/store/settings/taxes');
    $this->assertText($rate->name, t('Tax was saved successfully.'));

    $this->drupalGet("admin/store/settings/taxes/manage/uc_taxes_$rate->id");
    $this->assertText(t('Conditions'), t('Rules configuration linked to tax.'));

    $this->drupalPost('node/' . $this->product->nid, array(), t('Add to cart'));

    // Manually step through checkout. $this->checkout() doesn't know about taxes.
    $this->drupalPost('cart', array(), 'Checkout');
    $this->assertText(
      t('Enter your billing address and information here.'),
      t('Viewed cart page: Billing pane has been displayed.')
    );
    $this->assertRaw($rate->name, t('Tax line item displayed.'));
    $this->assertRaw(uc_currency_format($rate->rate * $this->product->sell_price), t('Correct tax amount displayed.'));

    // Build the panes.
    $zone_id = db_query_range('SELECT zone_id FROM {uc_zones} WHERE zone_country_id = :country ORDER BY rand()', 0, 1, array('country' => variable_get('uc_store_country', 840)))->fetchField();
    $edit = array(
      'panes[delivery][delivery_first_name]' => $this->randomName(10),
      'panes[delivery][delivery_last_name]' => $this->randomName(10),
      'panes[delivery][delivery_street1]' => $this->randomName(10),
      'panes[delivery][delivery_city]' => $this->randomName(10),
      'panes[delivery][delivery_zone]' => $zone_id,
      'panes[delivery][delivery_postal_code]' => mt_rand(10000, 99999),

      'panes[billing][billing_first_name]' => $this->randomName(10),
      'panes[billing][billing_last_name]' => $this->randomName(10),
      'panes[billing][billing_street1]' => $this->randomName(10),
      'panes[billing][billing_city]' => $this->randomName(10),
      'panes[billing][billing_zone]' => $zone_id,
      'panes[billing][billing_postal_code]' => mt_rand(10000, 99999),
    );

    // Submit the checkout page.
    $this->drupalPost('cart/checkout', $edit, t('Review order'));
    $this->assertRaw(t('Your order is almost complete.'));
    $this->assertRaw($rate->name, t('Tax line item displayed.'));
    $this->assertRaw(uc_currency_format($rate->rate * $this->product->sell_price), t('Correct tax amount displayed.'));

    // Complete the review page.
    $this->drupalPost(NULL, array(), t('Submit order'));

    $order_id = db_query("SELECT order_id FROM {uc_orders} WHERE delivery_first_name = :name", array(':name' => $edit['panes[delivery][delivery_first_name]']))->fetchField();
    if ($order_id) {
      $this->pass(
        t('Order %order_id has been created', array('%order_id' => $order_id))
      );

      $this->drupalGet('admin/store/orders/' . $order_id . '/edit');
      $this->assertTaxLineCorrect($this->loadTaxLine($order_id), $rate->rate, 'on initial order load');

      $this->drupalPost('admin/store/orders/' . $order_id . '/edit', array(), t('Submit changes'));
      $this->assertText(t('Order changes saved.'));
      $this->assertTaxLineCorrect($this->loadTaxLine($order_id), $rate->rate, 'after saving order');

      // Change tax rate and ensure order doesn't change.
      $oldrate = $rate->rate;
      $rate->rate = 0.1;
      $rate = uc_taxes_rate_save($rate);

      // Save order because tax changes are only updated on save.
      $this->drupalPost('admin/store/orders/' . $order_id . '/edit', array(), t('Submit changes'));
      $this->assertText(t('Order changes saved.'));
      $this->assertTaxLineCorrect($this->loadTaxLine($order_id), $oldrate, 'after rate change');

      // Change taxable products and ensure order doesn't change.
      $class = $this->createProductClass();
      $rate->taxed_product_types = array($class->name);
      uc_taxes_rate_save($rate);
      entity_flush_caches();
      $this->drupalPost('admin/store/orders/' . $order_id . '/edit', array(), t('Submit changes'));
      $this->assertText(t('Order changes saved.'));
      $this->assertTaxLineCorrect($this->loadTaxLine($order_id), $oldrate, 'after applicable product change');

      // Change order Status back to in_checkout and ensure tax-rate changes now update the order.
      uc_order_update_status($order_id, 'in_checkout');
      $this->drupalPost('admin/store/orders/' . $order_id . '/edit', array(), t('Submit changes'));
      $this->assertText(t('Order changes saved.'));
      $this->assertFalse($this->loadTaxLine($order_id), t('The tax line was removed from the order when order status changed back to in_checkout.'));

      // Restore taxable product and ensure new tax is added.
      $rate->taxed_product_types = array('product');
      uc_taxes_rate_save($rate);
      $this->drupalPost('admin/store/orders/' . $order_id . '/edit', array(), t('Submit changes'));
      $this->assertText(t('Order changes saved.'));
      $this->assertTaxLineCorrect($this->loadTaxLine($order_id), $rate->rate, 'when order status changed back to in_checkout');
    }
    else {
      $this->fail(t('No order was created.'));
    }
  }

}
