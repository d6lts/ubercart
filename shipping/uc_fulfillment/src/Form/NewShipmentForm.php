<?php

/**
 * @file
 * Contains \Drupal\uc_fulfillment\Form\NewShipmentForm.
 */

namespace Drupal\uc_fulfillment\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;

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
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $uc_order = NULL) {
    $checked_pkgs = isset($_GET['pkgs']) ? $_GET['pkgs'] : array();

    $form['#tree'] = TRUE;
    $form['order_id'] = array('#type' => 'hidden', '#value' => $uc_order->id());

    $packages_by_type = array();
    $units = \Drupal::config('uc_store.settings')->get('weight.units');
    $result = db_query('SELECT * FROM {uc_packages} WHERE order_id = :id AND sid IS NULL', [':id' => $uc_order->id()]);
    foreach ($result as $package) {
      $products = array();
      $weight = 0;
      $result2 = db_query('SELECT pp.order_product_id, pp.qty, pp.qty * op.weight AS weight, op.weight_units, op.title, op.model FROM {uc_packaged_products} pp LEFT JOIN {uc_order_products} op ON op.order_product_id = pp.order_product_id WHERE pp.package_id = :id', [':id' => $package->package_id]);
      foreach ($result2 as $product) {
        $units_conversion = uc_weight_conversion($product->weight_units, $units);
        $weight += $product->weight * $units_conversion;
        $products[$product->order_product_id->value] = $product;
      }
      $package->weight = $weight;
      $package->products = $products;
      $packages_by_type[$package->shipping_type][$package->package_id] = $package;
    }
    $option_methods = array();
    $shipping_types = uc_quote_get_fulfillment_types();
    $shipping_methods = \Drupal::moduleHandler()->invokeAll('uc_fulfillment_method');
    $shipping_methods_by_type = array();
    foreach ($shipping_methods as $method) {
      if (isset($method['ship'])) {
        $shipping_methods_by_type[$method['ship']['type']][] = $method;
      }
    }
    $pkgs_exist = FALSE;
    foreach ($packages_by_type as $shipping_type => $packages) {
      $form['shipping_types'][$shipping_type] = array(
        '#type' => 'fieldset',
        '#title' => $shipping_types[$shipping_type]['title'],
      );
      $form['shipping_types'][$shipping_type]['packages'] = array();
      foreach ($packages as $package) {
        $pkgs_exist = TRUE;
        $package_row = array();
        $package_row['checked'] = array('#type' => 'checkbox', '#default_value' => (in_array($package->package_id, $checked_pkgs) ? 1 : 0));
        $package_row['package_id'] = array('#markup' => $package->package_id);
        $product_list = array();
        foreach ($package->products as $product) {
          $product_list[] = $product->qty->value . ' x ' . SafeMarkup::checkPlain($product->model->value);
        }
        $package_row['products'] = array('#markup' => '<ul><li>' . implode('</li><li>', $product_list) . '</li></ul>');
        $package_row['weight'] = array('#markup' => uc_weight_format($package->weight, $units));
        $form['shipping_types'][$shipping_type]['packages'][$package->package_id] = $package_row;
      }

      if (isset($shipping_methods_by_type[$shipping_type])) {
        foreach ($shipping_methods_by_type[$shipping_type] as $method) {
          $option_methods += array($method['id'] => $method['title']);
        }
      }
    }
    if ($pkgs_exist) {
      $option_methods = array('all' => $this->t('Ship Manually')) + $option_methods;
      $form['method'] = array(
        '#type' => 'select',
        '#title' => $this->t('Shipping method'),
        '#options' => $option_methods,
        '#default_value' => 'all',
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
    foreach ($form_state->getValue('shipping_types') as $shipping_type) {
      if (is_array($shipping_type['packages'])) {
        foreach ($shipping_type['packages'] as $id => $input) {
          if ($input['checked']) {
            $packages[] = $id;
          }
        }
      }
    }
    //$form_state->setRedirect('uc_fulfillment.packages', ['uc_order' => $form_state->getValue('order_id')]);
    $form_state['redirect'] = 'admin/store/orders/' . $form_state->getValue('order_id') . '/ship/' . $form_state->getValue('method') . '/' . implode('/', $packages);
  }

}
