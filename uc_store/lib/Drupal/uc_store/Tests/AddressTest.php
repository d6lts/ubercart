<?php

/**
 * @file
 * Contains \Drupal\uc_store\Tests\AddressTest.
 */

namespace Drupal\uc_store\Tests;

use Drupal\uc_store\Address;

class AddressTest extends UbercartTestBase {

  /** Array of Address objects */
  protected $test_address = array();

  public static function getInfo() {
    return array(
      'name' => 'Address functionality',
      'description' => 'Create and compare addresses.',
      'group' => 'Ubercart',
    );
  }

  /**
   * Overrides WebTestBase::setUp().
   */
  public function setUp() {
    parent::setUp();

    // Create a random address object for use in tests.
    $this->test_address[] = $this->createAddress();

    // Create a specific address object for use in tests.
    $settings = array(
      'first_name'  => 'Elmo',
      'last_name'   => 'Monster',
      'company'     => 'CTW, Inc.',
      'street1'     => '123 Sesame Street',
      'city'        => 'New York',
      'zone'        => 43,
      'country'     => 840,
      'postal_code' => '10010',
      'phone'       => '1234567890',
      'email'       => 'elmo@ctw.org',
    );
    $this->test_address[] = $this->createAddress($settings);
  }

  public function testAddressComparison() {
    $this->pass((string) $this->test_address[0]);
    $this->pass((string) $this->test_address[1]);

    // Use randomly generated address first.
    $address = clone($this->test_address[0]);

    // Modify phone number and test equality
    $address->phone = 'this is not a valid phone number';
    $this->assertTrue(
      $this->test_address[0]->isSamePhysicalLocation($address),
      t('Physical address comparison ignores non-physical fields.')
    );

    // Use specifc address.
    $address = clone($this->test_address[1]);

    // Modify city and test equality
    $address->city = 'nEw YoRk';
    $this->pass((string) $address);
    $this->assertTrue(
      $this->test_address[1]->isSamePhysicalLocation($address),
      t('Case-insensitive address comparison works.')
    );

    // Modify city and test equality
    $address->city = '		NewYork ';
    $this->pass((string) $address);
    $this->assertTrue(
      $this->test_address[1]->isSamePhysicalLocation($address),
      t('Whitespace-insensitive address comparison works.')
    );

  }

  /**
   * Creates an address object based on default settings.
   *
   * @param $settings
   *   An associative array of settings to change from the defaults, keys are
   *   address properties. For example, 'city' => 'London'.
   *
   * @return
   *   Address object.
   */
  protected function createAddress($settings = array()) {
    $street = array_flip(array(
      'Street',
      'Avenue',
      'Place',
      'Way',
      'Road',
      'Boulevard',
      'Court',
    ));

    // Populate object with defaults.
    $address = new Address();
    $address->first_name  = $this->randomName(6);
    $address->last_name   = $this->randomName(12);
    $address->company     = $this->randomName(10) . ', Inc.';
    $address->street1     = mt_rand(100, 1000) . ' ' .
                            $this->randomName(10) . ' ' .
                            array_rand($street);
    $address->street2     = 'Suite ' . mt_rand(100, 999);
    $address->city        = $this->randomName(10);
    $address->zone        = 23;
    $address->country     = 840;
    $address->postal_code = mt_rand(10000, 99999);
    $address->phone       = '(' . mt_rand(100, 999) . ') ' .
                            mt_rand(100, 999) . '-' . mt_rand(0, 9999);
    $address->email       = $this->randomName(6) . '@' .
                            $this->randomName(8) . '.com';

    foreach ($settings as $property => $value) {
      $address->$property = $value;
    }

    return $address;
  }

}
