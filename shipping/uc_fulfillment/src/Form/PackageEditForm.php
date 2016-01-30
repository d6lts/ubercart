<?php

/**
 * @file
 * Contains \Drupal\uc_fulfillment\Form\PackageEditForm.
 */

namespace Drupal\uc_fulfillment\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;

/**
 * Rearranges the products in or out of a package.
 */
class PackageEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_fulfillment_package_edit';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $order = NULL, $package = NULL) {
    $products = array();
    $shipping_types_products = array();
    foreach ($order->products as $product) {
      if ($product->data['shippable']) {
        $product->shipping_type = uc_product_get_fulfillment_type($product);
        $shipping_types_products[$product->shipping_type][$product->order_product_id] = $product;
        $products[$product->order_product_id] = $product;
      }
    }

    $result = db_query("SELECT order_product_id, SUM(qty) AS quantity FROM {uc_packaged_products} pp LEFT JOIN {uc_packages} p ON pp.package_id = p.package_id WHERE p.order_id = :id GROUP BY order_product_id", [':id' => $order->id()]);
    foreach ($result as $packaged_product) {
      // Make already packaged products unavailable, except those in this package.
      $products[$packaged_product->order_product_id]->qty -= $packaged_product->quantity;
      if (isset($package->products[$packaged_product->order_product_id])) {
        $products[$packaged_product->order_product_id]->qty += $package->products[$packaged_product->order_product_id]->qty;
      }
    }

    $form['#tree'] = TRUE;
    $form['package_id'] = array('#type' => 'hidden', '#value' => $package->package_id);
    $form['products'] = array();
    foreach ($products as $product) {
      if ($product->qty > 0) {
        $product_row = array();
        $product_row['checked'] = array('#type' => 'checkbox', '#default_value' => isset($package->products[$product->order_product_id]));
        $product_row['model'] = array('#markup' => $product->model);
        $product_row['name'] = array('#markup' => Xss::filterAdmin($product->title));
        $range = range(1, $product->qty);
        $product_row['qty'] = array(
          '#type' => 'select',
          '#options' => array_combine($range, $range),
          '#default_value' => isset($package->products[$product->order_product_id]) ? $package->products[$product->order_product_id]->qty : 1,
        );

        $form['products'][$product->order_product_id] = $product_row;
      }
    }
    $form['products']['#theme'] = 'uc_fulfillment_edit_package_fieldset';
    $options = array();
    $shipping_type_options = uc_quote_fulfillment_type_options();
    foreach (array_keys($shipping_types_products) as $type) {
      $options[$type] = isset($shipping_type_options[$type]) ? $shipping_type_options[$type] : ucwords(str_replace('_', ' ', $type));
    }
    $form['shipping_type'] = array(
      '#type' => 'select',
      '#title' => t('Shipping type'),
      '#options' => $options,
      '#default_value' => $package->shipping_type,
    );
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $package = uc_fulfillment_package_load($form_state->getValue('package_id'));
    foreach ($form_state->getValue('products') as $id => $product) {
      if ($product['checked']) {
        $package->products[$id] = (object)$product;
      }
      else {
        unset($package->products[$id]);
      }
    }
    $package->shipping_type = $form_state->getValue('shipping_type');
    uc_fulfillment_package_save($package);

    $form_state['redirect'] = 'admin/store/orders/' . $package->order_id . '/packages';
  }

}
