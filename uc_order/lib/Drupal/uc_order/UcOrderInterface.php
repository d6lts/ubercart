<?php

/**
 * @file
 * Contains \Drupal\uc_order\UcOrderInterface.
 */

namespace Drupal\uc_order;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\uc_store\UcAddress;

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

  /**
   * Returns the order state ID.
   *
   * @return string
   *   The order state ID.
   */
  public function getStateId();

  /**
   * Returns the order e-mail address.
   *
   * @return string
   *   The e-mail address.
   */
  public function getEmail();

  /**
   * Sets the order e-mail address.
   *
   * @param string $email
   *   The e-mail address.
   *
   * @return \Drupal\uc_order\UcOrderInterface
   *   The called owner entity.
   */
  public function setEmail($email);

  /**
   * Returns the order subtotal amount (products only).
   *
   * @return float
   *   The order subtotal.
   */
  public function getSubtotal();

  /**
   * Returns the order total amount (including all line items).
   *
   * @return float
   *   The order total.
   */
  public function getTotal();

  /**
   * Returns the number of products in an order.
   *
   * @return int
   *   The number of products.
   */
  public function getProductCount();

  /**
   * Returns the order currency code.
   *
   * @return string
   *   The order currency code.
   */
  public function getCurrency();

  /**
   * Returns the order payment method.
   *
   * @return string
   *   The payment method.
   */
  public function getPaymentMethod();

  /**
   * Sets the order payment method.
   *
   * @param string $payment_method
   *   The payment method ID.
   *
   * @return \Drupal\uc_order\UcOrderInterface
   *   The called owner entity.
   */
  public function setPaymentMethod($payment_method);

  /**
   * Returns an address attached to the order.
   *
   * @param string $type
   *   The address type, usually 'billing' or 'delivery'.
   *
   * @return \Drupal\uc_store\UcAddress
   *   The address object.
   */
  public function getAddress($type);

  /**
   * Sets an address attached to the order.
   *
   * @param string $type
   *   The address type, usually 'billing' or 'delivery'.
   * @param \Drupal\uc_store\UcAddress $address
   *   The address object.
   *
   * @return \Drupal\uc_order\UcOrderInterface
   *   The called owner entity.
   */
  public function setAddress($type, UcAddress $address);

}
