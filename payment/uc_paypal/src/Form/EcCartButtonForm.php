<?php

/**
 * @file
 * Contains \Drupal\uc_paypal\src\Form\EcCartButtonForm.
 */

namespace Drupal\uc_paypal\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\Entity\Order;

/**
 * Returns the form for Express Checkout Shortcut Flow.
 */
class EcCartButtonForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_paypal_ec_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
     $form['uc_paypal'] = array(
       '#type' => 'image_button',
       '#button_type' => 'checkout',
       '#src' => 'https://www.paypal.com/en_US/i/btn/btn_xpressCheckoutsm.gif',
       '#title' => $this->t('Checkout with PayPal.'),
       '#submit' => array('uc_cart_view_form_submit', 'uc_paypal_ec_form_submit'),
       '#value' => 'PayPal Express Checkout',
     );

     return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $items = \Drupal::service('uc_cart.manager')->get()->getContents();
    $paypal_config = $this->config('uc_paypal.settings');

    if (empty($items)) {
      drupal_set_message($this->t('You do not have any items in your shopping cart.'));
      return;
    }

    list($desc, $subtotal) = _uc_paypal_product_details($items);

    $order = Order::create(['uid' => $this->currentUser()->id()]);
    $order->save();

    $nvp_request = array(
      'METHOD' => 'SetExpressCheckout',
      'RETURNURL' => Url::fromRoute('uc_paypal.ec_review', [], ['absolute' => TRUE])->toString(),
      'CANCELURL' => Url::fromRoute('uc_cart.cart', [], ['absolute' => TRUE])->toString(),
      'AMT' => uc_currency_format($subtotal, FALSE, FALSE, '.'),
      'CURRENCYCODE' => $order->getCurrency(),
      'PAYMENTACTION' => $paypal_config->get('wpp_cc_txn_type') == 'authorize' ? 'Authorization' : 'Sale',
      'DESC' => substr($desc, 0, 127),
      'INVNUM' => $order->id() . '-' . REQUEST_TIME,
      'REQCONFIRMSHIPPING' => $paypal_config->get('ec_rqconfirmed_addr'),
      'BUTTONSOURCE' => 'Ubercart_ShoppingCart_EC_US',
      'NOTIFYURL' => Url::fromRoute('uc_paypal.ipn', [], ['absolute' => TRUE])->toString(),
      'LANDINGPAGE' => $paypal_config->get('ec_landingpage_style'),
    );

    $order->products = $items;
    $order->save();

    $nvp_response = uc_paypal_api_request($nvp_request, $paypal_config->get('wpp_server'));

    if ($nvp_response['ACK'] != 'Success') {
      drupal_set_message($this->t('PayPal reported an error: @code: @message', ['@code' => $nvp_response['L_ERRORCODE0'], '@message' => $nvp_response['L_LONGMESSAGE0']]), 'error');
      return;
    }

    $session = \Drupal::service('session');
    $session->set('cart_order', $order->id());
    $session->set('TOKEN', $nvp_response['TOKEN']);

    $sandbox = '';
    if (strpos($paypal_config->get('wpp_server'), 'sandbox') > 0) {
      $sandbox = 'sandbox.';
    }

    header('Location: https://www.' . $sandbox . 'paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=' . $session->get('TOKEN'));
    exit();
  }

}
