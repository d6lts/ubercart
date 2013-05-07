<?php

/**
 * @file
 * Definition of Drupal\uc_order\Plugin\Core\Entity\UcOrder.
 */

namespace Drupal\uc_order\Plugin\Core\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the order entity class.
 *
 * @EntityType(
 *   id = "uc_order",
 *   label = @Translation("Order"),
 *   module = "uc_order",
 *   controllers = {
 *     "storage" = "Drupal\uc_order\UcOrderStorageController",
 *     "render" = "Drupal\uc_order\UcOrderRenderController",
 *   },
 *   base_table = "uc_orders",
 *   uri_callback = "uc_order_uri",
 *   fieldable = TRUE,
 *   route_base_path = "admin/store/settings/orders",
 *   entity_keys = {
 *     "id" = "order_id",
 *   }
 * )
 */
class UcOrder extends Entity implements ContentEntityInterface {

  /**
   * The order ID.
   *
   * @var integer
   */
  public $order_id;

  /**
   * The order owner's user ID.
   *
   * @var integer
   */
  public $uid;

  public $currency = '';
  public $order_status = '';
  public $order_total = 0;
  public $primary_email = '';

  public $delivery_first_name = '';
  public $delivery_last_name = '';
  public $delivery_phone = '';
  public $delivery_company = '';
  public $delivery_street1 = '';
  public $delivery_street2 = '';
  public $delivery_city = '';
  public $delivery_zone = 0;
  public $delivery_postal_code = '';
  public $delivery_country = 0;

  public $billing_first_name = '';
  public $billing_last_name = '';
  public $billing_phone = '';
  public $billing_company = '';
  public $billing_street1 = '';
  public $billing_street2 = '';
  public $billing_city = '';
  public $billing_zone = 0;
  public $billing_postal_code = '';
  public $billing_country = 0;

  public $products = array();
  public $line_items = array();

  public $payment_method = '';
  public $data = array();

  /**
   * The order creation timestamp.
   *
   * @var integer
   */
  public $created;

  /**
   * The order modification timestamp.
   *
   * @var integer
   */
  public $modified;

  /**
   * Implements Drupal\Core\Entity\EntityInterface::id().
   */
  public function id() {
    return $this->order_id;
  }

}
