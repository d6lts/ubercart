<?php

/**
 * @file
 * Contains \Drupal\uc_country\Entity\Country.
 */

namespace Drupal\uc_country\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the uc_country type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "uc_country",
 *   label = @Translation("Country"),
 *   handlers = {
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "list_builder" = "Drupal\uc_country\CountryListBuilder",
 *     "form" = {
 *       "edit" = "Drupal\uc_country\Form\CountryForm",
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "alpha_2",
 *     "label" = "name",
 *     "status" = "status",
 *   },
 *   config_prefix = "country",
 *   admin_permission = "administer countries",
 *   links = {
 *     "edit-form" = "/admin/store/config/country/{uc_country}",
 *     "enable" = "/admin/store/config/country/{uc_country}/enable",
 *     "disable" = "/admin/store/config/country/{uc_country}/disable"
 *   }
 * )
 */
class Country extends ConfigEntityBase {

  /**
   * The 2-character ISO 3166-1 code identifying the country.
   *
   * @var string
   */
  public $alpha_2;

  /**
   * The 3-character ISO 3166-1 code identifying the country.
   *
   * @var string
   */
  public $alpha_3;

  /**
   * The human-readable name of the country.
   *
   * @var string
   */
  public $name;

  /**
   * The numeric ISO 3166-1 code of the country.
   *
   * @var int
   */
  public $numeric;

  /**
   * The address format string for the country.
   *
   * @var array
   */
  public $address_format;

  /**
   * An associative array of zone names, keyed by ISO 3166-2 zone code.
   *
   * @var array
   */
  public $zones = array();

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->alpha_2;
  }

  /**
   * {@inheritdoc}
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    $a_status = (int) $a->status();
    $b_status = (int) $b->status();
    if ($a_status != $b_status) {
      return ($a_status > $b_status) ? -1 : 1;
    }
    return parent::sort($a, $b);
  }

}
