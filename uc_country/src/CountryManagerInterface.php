<?php

/**
 * @file
 * Definition of \Drupal\uc_country\CountryManagerInterface.
 */

namespace Drupal\uc_country;

/**
 * Defines a common interface for country managers.
 */
interface CountryManagerInterface extends \Drupal\Core\Locale\CountryManagerInterface {

  /**
   * Returns a list of all available country code => country name pairs.
   *
   * @return array
   *   An array of country code => country name pairs.
   */
  public function getAvailableList();
//  public function uc_country_import_list() {

  /**
   * Returns a list of country code => country name pairs for enabled countries.
   *
   * @return array
   *   An array of country code => country name pairs.
   */
  public function getEnabledList();
//  public function uc_country_option_list() {

  /**
   * Returns the uc_country config entity with the specified country code.
   *
   * @param string $id
   *   The two-character ISO 3166 country code.
   *
   * @return \Drupal\uc_country\Entity\Country
   */
  public function getCountry(string $alpha_2);
//  public function getCountryById($id) {
//  public function getCountryData($match = array(), $sort = 'country_name') {

  /**
   * Returns all uc_country config entities with the specified propertes.
   *
   * For example:
   * To obtain all enabled countries, use getByProperty(['status' => TRUE]
   * To  obtain the country with the two-character ISO 3166 code of 'ES', use
   * getByProperty(['alpha_2' => 'ES']. Any property/properties defined in
   * \Drupal\uc_country\Entity\Country may be used. Keep in mind that in most
   * cases these properties are <em>unique</em>, so this method will return
   * only one country configuration entity.
   * 
   * @param array $properties
   *   An associative array where the keys are the property names and the values
   *   are the values those properties must have.
   *
   * @return \Drupal\uc_country\Entity\Country
   *   An array of \Drupal\uc_country\Entity\Country configuration entities,
   *   keyed by Id.
   */
  public function getByProperty(array $properties);

  /**
   * Returns a list of zone code => zone name pairs for the specified country.
   *
   * @return array
   *   An array of zone code => zone name pairs.
   */
  public function getZoneList(string $alpha_2);
//  public function uc_zone_option_list() {
//  public function getZoneOptions() {


  public function getZoneById(string $id);
//  public function getZoneById($id) {

//  public function uc_zone_select($title = '', $default = NULL, $country_id = NULL, $options = array()) {
//  public function getZoneCode($zone = NULL) {

}
