<?php

/**
 * @file
 * Contains Drupal\uc_cart\Tests\CheckoutSettingsTest.
 */

namespace Drupal\uc_cart\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Tests the checkout settings page.
 */
class CheckoutSettingsTest extends UbercartTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Checkout settings',
      'description' => 'Tests the checkout settings page.',
      'group' => 'Ubercart',
    );
  }

  public function testEnableCheckout() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/store/settings/checkout');
    $this->assertField(
      'uc_checkout_enabled',
      t('Enable checkout field exists')
    );

    $this->drupalPostForm(
      'admin/store/settings/checkout',
      array('uc_checkout_enabled' => FALSE),
      t('Save configuration')
    );

    $this->drupalPostForm(
      'node/' . $this->product->id(),
      array(),
      t('Add to cart')
    );
    $this->assertNoRaw(t('Checkout'));
    $buttons = $this->xpath('//input[@value="' . t('Checkout') . '"]');
    $this->assertFalse(
      isset($buttons[0]),
      t('The checkout button is not shown.')
    );
  }

  public function testAnonymousCheckout() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/store/settings/checkout');
    $this->assertField(
      'uc_checkout_anonymous',
      t('Anonymous checkout field exists')
    );

    $this->drupalPostForm(
      'admin/store/settings/checkout',
      array('uc_checkout_anonymous' => FALSE),
      t('Save configuration')
    );

    $this->drupalLogout();
    $this->drupalPostForm(
      'node/' . $this->product->id(),
      array(),
      t('Add to cart')
    );
    $this->drupalPostForm(
      'cart',
      array(), 'Checkout');
    $this->assertNoText(
      'Enter your billing address and information here.',
      t('The checkout page is not displayed.')
    );
  }
}
