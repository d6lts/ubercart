<?php

/**
 * @file
 * Definition of Drupal\uc_catalog\Tests\UbercartCatalogTest.
 */

namespace Drupal\uc_catalog\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Tests for the Ubercart catalog.
 */
class UbercartCatalogTest extends UbercartTestBase {

  public static $modules = array('uc_catalog', 'field_ui');
  public static $adminPermissions = array('administer catalog', 'administer node fields');

  public static function getInfo() {
    return array(
      'name' => 'Catalog',
      'description' => 'Ensure that the catalog functions properly.',
      'group' => 'Ubercart',
    );
  }

  public function testCatalogRepair() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/structure/types/manage/product/fields');
    $this->assertText('taxonomy_catalog', 'Catalog taxonomy term reference field exists.');

    $this->drupalPost('admin/structure/types/manage/product/fields/node.product.taxonomy_catalog/delete', array(), t('Delete'));
    $this->assertText('The field Catalog has been deleted from the Product content type.', 'Catalog taxonomy term reference field deleted.');

    $this->drupalGet('admin/structure/types/manage/product/fields');
    $this->assertNoText('taxonomy_catalog', 'Catalog taxonomy term reference field does not exist.');

    $this->drupalGet('admin/store');
    $this->assertText('The catalog taxonomy reference field is missing.', 'Store status message mentions the missing field.');

    $this->drupalGet('admin/store/settings/catalog/repair');
    $this->assertText('The catalog taxonomy reference field has been repaired.', 'Repair message is displayed.');

    $this->drupalGet('admin/structure/types/manage/product/fields');
    $this->assertText('taxonomy_catalog', 'Catalog taxonomy term reference field exists.');
  }
}
