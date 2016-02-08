<?php

/**
 * @file
 * Contains \Drupal\uc_fulfillment\FulfillmentMethodInterface.
 */

namespace Drupal\uc_fulfillment;

/**
 * Provides an interface for defining fulfillment method entities.
 */
class Shipment {

  /** These variables map to DB columns */
  public $order_id;
  public $shipping_method;
  public $accessorials;
  public $carrier;
  public $transaction_id;
  public $tracking_number;
  public $ship_date;
  public $expected_delivery;
  public $cost;
  public $changed;

  /** These variables don't map to DB columns */
  public $packages;
  public $origin;
  public $destination;

  /**
   * Constructor.
   */
  protected function __construct() {
  }

  /**
   * Creates a Shipment.
   *
   * @param array $values
   *   (optional) Array of initialization values.
   *
   * @return \Drupal\uc_fulfillment\Shipment
   *   A Shipment object.
   */
  public static function create(array $values = NULL) {
    $shipment = new Shipment();
    if (isset($values)) {
      foreach ($values as $key => $value) {
        $shipment->$key = $value;
      }
    }
    return $shipment;
  }

  /**
   * Loads a shipment and its packages for a given order.
   *
   * @param array $order_id
   *   An order ID.
   *
   * @return \Drupal\uc_fulfillment\Shipment[]
   *   Array of shipment object for the given order.
   */
  public static function loadByOrder($order_id) {
    $shipments = array();
    $result = db_query('SELECT sid FROM {uc_shipments} WHERE order_id = :id', [':id' => $order_id]);
    while ($shipment_id = $result->fetchField()) {
      $shipments[] = Shipment::load($shipment_id);
    }

    return $shipments;
  }

  /**
   * Loads a shipment and its packages.
   *
   * @param int $shipment_id
   *   The shipment ID.
   *
   * @return \Drupal\uc_fulfillment\Shipment|null
   *   The Shipment object, or NULL if there isn't one.
   */
  public static function load($shipment_id) {
    $shipment = NULL;
    $result = db_query('SELECT * FROM {uc_shipments} WHERE sid = :sid', [':sid' => $shipment_id]);
    if ($assoc = $result->fetchAssoc()) {
      $shipment = new Shipment();
      foreach ($assoc as $key => $value) {
        $shipment->$key = $value;
      }
      $result2 = db_query('SELECT package_id FROM {uc_packages} WHERE sid = :sid', [':sid' => $shipment_id]);
      $packages = array();
      foreach ($result2 as $package) {
        $packages[$package->package_id] = Package::load($package->package_id);
      }
      $shipment->packages = $packages;

      $extra = \Drupal::moduleHandler()->invokeAll('uc_shipment', array('load', $shipment));
      if (is_array($extra)) {
        foreach ($extra as $key => $value) {
          $shipment->$key = $value;
        }
      }
    }

    return $shipment;
  }

  /**
   * Saves this shipment.
   */
  public function save() {
    $this->changed = time();

    $fields = array();
    if (isset($this->origin)) {
      foreach ($this->origin as $field => $value) {
        $field = 'o_' . $field;
        $fields[$field] = $value;
      }
    }
    if (isset($this->destination)) {
      foreach ($this->destination as $field => $value) {
        $field = 'd_' . $field;
        $fields[$field] = $value;
      }
    }

    // Yuck.
    $fields += array(
      'order_id' => $this->order_id,
      'shipping_method' => $this->shipping_method,
      'accessorials' => $this->accessorials,
      'carrier' => $this->carrier,
      'transaction_id' => $this->transaction_id,
      'tracking_number' => $this->tracking_number,
      'ship_date' => $this->ship_date,
      'expected_delivery' => $this->expected_delivery,
      'cost' => $this->cost,
      'changed' => $this->changed,
    );
    if (!isset($this->sid)) {
//  drupal_write_record('uc_shipments', $shipment);
      $this->sid = db_insert('uc_shipments')
        ->fields($fields)
        ->execute();
      $this->is_new = TRUE;
    }
    else {
//  drupal_write_record('uc_shipments', $shipment, 'sid');
      db_update('uc_shipments')
        ->fields($fields)
        ->condition('sid', $this->sid, '=')
        ->execute();
      $this->is_new = FALSE;
    }

    if (is_array($this->packages)) {
      foreach ($this->packages as $package) {
        $package->sid = $this->sid;
        // Since the products haven't changed, we take them out of the object so
        // that they are not deleted and re-inserted.
        $products = $package->products;
        unset($package->products);
drupal_set_message("here".print_r($fields, true));
        $package->save();
        // But they're still necessary for hook_uc_shipment(), so they're added
        // back in.
        $package->products = $products;
      }
    }

    \Drupal::moduleHandler()->invokeAll('uc_shipment', array('save', $this));
    $order = Order::load($this->order_id);
    // rules_invoke_event('uc_shipment_save', $order, $shipment);
  }

  /**
   * Deletes this shipment.
   */
  public function delete() {
    db_update('uc_packages')
      ->fields(array(
        'sid' => NULL,
        'tracking_number' => NULL,
        'label_image' => NULL,
      ))
      ->condition('sid', $this->sid)
      ->execute();

    db_delete('uc_shipments')
      ->condition('sid', $this->sid)
      ->execute();

    foreach ($this->packages as $package) {
      if (isset($package->label_image)) {
        file_delete($package->label_image);
        unset($package->label_image);
      }
    }

    \Drupal::moduleHandler()->invokeAll('uc_shipment', array('delete', $this));
  }

}
