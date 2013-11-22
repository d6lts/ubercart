<?php

/**
 * @file
 * Definition of Drupal\uc_cart\Tests\CartBlockTest.
 */

namespace Drupal\uc_cart\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Tests the cart block functionality.
 */
class CartBlockTest extends UbercartTestBase {

  public static $modules = array('uc_cart', 'block');

  public static function getInfo() {
    return array(
      'name' => 'Cart block',
      'description' => 'Ensures the cart block functions as expected.',
      'group' => 'Ubercart',
    );
  }

  function setUp() {
    parent::setUp();
  }

  function testCartBlock() {
    $block = $this->drupalPlaceBlock('uc_cart');

    // Test the empty cart block.
    $this->drupalGet('');

    $this->assertRaw('cart-block-icon-empty');
    $this->assertNoRaw('cart-block-icon-full');
    $this->assertText('There are no products in your shopping cart.');
    $this->assertText('0 Items');
    $this->assertText('Total: $0.00');
    $this->assertNoLink('View cart');
    $this->assertNoLink('Checkout');

    // Test the cart block with an item.
    $this->addToCart($this->product);
    $this->drupalGet('');

    $this->assertNoRaw('cart-block-icon-empty');
    $this->assertRaw('cart-block-icon-full');
    $this->assertNoText('There are no products in your shopping cart.');
    $this->assertText('1 ×');
    $this->assertText($this->product->label());
    $this->assertNoUniqueText(uc_currency_format($this->product->sell_price));
    $this->assertText('1 Item');
    $this->assertText('Total: ' . uc_currency_format($this->product->sell_price));
    $this->assertLink('View cart');
    $this->assertLink('Checkout');
  }

  function testHiddenCartBlock() {
    $block = $this->drupalPlaceBlock('uc_cart');
    $block->getPlugin()->setConfigurationValue('hide_empty', TRUE);
    $block->save();

    // Test the empty cart block.
    $this->drupalGet('');
    $this->assertNoText($block->label());

    // Test the cart block with an item.
    $this->addToCart($this->product);
    $this->drupalGet('');
    $this->assertText($block->label());
  }

  function testCartIcon() {
    $block = $this->drupalPlaceBlock('uc_cart');

    $this->drupalGet('');
    $this->assertRaw('cart-block-icon');

    $block->getPlugin()->setConfigurationValue('show_image', FALSE);
    $block->save();

    $this->drupalGet('');
    $this->assertNoRaw('cart-block-icon');
  }

}
