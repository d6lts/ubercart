<?php

/**
 * @file
 * Contains \Drupal\uc_country\Tests\CountryManagerTest.
 */

namespace Drupal\uc_country\Tests;

//use Drupal\uc_store\Tests\UbercartTestBase;
use Drupal\simpletest\WebTestBase;

/**
 * Tests functionality of the extended CountryManager service.
 *
 * @group Ubercart
 */
class CountryManagerTest extends WebTestBase {

  public static $modules = array('uc_country', 'uc_store');

  /**
   * Test overriding the core Drupal country_manager service.
   */
  public function testServiceOverride() {
    $country_manager = \Drupal::service('country_manager');

    // Verify that all Drupal-provided countries were imported without error.
    $this->assertEqual(count($country_manager->getList()), 258, '258 core Drupal countries found');

    // Verify that all Ubercart-provided countries were imported without error.
    $this->assertEqual(count($country_manager->getAvailableList()), 248, '248 Ubercart countries found');

    // Verify that US and CA are enabled by default.
    $this->assertEqual(count($country_manager->getEnabledList()), 2, 'Two Ubercart countries enabled by default');

    debug($country_manager->getByProperty(['status' => TRUE]));
    debug("Count = " . count($country_manager->getByProperty(['status' => TRUE])));

  //$countries = entity_load_multiple_by_properties('uc_country', ['zones.AK' => 'Alaska']);
  //debug($countries);
    // Compare getList() to core getStandardList().
    // Test standard list alter, to make sure we don't break contrib.
    //
    // Test new functions provided by this extended service.

    // Verify that CA has 13 zones.
    $this->assertEqual(count($country_manager->getZoneList('CA')), 13, 'Canada has 13 zones');

    debug($country_manager->getByProperty(['status' => TRUE]));
    debug("Count = " . count($country_manager->getByProperty(['status' => TRUE])));

  //$countries = entity_load_multiple_by_properties('uc_country', ['zones.AK' => 'Alaska']);
  //debug($countries);
    // Compare getList() to core getStandardList().
    // Test standard list alter, to make sure we don't break contrib.
    //
    // Test new functions provided by this extended service.
  }

  /**
   * Test enable/disable of countries.
   */
  public function testCountryConfig() {
    $this->drupalLogin($this->drupalCreateUser(array('administer countries', 'administer store')));

    $this->drupalGet('admin/store/config/country');
    $this->assertLinkByHref('admin/store/config/country/US/disable', 0, 'United States is enabled by default.');
    $this->assertLinkByHref('admin/store/config/country/CA/disable', 0, 'Canada is enabled by default.');
    $this->assertLinkByHref('admin/store/config/country/BE/enable', 0, 'Belgium is not enabled by default.');

    $this->drupalGet('admin/store/config/store');
    $this->assertNoOption('edit-uc-store-country', 'BE', 'Belgium not listed as an selectable country.');

    $this->drupalGet('admin/store/config/country/BE/enable');
    $this->assertText('The country Belgium has been enabled.');
    $this->assertLinkByHref('admin/store/config/country/BE/disable', 0, 'Belgium is now enabled.');

    $this->drupalGet('admin/store/config/store');
    $this->assertOption('edit-uc-store-country', 'BE', 'Belgium listed as a selectable country.');

    $this->drupalGet('admin/store/config/country');
    $this->clickLink('Disable');
    $this->assertText('The country Belgium has been disabled.');
    $this->assertLinkByHref('admin/store/config/country/BE/enable', 0, 'Belgium is now disabled.');
  }

}
