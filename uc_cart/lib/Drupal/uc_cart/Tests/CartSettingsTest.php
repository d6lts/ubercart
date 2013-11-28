<?php

/**
 * @file
 * Contains Drupal\uc_cart\Tests\CartSettingsTest.
 */

namespace Drupal\uc_cart\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Tests the cart settings page.
 */
class CartSettingsTest extends UbercartTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Cart settings',
      'description' => 'Tests the cart settings page.',
      'group' => 'Ubercart',
    );
  }

  public function testAddToCartRedirect() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/store/settings/cart');
    $this->assertField(
      'uc_add_item_redirect',
      t('Add to cart redirect field exists')
    );

    $redirect = $this->randomName(8);
    $this->drupalPostForm(
      'admin/store/settings/cart',
      array('uc_add_item_redirect' => $redirect),
      t('Save configuration')
    );

    $this->drupalPostForm(
      'node/' . $this->product->id(),
      array(),
      t('Add to cart')
    );
    $url_pass = ($this->getUrl() == url($redirect, array('absolute' => TRUE)));
    $this->assertTrue(
      $url_pass,
      t('Add to cart redirect takes user to the correct URL.')
    );

    $this->drupalPostForm(
      'admin/store/settings/cart',
      array('uc_add_item_redirect' => '<none>'),
      t('Save configuration')
    );

    $this->drupalPostForm('node/' . $this->product->id(), array(), t('Add to cart'), array('query' => array('test' => 'querystring')));
    $url = url('node/' . $this->product->id(), array('absolute' => TRUE, 'query' => array('test' => 'querystring')));
    $this->assertTrue($this->getUrl() == $url, 'Add to cart no-redirect works with a query string.');
  }

  public function testMinimumSubtotal() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/store/settings/cart');
    $this->assertField(
      'uc_minimum_subtotal',
      t('Minimum order subtotal field exists')
    );

    $minimum_subtotal = mt_rand(2, 9999);
    $this->drupalPostForm(
      NULL,
      array('uc_minimum_subtotal' => $minimum_subtotal),
      t('Save configuration')
    );

    // Create two products, one below the minimum price, and one above the minimum price.
    $product_below_limit = $this->createProduct(array('sell_price' => $minimum_subtotal - 1));
    $product_above_limit = $this->createProduct(array('sell_price' => $minimum_subtotal + 1));
    $this->drupalLogout();

    // Check to see if the lower priced product triggers the minimum price logic.
    $this->drupalPostForm(
      'node/' . $product_below_limit->id(),
      array(),
      t('Add to cart')
    );
    $this->drupalPostForm('cart',
      array(),
      t('Checkout')
    );
    $this->assertRaw(
      'The minimum order subtotal for checkout is',
      t('Prevented checkout below the minimum order total.')
    );

    // Add another product to the cart, and verify that we land on the checkout page.
    $this->drupalPostForm(
      'node/' . $product_above_limit->id(),
      array(),
      t('Add to cart')
    );
    $this->drupalPostForm(
      'cart',
      array(),
      t('Checkout')
    );
    $this->assertText('Enter your billing address and information here.');
  }

  public function testContinueShopping() {
    // Continue shopping link should take you back to the product page.
    $this->drupalPostForm(
      'node/' . $this->product->id(),
      array(),
      t('Add to cart')
    );
    $this->assertLink(
      t('Continue shopping'),
      0,
      t('Continue shopping link appears on the page.')
    );
    $links = $this->xpath('//a[@href="' . url('node/' . $this->product->id(), array('absolute' => FALSE)) . '"]');
    $this->assertTrue(
      isset($links[0]),
      t('Continue shopping link returns to the product page.')
    );

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/store/settings/cart');
    $this->assertField(
      'uc_continue_shopping_type',
      t('Continue shopping element display field exists')
    );
    $this->assertField(
      'uc_continue_shopping_url',
      t('Default continue shopping link URL field exists')
    );

    // Test continue shopping button that sends users to a fixed URL.
    $settings = array(
      'uc_continue_shopping_type' => 'button',
      'uc_continue_shopping_use_last_url' => FALSE,
      'uc_continue_shopping_url' => $this->randomName(8),
    );
    $this->drupalPostForm(
      NULL,
      $settings,
      t('Save configuration')
    );

    $this->drupalPostForm(
      'cart',
      array(),
      t('Continue shopping')
    );
    $url_pass = ($this->getUrl() == url($settings['uc_continue_shopping_url'],
      array('absolute' => TRUE)));
    $this->assertTrue(
      $url_pass,
      t('Continue shopping button takes the user to the correct URL.')
    );
  }

  public function testCartBreadcrumb() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/store/settings/cart');
    $this->assertField(
      'uc_cart_breadcrumb_text',
      t('Custom cart breadcrumb text field exists')
    );
    $this->assertField(
      'uc_cart_breadcrumb_url',
      t('Custom cart breadcrumb URL')
    );

    $settings = array(
      'uc_cart_breadcrumb_text' => $this->randomName(8),
      'uc_cart_breadcrumb_url' => $this->randomName(7),
    );
    $this->drupalPostForm(
      NULL,
      $settings,
      t('Save configuration')
    );

    $this->drupalPostForm(
      'node/' . $this->product->id(),
      array(),
      t('Add to cart')
    );
    $this->assertLink(
      $settings['uc_cart_breadcrumb_text'],
      0,
      t('The breadcrumb link text is set correctly.')
    );
    $links = $this->xpath('//a[@href="' . url($settings['uc_cart_breadcrumb_url']) . '"]');
    $this->assertTrue(
      isset($links[0]),
      t('The breadcrumb link is set correctly.')
    );
  }
}
