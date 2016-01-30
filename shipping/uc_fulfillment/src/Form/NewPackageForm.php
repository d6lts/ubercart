<?php

/**
 * @file
 * Contains \Drupal\uc_fulfillment\Form\NewPackageForm.
 */

namespace Drupal\uc_fulfillment\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;

/**
 * Puts ordered products into a package.
 */
class NewPackageForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_fulfillment_new_package';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $order = NULL) {
    $form['#tree'] = TRUE;
    $shipping_types_products = array();
    foreach ($order->products as $product) {
      if ($product->data['shippable']) {
        $product->shipping_type = uc_product_get_shipping_type($product);
        $shipping_types_products[$product->shipping_type][] = $product;
      }
    }

    $quote_config = \Drupal::config('uc_quote.settings');
    $shipping_type_weights = $quote_config->get('type_weight');

    $result = db_query("SELECT op.order_product_id, SUM(pp.qty) AS quantity FROM {uc_packaged_products} pp LEFT JOIN {uc_packages} p ON pp.package_id = p.package_id LEFT JOIN {uc_order_products} op ON op.order_product_id = pp.order_product_id WHERE p.order_id = :id GROUP BY op.order_product_id", [':id' => $order->id()]);
    $packaged_products = $result->fetchAllKeyed();

    $form['shipping_types'] = array();
    $shipping_type_options = uc_quote_fulfillment_type_options();
    foreach ($shipping_types_products as $shipping_type => $products) {
      $form['shipping_types'][$shipping_type] = array(
        '#type'   => 'fieldset',
        '#title'  => isset($shipping_type_options[$shipping_type]) ?
                           $shipping_type_options[$shipping_type]        :
                           ucwords(str_replace('_', ' ', $shipping_type)),
        '#weight' => isset($shipping_type_weights[$shipping_type]) ? $shipping_type_weights[$shipping_type] : 0,
      );

      foreach ($products as $product) {
        $unboxed_qty = $product->qty;
        if (isset($packaged_products[$product->order_product_id])) {
          $unboxed_qty -= $packaged_products[$product->order_product_id];
        }

        if ($unboxed_qty > 0) {
          $product_row = array();
          $product_row['checked'] = array(
            '#type'          => 'checkbox',
            '#default_value' => 0,
          );
          $product_row['model'] = array(
            '#markup' => $product->model
          );
          $product_row['name'] = array(
            '#markup' => Xss::filterAdmin($product->title)
          );
          $range = range(1, $unboxed_qty);
          $product_row['qty'] = array(
            '#type'          => 'select',
            '#title'         => $this->t('Quantity'),
            '#title_display' => 'invisible',
            '#options'       => array_combine($range, $range),
            '#default_value' => $unboxed_qty,
          );

          $range = range(0, count($order->products));
          $options = array_combine($options, $options);
          $options[0] = $this->t('Sep.');
          $product_row['package'] = array(
            '#type' => 'select',
            '#title' => $this->t('Package'),
            '#title_display' => 'invisible',
            '#options' => $options,
            '#default_value' => 0,
          );

          $form['shipping_types'][$shipping_type][$product->order_product_id] = $product_row;
        }
      }

      $form['shipping_types'][$shipping_type]['#theme'] = 'uc_fulfillment_new_package_fieldset';
    }

    $form['order_id'] = array(
      '#type'  => 'hidden',
      '#value' => $order->id(),
    );
    $form['actions'] = array(
      '#type'  => 'actions',
    );
    $form['actions']['create'] = array(
      '#type'  => 'submit',
      '#value' => $this->t('Make packages'),
    );
    $form['actions']['combine'] = array(
      '#type'  => 'submit',
      '#value' => $this->t('Create one package'),
    );
    $form['actions']['cancel'] = array(
      '#type'  => 'submit',
      '#value' => $this->t('Cancel'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('op') != $this->t('Cancel')) {
      $empty = TRUE;

      foreach ($form_state->getValue('shipping_types') as $shipping_type => $products) {
        foreach ($products as $product) {
          if ($product['checked'] != 0) {
            $empty = FALSE;
            break 2;
          }
        }
      }

      if ($empty) {
        $form_state->setErrorByName($shipping_type, $this->t('Packages should have at least one product in them.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('op') != $this->t('Cancel')) {
      $packages = array(0 => array());

      foreach ($form_state->getValue('shipping_types') as $shipping_type => $products) {
        foreach ($products as $id => $product) {
          if ($product['checked']) {
            if ($form_state->getValue('op') == $this->t('Create one package')) {
              $product['package'] = 1;
            }

            if ($product['package'] != 0) {
              $packages[$product['package']]['products'][$id] = (object)$product;

              if (!isset($packages[$product['package']]['shipping_type'])) {
                $packages[$product['package']]['shipping_type'] = $shipping_type;
              }
            }
            else {
              $packages[0][$shipping_type][$id] = (object)$product;
            }
          }
        }
        if (isset($packages[0][$shipping_type])) {
          foreach ($packages[0][$shipping_type] as $id => $product) {
            $qty = $product->qty;
            $product->qty = 1;
            for ($i = 0; $i < $qty; $i++) {
              $packages[] = array('products' => array($id => $product), 'shipping_type' => $shipping_type);
            }
          }
        }

        unset($packages[0][$shipping_type]);
      }

      if (empty($packages[0])) {
        unset($packages[0]);
      }

      foreach ($packages as $package) {
        $package['order_id'] = $form_state->getValue('order_id');
        uc_fulfillment_package_save($package);
      }
    }

    $form_state->setRedirect('uc_fulfillment.packages', ['uc_order' => $form_state->getValue('order_id')]);
  }

}
