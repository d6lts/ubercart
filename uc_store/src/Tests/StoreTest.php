<?php

/**
 * @file
 * Contains \Drupal\uc_store\Tests\StoreTest.
 */

namespace Drupal\uc_store\Tests;

/**
 * Tests basic store functionality.
 *
 * @group Ubercart
 */
class StoreTest extends UbercartTestBase {

  public function testStoreAdmin() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/store');
    $this->assertTitle('Store | Drupal');
    $this->assertText('Configuration');
    $this->assertLink('Store');
//    $this->assertLink('Countries and addresses');
    $this->assertText('Store status');

    $edit = array(
      'uc_store_name' => $this->randomName(),
      'uc_store_email' => $this->randomName() . '@example.com',
      'uc_store_phone' => $this->randomName(),
      'uc_store_fax' => $this->randomName(),
      'uc_store_help_page' => $this->randomName(),
      'uc_store_street1' => $this->randomName(),
      'uc_store_street2' => $this->randomName(),
      'uc_store_city' => $this->randomName(),
      'uc_store_zone' => mt_rand(1, 65),
      'uc_store_postal_code' => $this->randomName(),
      'uc_currency_code' => $this->randomName(3),
      'uc_currency_sign' => $this->randomName(1),
      'uc_currency_thou' => $this->randomName(1),
      'uc_currency_dec' => $this->randomName(1),
      'uc_currency_prec' => mt_rand(0, 2),
    );
    $this->drupalPostForm('admin/store/settings/store', $edit, 'Save configuration');

    foreach ($edit as $name => $value) {
      $this->assertFieldByName($name, $value);
    }
  }

}
