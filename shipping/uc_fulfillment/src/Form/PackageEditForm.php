<?php

/**
 * @file
 * Contains \Drupal\uc_fulfillment\Form\PackageEditForm.
 */

namespace Drupal\uc_fulfillment\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_fulfillment\Package;
use Drupal\uc_order\OrderInterface;

/**
 * Rearranges the products in or out of a package.
 */
class PackageEditForm extends FormBase {

  /**
   * The package.
   *
   * @var \Drupal\uc_fulfillment\Package
   */
  protected $package;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_fulfillment_package_edit';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $uc_order = NULL, $package_id = NULL) {
    $this->package = Package::load($package_id);

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'uc_fulfillment/uc_fulfillment.scripts';
    $products = array();
    $shipping_types_products = array();
    foreach ($uc_order->products as $product) {
      if (uc_order_product_is_shippable($product)) {
        $product->shipping_type = uc_product_get_shipping_type($product);
        $shipping_types_products[$product->shipping_type][$product->order_product_id->value] = $product;
        $products[$product->order_product_id->value] = $product;
      }
    }

    $header = array(
      // Fake out tableselect JavaScript into operating on our table.
      array('data' => '', 'class' => array('select-all')),
      'model' => $this->t('SKU'),
      'name' => $this->t('Title'),
      'qty' => $this->t('Quantity'),
    );

    $result = db_query('SELECT order_product_id, SUM(qty) AS quantity FROM {uc_packaged_products} pp LEFT JOIN {uc_packages} p ON pp.package_id = p.package_id WHERE p.order_id = :id GROUP BY order_product_id', [':id' => $uc_order->id()]);
    foreach ($result as $packaged_product) {
      // Make already packaged products unavailable, except those in this package.
      $products[$packaged_product->order_product_id]->qty->value -= $packaged_product->quantity;
      if (isset($this->package->products[$packaged_product->order_product_id])) {
        $products[$packaged_product->order_product_id]->qty->value += $this->package->products[$packaged_product->order_product_id]->qty;
      }
    }

    $form['products'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#empty' => $this->t('There are no products available for this type of package.'),
    );

    foreach ($products as $product) {
      if ($product->qty->value > 0) {
        $row = array();
        $row['checked'] = array(
          '#type' => 'checkbox',
          '#default_value' => isset($this->package->products[$product->order_product_id->value]),
        );
        $row['model'] = array(
          '#markup' => $product->model->value,
        );
        $row['name'] = array(
          '#markup' => $product->title->value,
        );

        $range = range(1, $product->qty->value);
        $row['qty'] = array(
          '#type' => 'select',
          '#options' => array_combine($range, $range),
          '#default_value' => isset($this->package->products[$product->order_product_id->value]) ? $this->package->products[$product->order_product_id->value]->qty : 1,
        );

        $form['products'][$product->order_product_id->value] = $row;
      }
    }

    $options = array();
    $shipping_type_options = uc_quote_shipping_type_options();
    foreach (array_keys($shipping_types_products) as $type) {
      $options[$type] = isset($shipping_type_options[$type]) ? $shipping_type_options[$type] : Unicode::ucwords(str_replace('_', ' ', $type));
    }
    $form['shipping_type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Shipping type'),
      '#options' => $options,
      '#default_value' => isset($this->package->shipping_type) ? $this->package->shipping_type : 'small_package',
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('products') as $id => $product) {
      if ($product['checked']) {
        $this->package->products[$id] = (object)$product;
      }
      else {
        unset($this->package->products[$id]);
      }
    }
    $this->package->shipping_type = $form_state->getValue('shipping_type');
    $this->package->save();

    $form_state->setRedirect('uc_fulfillment.packages', ['uc_order' => $this->package->order_id]);
  }

}
