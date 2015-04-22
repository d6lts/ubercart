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
    $this->assertLink('Countries and addresses');
    $this->assertText('Store status');

    $country_id = \Drupal::config('uc_store.settings')->get('address.country');
    $edit = array(
      'uc_store_name' => $this->randomMachineName(),
      'uc_store_email' => $this->randomMachineName() . '@example.com',
      'uc_store_phone' => $this->randomMachineName(),
      'uc_store_fax' => $this->randomMachineName(),
      'uc_store_help_page' => $this->randomMachineName(),
      'uc_store_street1' => $this->randomMachineName(),
      'uc_store_street2' => $this->randomMachineName(),
      'uc_store_city' => $this->randomMachineName(),
      'uc_store_zone' => array_rand(\Drupal::service('country_manager')->getZoneList($country_id)),
      'uc_store_postal_code' => $this->randomMachineName(),
      'uc_currency_code' => $this->randomMachineName(3),
      'uc_currency_sign' => $this->randomMachineName(1),
      'uc_currency_thou' => $this->randomMachineName(1),
      'uc_currency_dec' => $this->randomMachineName(1),
      'uc_currency_prec' => mt_rand(0, 2),
    );
    $this->drupalPostForm('admin/store/settings/store', $edit, 'Save configuration');

    foreach ($edit as $name => $value) {
      $this->assertFieldByName($name, $value);
    }
  }

}
