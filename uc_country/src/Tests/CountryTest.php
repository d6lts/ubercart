<?php

/**
 * @file
 * Contains \Drupal\uc_country\Tests\CountryTest.
 */

namespace Drupal\uc_country\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Import, edit, and remove countries and their settings.
 *
 * @group Ubercart
 */
class CountryTest extends WebTestBase {

  public static $modules = array('uc_country', 'uc_store');

  /**
   * Test enable/disable of countries.
   */
  public function testCountryUI() {
    $this->drupalLogin($this->drupalCreateUser(array('administer countries', 'administer store')));

    $this->drupalGet('admin/store/config/country');
    $this->assertLinkByHref('admin/store/config/country/US/disable', 0, 'United States is enabled by default.');
    $this->assertLinkByHref('admin/store/config/country/CA/disable', 0, 'Canada is enabled by default.');
    $this->assertLinkByHref('admin/store/config/country/BE/enable', 0, 'Belgium is not enabled by default.');

    $this->drupalGet('admin/store/config/store');
    $this->assertNoOption('edit-uc-store-country', 'BE', 'Belgium not listed in uc_address select country field.');

    $this->drupalGet('admin/store/config/country/BE/enable');
    $this->assertText('The country Belgium has been enabled.');
    $this->assertLinkByHref('admin/store/config/country/BE/disable', 0, 'Belgium is now enabled.');

    $this->drupalGet('admin/store/config/store');
    $this->assertOption('edit-uc-store-country', 'BE', 'Belgium listed in uc_address select country field.');

    $this->drupalGet('admin/store/config/country');
    $this->clickLink('Disable');
    $this->assertText('The country Belgium has been disabled.');
    $this->assertLinkByHref('admin/store/config/country/BE/enable', 0, 'Belgium is now disabled.');
  }
}
