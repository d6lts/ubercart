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
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   */
  public function __construct(ModuleHandlerInterface $module_handler, EntityManagerInterface $entity_manager) {
    $this->moduleHandler = $module_handler;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
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
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function getCountry($alpha_2) {
    return $this->entityManager->getStorage('uc_country')->load($alpha_2);
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function getZoneList($alpha_2) {
    if ($country = $this->entityManager->getStorage('uc_country')->load($alpha_2)) {
      return $country->zones;
    }
    return array();
  }

}
