<?php

/**
 * @file
 * Definition of \Drupal\uc_country\CountryManager.
 */

namespace Drupal\uc_country;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Provides list of countries.
 */
class CountryManager implements CountryManagerInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * An array of country code => country name pairs.
   */
  protected $countries;

  /*
   * Constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  public function __construct(ModuleHandlerInterface $module_handler, EntityManagerInterface $entity_manager) {
    $this->moduleHandler = $module_handler;
    $this->entityManager = $entity_manager;
  }

  /**
   * Get an array of country code => country name pairs, altered by alter hooks.
   *
   * @return array
   *   An array of country code => country name pairs.
   *
   * @see \Drupal\Core\Locale\CountryManager::getStandardList()
   */
  public function getList() {
    // Populate the country list if it is not already populated.
    if (!isset($this->countries)) {
      $this->countries = \Drupal\Core\Locale\CountryManager::getStandardList();
      $this->moduleHandler->alter('countries', $this->countries);
    }

    return $this->countries;
  }

  /**
   * Get an array of country numeric code => country name pairs, altered by alter hooks.
   *
   * @return array
   *   An array of country numeric code => country name pairs.
   */
  public function getAvailableList() {
    $countries = $this->entityManager->getStorage('uc_country')->loadMultiple(NULL);
    $country_names = [];
    foreach ($countries as $alpha_2 => $country) {
      $country_names[$alpha_2] = t($country->name);
    }
    natcasesort($country_names);
    $this->moduleHandler->alter('countries', $country_names);
    return $country_names;
  }

  /**
   * Get an array of all countries enabled for checkout, altered by alter hooks.
   *
   * @return array
   *   An array of country numeric code => country name pairs.
   */
  public function getEnabledList() {
    $countries = $this->entityManager->getStorage('uc_country')->loadByProperties(['status' => TRUE]);
    $country_names = [];
    foreach ($countries as $alpha_2 => $country) {
      $country_names[$alpha_2] = t($country->name);
    }
    natcasesort($country_names);
    $this->moduleHandler->alter('countries', $country_names);
    return $country_names;
  }

  /**
   * Get an array of all countries enabled for checkout, altered by alter hooks.
   *
   * @return array
   *   An array of country numeric code => country name pairs.
   */
  public function getCountry($alpha_2) {
    return $this->entityManager->getStorage('uc_country')->load($alpha_2);
  }

  /**
   * Get an array of all countries enabled for checkout, altered by alter hooks.
   *
   * @return array
   *   An array of country numeric code => country name pairs.
   */
  public function getByProperty(array $properties) {
    $countries = $this->entityManager->getStorage('uc_country')->loadByProperties($properties);
    $country_names = [];
    foreach ($countries as $alpha_2 => $country) {
      $country_names[$alpha_2] = t($country->name);
    }
    natcasesort($country_names);
    return $country_names;
  }

  /**
   * Returns a list of zone code => zone name pairs for the specified country.
   *
   * @return array
   *   An array of zone code => zone name pairs.
   */
  public function getZoneList($alpha_2) {
    $country = $this->entityManager->getStorage('uc_country')->load($alpha_2);
    return $country->zones;
  }

  /**
   * Returns a list of zone code => zone name pairs for all enabled countries.
   *
   * @return array
   *   An array of zone code => zone name pairs.
   */
  public function getAllZones() {
    $options = array();
    $countries = entity_load_multiple_by_properties('uc_country', array('status' => TRUE));
    foreach ($countries as $country) {
      if (!empty($country->zones)) {
        $options[t($country->name)] = $country->zones;
      }
    }
    uksort($options, 'strnatcasecmp');

    return $options;
  }
}
