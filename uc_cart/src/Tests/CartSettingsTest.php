<?php

/**
 * @file
 * Contains \Drupal\uc_cart\Tests\CartSettingsTest.
 */

namespace Drupal\uc_cart\Tests;

use Drupal\Core\Url;
use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Tests the cart settings page.
 *
 * @group Ubercart
 */
class CartSettingsTest extends UbercartTestBase {

  public static $modules = array('uc_cart', 'block');

  public function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');
  }

  public function testAddToCartMessage() {
    $this->drupalLogin($this->adminUser);

    $this->addToCart($this->product);
    $this->assertText($this->product->getTitle() . ' added to your shopping cart.');

    $this->drupalPostForm('cart', [], 'Remove');
    $this->drupalPostForm('admin/store/settings/cart', ['uc_cart_add_item_msg' => FALSE], 'Save configuration');

    $this->addToCart($this->product);
    $this->assertNoText($this->product->getTitle() . ' added to your shopping cart.');
  }

  public function testAddToCartRedirect() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/store/settings/cart');
    $this->assertField(
      'uc_add_item_redirect',
      t('Add to cart redirect field exists')
    );

    $redirect = 'admin/store';
    $this->drupalPostForm(
      'admin/store/settings/cart',
      array('uc_add_item_redirect' => $redirect),
      t('Save configuration')
    );

    $this->drupalPostForm(
      'node/' . $this->product->id(),
      [],
      t('Add to cart')
    );
    $url_pass = ($this->getUrl() == Url::fromUri('base:' . $redirect, ['absolute' => TRUE])->toString());
    $this->assertTrue(
      $url_pass,
      t('Add to cart redirect takes user to the correct URL.')
    );
  }

  public function testAddToCartQueryRedirect() {
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm(
      'admin/store/settings/cart',
      array('uc_add_item_redirect' => '<none>'),
      t('Save configuration')
    );

    $this->drupalPostForm('node/' . $this->product->id(), [], t('Add to cart'), ['query' => ['test' => 'querystring']]);
    $url = \Drupal::url('entity.node.canonical', ['node' => $this->product->id()], ['absolute' => TRUE, 'query' => ['test' => 'querystring']]);
    $this->assertTrue($this->getUrl() == $url, 'Add to cart no-redirect preserves the query string.');
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
    $product_below_limit = $this->createProduct(array('price' => $minimum_subtotal - 1));
    $product_above_limit = $this->createProduct(array('price' => $minimum_subtotal + 1));
    $this->drupalLogout();

    // Check to see if the lower priced product triggers the minimum price logic.
    $this->drupalPostForm(
      'node/' . $product_below_limit->id(),
      [],
      t('Add to cart')
    );
    $this->drupalPostForm('cart',
      [],
      t('Checkout')
    );
    $this->assertRaw(
      'The minimum order subtotal for checkout is',
      t('Prevented checkout below the minimum order total.')
    );

    // Add another product to the cart, and verify that we land on the checkout page.
    $this->drupalPostForm(
      'node/' . $product_above_limit->id(),
      [],
      t('Add to cart')
    );
    $this->drupalPostForm(
      'cart',
      [],
      t('Checkout')
    );
    $this->assertText('Enter your billing address and information here.');
  }

  public function testContinueShopping() {
    // Continue shopping link should take you back to the product page.
    $this->drupalPostForm(
      'node/' . $this->product->id(),
      [],
      t('Add to cart')
    );
    $this->assertLink(
      t('Continue shopping'),
      0,
      t('Continue shopping link appears on the page.')
    );
    $links = $this->xpath('//a[@href="' . \Drupal::url('entity.node.canonical', ['node' => $this->product->id()]) . '"]');
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
      'uc_continue_shopping_url' => 'admin/store',
    );
    $this->drupalPostForm(
      NULL,
      $settings,
      t('Save configuration')
    );

    $this->drupalPostForm(
      'cart',
      [],
      t('Continue shopping')
    );
    $url_pass = ($this->getUrl() == Url::fromUri('base:' . $settings['uc_continue_shopping_url'], ['absolute' => TRUE])->toString());
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
      'uc_cart_breadcrumb_text' => $this->randomMachineName(8),
      'uc_cart_breadcrumb_url' => $this->randomMachineName(8),
    );
    $this->drupalPostForm(
      NULL,
      $settings,
      t('Save configuration')
    );

    $this->drupalPostForm(
      'node/' . $this->product->id(),
      [],
      t('Add to cart')
    );
    $this->assertLink(
      $settings['uc_cart_breadcrumb_text'],
      0,
      t('The breadcrumb link text is set correctly.')
    );
    $links = $this->xpath('//a[@href="' . Url::fromUri('internal:/' . $settings['uc_cart_breadcrumb_url'], ['absolute' => TRUE])->toString() . '"]');
    $this->assertTrue(
      isset($links[0]),
      t('The breadcrumb link is set correctly.')
    );
  }
}
