<?php

/**
 * @file
 * Definition of Drupal\uc_product\Tests\UbercartProductTest.
 */

namespace Drupal\uc_product\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;

class UbercartProductTest extends UbercartTestBase {

  public static $adminPermissions = array('administer content types');

  public static function getInfo() {
    return array(
      'name' => 'Products',
      'description' => 'Ensure that the product content types provided function properly.',
      'group' => 'Ubercart',
    );
  }

  public function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
  }

  public function testProductAdmin() {
    $this->drupalGet('admin/store/products/view');
    $this->assertText('Title');
    $this->assertText($this->product->title);
    $this->assertText('Price');
    $this->assertText(uc_currency_format($this->product->sell_price));
  }

  public function testProductNodeForm() {
    $this->drupalGet('node/add/product');

    foreach (array('model', 'list_price', 'cost', 'sell_price', 'shippable', 'weight', 'weight_units', 'dim_length', 'dim_width', 'dim_height', 'length_units', 'pkg_qty', 'ordering') as $field) {
      $this->assertFieldByName($field, NULL);
    }
    $this->assertFieldByName('files[uc_product_image_und_0][]', NULL);

    $body_key = 'body[und][0][value]';

    // Make a node with those fields.
    $edit = array(
      'title' => $this->randomName(32),
      $body_key => $this->randomName(64),
      'model' => $this->randomName(8),
      'list_price' => mt_rand(1, 200),
      'cost' => mt_rand(0, 100),
      'sell_price' => mt_rand(1, 150),
      'shippable' => mt_rand(0, 1),
      'weight' => mt_rand(1, 50),
      'weight_units' => array_rand(array(
        'lb' => t('Pounds'),
        'kg' => t('Kilograms'),
        'oz' => t('Ounces'),
        'g'  => t('Grams'),
      )),
      'dim_length' => mt_rand(1, 50),
      'dim_width' => mt_rand(1, 50),
      'dim_height' => mt_rand(1, 50),
      'length_units' => array_rand(array(
        'in' => t('Inches'),
        'ft' => t('Feet'),
        'cm' => t('Centimeters'),
        'mm' => t('Millimeters'),
      )),
      'pkg_qty' => 1,
    );
    $this->drupalPost('node/add/product', $edit, 'Save');

    $this->assertText(t('Product @title has been created.', array('@title' => $edit['title'])), 'Product created.');
    $this->assertText($edit[$body_key], 'Product body found.');
    $this->assertText($edit['model'], 'Product model found.');
    $this->assertText(uc_currency_format($edit['list_price']), 'Product list price found.');
    $this->assertText(uc_currency_format($edit['cost']), 'Product cost found.');
    $this->assertNoUniqueText(uc_currency_format($edit['sell_price']), 'Product sell price found.');
    $this->assertText(uc_weight_format($edit['weight'], $edit['weight_units']), 'Product weight found.');
    $this->assertText(uc_length_format($edit['dim_length'], $edit['length_units']), 'Product length found.');
    $this->assertText(uc_length_format($edit['dim_width'], $edit['length_units']), 'Product width found.');
    $this->assertText(uc_length_format($edit['dim_height'], $edit['length_units']), 'Product height found.');

    $elements = $this->xpath('//body[contains(@class, "uc-product-node")]');
    $this->assertEqual(count($elements), 1, t('Product page contains body CSS class.'));

    // Update the node fields.
    $edit = array(
      'title' => $this->randomName(32),
      $body_key => $this->randomName(64),
      'model' => $this->randomName(8),
      'list_price' => mt_rand(1, 200),
      'cost' => mt_rand(0, 100),
      'sell_price' => mt_rand(1, 150),
      'shippable' => mt_rand(0, 1),
      'weight' => mt_rand(1, 50),
      'weight_units' => array_rand(array(
        'lb' => t('Pounds'),
        'kg' => t('Kilograms'),
        'oz' => t('Ounces'),
        'g'  => t('Grams'),
      )),
      'dim_length' => mt_rand(1, 50),
      'dim_width' => mt_rand(1, 50),
      'dim_height' => mt_rand(1, 50),
      'length_units' => array_rand(array(
        'in' => t('Inches'),
        'ft' => t('Feet'),
        'cm' => t('Centimeters'),
        'mm' => t('Millimeters'),
      )),
    );
    $this->clickLink('Edit');
    $this->drupalPost(NULL, $edit, 'Save');

    $this->assertText(t('Product @title has been updated.', array('@title' => $edit['title'])), 'Product updated.');
    $this->assertText($edit[$body_key], 'Updated product body found.');
    $this->assertText($edit['model'], 'Updated product model found.');
    $this->assertText(uc_currency_format($edit['list_price']), 'Updated product list price found.');
    $this->assertText(uc_currency_format($edit['cost']), 'Updated product cost found.');
    $this->assertNoUniqueText(uc_currency_format($edit['sell_price']), 'Updated product sell price found.');
    $this->assertText(uc_weight_format($edit['weight'], $edit['weight_units']), 'Product weight found.');
    $this->assertText(uc_length_format($edit['dim_length'], $edit['length_units']), 'Product length found.');
    $this->assertText(uc_length_format($edit['dim_width'], $edit['length_units']), 'Product width found.');
    $this->assertText(uc_length_format($edit['dim_height'], $edit['length_units']), 'Product height found.');

    $this->clickLink('Edit');
    $this->drupalPost(NULL, array(), 'Delete');
    $this->drupalPost(NULL, array(), 'Delete');
    $this->assertText(t('Product @title has been deleted.', array('@title' => $edit['title'])), 'Product deleted.');
  }

  public function testProductClassForm() {
    // Try making a new product class.
    $class = strtolower($this->randomName(12));
    $edit = array(
      'type' => $class,
      'name' => $class,
      'description' => $this->randomName(32),
      'settings[uc_product][product]' => 1,
    );
    $this->drupalPost('admin/structure/types/add', $edit, t('Save content type'));
    $this->assertTrue(uc_product_is_product($class), 'The new content type is a product class.');

    // Make an existing node type a product class.
    $type = $this->drupalCreateContentType();
    $class = $type->type;
    $edit = array(
      'settings[uc_product][product]' => 1,
    );

    $this->drupalPost('admin/structure/types/manage/' . $class, $edit, t('Save content type'));
    $this->assertTrue(uc_product_is_product($class), 'The updated content type is a product class.');
  }

  public function testProductQuantity() {
    variable_set('uc_product_add_to_cart_qty', TRUE);

    // Check zero quantity message.
    $this->drupalPost('node/' . $this->product->nid, array('qty' => '0'), 'Add to cart');
    $this->assertText('The quantity cannot be zero.');

    // Check invalid quantity messages.
    $this->drupalPost('node/' . $this->product->nid, array('qty' => 'x'), 'Add to cart');
    $this->assertText('The quantity must be a number.');

    $this->drupalPost('node/' . $this->product->nid, array('qty' => '1a'), 'Add to cart');
    $this->assertText('The quantity must be a number.');

    // Check cart add message.
    $this->drupalPost('node/' . $this->product->nid, array('qty' => '1'), 'Add to cart');
    $this->assertText($this->product->title . ' added to your shopping cart.');

    // Check cart update message.
    $this->drupalPost('node/' . $this->product->nid, array('qty' => '1'), 'Add to cart');
    $this->assertText('Your item(s) have been updated.');
  }
}
