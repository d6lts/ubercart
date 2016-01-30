<?php

/**
 * @file
 * Contains \Drupal\uc_fulfillment\Controller\PackageController.
 */

namespace Drupal\uc_fulfillment\Controller;


use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\uc_order\OrderInterface;

/**
 * Controller routines for order routes.
 */
class PackageController extends ControllerBase {

  /**
   * Displays a list of an order's packaged products.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order.
   *
   * @return array
   *   A render array.
   */
  public function listOrderPackages(OrderInterface $order) {
    $shipping_type_options = uc_quote_fulfillment_type_options();
    $header = array($this->t('Package ID'), $this->t('Products'), $this->t('Shipping type'), $this->t('Package type'), $this->t('Shipment ID'), $this->t('Tracking number'), $this->t('Labels'), array('data' => $this->t('Actions'), 'colspan' => 4));
    $rows = array();
    $result = db_query('SELECT * FROM {uc_packages} WHERE order_id = :id', [':id' => $order->id()]);
    foreach ($result as $package) {
      $row = array();

      $row[] = $package->package_id;

      $product_list = array();
      $result2 = db_query('SELECT op.order_product_id, pp.qty, op.title, op.model FROM {uc_packaged_products} pp LEFT JOIN {uc_order_products} op ON op.order_product_id = pp.order_product_id WHERE pp.package_id = :id', [':id' => $package->package_id]);
      foreach ($result2 as $product) {
        $product_list[] = $product->qty . ' x ' . SafeMarkup::checkPlain($product->model);
      }

      $row[] = '<ul><li>' . implode('</li><li>', $product_list) . '</li></ul>';
      $row[] = isset($shipping_type_options[$package->shipping_type]) ? $shipping_type_options[$package->shipping_type] : strtr($package->shipping_type, '_', ' ');
      $row[] = SafeMarkup::checkPlain($package->pkg_type);
      $row[] = isset($package->sid) ? Link::fromTextAndUrl($package->sid, Url::fromUri('base:admin/store/orders/{order_id}/shipments/{shipment_id}/view', ['order_id' => $order->id(), 'shipment_id' => $package->sid])) : '';
      $row[] = isset($package->tracking_number) ? SafeMarkup::checkPlain($package->tracking_number) : '';

      if ($package->label_image && $image = file_load($package->label_image)) {
        $package->label_image = $image;
      }
      else {
        unset($package->label_image);
      }

      if (isset($package->sid) && isset($package->label_image)) {
        $method = db_query('SELECT shipping_method FROM {uc_shipments} WHERE sid = :sid', [':sid' => $package->sid])->fetchField();
        $row[] = Link::fromTextAndUrl(
          theme('image_style', array(
            'style_name' => 'uc_thumbnail',
            'uri' => $package->label_image->uri,
            'alt' => $this->t('Shipping label'),
            'title' => $this->t('Shipping label'),
          )),
          Url::fromUri('base:admin/store/orders/{order_id}/shipments/labels/{method}/{image_uri}', ['order_id' => $order->id(), 'method' => $method, 'image_uri' => $package->label_image->uri])
        );
      }
      else {
        $row[] = '';
      }

      $row[] = Link::fromTextAndUrl($this->t('edit'), Url::fromUri('base:admin/store/orders/{order_id}/packages/{package_id}/edit', ['order_id' => $order->id(), 'package_id' => $package->package_id]));
      $row[] = Link::fromTextAndUrl($this->t('ship'), Url::fromUri('base:admin/store/orders/{order_id}/shipments/new', ['order_id' => $order->id()]), ['query' => ['pkgs' => [$package->package_id]]]);
      $row[] = Link::fromTextAndUrl($this->t('delete'), Url::fromUri('base:admin/store/orders/{order_id}/packages/{package_id}/delete', ['order_id' => $order->id(), 'package_id' => $package->package_id]));

      if ($package->sid) {
        $row[] = Link::fromTextAndUrl($this->t('cancel shipment'), Url::fromUri('base:admin/store/orders/{order_id}/packages/{package_id}/cancel', ['order_id' => $order->id(), 'package_id' => $package->package_id]));
      }
      else {
        $row[] = '';
      }

      $rows[] = $row;
    }

    if (empty($rows)) {
      drupal_set_message($this->t("This order's products have not been organized into packages."));
      drupal_goto('admin/store/orders/' . $order->id() . '/packages/new');
    }

    $build['packages'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );

    return $build;
  }

}
