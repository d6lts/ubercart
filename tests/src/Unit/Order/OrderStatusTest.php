<?php

/**
 * @file
 * Contains \Drupal\Tests\ubercart\Unit\Order\OrderStatusTest.
 */

namespace Drupal\Tests\ubercart\Unit\Order;

use Drupal\Tests\UnitTestCase;
use Drupal\uc_order\Entity\OrderStatus;

/**
 * @coversDefaultClass \Drupal\uc_order\Entity\OrderStatus
 *
 * @group Ubercart
 */
class OrderStatusTest extends UnitTestCase {

  /**
   * The tested order status.
   *
   * @var \Drupal\uc_order\Entity\OrderStatus
   */
  protected $orderStatus;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->orderStatus = new OrderStatus([], 'order_status');
  }

  /**
   * Tests that setState() throws an exception when the status is locked.
   *
   * @covers ::setState
   * @expectedException \LogicException
   */
  public function testSetStateWhenLocked() {
    $this->orderStatus->setLocked(TRUE);
    $this->orderStatus->setState('state');
  }

  /**
   * Tests that delete() throws an exception when the status is locked.
   *
   * @covers ::delete
   * @expectedException \LogicException
   */
  public function testDeleteWhenLocked() {
    $this->orderStatus->setLocked(TRUE);
    $this->orderStatus->delete();
  }

}
