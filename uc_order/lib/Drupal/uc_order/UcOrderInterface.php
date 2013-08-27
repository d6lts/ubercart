<?php

/**
 * @file
 * Contains \Drupal\uc_order\UcOrderInterface.
 */

namespace Drupal\uc_order;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining an Ubercart order entity.
 */
interface UcOrderInterface extends ContentEntityInterface {

  /**
   * Returns the order user entity.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity.
   */
  public function getUser();

  /**
   * Returns the order user ID.
   *
   * @return int
   *   The user ID.
   */
  public function getUserId();

  /**
   * Sets the order user ID.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return \Drupal\uc_order\UcOrderInterface
   *   The called owner entity.
   */
  public function setUserId($uid);

  /**
   * Returns the order status ID.
   *
   * @return string
   *   The order status ID.
   */
  public function getStatusId();

  /**
   * Sets the order status ID.
   *
   * @param string $status
   *   The order status ID.
   *
   * @return \Drupal\uc_order\UcOrderInterface
   *   The called owner entity.
   */
  public function setStatusId($status);

}
