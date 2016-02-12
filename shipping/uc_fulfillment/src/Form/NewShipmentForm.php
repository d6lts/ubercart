<?php

/**
 * @file
 * Contains \Drupal\uc_fulfillment\Form\NewShipmentForm.
 */

namespace Drupal\uc_fulfillment\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_fulfillment\Entity\FulfillmentMethod;
use Drupal\uc_fulfillment\Package;
use Drupal\uc_order\OrderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Sets up a new shipment with the chosen packages.
 */
class NewShipmentForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_fulfillment_new_shipment';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $uc_order = NULL, Request $request = NULL) {
    $checked_pkgs = $request->query->has('pkgs') ? (array) $request->query->get('pkgs') : array();
    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'uc_fulfillment/uc_fulfillment.scripts';

    $units = \Drupal::config('uc_store.settings')->get('weight.units');

    $header = array(
      // Fake out tableselect JavaScript into operating on our table.
      array('data' => '', 'class' => array('select-all')),
      'package' => $this->t('Package'),
      'product' => $this->t('Products'),
      'weight' => $this->t('Weight'),
    );

    $packages_by_type = array();
    $packages = Package::loadByOrder($uc_order->id());
    foreach ($packages as $package) {
      if (!empty($package->getSid())) {
        // This package is already part of a shipment.
        break;
      }
//@todo: This looks like the code in Package::load() ... should try to replace it here
// to eliminate the SQL
      $products = array();
      $weight = 0;
      $result2 = db_query('SELECT pp.order_product_id, pp.qty, pp.qty * op.weight__value AS weight, op.weight__units, op.title, op.model FROM {uc_packaged_products} pp LEFT JOIN {uc_order_products} op ON op.order_product_id = pp.order_product_id WHERE pp.package_id = :id', [':id' => $package->id()]);
      foreach ($result2 as $product) {
        // Normalize all weights to default units.
        $units_conversion = uc_weight_conversion($product->weight__units, $units);
        $weight += $product->weight * $units_conversion;
        $products[$product->order_product_id] = $product;
      }
      $package->setWeight($weight);
      $package->setProducts($products);
      $packages_by_type[$package->getShippingType()][$package->id()] = $package;
    }

    // Find FulfillmentMethod plugins.
    $methods = FulfillmentMethod::loadMultiple();
    uasort($methods, 'Drupal\uc_fulfillment\Entity\FulfillmentMethod::sort');
    foreach ($methods as $method) {
      // Available fulfillment methods indexed by package type.
      $shipping_methods_by_type[$method->getPackageType()][] = $method;
    }

    $pkgs_exist = FALSE;
    $option_methods = array();
    $shipping_types = uc_quote_get_shipping_types();

    foreach ($packages_by_type as $shipping_type => $packages) {
      $form['shipping_types'][$shipping_type] = array(
        '#type' => 'fieldset',
        '#title' => $shipping_types[$shipping_type]['title'],
      );

      $rows = array();
      $form['shipping_types'][$shipping_type]['table'] = array(
        '#type' => 'table',
        '#header' => $header,
        '#empty' => $this->t('There are no products available for this type of package.'),
      );

      foreach ($packages as $package) {
        $pkgs_exist = TRUE;

        $row = array();
        $row['checked'] = array(
          '#type' => 'checkbox',
          '#default_value' => (in_array($package->id(), $checked_pkgs) ? 1 : 0)
        );
        $row['package_id'] = array(
          '#markup' => $package->id(),
        );

        $product_list = array();
        foreach ($package->getProducts() as $product) {
          $product_list[] = $product->qty . ' x ' . $product->model;
        }
        $row['products'] = array(
          '#theme' => 'item_list',
          '#items' => $product_list,
        );
        $row['weight'] = array(
          '#markup' => uc_weight_format($package->getWeight(), $units),
        );
        $form['shipping_types'][$shipping_type]['table'][$package->id()] = $row;
      }

      if (isset($shipping_methods_by_type[$shipping_type])) {
        foreach ($shipping_methods_by_type[$shipping_type] as $method) {
          $option_methods += array($method->id() => $method->label());
        }
      }
    }

    $form['order_id'] = array(
      '#type' => 'hidden',
      '#value' => $uc_order->id(),
    );

    if ($pkgs_exist) {
      // uc_fulfillment has a default plugin to provide the "Manual" method.
      $form['method'] = array(
        '#type' => 'select',
        '#title' => $this->t('Shipping method'),
        '#options' => $option_methods,
        '#default_value' => 'manual',
      );
      $form['actions'] = array('#type' => 'actions');
      $form['actions']['ship'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Ship packages'),
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $packages = array();
    $i = 1;
    foreach ($form_state->getValue('shipping_types') as $shipping_type) {
      if (is_array($shipping_type['table'])) {
        foreach ($shipping_type['table'] as $id => $input) {
          if ($input['checked']) {
            $packages[$i++] = $id;
          }
        }
      }
    }

    $form_state->setRedirect('uc_fulfillment.make_shipment', ['uc_order' => $form_state->getValue('order_id')],
                       ['query' => array_merge(['method_id' => $form_state->getValue('method')], $packages)]);
    //$form_state['redirect'] = 'admin/store/orders/{uc_order}/ship/' . $form_state->getValue('method') . '/' . implode('/', $packages);
  }

}
