<?php

/**
 * @file
 * Definition of Drupal\uc_taxes\Tests\InclusiveTaxesTest.
 */

namespace Drupal\uc_taxes\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Tests the inclusive taxes functionality.
 */
class InclusiveTaxesTest extends UbercartTestBase {

  public static $modules = array('uc_product_kit', 'uc_attribute', 'uc_cart', 'uc_payment', 'uc_payment_pack', 'uc_taxes');
  public static $adminPermissions = array(/*'administer rules', */'configure taxes');

  public static function getInfo() {
    return array(
      'name' => 'Inclusive taxes',
      'description' => 'Ensures that inclusive taxes are calculated and displayed correctly.',
      'group' => 'Ubercart',
    );
  }

  function testProductKitAttributes() {
    $this->drupalLogin($this->adminUser);

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
      'inclusion_text' => $this->randomName(6),
    );
    uc_taxes_rate_save($rate);

    // Ensure Rules picks up the new condition.
    // entity_flush_caches();

    // Create a $10 product.
    $product = $this->createProduct(array(
      'sell_price' => 10,
    ));

    // Create an attribute.
    $attribute = (object) array(
      'name' => $this->randomName(8),
      'label' => $this->randomName(8),
      'description' => $this->randomName(8),
      'required' => TRUE,
      'display' => 1,
      'ordering' => 0,
    );
    uc_attribute_save($attribute);

    // Create an option with a price adjustment of $5.
    $option = (object) array(
      'aid' => $attribute->aid,
      'name' => $this->randomName(8),
      'cost' => 0,
      'price' => 5,
      'weight' => 0,
      'ordering' => 0,
    );
    uc_attribute_option_save($option);

    // Attach the attribute to the product.
    $attribute = uc_attribute_load($attribute->aid);
    uc_attribute_subject_save($attribute, 'product', $product->id(), TRUE);

    // Create a product kit containing the product.
    $kit = $this->drupalCreateNode(array(
      'type' => 'product_kit',
      'products' => array($product->id()),
      'default_qty' => 1,
      'ordering' => 0,
      'mutable' => UC_PRODUCT_KIT_UNMUTABLE_WITH_LIST,
    ));

    // Set the kit total to $9 to automatically apply a discount.
    $kit = node_load($kit->id());
    $kit->kit_total = 9;
    $kit->save();
    $kit = node_load($kit->id());
    $this->assertEqual($kit->products[$product->id()]->discount, -1, 'Product kit component has correct discount applied.');

    // Ensure the price is displayed tax-inclusively on the add-to-cart form.
    $this->drupalGet('node/' . $kit->id());
    $this->assertText('$10.80' . $rate->inclusion_text, 'Tax inclusive price on node-view form is accurate.'); // $10.80 = $9.00 + 20%
    $this->assertRaw($option->name . ', +$6.00</option>', 'Tax inclusive option price on node view form is accurate.'); // $6.00 = $5.00 + 20%

    // Add the product kit to the cart, selecting the option.
    $attribute_key = 'products[' . $product->id() . '][attributes][' . $attribute->aid . ']';
    $this->addToCart($kit, array($attribute_key => $option->oid));

    // Check that the subtotal is $16.80 ($10 base + $5 option - $1 discount, with 20% tax)
    $this->drupalGet('cart');
    $this->assertText('Subtotal: $16.80', 'Order subtotal is correct on cart page.');

    // Make sure that the subtotal is also correct on the checkout page.
    $this->drupalPostForm('cart', array(), 'Checkout');
    $this->assertText('Subtotal: $16.80', 'Order subtotal is correct on checkout page.');

    // Manually proceed to checkout review.
    $zone_id = db_query_range('SELECT zone_id FROM {uc_zones} WHERE zone_country_id = :country ORDER BY rand()', 0, 1, array('country' => config('uc_store.settings')->get('address.country')))->fetchField();
    $edit = array(
      'panes[delivery][first_name]' => $this->randomName(10),
      'panes[delivery][last_name]' => $this->randomName(10),
      'panes[delivery][street1]' => $this->randomName(10),
      'panes[delivery][city]' => $this->randomName(10),
      'panes[delivery][zone]' => $zone_id,
      'panes[delivery][postal_code]' => mt_rand(10000, 99999),

      'panes[billing][first_name]' => $this->randomName(10),
      'panes[billing][last_name]' => $this->randomName(10),
      'panes[billing][street1]' => $this->randomName(10),
      'panes[billing][city]' => $this->randomName(10),
      'panes[billing][zone]' => $zone_id,
      'panes[billing][postal_code]' => mt_rand(10000, 99999),
    );
    $this->drupalPostForm('cart/checkout', $edit, t('Review order'));
    $this->assertRaw(t('Your order is almost complete.'));

    // Make sure the price is still listed tax-inclusively.
    // !TODO This could be handled more specifically with a regex.
    $this->assertText('$16.80' . $rate->inclusion_text, 'Tax inclusive price appears in cart pane on checkout review page');

    // Ensure the tax-inclusive price is listed on the order admin page.
    $order_id = db_query("SELECT order_id FROM {uc_orders} WHERE delivery_first_name = :name", array(':name' => $edit['panes[delivery][first_name]']))->fetchField();
    $this->assertTrue($order_id, 'Order was created successfully');
    $this->drupalGet('admin/store/orders/' . $order_id);
    $this->assertText('$16.80' . $rate->inclusion_text, 'Tax inclusive price appears on the order view page.');

    // And on the invoice.
    $this->drupalGet('admin/store/orders/' . $order_id . '/invoice');
    $this->assertText('$16.80' . $rate->inclusion_text, 'Tax inclusive price appears on the invoice.');

    // And on the printable invoice.
    $this->drupalGet('admin/store/orders/' . $order_id . '/invoice');
    $this->assertText('$16.80' . $rate->inclusion_text, 'Tax inclusive price appears on the printable invoice.');
  }
}
