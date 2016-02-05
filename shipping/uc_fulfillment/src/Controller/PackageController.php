<?php

/**
 * @file
 * Contains \Drupal\uc_fulfillment\Controller\PackageController.
 */

namespace Drupal\uc_fulfillment\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\uc_fulfillment\Package;
use Drupal\uc_order\OrderInterface;

/**
 * Controller routines for packaging.
 */
class PackageController extends ControllerBase {

  /**
   * Displays a list of an order's packaged products.
   *
   * @param \Drupal\uc_order\OrderInterface $uc_order
   *   The order.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array, or a redirect response if there are no packaged products.
   */
  public function listOrderPackages(OrderInterface $uc_order) {
    $shipping_type_options = uc_quote_shipping_type_options();
    $header = array(
      $this->t('Package ID'),
      $this->t('Products'),
      $this->t('Shipping type'),
      $this->t('Package type'),
      $this->t('Shipment ID'),
      $this->t('Tracking number'),
      $this->t('Labels'),
      $this->t('Actions')
    );
    $rows = array();
    $result = db_query('SELECT package_id FROM {uc_packages} WHERE order_id = :id', [':id' => $uc_order->id()]);
    while ($package_id = $result->fetchField()) {
      $package = Package::load($package_id);

      $row = array();
      // Package ID.
      $row[] = array('data' => array('#plain_text' => $package->package_id));

      $product_list = array();
      $result2 = db_query('SELECT op.order_product_id, pp.qty, op.title, op.model FROM {uc_packaged_products} pp LEFT JOIN {uc_order_products} op ON op.order_product_id = pp.order_product_id WHERE pp.package_id = :id', [':id' => $package->package_id]);
      foreach ($result2 as $product) {
        $product_list[] = $product->qty . ' x ' . $product->model;
      }
      // Products.
      $row[] = array('data' => array('#theme' => 'item_list', '#items' => $product_list));

      // Shipping type.
      $row[] = isset($shipping_type_options[$package->shipping_type]) ? $shipping_type_options[$package->shipping_type] : strtr($package->shipping_type, '_', ' ');

      // Package type.
      $row[] = array('data' => array('#plain_text' => $package->pkg_type));

      // Shipment ID.
      $row[] = isset($package->sid) ? Link::createFromRoute($package->sid, 'uc_fulfillment.view_shipment', ['uc_order' => $uc_order->id(), 'shipment_id' => $package->sid])->toString() : '';

      // Tracking number.
      $row[] = isset($package->tracking_number) ? array('data' => array('#plain_text' => $package->tracking_number)) : '';

      if ($package->label_image && $image = file_load($package->label_image)) {
        $package->label_image = $image;
      }
      else {
        unset($package->label_image);
      }

      // Shipping label.
      if (isset($package->sid) && isset($package->label_image)) {
        $method = db_query('SELECT shipping_method FROM {uc_shipments} WHERE sid = :sid', [':sid' => $package->sid])->fetchField();
        $row[] = Link::fromTextAndUrl("image goes here",
     //     theme('image_style', array(
     //       'style_name' => 'uc_thumbnail',
     //       'uri' => $package->label_image->uri,
     //       'alt' => $this->t('Shipping label'),
     //       'title' => $this->t('Shipping label'),
     //     )),
          Url::fromUri('base:admin/store/orders/' . $uc_order->id() . '/shipments/labels/' . $method . '/' . $package->label_image->uri, ['uc_order' => $uc_order->id(), 'method' => $method, 'image_uri' => $package->label_image->uri])
        )->toString();
      }
      else {
        $row[] = '';
      }

      // Operations.
      $ops = array(
        '#type' => 'operations',
        '#links' => array(
          'edit' => array(
            'title' => $this->t('Edit'),
            'url' => Url::fromRoute('uc_fulfillment.edit_package', ['uc_order' => $uc_order->id(), 'package_id' => $package->package_id]),
          ),
          'ship' => array(
            'title' => $this->t('Ship'),
            'url' => Url::fromRoute('uc_fulfillment.new_shipment', ['uc_order' => $uc_order->id()], ['query' => ['pkgs' => $package->package_id]]),
          ),
          'delete' => array(
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('uc_fulfillment.delete_package', ['uc_order' => $uc_order->id(), 'package_id' => $package->package_id]),
          ),
        ),
      );
      if ($package->sid) {
        $ops['#links']['cancel'] = array(
          'title' => $this->t('Cancel'),
          'url' => Url::fromRoute('uc_fulfillment.cancel_package', ['uc_order' => $uc_order->id(), 'package_id' => $package->package_id]),
        );
      }
      $row[] = array('data' => $ops);
      $rows[] = $row;
    }

    if (empty($rows)) {
      drupal_set_message($this->t("This order's products have not been organized into packages."), 'warning');
      return $this->redirect('uc_fulfillment.new_package', ['uc_order' => $uc_order->id()]);
    }

    $build['packages'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );

    return $build;
  }

}
