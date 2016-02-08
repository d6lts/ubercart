<?php

/**
 * @file
 * Contains \Drupal\uc_store\AddressTrait.
 */

namespace Drupal\uc_store;

/**
 * Defines the address trait.
 */
trait AddressTrait {

  /**
   * The unique address identifier.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable nickname of the location.
   *
   * @var string
   */
  protected $label;

  /**
   * Given name.
   *
   * @var string
   */
  public $first_name = '';

  /**
   * Surname.
   *
   * @var string
   */
  public $last_name = '';

  /**
   * Company or organization.
   *
   * @var string
   */
  public $company = '';

  /**
   * First line of street address.
   *
   * @var string
   */
  public $street1 = '';

  /**
   * Second line of street address.
   *
   * @var string
   */
  public $street2 = '';

  /**
   * City name.
   *
   * @var string
   */
  public $city = '';

  /**
   * State, provence, or region id.
   *
   * @var string
   */
  public $zone = '';

  /**
   * Postal code.
   *
   * @var string
   */
  public $postal_code = '';

  /**
   * ISO 3166-1 2-character numeric country code.
   *
   * @var string
   */
  public $country = '';

  /**
   * Telephone number.
   *
   * @var string
   */
  public $phone = '';

  /**
   * Email address.
   *
   * @var string
   */
  public $email = '';

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    $this->id = $id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFirstName() {
    return $this->first_name;
  }

  /**
   * {@inheritdoc}
   */
  public function setFirstName($first_name) {
    $this->first_name = $first_name;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastName() {
    return $this->last_name;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastName($last_name) {
    $this->last_name = $last_name;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompany() {
    return $this->company;
  }

  /**
   * {@inheritdoc}
   */
  public function setCompany($company) {
    $this->company = $company;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStreet1() {
    return $this->street1;
  }

  /**
   * {@inheritdoc}
   */
  public function setStreet1($street1) {
    $this->street1 = $street1;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStreet2() {
    return $this->street2;
  }

  /**
   * {@inheritdoc}
   */
  public function setStreet2($street2) {
    $this->street2 = $street2;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCity() {
    return $this->city;
  }

  /**
   * {@inheritdoc}
   */
  public function setCity($city) {
    $this->city = $city;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getZone() {
    return $this->zone;
  }

  /**
   * {@inheritdoc}
   */
  public function setZone($zone) {
    $this->zone = $zone;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPostalCode() {
    return $this->postal_code;
  }

  /**
   * {@inheritdoc}
   */
  public function setPostalCode($postal_code) {
    $this->postal_code = $postal_code;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountry() {
    return $this->country;
  }

  /**
   * {@inheritdoc}
   */
  public function setCountry($country) {
    $this->country = $country;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPhone() {
    return $this->phone;
  }

  /**
   * {@inheritdoc}
   */
  public function setPhone($phone) {
    $this->phone = $phone;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail() {
    return $this->email;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmail($email) {
    $this->email = $email;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isSamePhysicalLocation(AddressInterface $address) {
    $physicalProperty = array(
      'street1', 'street2', 'city', 'zone', 'country', 'postal_code'
    );

    foreach ($physicalProperty as $property) {
      // Canonicalize properties before comparing.
      if (Address::makeCanonical($this->$property)   !=
          Address::makeCanonical($address->$property)  ) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function makeCanonical($string = '') {
    // Remove all whitespace.
    $string = preg_replace('/\s+/', '', $string);
    // Make all characters upper case.
    $string = Unicode::strtoupper($string);

    return $string;
  }

  /**
   * Formats the address for display based on the country's address format.
   *
   * @return
   *   A formatted string containing the address.
   */
  public function __toString() {
    $variables = array(
      '!company' => $this->company,
      '!first_name' => $this->first_name,
      '!last_name' => $this->last_name,
      '!street1' => $this->street1,
      '!street2' => $this->street2,
      '!city' => $this->city,
      '!postal_code' => $this->postal_code,
    );

    $country = \Drupal::service('country_manager')->getCountry($this->country);
    if ($country) {
      $variables += array(
        '!zone_code' => $this->zone ?: t('N/A'),
        '!zone_name' => isset($country->getZones()[$this->zone]) ? $country->getZones()[$this->zone] : t('Unknown'),
        '!country_name' => t($country->getName()),
        '!country_code2' => $country->id(),
        '!country_code3' => $country->getAlpha3(),
      );
      $format = implode("\r\n", $country->getAddressFormat());
    }
    else {
      $variables += array(
        '!zone_code' => t('N/A'),
        '!zone_name' => t('Unknown'),
        '!country_name' => t('Unknown'),
        '!country_code2' => t('N/A'),
        '!country_code3' => t('N/A'),
      );
      $format = "!company\r\n!first_name !last_name\r\n!street1\r\n!street2\r\n!city, !zone_code !postal_code\r\n!country_name_if";
    }

    if (uc_store_default_country() != $this->country) {
      $variables['!country_name_if'] = $variables['!country_name'];
      $variables['!country_code2_if'] = $variables['!country_code2'];
      $variables['!country_code3_if'] = $variables['!country_code3'];
    }
    else {
      $variables['!country_name_if']  = '';
      $variables['!country_code2_if'] = '';
      $variables['!country_code3_if'] = '';
    }

    $address = SafeMarkup::checkPlain(strtr($format, $variables));
    $address = preg_replace("/\r/", '', $address);
    $address = preg_replace("/\n +\n/", "\n", $address);
    $address = trim($address, "\n");

    if (\Drupal::config('uc_store.settings')->get('capitalize_address')) {
      $address = Unicode::strtoupper($address);
    }

    // <br> instead of <br />, because Twig will change it to <br> anyway and it's nice
    // to be able to test the Raw output.
    return nl2br($address, FALSE);
  }

}
