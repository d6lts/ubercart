<?php
 
/**
 * @file
 * Contains \Drupal\uc_store\Controller\CountryController.
 */
 
namespace Drupal\uc_store\Controller;

use Drupal\Core\Controller\ControllerBase;

 
/**
 *
 */
class CountryController extends ControllerBase {

  /**
   * Enables a disabled country.
   *
   * @param $country_id
   *   The ISO 3166-1 numeric country code.
   */
  public function enable($country_id) {
    $result = db_query("SELECT * FROM {uc_countries} WHERE country_id = :id", array(':id' => $country_id));
    if ($country = $result->fetchObject()) {
      if ($country->version < 0) {
        db_update('uc_countries')
          ->fields(array(
            'version' => abs($country->version),
          ))
          ->condition('country_id', $country_id)
          ->execute();
        drupal_set_message(t('@country enabled.', array('@country' => t($country->country_name))));
      }
      else {
        drupal_set_message(t('@country is already enabled.', array('@country' => t($country->country_name))), 'error');
      }
    }
    else {
      drupal_set_message(t('Attempted to enable an invalid country.'), 'error');
    }
    return $this->redirect('uc_countries.settings');
  }
 
  /**
   * Disables a country so it remains installed but is no longer selectable.
   *
   * @param $country_id
   *   The ISO 3166-1 numeric country code.
   */
  public function disable($country_id) {
    $result = db_query("SELECT * FROM {uc_countries} WHERE country_id = :id", array(':id' => $country_id));
    if ($country = $result->fetchObject()) {
      if ($country->version > 0) {
        db_update('uc_countries')
          ->fields(array(
            'version' => -$country->version,
          ))
          ->condition('country_id', $country_id)
          ->execute();
        drupal_set_message(t('@country disabled.', array('@country' => t($country->country_name))));
      }
      else {
        drupal_set_message(t('@country is already disabled.', array('@country' => t($country->country_name))), 'error');
      }
    }
    else {
      drupal_set_message(t('Attempted to disable an invalid country.'), 'error');
    }
    return $this->redirect('uc_countries.settings');
  }

  /**
   * Updates a country definition to a specific CIF file version.
   *
   * @param $country_id
   *   The ISO 3166-1 numeric country code.
   * @param $version
   *   Version number of CIF file.
   */
  public function update($country_id, $version) {
    $result = db_query("SELECT * FROM {uc_countries} WHERE country_id = :id", array(':id' => $country_id));
    if (!($country = $result->fetchObject())) {
      drupal_set_message(t('Attempted to update an invalid country.'));
      return $this->redirect('uc_countries.settings');
    }

    if ($version < $country->version) {
      drupal_set_message(t('You cannot update to a previous version.'));
      return $this->redirect('uc_countries.settings');
    }

    $func_base = importInclude($country_id, $version);
    if ($func_base !== FALSE) {
      $func = $func_base . '_update';
      if (function_exists($func)) {
        for ($i = $country->version; $i <= $version; $i++) {
          $func($i);
        }
      }

      db_update('uc_countries')
        ->fields(array(
          'version' => $version,
        ))
        ->condition('country_id', $country_id)
        ->execute();
      drupal_set_message(t('Country update complete.'));
    }
    else {
      drupal_set_message(t('Attempted to update an invalid country.'));
    }

    return $this->redirect('uc_countries.settings');
  }

  /**
   * Imports an Ubercart country file by filename.
   *
   * @param $file
   *   The filename of the country to import.
   *
   * @return
   *   TRUE or FALSE indicating whether or not the country was imported.
   */
  public function import($file) {
    require_once(drupal_get_path('module', 'uc_store') . '/countries/' . $file);

    $pieces = explode('_', substr($file, 0, strlen($file) - 4));

    $country_id = $pieces[count($pieces) - 2];
    $version = $pieces[count($pieces) - 1];
    $country = substr($file, 0, strlen($file) - strlen($country_id) - strlen($version) - 6);

    $func = $country . '_install';

    if (function_exists($func)) {
      $func();
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Includes the appropriate country file and return the base for hooks.
   *
   * @param $country_id
   *   ISO 3166-1 numeric country code for the CIF file to import.
   * @param $version
   *   Version number of the CIF to import.
   *
   * @return
   *   A string containing the portion of the filename holding the country name.
   */
  protected function importInclude($country_id, $version) {
    $dir = drupal_get_path('module', 'uc_store') . '/countries/';
    $match = '_' . $country_id . '_' . $version . '.cif';
    $matchlen = strlen($match);

    if (is_dir($dir)) {
      if ($dh = opendir($dir)) {
        while (($file = readdir($dh)) !== FALSE) {
          switch (filetype($dir . $file)) {
            case 'file':
              if (substr($file, -$matchlen) == $match) {
                require_once($dir . $file);
                return substr($file, 0, strlen($file) - $matchlen);
              }
              break;
          }
        }
        closedir($dh);
      }
    }

    return FALSE;
  }
}
