<?php

/**
 * @file
 * Contains \Drupal\uc_country\Tests\CountryTest.
 */

namespace Drupal\uc_country\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Import, edit, and remove countries and their settings.
 *
 * @group Ubercart
 */
class CountryTest extends UbercartTestBase {

  /**
   * Test import/enable/disable/remove of Country information files.
   */
  public function testCountries() {
    $import_file = 'belgium_56_3.cif';
    $country_name = 'Belgium';
    $country_code = 'BEL';

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/store/settings/country');
    $this->assertRaw(
      '<option value="' . $import_file . '">' . $import_file . '</option>',
      t('Ensure country file is not imported yet.')
    );

    $edit = array(
      'import_file[]' => array($import_file => $import_file),
    );
    $this->drupalPostForm(
      'admin/store/settings/country',
      $edit,
      t('Import')
    );
    $this->assertText(
      t('Country file @file imported.', ['@file' => $import_file]),
      t('Country was imported successfully.')
    );
    $this->assertText(
      $country_code,
      t('Country appears in the imported countries table.')
    );
    $this->assertNoRaw(
      '<option value="' . $import_file . '">' . $import_file . '</option>',
      t('Country does not appear in list of files to be imported.')
    );

    // Have to pick the right one here!
    $this->clickLink(t('Disable'));
    $this->assertText(
      t('@name disabled.', ['@name' => $country_name]),
      t('Country was disabled.')
    );

    $this->clickLink(t('Enable'));
    $this->assertText(
      t('@name enabled.', ['@name' => $country_name]),
      t('Country was enabled.')
    );

    $this->clickLink(t('Remove'));
    $this->assertText(
      t('Are you sure you want to remove @name from the system?', ['@name' => $country_name]),
      t('Confirm form is displayed.')
    );

    $this->drupalPostForm(
      'admin/store/settings/country/56/remove',
      [],
      t('Remove')
    );
    $this->assertText(
      t('@name removed.', ['@name' => $country_name]),
      t('Country removed.')
    );
    $this->assertRaw(
      '<option value="' . $import_file . '">' . $import_file . '</option>',
      t('Ensure country file is not imported yet.')
    );
    $this->assertNoText(
      $country_code,
      t('Country does not appear in imported countries table.')
    );
  }
}
