<?php

/**
 * @file
 * Contains \Drupal\uc_fulfillment\Controller\Shipmentontroller.
 */

namespace Drupal\uc_fulfillment\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Link;
use Drupal\Core\Url;

use Drupal\Core\Controller\ControllerBase;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_store\Address;

/**
 * Controller routines for order routes.
 */
class ShipmentController extends ControllerBase {

  /**
   * The page title callback for shipment views.
   *
   * @param \Drupal\uc_order\OrderInterface $uc_order
   *   The shipment's order.
   * @param $shipment
   *   The shipment that is being viewed.
   *
   * @return string
   *   The page title.
   */
  public function pageTitle(OrderInterface $uc_order, $shipment) {
    return $this->t('Shipment @id', ['@id' => $shipment->sid]);
  }

  /**
   * Default method to send packages on a shipment.
   *
   * @param \Drupal\uc_order\OrderInterface $uc_order
   *   The order object.
   */
  public function makeShipment(OrderInterface $uc_order) {
    $args = func_get_args();
    if (count($args) > 2) {
      $breadcrumb = drupal_get_breadcrumb();
      $breadcrumb[] = Link::createFromRoute($this->t('Shipments'), 'uc_fulfillment.shipments', ['uc_order' => $uc_order->id()]);
      drupal_set_breadcrumb($breadcrumb);

      $uc_order = array_shift($args);
      $method_id = array_shift($args);
      $package_ids = $args;
      $methods = \Drupal::moduleHandler()->invokeAll('uc_fulfillment_method');
      if (isset($methods[$method_id])) {
        $method = $methods[$method_id];
        if (isset($method['ship']['file'])) {
          $inc_file = drupal_get_path('module', $method['module']) . '/' . $method['ship']['file'];
          if (is_file($inc_file)) {
            require_once($inc_file);
          }
        }
        return \Drupal::formBuilder()->getForm($method['ship']['callback'], $uc_order, $package_ids);
      }
      else {
        $shipment = new stdClass();
        $shipment->order_id = $uc_order->id();
        $shipment->packages = array();
        foreach ($package_ids as $id) {
          $package = uc_fulfillment_package_load($id);
          $shipment->packages[$id] = $package;
        }
        return \Drupal::formBuilder()->getForm('uc_fulfillment_shipment_edit', $uc_order, $shipment);
      }
    }
    else {
      drupal_set_message($this->t('There is no sense in making a shipment with no packages on it, right?'), 'warning');
      return $this->redirect('uc_fulfillment.new_shipment', ['uc_order' => $args[0]->order_id]);
    }
  }

  /**
   * Shows a printer-friendly version of a shipment.
   *
   * @param \Drupal\uc_order\OrderInterface $uc_order
   *   The order object.
   */
  function printShipment(OrderInterface $uc_order, $shipment, $labels = TRUE) {
    $build = array(
      '#theme' => 'uc_fulfillment_shipment_print',
      '#order' => $uc_order,
      '#shipment' => $shipment,
      '#labels' => $labels,
    );

    drupal_add_http_header('Content-Type', 'text/html; charset=utf-8');
    print theme('uc_packing_slip_page', array('content' => drupal_render($build)));
    exit();
  }

  /**
   * Displays a list of shipments for an order.
   *
   * @param \Drupal\uc_order\OrderInterface $uc_order
   *   The order object.
   *
   * @return array
   *   A render array.
   */
  public function listOrderShipments(OrderInterface $uc_order) {
    $result = db_query('SELECT * FROM {uc_shipments} WHERE order_id = :id', [':id' => $uc_order->id()]);
    $header = array($this->t('Shipment ID'), $this->t('Name'), $this->t('Company'), $this->t('Destination'), $this->t('Ship date'), $this->t('Estimated delivery'), $this->t('Tracking number'), $this->t('Actions'));

    $rows = array();
    foreach ($result as $shipment) {
      $row = array();
      $row[] = $shipment->sid;
      $row[] = array('#plain_text' => $shipment->d_first_name . ' ' . $shipment->d_last_name);
      $row[] = array('#plain_text' => $shipment->d_company);
      $row[] = array('#plain_text' => $shipment->d_city . ', ' . $shipment->d_zone . ' ' . $shipment->d_postal_code);
      $row[] = \Drupal::service('date.formatter')->format($shipment->ship_date, 'uc_store');
      $row[] = \Drupal::service('date.formatter')->format($shipment->expected_delivery, 'uc_store');
      $row[] = is_null($shipment->tracking_number) ? $this->t('n/a') : array('#plain_text' => $shipment->tracking_number);
      $row[] = array(
        '#type' => 'operations',
        '#links' => array(
          'view' => array(
            'title' => $this->t('View'),
            'url' => Url::fromRoute('uc_fulfillment.view_shipment', ['uc_order' => $uc_order->order->id(), 'shipment_id' => $shipment->sid]),
          ),
          'edit' => array(
            'title' => $this->t('Edit'),
            'url' => Url::fromRoute('uc_fulfillment.edit_shipment', ['uc_order' => $uc_order->order->id(), 'shipment_id' => $shipment->sid]),
          ),
          'print' => array(
            'title' => $this->t('Print'),
            'url' => Url::fromRoute('uc_fulfillment.print_shipment', ['uc_order' => $uc_order->order->id(), 'shipment_id' => $shipment->sid]),
          ),
          'packing_slip' => array(
            'title' => $this->t('Packing slip'),
            'url' => Url::fromRoute('uc_fulfillment.packing_slip', ['uc_order' => $uc_order->order->id(), 'shipment_id' => $shipment->sid]),
          ),
          'delete' => array(
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('uc_fulfillment.delete_shipment', ['uc_order' => $uc_order->order->id(), 'shipment_id' => $shipment->sid]),
          ),
        ),
      );
      $rows[] = $row;
    }

    if (empty($rows)) {
      if (!db_query('SELECT COUNT(*) FROM {uc_packages} WHERE order_id = :id', [':id' => $uc_order->id()])->fetchField()) {
        drupal_set_message($this->t("This order's products have not been organized into packages."), 'warning');
        return $this->redirect('uc_fulfillment.new_package', ['uc_order' => $uc_order->id()]);
      }
      else {
        drupal_set_message($this->t('No shipments have been made for this order.'), 'warning');
        return $this->redirect('uc_fulfillment.new_shipment', ['uc_order' => $uc_order->id()]);
      }
    }

    $build['shipments'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );

    return $build;
  }


  /**
   * Displays shipment details.
   *
   * @param \Drupal\uc_order\OrderInterface $uc_order
   *   The order object.
   *
   * @return array
   *   A render array.
   */
function viewShipment(OrderInterface $uc_order, $shipment) {
    $build = array();

    $origin = $this->getAddress($shipment, 'o');
    $destination = $this->getAddress($shipment, 'd');

    $build['pickup_address'] = array('#markup' => '<div class="order-pane pos-left"><div class="order-pane-title">' . $this->t('Pickup Address:') . '</div>' . $origin . '</div>');
    $build['delivery_address'] = array('#markup' => '<div class="order-pane pos-left"><div class="order-pane-title">' . $this->t('Delivery Address:') . '</div>' . $destination . '</div>');

    $rows = array();
    $rows[] = array($this->t('Ship date:'), \Drupal::service('date.formatter')->format($shipment->ship_date, 'uc_store'));
    $rows[] = array($this->t('Expected delivery:'), \Drupal::service('date.formatter')->format($shipment->expected_delivery, 'uc_store'));

    $build['schedule'] = array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#attributes' => array('style' => 'width: auto'),
      '#prefix' => '<div class="order-pane abs-left"><div class="order-pane-title">' . $this->t('Schedule:') . '</div>',
      '#suffix' => '</div>',
    );

    $rows = array();
    $rows[] = array($this->t('Carrier:'), SafeMarkup::checkPlain($shipment->carrier));

    if ($shipment->transaction_id) {
      $rows[] = array($this->t('Transaction ID:'), SafeMarkup::checkPlain($shipment->transaction_id));
    }

    if ($shipment->tracking_number) {
      $rows[] = array($this->t('Tracking number:'), SafeMarkup::checkPlain($shipment->tracking_number));
    }

    $methods = \Drupal::moduleHandler()->invokeAll('uc_fulfillment_method');

    if (isset($methods[$shipment->shipping_method]['quote']['accessorials'][$shipment->accessorials])) {
      $rows[] = array($this->t('Services:'), $methods[$shipment->shipping_method]['quote']['accessorials'][$shipment->accessorials]);
    }
    else {
      $rows[] = array($this->t('Services:'), $shipment->accessorials);
    }

    $rows[] = array($this->t('Cost:'), array('data' => array('#theme' => 'uc_price', '#price' => $shipment->cost)));

    $build['details'] = array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#attributes' => array('style' => 'width:auto'),
      '#prefix' => '<div class="order-pane abs-left"><div class="order-pane-title">' . $this->t('Shipment Details:') . '</div>',
      '#suffix' => '</div>',
    );

    foreach ($shipment->packages as $package) {
      $build['packages'][] = $this->viewPackage($package);
    }

    return $build;
  }

  /**
   * Returns an address from an object.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   An order object.
   * @param $type
   *   The key prefix to use to extract the address.
   *
   * @return string
   *   An address object.
   */
  protected function getAddress(OrderInterface $order, $type) {
    $name = $order->{$type . '_first_name'} . ' ' . $order->{$type . '_last_name'};
    $address = new Address();
    $address->first_name = $order->{$type . '_first_name'};
    $address->last_name = $order->{$type . '_last_name'};
    $address->company = $order->{$type . '_company'};
    $address->street1 = $order->{$type . '_street1'};
    $address->street1 = $order->{$type . '_street2'};
    $address->city = $order->{$type . '_city'};
    $address->zone = $order->{$type . '_zone'};
    $address->postal_code = $order->{$type . '_postal_code'};
    $address->country = $order->{$type . '_country'};

    $output = (string) $address;

    return $output;
  }

  /**
   * Displays the details of a package.
   *
   * @param $package
   *   The package object.
   *
   * @return array
   *   A render array.
   */
  public function viewPackage($package) {
    $shipment = uc_fulfillment_shipment_load($package->sid);
    $build = array(
      '#prefix' => '<div class="order-pane pos-left">',
      '#suffix' => '</div>',
    );
    $rows = array();

    $build['title'] = array(
      '#prefix' => '<div class="order-pane-title">',
      '#markup' => $this->t('Package %id:', ['%id' => $package->package_id]),
      '#suffix' => '</div>',
    );

    $rows[] = array($this->t('Contents:'), Xss::filterAdmin($package->description));

    if ($shipment) {
      $methods = \Drupal::moduleHandler()->invokeAll('uc_fulfillment_method');
      if (isset($methods[$shipment->shipping_method])) {
        $pkg_type = $methods[$shipment->shipping_method]['ship']['pkg_types'][$package->pkg_type];
      }
    }

    $rows[] = array($this->t('Package type:'), isset($pkg_type) ? $pkg_type : SafeMarkup::checkPlain($package->pkg_type));

    if ($package->length && $package->width && $package->height) {
      $rows[] = array($this->t('Dimensions:'), $this->t('@l x @w x @h', ['@l' => uc_length_format($package->length), '@w' => uc_length_format($package->width), '@h' => uc_length_format($package->height)]));
    }

    $rows[] = array($this->t('Insured value:'), array('data' => array('#theme' => 'uc_price', '#price' => $package->value)));

    if ($package->tracking_number) {
      $rows[] = array($this->t('Tracking number:'), SafeMarkup::checkPlain($package->tracking_number));
    }

    if ($shipment && isset($package->label_image) &&
        file_exists($package->label_image->uri)) {
      $rows[] = array($this->t('Label:'), Link::fromTextAndUrl($this->t('Click to view.'), Url::fromUri('admin/store/orders/' . $package->order_id . '/shipments/labels/' . $shipment->shipping_method . '/' . $package->label_image->uri))->toString());
    }
    else {
      $rows[] = array($this->t('Label:'), $this->t('n/a'));
    }

    $build['package'] = array(
      '#theme' => 'table',
      '#rows' => $rows,
      'attributes' => array('style' => 'width:auto;'),
    );

    return $build;
  }

}