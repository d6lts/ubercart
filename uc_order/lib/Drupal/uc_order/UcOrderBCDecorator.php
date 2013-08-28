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

  /**
   * {@inheritdoc}
   */
  public function getStateId() {
    return $this->decorated->getStateId();
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail() {
    return $this->decorated->getEmail();
  }

  /**
   * {@inheritdoc}
   */
  public function setEmail($email) {
    $this->decorated->setEmail($email);
  }

  /**
   * {@inheritdoc}
   */
  public function getSubtotal() {
    return $this->decorated->getSubtotal();
  }

  /**
   * {@inheritdoc}
   */
  public function getTotal() {
    return $this->decorated->getTotal();
  }

}
