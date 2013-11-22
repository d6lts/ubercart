<?php

/**
 * @file
 * Definition of Drupal\uc_stock\Tests\StockTest.
 */

namespace Drupal\uc_stock\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Ubercart stock related tests
 */
class StockTest extends UbercartTestBase {

  public static $modules = array('uc_stock');
  public static $adminPermissions = array('administer product stock');

  public static function getInfo() {
    return array(
      'name' => 'Stock',
      'description' => 'Ensure that stock control functions properly.',
      'group' => 'Ubercart',
    );
  }

  public function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
  }

  public function testProductStock() {
    $this->drupalGet('node/' . $this->product->id() . '/edit/stock');
    $this->assertText($this->product->label());
    $this->assertText($this->product->model, 'Product SKU found.');

    $this->assertNoFieldChecked('edit-stock-0-active', 'Stock tracking is not active.');
    $this->assertFieldByName('stock[0][stock]', '0', 'Default stock level found.');
    $this->assertFieldByName('stock[0][threshold]', '0', 'Default stock threshold found.');

    $stock = rand(1, 1000);
    $edit = array(
      'stock[0][active]' => 1,
      'stock[0][stock]' => $stock,
      'stock[0][threshold]' => rand(1, 100),
    );
    $this->drupalPostForm(NULL, $edit, t('Save changes'));
    $this->assertText('Stock settings saved.');
    $this->assertTrue(uc_stock_is_active($this->product->model));
    $this->assertEqual($stock, uc_stock_level($this->product->model));

    $stock = rand(1, 1000);
    uc_stock_set($this->product->model, $stock);
    $this->drupalGet('node/' . $this->product->id() . '/edit/stock');
    $this->assertFieldByName('stock[0][stock]', (string)$stock, 'Set stock level found.');
  }

  public function testStockDecrement() {
    $stock = rand(100, 1000);
    $edit = array(
      'stock[0][active]' => 1,
      'stock[0][stock]' => $stock,
    );
    $this->drupalPostForm('node/' . $this->product->id() . '/edit/stock', $edit, t('Save changes'));
    $this->assertText('Stock settings saved.');

    // Enable product quantity field.
    variable_set('uc_product_add_to_cart_qty', TRUE);

    $qty = rand(1, 100);
    $edit = array('qty' => $qty);
    $this->addToCart($this->product, $edit);
    $this->checkout();

    $this->assertEqual($stock - $qty, uc_stock_level($this->product->model));
  }

  public function testStockThresholdMail() {
    $edit = array('uc_stock_threshold_notification' => 1);
    $this->drupalPostForm('admin/store/settings/stock', $edit, 'Save configuration');

    $qty = rand(10, 100);
    $edit = array(
      'stock[0][active]' => 1,
      'stock[0][stock]' => $qty + 1,
      'stock[0][threshold]' => $qty,
    );
    $this->drupalPostForm('node/' . $this->product->id() . '/edit/stock', $edit, 'Save changes');

    $this->addToCart($this->product);
    $this->checkout();

    $mail = $this->drupalGetMails(array('id' => 'uc_stock_threshold'));
    $mail = array_pop($mail);
    $this->assertTrue(strpos($mail['subject'], 'Stock threshold limit reached') !== FALSE, 'Threshold mail subject is correct.');
    $this->assertTrue(strpos($mail['body'], $this->product->label()) !== FALSE, 'Mail body contains product title.');
    $this->assertTrue(strpos($mail['body'], $this->product->model) !== FALSE, 'Mail body contains SKU.');
    $this->assertTrue(strpos($mail['body'], 'has reached ' . $qty) !== FALSE, 'Mail body contains quantity.');
  }
}
