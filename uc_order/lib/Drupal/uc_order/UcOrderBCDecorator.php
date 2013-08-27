<?php

/**
 * @file
 * Contains \Drupal\uc_order\UcOrderBCDecorator.
 */

namespace Drupal\uc_order;

use Drupal\Core\Entity\EntityBCDecorator;

/**
 * Defines the Ubercart order specific entity BC decorator.
 */
class UcOrderBCDecorator extends EntityBCDecorator implements UcOrderInterface {

  /**
   * {@inheritdoc}
   */
  public function getUser() {
    return $this->decorated->getUser();
  }

  /**
   * {@inheritdoc}
   */
  public function getUserId() {
    return $this->decorated->getUserId();
  }

  /**
   * {@inheritdoc}
   */
  public function setUserId($uid) {
    $this->decorated->setUserId($uid);
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusId() {
    return $this->decorated->getStatusId();
  }

  /**
   * {@inheritdoc}
   */
  public function setStatusId($status) {
    $this->decorated->setStatusId($status);
  }

}
