<?php

/**
 * @file
 * Contains \Drupal\uc_paypal\Form\WpsForm.
 */

namespace Drupal\uc_paypal\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\PaymentMethodPluginInterface;

/**
 * Form to build the submission to PayPal.
 */
class WpsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_paypal_wps_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $order = NULL, PaymentMethodPluginInterface $plugin = NULL) {
    $configuration = $plugin->getConfiguration();

    $shipping = 0;
    foreach ($order->line_items as $item) {
      if ($item['type'] == 'shipping') {
        $shipping += $item['amount'];
      }
    }

    $tax = 0;
    if (\Drupal::moduleHandler()->moduleExists('uc_tax')) {
      foreach (uc_tax_calculate($order) as $tax_item) {
        $tax += $tax_item->amount;
      }
    }

    $address = $order->getAddress($configuration['wps_address_selection']);

    $country = $address->country;
    $phone = '';
    for ($i = 0; $i < strlen($address->phone); $i++) {
      if (is_numeric($address->phone[$i])) {
        $phone .= $address->phone[$i];
      }
    }

    /**
     * night_phone_a: The area code for U.S. phone numbers, or the country code
     *                for phone numbers outside the U.S.
     * night_phone_b: The three-digit prefix for U.S. phone numbers, or the
     *                entire phone number for phone numbers outside the U.S.,
     *                excluding country code.
     * night_phone_c: The four-digit phone number for U.S. phone numbers.
     *                (Not Used for UK numbers)
     */
    if ($country == 'US' || $country == 'CA') {
      $phone = substr($phone, -10);
      $phone_a = substr($phone, 0, 3);
      $phone_b = substr($phone, 3, 3);
      $phone_c = substr($phone, 6, 4);
    }
    else {
      $phone_a = $phone_b = $phone_c = '';
    }

    $data = array(
      // PayPal command variable.
      'cmd' => '_cart',

      // Set the correct codepage.
      'charset' => 'utf-8',

      // IPN control notify URL.
      'notify_url' => Url::fromRoute('uc_paypal.ipn', [], ['absolute' => TRUE])->toString(),

      // Display information.
      'cancel_return' => Url::fromRoute('uc_paypal.wps_cancel', [], ['absolute' => TRUE])->toString(),
      'no_note' => 1,
      'no_shipping' => $configuration['wps_no_shipping'],
      'return' => Url::fromRoute('uc_paypal.wps_complete', ['uc_order' => $order->id()], ['absolute' => TRUE])->toString(),
      'rm' => 1,

      // Transaction information.
      'currency_code' => $configuration['wps_currency'],
      'handling_cart' => uc_currency_format($shipping, FALSE, FALSE, '.'),
      'invoice' => $order->id() . '-' .  \Drupal::service('uc_cart.manager')->get()->getId(),
      'tax_cart' => uc_currency_format($tax, FALSE, FALSE, '.'),

      // Shopping cart specific variables.
      'business' => trim($configuration['wps_email']),
      'upload' => 1,

      'lc' => $configuration['wps_language'],

      // Prepopulating forms/address overriding.
      'address1' => substr($address->street1, 0, 100),
      'address2' => substr($address->street2, 0, 100),
      'city' => substr($address->city, 0, 40),
      'country' => $country,
      'email' => $order->getEmail(),
      'first_name' => substr($address->first_name, 0, 32),
      'last_name' => substr($address->last_name, 0, 64),
      'state' => $address->zone,
      'zip' => $address->postal_code,
      'night_phone_a' => $phone_a,
      'night_phone_b' => $phone_b,
      'night_phone_c' => $phone_c,
    );

    if ($configuration['wps_address_override']) {
      $data['address_override'] = 1;
    }

    // Account for stores that just want to authorize funds instead of capture.
    if ($configuration['wps_payment_action'] == 'Authorization') {
      $data['paymentaction'] = 'authorization';
    }

    if ($configuration['wps_submit_method'] == 'itemized') {
      // List individual items.
      $i = 0;
      foreach ($order->products as $item) {
        $i++;
        $data['amount_' . $i] = uc_currency_format($item->price, FALSE, FALSE, '.');
        $data['item_name_' . $i] = $item->title;
        $data['item_number_' . $i] = $item->model;
        $data['quantity_' . $i] = $item->qty;

        // PayPal will only display the first two...
        if (!empty($item->data['attributes']) && count($item->data['attributes']) > 0) {
          $o = 0;
          foreach ($item->data['attributes'] as $name => $setting) {
            $data['on' . $o . '_' . $i] = $name;
            $data['os' . $o . '_' . $i] = implode(', ', (array)$setting);
            $o++;
          }
        }
      }

      // Apply discounts (negative amount line items). For example, this handles
      // line items created by uc_coupon.
      $discount = 0;

      foreach ($order->line_items as $item) {
        if ($item['amount'] < 0) {
          // The minus sign is not an error! The discount amount must be positive.
          $discount -= $item['amount'];
        }
      }

      if ($discount != 0) {
        $data['discount_amount_cart'] = $discount;
      }
    }
    else {
      // List the whole cart as a single item to account for fees/discounts.
      $data['amount_1'] = uc_currency_format($order->getTotal() - $shipping - $tax, FALSE, FALSE, '.');
      $data['item_name_1'] = $this->t('Order @order_id at @store', ['@order_id' => $order->id(), '@store' => uc_store_name()]);
      $data['on0_1'] = $this->t('Product count');
      $data['os0_1'] = count($order->products);
    }

    $form['#action'] = $configuration['wps_server'];

    foreach ($data as $name => $value) {
      if (!empty($value)) {
        $form[$name] = array('#type' => 'hidden', '#value' => $value);
      }
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit order'),
    );

    $form_state->disableCache();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
