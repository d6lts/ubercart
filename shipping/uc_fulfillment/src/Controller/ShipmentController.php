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
use Drupal\uc_fulfillment\Entity\FulfillmentMethod;
use Drupal\uc_fulfillment\Shipment;
use Drupal\uc_fulfillment\ShipmentInterface;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_store\Address;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for shipments.
 */
class ShipmentController extends ControllerBase {

  /**
   * The page title callback for shipment views.
   *
   * @param \Drupal\uc_order\OrderInterface $uc_order
   *   The shipment's order.
   * @param \Drupal\uc_fulfillment\ShipmentInterface $uc_shipment
   *   The ID of shipment.
   *
   * @return string
   *   The page title.
   */
  public function pageTitle(OrderInterface $uc_order, ShipmentInterface $uc_shipment) {
    return $this->t('Shipment @id', ['@id' => $uc_shipment->id()]);
  }

  /**
   * Default method to create a shipment from packages.
   *
   * @param \Drupal\uc_order\OrderInterface $uc_order
   *   The order object.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array, or a redirect response if there are selected packages.
   */
  public function makeShipment(OrderInterface $uc_order, Request $request) {
    $method_id = $request->query->get('method_id');
    $request->query->remove('method_id');
    $package_ids = $request->query->all();
    if (count($package_ids) > 0) {
//      $breadcrumb = drupal_get_breadcrumb();
//      $breadcrumb[] = Link::createFromRoute($this->t('Shipments'), 'uc_fulfillment.shipments', ['uc_order' => $uc_order->id()]);
//      drupal_set_breadcrumb($breadcrumb);

      // Find FulfillmentMethod plugins.
      $manager = \Drupal::service('plugin.manager.uc_fulfillment.method');
      $methods = FulfillmentMethod::loadMultiple();

      if (isset($methods[$method_id])) {
        $method = $methods[$method_id];
      }
      else {
        // The selected fulfullment isn't available, so use built-in "Manual" shipping.
        $method = $methods['manual'];
      }
      $plugin = $manager->createInstance($method->getPluginId(), $method->getPluginConfiguration());
      return $plugin->fulfillOrder($uc_order, $package_ids);
    }
    else {
      drupal_set_message($this->t('There is no sense in making a shipment with no packages on it, right?'), 'warning');
      return $this->redirect('uc_fulfillment.new_shipment', ['uc_order' => $uc_order->id()]);
    }
  }

  /**
   * Shows a printer-friendly version of a shipment.
   *
   * @param \Drupal\uc_order\OrderInterface $uc_order
   *   The order object.
   * @param int $shipment_id
   *   The ID of shipment.
   *
   * @return array
   *   HTML for the shipment.
   */
  public function printShipment(OrderInterface $uc_order, $shipment_id, $labels = TRUE) {
    $build = array(
      '#theme' => 'uc_fulfillment_shipment_print',
      '#order' => $uc_order,
      '#shipment' => $shipment_id,
      '#labels' => $labels,
    );

    $markup = \Drupal::service('renderer')->renderPlain($build);
    $response = new Response($markup);
    $response->headers->set('Content-Type', 'text/html; charset=utf-8');
// @todo: Fix so this uses the template.
//    print theme('uc_packing_slip_page', array('content' => drupal_render($build)));
    return $response;
  }

  /**
   * Displays a list of shipments for an order.
   *
   * @param \Drupal\uc_order\OrderInterface $uc_order
   *   The order object.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array, or redirect response if there are no shipments.
   */
  public function listOrderShipments(OrderInterface $uc_order) {
    $header = array(
      $this->t('Shipment ID'),
      $this->t('Name'),
      $this->t('Company'),
      $this->t('Destination'),
      $this->t('Ship date'),
      $this->t('Estimated delivery'),
      $this->t('Tracking number'),
      $this->t('Actions')
    );

    $shipments = Shipment::loadByOrder($uc_order->id());
    $rows = array();
    foreach ($shipments as $shipment) {
      $row = array();
      // Shipment ID.
      $row[] = array('data' => array('#plain_text' => $shipment->id()));

      // Destination address.
      $destination = $shipment->getDestination();

      // Name.
      $row[] = array('data' => array('#plain_text' => $destination->getFirstName() . ' ' . $destination->getLastName()));

      // Company.
      $row[] = array('data' => array('#plain_text' => $destination->getCompany()));

      // Destination.
      $row[] = array('data' => array('#plain_text' => $destination->getCity() . ', ' . $destination->getZone() . ' ' . $destination->getPostalCode()));

      // Ship date.
      $row[] = \Drupal::service('date.formatter')->format($shipment->getShipDate(), 'uc_store');

      // Estimated delivery.
      $row[] = \Drupal::service('date.formatter')->format($shipment->getExpectedDelivery(), 'uc_store');

      // Tracking number.
      $row[] = empty($shipment->getTrackingNumber()) ? $this->t('n/a') : array('data' => array('#plain_text' => $shipment->getTrackingNumber()));

      // Actions.
      $ops[] = array(
        '#type' => 'operations',
        '#links' => array(
          'view' => array(
            'title' => $this->t('View'),
            'url' => Url::fromRoute('uc_fulfillment.view_shipment', ['uc_order' => $uc_order->id(), 'uc_shipment' => $shipment->id()]),
          ),
          'edit' => array(
            'title' => $this->t('Edit'),
            'url' => Url::fromRoute('uc_fulfillment.edit_shipment', ['uc_order' => $uc_order->id(), 'uc_shipment' => $shipment->id()]),
          ),
          'print' => array(
            'title' => $this->t('Print'),
            'url' => Url::fromRoute('uc_fulfillment.print_shipment', ['uc_order' => $uc_order->id(), 'uc_shipment' => $shipment->id()]),
          ),
          'packing_slip' => array(
            'title' => $this->t('Packing slip'),
            'url' => Url::fromRoute('uc_fulfillment.packing_slip', ['uc_order' => $uc_order->id(), 'uc_shipment' => $shipment->id()]),
          ),
          'delete' => array(
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('uc_fulfillment.delete_shipment', ['uc_order' => $uc_order->id(), 'uc_shipment' => $shipment->id()]),
          ),
        ),
      );
      $row[] = array('data' => $ops);
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
   * @param \Drupal\uc_fulfillment\ShipmentInterface $uc_shipment
   *   The shipment.
   *
   * @return array
   *   A render array.
   */
  public function viewShipment(OrderInterface $uc_order, ShipmentInterface $uc_shipment) {

    // Origin address.
    $build['pickup_address'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('order-pane', 'pos-left')),
    );
    $build['pickup_address']['title'] = array(
      '#type' => 'container',
      '#markup' => $this->t('Pickup Address:'),
      '#attributes' => array('class' => array('order-pane-title')),
    );
    $build['pickup_address']['address'] = array(
      '#type' => 'container',
      '#markup' => $uc_shipment->getOrigin(),
    );

    // Destination address.
    $build['delivery_address'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('order-pane', 'pos-left')),
    );
    $build['delivery_address']['title'] = array(
      '#type' => 'container',
      '#markup' => $this->t('Delivery Address:'),
      '#attributes' => array('class' => array('order-pane-title')),
    );
    $build['delivery_address']['address'] = array(
      '#type' => 'container',
      '#markup' => $uc_shipment->getDestination(),
    );

    // Fulfillment schedule.
    $rows = array();
    $rows[] = array(
      $this->t('Ship date:'),
      \Drupal::service('date.formatter')->format($uc_shipment->getShipDate(), 'uc_store')
    );
    $rows[] = array(
      $this->t('Expected delivery:'),
      \Drupal::service('date.formatter')->format($uc_shipment->getExpectedDelivery(), 'uc_store')
    );
    $build['schedule'] = array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#attributes' => array('style' => 'width: auto'),
      '#prefix' => '<div class="order-pane abs-left"><div class="order-pane-title">' . $this->t('Schedule:') . '</div>',
      '#suffix' => '</div>',
    );

    // Shipment details.
    $rows = array();
    $rows[] = array(
      $this->t('Carrier:'),
      array('data' => array('#plain_text' => $uc_shipment->getCarrier())),
    );
    if ($uc_shipment->getTransactionId()) {
      $rows[] = array(
        $this->t('Transaction ID:'),
        array('data' => array('#plain_text' => $uc_shipment->getTransactionId())),
      );
    }
    if ($uc_shipment->getTrackingNumber()) {
      $rows[] = array(
        $this->t('Tracking number:'),
        array('data' => array('#plain_text' => $uc_shipment->getTrackingNumber())),
      );
    }
    $methods = \Drupal::moduleHandler()->invokeAll('uc_fulfillment_method');
    if (isset($methods[$uc_shipment->getShippingMethod()]['quote']['accessorials'][$uc_shipment->getAccessorials()])) {
      $rows[] = array($this->t('Services:'),
        $methods[$uc_shipment->getShippingMethod()]['quote']['accessorials'][$uc_shipment->getAccessorials()],
      );
    }
    else {
      $rows[] = array($this->t('Services:'),
        $uc_shipment->getAccessorials(),
      );
    }
    $rows[] = array(
      $this->t('Cost:'),
      array('data' => array('#theme' => 'uc_price', '#price' => $uc_shipment->getCost())),
    );
    $build['details'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('order-pane', 'abs-left')),
    );
    $build['details']['title'] = array(
      '#type' => 'container',
      '#markup' => $this->t('Shipment Details:'),
      '#attributes' => array('class' => array('order-pane-title')),
    );
    $build['details']['table'] = array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#attributes' => array('style' => 'width:auto'),
    );

    // Packages.
    foreach ($uc_shipment->getPackages() as $package) {
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
  protected function getAddress($order, $type) {
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
    $shipment = Shipment::load($package->sid);
    $build = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('order-pane', 'pos-left')),
    );
    $build['title'] = array(
      '#type' => 'container',
      '#markup' => $this->t('Package %id:', ['%id' => $package->package_id]),
      '#attributes' => array('class' => array('order-pane-title')),
    );

    $rows = array();
    $rows[] = array($this->t('Contents:'), Xss::filterAdmin($package->description));

    if ($shipment) {
      $methods = \Drupal::moduleHandler()->invokeAll('uc_fulfillment_method');
      if (isset($methods[$shipment->getShippingMethod()])) {
        $pkg_type = $methods[$shipment->getShippingMethod()]['ship']['pkg_types'][$package->pkg_type];
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
      $rows[] = array($this->t('Label:'), Link::fromTextAndUrl($this->t('Click to view.'), Url::fromUri('admin/store/orders/' . $package->order_id . '/shipments/labels/' . $shipment->getShippingMethod() . '/' . $package->label_image->uri))->toString());
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
