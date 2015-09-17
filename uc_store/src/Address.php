<?php

/**
 * @file
 * Contains \Drupal\uc_store\Address.
 */

namespace Drupal\uc_store;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines an object to hold Ubercart mailing address information.
 */
class Address {
  use StringTranslationTrait;

  /** Given name. */
  public $first_name = '';

  /** Surname. */
  public $last_name = '';

  /** Company or organization. */
  public $company = '';

  /** First line of street address. */
  public $street1 = '';

  /** Second line of street address. */
  public $street2 = '';

  /** City name. */
  public $city = '';

  /** State, provence, or region id. */
  public $zone = '';

  /** ISO 3166-1 2-character numeric country code. */
  public $country = '';

  /** Postal code. */
  public $postal_code = '';

  /** Telephone number. */
  public $phone = '';

  /** Email address. */
  public $email = '';

  /** Store default country code. */
  protected $default_country;

  /**
   * Constructor.
   *
   * For convenience, country defaults to store country.
   */
  public function __construct() {
    $this->default_country = \Drupal::config('uc_store.settings')->get('address.country');
    $this->country = $this->default_country;
  }

  /**
   * Compares two Address objects to determine if they represent the same
   * physical address.
   *
   * Address properties such as first_name, phone, and email aren't considered
   * in this comparison because they don't contain information about the
   * physical location.
   *
   * @param \Drupal\uc_store\Address $address
   *   An object of type Address.
   *
   * @return bool
   *   TRUE if the two addresses are the same physical location, else FALSE.
   */
  public function isSamePhysicalLocation(Address $address) {
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
   * Utility function to simplify comparison of address properties.
   *
   * For the purpose of this function, the canonical form is stripped of all
   * whitespace and has been converted to all upper case. This ensures that we
   * don't get false inequalities when comparing address properties that a
   * human would consider identical, but may be capitalized differently or
   * have different whitespace.
   *
   * @param string $string
   *   String to make canonical.
   *
   * @return string
   *   Canonical form of input string.
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
   * @return string
   *   An HTML formatted string containing the address.
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

    $country = $this->country ? \Drupal::service('country_manager')->getCountry($this->country) : NULL;
    if ($country) {
      $variables += array(
        '!zone_code' => $this->zone ?: $this->t('N/A'),
        '!zone_name' => isset($country->getZones()[$this->zone]) ? $country->getZones()[$this->zone] : $this->t('Unknown'),
        '!country_name' => $this->t($country->getName()),
        '!country_code2' => $country->id(),
        '!country_code3' => $country->getAlpha3(),
      );

      if ($this->country != $this->default_country) {
        $variables['!country_name_if'] = $variables['!country_name'];
        $variables['!country_code2_if'] = $variables['!country_code2'];
        $variables['!country_code3_if'] = $variables['!country_code3'];
      }
      else {
        $variables['!country_name_if']  = '';
        $variables['!country_code2_if'] = '';
        $variables['!country_code3_if'] = '';
      }

      $format = implode("\n", $country->getAddressFormat());
    }
    else {
      $format = "!company\n!first_name !last_name\n!street1\n!street2\n!city\n!postal_code";
    }

    $address = Html::escape(strtr($format, $variables));
    $address = trim(trim(preg_replace("/\n+/", "\n", $address), "\n"), ' ');

    if (\Drupal::config('uc_store.settings')->get('capitalize_address')) {
      $address = Unicode::strtoupper($address);
    }

    // <br> instead of <br />, because Twig will change it to <br> anyway and it's nice
    // to be able to test the Raw output.
    return nl2br($address, FALSE);
  }

}
