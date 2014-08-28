<?php

/**
 * @file
 * Contains \Drupal\uc_order\Entity\UcOrder.
 */

namespace Drupal\uc_order\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\uc_order\UcOrderInterface;
use Drupal\uc_store\Address;

/**
 * Defines the order entity class.
 *
 * @ContentEntityType(
 *   id = "uc_order",
 *   label = @Translation("Order"),
 *   module = "uc_order",
 *   handlers = {
 *     "storage" = "Drupal\uc_order\UcOrderStorage",
 *     "view_builder" = "Drupal\uc_order\UcOrderViewBuilder",
 *     "access" = "Drupal\uc_order\UcOrderAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\uc_order\UcOrderForm",
 *       "delete" = "Drupal\uc_order\Form\OrderDeleteForm",
 *       "edit" = "Drupal\uc_order\UcOrderForm"
 *     }
 *   },
 *   base_table = "uc_orders",
 *   fieldable = TRUE,
 *   links = {
 *     "canonical" = "uc_order.admin_view",
 *     "delete-form" = "uc_order.admin_delete",
 *     "edit-form" = "uc_order.admin_edit",
 *     "admin-form" = "uc_order.workflow"
 *   },
 *   entity_keys = {
 *     "id" = "order_id",
 *   }
 * )
 */
class UcOrder extends ContentEntityBase implements UcOrderInterface {

  public $products = array();
  public $line_items = array();

  /**
   * Implements Drupal\Core\Entity\EntityInterface::id().
   */
  public function id() {
    return $this->get('order_id')->value;
  }

  /**
   * Implements Drupal\Core\Entity\EntityInterface::label().
   */
  public function label($langcode = NULL) {
    return t('Order @order_id', array('@order_id' => $this->id()));
  }

  /**
   * {@inheritdoc}
   */
  public function uuid() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$orders) {
    parent::postLoad($storage, $orders);

    foreach ($orders as $id => $order) {
      $order->products = entity_load_multiple_by_properties('uc_order_product', array('order_id' => $id));

      // Load line items... has to be last after everything has been loaded.
      $order->line_items = $order->getLineItems();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    $this->order_total->value = $this->getTotal();
    $this->product_count->value = $this->getProductCount();
    if (is_null($this->delivery_country->value) || $this->delivery_country->value == 0) {
      $this->delivery_country->value = \Drupal::config('uc_store.settings')->get('address.country');
    }
    if (is_null($this->billing_country->value) || $this->billing_country->value == 0) {
      $this->billing_country->value = \Drupal::config('uc_store.settings')->get('address.country');
    }
    $this->host->value = \Drupal::request()->getClientIp();
    $this->modified->value = REQUEST_TIME;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    foreach ($this->products as $product) {
      \Drupal::moduleHandler()->alter('uc_order_product', $product, $this);
      uc_order_product_save($this->id(), $product);
    }

    // Record a log entry if the order status has changed.
    if ($update && $this->getStatusId() != $this->original->getStatusId()) {
      $this->logChanges(array(t('Order status') => array(
        'old' => $this->original->getStatus()->name,
        'new' => $this->getStatus()->name,
      )));

      // rules_invoke_event('uc_order_status_update', $this->original, $this);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $orders) {
    // Delete data from the appropriate Ubercart order tables.
    $ids = array_keys($orders);
    $result = \Drupal::entityQuery('uc_order_product')
      ->condition('order_id', $ids, 'IN')
      ->execute();
    if (!empty($result)) {
      entity_delete_multiple('uc_order_product', array_keys($result));
    }
    db_delete('uc_order_comments')
      ->condition('order_id', $ids, 'IN')
      ->execute();
    db_delete('uc_order_admin_comments')
      ->condition('order_id', $ids, 'IN')
      ->execute();
    db_delete('uc_order_log')
      ->condition('order_id', $ids, 'IN')
      ->execute();

    foreach ($orders as $order_id => $order) {
      // Delete line items for the order.
      uc_order_delete_line_item($order_id, TRUE);

      // Log the action in the database.
      watchdog('uc_order', 'Order @order_id deleted by user @uid.', array('@order_id' => $order_id, '@uid' => $GLOBALS['user']->id()));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLineItems() {
    $items = array();

    $result = db_query("SELECT * FROM {uc_order_line_items} WHERE order_id = :id", array(':id' => $this->id()));
    foreach ($result as $row) {
      $item = array(
        'line_item_id' => $row->line_item_id,
        'type' => $row->type,
        'title' => $row->title,
        'amount' => $row->amount,
        'weight' => $row->weight,
        'data' => unserialize($row->data),
      );
      \Drupal::moduleHandler()->alter('uc_line_item', $item, $this);
      $items[] = $item;
    }

    // Set stored line items so hook_uc_line_item_alter() can access them.
    // @todo Somehow avoid this!
    $this->line_items = $items;

    foreach (_uc_line_item_list() as $type) {
      if ($type['stored'] == FALSE && empty($type['display_only']) && !empty($type['callback']) && function_exists($type['callback'])) {
        $result = $type['callback']('load', $this);
        if ($result !== FALSE && is_array($result)) {
          foreach ($result as $line) {
            $item = array(
              'line_item_id' => $line['id'],
              'type' => $type['id'],
              'title' => $line['title'],
              'amount' => $line['amount'],
              'weight' => isset($line['weight']) ? $line['weight'] : $type['weight'],
              'data' => isset($line['data']) ? $line['data'] : array(),
            );
            \Drupal::moduleHandler()->alter('uc_line_item', $item, $this);
            $items[] = $item;
          }
        }
      }
    }

    usort($items, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayLineItems() {
    $temp = clone $this;
    $line_items = $this->getLineItems();

    $items = _uc_line_item_list();
    foreach ($items as $item) {
      if (!empty($item['display_only'])) {
        $result = $item['callback']('display', $temp);
        if (is_array($result)) {
          foreach ($result as $line) {
            $line_items[] = array(
              'type' => $item['id'],
              'title' => $line['title'],
              'amount' => $line['amount'],
              'weight' => isset($line['weight']) ? $line['weight'] : $item['weight'],
              'data' => isset($line['data']) ? $line['data'] : array(),
            );
          }
        }
      }
    }

    foreach ($line_items as &$item) {
      $item['formatted_amount'] = uc_currency_format($item['amount']);
    }

    usort($line_items, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    return $line_items;
  }

  /**
   * {@inheritdoc}
   */
  public function getUser() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setUserId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->get('order_status')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusId() {
    return $this->get('order_status')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatusId($status) {
    $this->set('order_status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStateId() {
    return $this->getStatus()->state;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail() {
    return $this->get('primary_email')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmail($email) {
    $this->set('primary_email', $email);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubtotal() {
    $subtotal = 0;
    foreach ($this->products as $product) {
      $subtotal += $product->price->value * $product->qty->value;
    }
    return $subtotal;
  }

  /**
   * {@inheritdoc}
   */
  public function getTotal() {
    $total = $this->getSubtotal();
    foreach ($this->line_items as $item) {
      if (_uc_line_item_data($item['type'], 'calculated') == TRUE) {
        $total += $item['amount'];
      }
    }
    return $total;
  }

  /**
   * {@inheritdoc}
   */
  public function getProductCount() {
    $count = 0;
    foreach ($this->products as $product) {
      $count += $product->qty->value;
    }
    return $count;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrency() {
    return $this->get('currency')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentMethodId() {
    return $this->get('payment_method')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPaymentMethodId($payment_method) {
    $this->set('payment_method', $payment_method);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddress($type) {
    $address = new Address();
    $address->first_name = $this->get($type . '_first_name')->value;
    $address->last_name = $this->get($type . '_last_name')->value;
    $address->company = $this->get($type . '_company')->value;
    $address->street1 = $this->get($type . '_street1')->value;
    $address->street2 = $this->get($type . '_street2')->value;
    $address->city = $this->get($type . '_city')->value;
    $address->zone = $this->get($type . '_zone')->value;
    $address->country = $this->get($type . '_country')->value;
    $address->postal_code = $this->get($type . '_postal_code')->value;
    $address->phone = $this->get($type . '_phone')->value;
    return $address;
  }

  /**
   * {@inheritdoc}
   */
  public function setAddress($type, Address $address) {
    $this->set($type . '_first_name', $address->first_name);
    $this->set($type . '_last_name', $address->last_name);
    $this->set($type . '_company', $address->company);
    $this->set($type . '_street1', $address->street1);
    $this->set($type . '_street2', $address->street2);
    $this->set($type . '_city', $address->city);
    $this->set($type . '_zone', $address->zone);
    $this->set($type . '_country', $address->country);
    $this->set($type . '_postal_code', $address->postal_code);
    $this->set($type . '_phone', $address->phone);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isShippable() {
    foreach ($this->products as $product) {
      if (uc_order_product_is_shippable($product)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function logChanges($changes) {
    global $user;

    if (!empty($changes)) {
      foreach ($changes as $key => $value) {
        if (is_array($value)) {
          $items[] = t('@key changed from %old to %new.', array('@key' => $key, '%old' => $value['old'], '%new' => $value['new']));
        }
        elseif (is_string($value)) {
          $items[] = $value;
        }
      }

      $item_list = array(
        '#theme' => 'item_list',
        '#items' => $items,
      );

      db_insert('uc_order_log')
        ->fields(array(
          'order_id' => $this->id(),
          'uid' => $user->id(),
          'changes' => drupal_render($item_list),
          'created' => REQUEST_TIME,
        ))
        ->execute();
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['order_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Order ID'))
      ->setDescription(t('The order ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Customer'))
      ->setDescription(t('The user that placed the order.'))
      ->setSetting('target_type', 'user')
      ->setSetting('default_value', 0);
    $fields['order_status'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Order status'))
      ->setDescription(t('The uc_order_status entity ID indicating the order status'))
      ->setSetting('target_type', 'uc_order_status')
      ->setSetting('default_value', '')
      ->setSetting('max_length', 32);
    $fields['order_total'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Order total'))
      ->setDescription(t('The total amount to be paid for the order.'))
      ->setSetting('default_value', 0.0);
    $fields['product_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Product count'))
      ->setDescription(t('The total product quantity of the order.'))
      ->setSetting('default_value', 0)
      ->setSetting('unsigned', TRUE);
    $fields['primary_email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('E-mail address'))
      ->setDescription(t('The email address of the customer.'))
      ->setSetting('default_value', '')
      ->setSetting('max_length', 96);
    $fields['delivery_first_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Delivery first name'))
      ->setDescription(t('The first name of the person receiving shipment.'))
      ->setSetting('default_value', '');
    $fields['delivery_last_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Delivery last name'))
      ->setDescription(t('The last name of the person receiving shipment.'))
      ->setSetting('default_value', '');
    $fields['delivery_phone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Delivery phone'))
      ->setDescription(t('The phone number at the delivery location.'))
      ->setSetting('default_value', '');
    $fields['delivery_company'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Delivery company'))
      ->setDescription(t('The company at the delivery location.'))
      ->setSetting('default_value', '');
    $fields['delivery_street1'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Delivery street 1'))
      ->setDescription(t('The street address of the delivery location.'))
      ->setSetting('default_value', '');
    $fields['delivery_street2'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Delivery street 2'))
      ->setDescription(t('The second line of the street address.'))
      ->setSetting('default_value', '');
    $fields['delivery_city'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Delivery city'))
      ->setDescription(t('The city of the delivery location.'))
      ->setSetting('default_value', '');
    $fields['delivery_zone'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Delivery state/province'))
      ->setDescription(t('The state/zone/province id of the delivery location.'))
      ->setSetting('default_value', 0)
      ->setSetting('unsigned', TRUE);
    $fields['delivery_postal_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Delivery postal code'))
      ->setDescription(t('The postal code of the delivery location.'))
      ->setSetting('default_value', '');
    $fields['delivery_country'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Delivery country'))
      ->setDescription(t('The country ID of the delivery location.'))
      ->setSetting('size', 'medium')
      ->setSetting('default_value', 0)
      ->setSetting('unsigned', TRUE);
    $fields['billing_first_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Billing first name'))
      ->setDescription(t('The first name of the person paying for the order.'))
      ->setSetting('default_value', '');
    $fields['billing_last_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Billing last name'))
      ->setDescription(t('The last name of the person paying for the order.'))
      ->setSetting('default_value', '');
    $fields['billing_phone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Billing phone'))
      ->setDescription(t('The phone number for the billing address.'))
      ->setSetting('default_value', '');
    $fields['billing_company'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Billing company'))
      ->setDescription(t('The company of the billing address.'))
      ->setSetting('default_value', '');
    $fields['billing_street1'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Billing street 1'))
      ->setDescription(t('The street address where the bill will be sent.'))
      ->setSetting('default_value', '');
    $fields['billing_street2'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Billing street 2'))
      ->setDescription(t('The second line of the street address.'))
      ->setSetting('default_value', '');
    $fields['billing_city'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Billing city'))
      ->setDescription(t('The city where the bill will be sent.'))
      ->setSetting('default_value', '');
    $fields['billing_zone'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Billing state/province'))
      ->setDescription(t('The state/zone/province ID where the bill will be sent.'))
      ->setSetting('default_value', 0)
      ->setSetting('size', 'medium')
      ->setSetting('unsigned', TRUE);
    $fields['billing_postal_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Billing postal code'))
      ->setDescription(t('The postal code where the bill will be sent.'))
      ->setSetting('default_value', '');
    $fields['billing_country'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Billing country'))
      ->setDescription(t('The country ID where the bill will be sent.'))
      ->setSetting('default_value', 0)
      ->setSetting('size', 'medium')
      ->setSetting('unsigned', TRUE);
    $fields['payment_method'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment method'))
      ->setDescription(t('The method of payment.'))
      ->setSetting('default_value', '')
      ->setSetting('max_length', 32);
    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('A serialized array of extra data.'));
    $fields['created'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Created'))
      ->setDescription(t('The Unix timestamp indicating when the order was created.'))
      ->setSetting('default_value', 0);
    $fields['modified'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Modified'))
      ->setDescription(t('The Unix timestamp indicating when the order was last modified.'))
      ->setSetting('default_value', 0);
    $fields['host'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Host'))
      ->setDescription(t('Host IP address of the person paying for the order.'))
      ->setSetting('default_value', '');
    $fields['currency'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Currency'))
      ->setDescription(t('The ISO currency code for the order.'))
      ->setPropertyConstraints('value', array('Length' => array('max' => 3)))
      ->setSetting('default_value', '')
      ->setSetting('max_length', 3);

    return $fields;
  }

}
