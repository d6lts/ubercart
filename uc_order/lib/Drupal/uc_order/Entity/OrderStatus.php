<?php

/**
 * @file
 * Contains \Drupal\uc_order\Entity\OrderStatus.
 */

namespace Drupal\uc_order\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;

/**
 * Defines the order status entity.
 *
 * @ConfigEntityType(
 *   id = "uc_order_status",
 *   label = @Translation("Order status"),
 *   admin_permission = "administer order workflow",
 *   config_prefix = "status",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "weight" = "weight",
 *     "uuid" = "uuid",
 *   }
 * )
 */
class OrderStatus extends ConfigEntityBase {

  /**
   * The order status ID.
   *
   * @var string
   */
  public $id;

  /**
   * The order status UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * Name of the status.
   *
   * @var string
   */
  public $name;

  /**
   * Specific state of the status.
   *
   * @var string
   */
  public $state;

  /**
   * The weight of this status in relation to other statuses.
   *
   * @var integer
   */
  public $weight = 0;

  /**
   * Locked statuses cannot be edited.
   *
   * @var bool
   */
  public $locked = FALSE;

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageControllerInterface $storage_controller, array &$entities) {
    parent::postLoad($storage_controller, $entities);
    uasort($entities, 'static::sort');
  }

}
