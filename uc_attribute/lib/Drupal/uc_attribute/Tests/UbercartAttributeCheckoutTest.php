<?php

/**
 * @file
 * Definition of Drupal\uc_attribute\Tests\UbercartAttributeCheckoutTest.
 */

namespace Drupal\uc_attribute\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;
use stdClass;

/**
 * Ubercart attribute checkout tests.
 */
class UbercartAttributeCheckoutTest extends UbercartTestBase {

  public static $modules = array('uc_attribute');
  public static $adminPermissions = array('administer attributes', 'administer product attributes', 'administer product options');

  public static function getInfo() {
    return array(
      'name' => 'Attribute Checkout',
      'description' => 'Test ordering products with attributes.',
      'group' => 'Ubercart',
    );
  }

  /**
   * Tests that product in cart has the selected attribute option.
   */
  function testAttributeAddToCart() {
    for ($display = 0; $display <= 3; ++$display) {
      // Set up an attribute.
      $data = array(
        'display' => $display,
      );
      $attribute = UbercartAttributeTest::createAttribute($data);

      if ($display) {
        // Give the attribute an option.
        $option = UbercartAttributeTest::createAttributeOption(array('aid' => $attribute->aid));
      }

      $attribute = uc_attribute_load($attribute->aid);

      // Put the attribute on a product.
      $product = $this->createProduct();
      uc_attribute_subject_save($attribute, 'product', $product->nid, TRUE);

      // Add the product to the cart.
      if ($display == 3) {
        $edit = array("attributes[$attribute->aid][$option->oid]" => $option->oid);
      }
      elseif (isset($option)) {
        $edit = array("attributes[$attribute->aid]" => $option->oid);
      }
      else {
        $option = new stdClass();
        $option->name = self::randomName();
        $option->price = 0;
        $edit = array("attributes[$attribute->aid]" => $option->name);
      }

      $this->drupalPost('node/' . $product->nid, $edit, t('Add to cart'));
      $this->assertText("$attribute->label: $option->name", t('Option selected on cart item.'));
      $this->assertText(uc_currency_format($product->sell_price + $option->price), t('Product has adjusted price.'));
    }
  }

}
