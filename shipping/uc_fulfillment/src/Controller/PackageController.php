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
   * @param \Drupal\uc_order\OrderInterface $uc_order
   *   The order.
   *
   * @return array
   *   A render array.
   */
  public function listOrderPackages(OrderInterface $uc_order) {
    $shipping_type_options = uc_quote_shipping_type_options();
    $header = array($this->t('Package ID'), $this->t('Products'), $this->t('Shipping type'), $this->t('Package type'), $this->t('Shipment ID'), $this->t('Tracking number'), $this->t('Labels'), array('data' => $this->t('Actions'), 'colspan' => 4));
    $rows = array();
    $result = db_query('SELECT * FROM {uc_packages} WHERE order_id = :id', [':id' => $uc_order->id()]);
    foreach ($result as $package) {
      $row = array();

      $row[] = $package->package_id;

      $product_list = array();
      $result2 = db_query('SELECT op.order_product_id, pp.qty, op.title, op.model FROM {uc_packaged_products} pp LEFT JOIN {uc_order_products} op ON op.order_product_id = pp.order_product_id WHERE pp.package_id = :id', [':id' => $package->package_id]);
      foreach ($result2 as $product) {
        $product_list[] = $product->qty->value . ' x ' . SafeMarkup::checkPlain($product->model->value);
      }

      $row[] = '<ul><li>' . implode('</li><li>', $product_list) . '</li></ul>';
      $row[] = isset($shipping_type_options[$package->shipping_type]) ? $shipping_type_options[$package->shipping_type] : strtr($package->shipping_type, '_', ' ');
      $row[] = SafeMarkup::checkPlain($package->pkg_type);
      $row[] = isset($package->sid) ? Link::createFromRoute($package->sid, 'uc_fulfillment.view_shipment', ['uc_order' => $uc_order->id(), 'shipment_id' => $package->sid]) : '';
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
          Url::fromUri('base:admin/store/orders/{order_id}/shipments/labels/{method}/{image_uri}', ['uc_order' => $uc_order->id(), 'method' => $method, 'image_uri' => $package->label_image->uri])
        );
      }
      else {
        $row[] = '';
      }

      $row[] = Link::createFromRoute($this->t('edit'), 'uc_fulfillment.edit_package', ['uc_order' => $uc_order->id(), 'package_id' => $package->package_id]);
      $row[] = Link::createFromRoute($this->t('ship'), 'uc_fulfillment.new_shipment', ['uc_order' => $uc_order->id()], ['query' => ['pkgs' => [$package->package_id]]]);
      $row[] = Link::createFromRoute($this->t('delete'), 'uc_fulfillment.delete_package', ['uc_order' => $uc_order->id(), 'package_id' => $package->package_id]);

      if ($package->sid) {
        $row[] = Link::createFromRoute($this->t('cancel shipment'), 'uc_fulfillment.cancel_package', ['uc_order' => $uc_order->id(), 'package_id' => $package->package_id]);
      }
      else {
        $row[] = '';
      }

      $rows[] = $row;
    }

    if (empty($rows)) {
      drupal_set_message($this->t("This order's products have not been organized into packages."));
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
