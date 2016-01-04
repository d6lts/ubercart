<?php

/**
 * @file
 * Contains \Drupal\uc_2checkout\Form\TwoCheckoutForm.
 */

namespace Drupal\uc_2checkout\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_order\Entity\Order;
use Drupal\uc_order\OrderInterface;

/**
 * Form to build the submission to 2Checkout.com.
 */
class TwoCheckoutForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_2checkout_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $order = NULL) {
    $module_config = $this->config('uc_2checkout.settings');
    $country = \Drupal::service('country_manager')->getCountry($order->getAddress('billing')->country);

    $data = array(
      'sid' => $module_config->get('sid'),
      'mode' => '2CO',
      'card_holder_name' => Unicode::substr($order->getAddress('billing')->first_name . ' ' . $order->getAddress('billing')->last_name, 0, 128),
      'street_address' => Unicode::substr($order->getAddress('billing')->street1, 0, 64),
      'street_address2' => Unicode::substr($order->getAddress('billing')->street2, 0, 64),
      'city' => Unicode::substr($order->getAddress('billing')->city, 0, 64),
      'state' => $order->getAddress('billing')->zone,
      'zip' => Unicode::substr($order->getAddress('billing')->postal_code, 0, 16),
      'country' => $country ? $country->getAlpha3() : 'USA',
      'email' => Unicode::substr($order->getEmail(), 0, 64),
      'phone' => Unicode::substr($order->getAddress('billing')->phone, 0, 16),
      'purchase_step' => 'payment-method',

      'demo' => $module_config->get('demo') ? 'Y' : 'N',
      'lang' => $module_config->get('language'),
      'merchant_order_id' => $order->id(),
      'pay_method' => 'CC',
      'x_receipt_link_url' => Url::fromRoute('uc_2checkout.complete', ['cart_id' => \Drupal::service('uc_cart.manager')->get()->getId()], ['absolute' => TRUE])->toString(),

      'total' => uc_currency_format($order->getTotal(), FALSE, FALSE, '.'),
      'cart_order_id' => $order->id(),
    );

    if ($currency_code = $module_config->get('currency_code')) {
      $data['currency_code'] = $currency_code;
    }

    $i = 0;
    $order->products = \Drupal::entityTypeManager()->getStorage('uc_order_product')->loadByProperties(['order_id' => $order->id()]);
    foreach ($order->products as $product) {
      $i++;
      $data['li_' . $i . '_type'] = 'product';
      $data['li_' . $i . '_name'] = $product->title->value; // @todo: HTML escape and limit to 128 chars
      $data['li_' . $i . '_quantity'] = $product->qty->value;
      $data['li_' . $i . '_product_id'] = $product->model->value;
      $data['li_' . $i . '_price'] = uc_currency_format($product->price->value, FALSE, FALSE, '.');
    }

    if ('direct' == $module_config->get('checkout_type')) {
      $form['#attached']['library'][] = 'uc_2checkout/2checkout.direct';
    }

    $form['#action'] = $module_config->get('server_url');

    foreach ($data as $name => $value) {
      $form[$name] = array('#type' => 'hidden', '#value' => $value);
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit order'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }

}
