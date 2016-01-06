<?php

/**
 * @file
 * Contains \Drupal\uc_paypal\Plugin\Ubercart\PaymentMethod\PayPalWebsitePaymentsStandard.
 */

namespace Drupal\uc_paypal\Plugin\Ubercart\PaymentMethod;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_order\OrderInterface;

/**
 * Defines the PayPal Website Payments Standard payment method.
 *
 * @UbercartPaymentMethod(
 *   id = "paypal_wps",
 *   name = @Translation("PayPal Website Payments Standard"),
 *   redirect = "\Drupal\uc_paypal\Form\WpsForm"
 * )
 */
class PayPalWebsitePaymentsStandard extends PayPalPaymentMethodPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'wps_email' => '',
      'wps_currency' => 'USD',
      'wps_language' => 'US',
      'wps_server' => 'https://www.sandbox.paypal.com/cgi-bin/webscr',
      'wps_payment_action' => 'Sale',
      'wps_cancel_return_url' => 'cart',
      'wps_submit_method' => 'single',
      'wps_no_shipping' => '1',
      'wps_address_override' => TRUE,
      'wps_address_selection' => 'billing',
      'wps_debug_ipn' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['wps_email'] = array(
      '#type' => 'email',
      '#title' => $this->t('PayPal e-mail address'),
      '#description' => $this->t('The e-mail address you use for the PayPal account you want to receive payments.'),
      '#default_value' => $this->configuration['wps_email'],
    );
    $form['wps_currency'] = array(
      '#type' => 'select',
      '#title' => $this->t('Currency code'),
      '#description' => $this->t('Transactions can only be processed in one of the listed currencies.'),
      '#options' => _uc_paypal_currency_array(),
      '#default_value' => $this->configuration['wps_currency'],
    );
    $languages = array('AU', 'DE', 'FR', 'IT', 'GB', 'ES', 'US');
    $form['wps_language'] = array(
      '#type' => 'select',
      '#title' => $this->t('PayPal login page language'),
      '#options' => array_combine($languages, $languages),
      '#default_value' => $this->configuration['wps_language'],
    );
    $form['wps_server'] = array(
      '#type' => 'select',
      '#title' => $this->t('PayPal server'),
      '#description' => $this->t('Sign up for and use a Sandbox account for testing.'),
      '#options' => array(
        'https://www.sandbox.paypal.com/cgi-bin/webscr' => ('Sandbox'),
        'https://www.paypal.com/cgi-bin/webscr' => ('Live'),
      ),
      '#default_value' => $this->configuration['wps_server'],
    );
    $form['wps_payment_action'] = array(
      '#type' => 'select',
      '#title' => $this->t('Payment action'),
      '#description' => $this->t('"Complete sale" will authorize and capture the funds at the time the payment is processed.<br />"Authorization" will only reserve funds on the card to be captured later through your PayPal account.'),
      '#options' => array(
        'Sale' => $this->t('Complete sale'),
        'Authorization' => $this->t('Authorization'),
      ),
      '#default_value' => $this->configuration['wps_payment_action'],
    );
    $form['wps_cancel_return_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Cancel return URL'),
      '#description' => $this->t('Specify the path customers who cancel their PayPal WPS payment will be directed to when they return to your site.'),
      '#default_value' => $this->configuration['wps_cancel_return_url'],
      '#size' => 32,
      '#field_prefix' => Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(),
    );
    $form['wps_submit_method'] = array(
      '#type' => 'radios',
      '#title' => $this->t('PayPal cart submission method'),
      '#options' => array(
        'single' => $this->t('Submit the whole order as a single line item.'),
        'itemized' => $this->t('Submit an itemized order showing each product and description.'),
      ),
      '#default_value' => $this->configuration['wps_submit_method'],
    );
    $form['wps_no_shipping'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Shipping address prompt in PayPal'),
      '#options' => array(
        '1' => $this->t('Do not show shipping address prompt at PayPal.'),
        '0' => $this->t('Prompt customer to include a shipping address.'),
        '2' => $this->t('Require customer to provide a shipping address.'),
      ),
      '#default_value' => $this->configuration['wps_no_shipping'],
    );
    $form['wps_address_override'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Submit address information to PayPal to override PayPal stored addresses.'),
      '#description' => $this->t('Works best with the first option above.'),
      '#default_value' => $this->configuration['wps_address_override'],
    );
    $form['wps_address_selection'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Sent address selection'),
      '#options' => array(
        'billing' => $this->t('Send billing address to PayPal.'),
        'delivery' => $this->t('Send shipping address to PayPal.'),
      ),
      '#default_value' => $this->configuration['wps_address_selection'],
    );
    $form['wps_debug_ipn'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show debug info in the logs for Instant Payment Notifications.'),
      '#default_value' => $this->configuration['wps_debug_ipn'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['wps_email'] = $form_state->getValue('wps_email');
    $this->configuration['wps_currency'] = $form_state->getValue('wps_currency');
    $this->configuration['wps_language'] = $form_state->getValue('wps_language');
    $this->configuration['wps_server'] = $form_state->getValue('wps_server');
    $this->configuration['wps_submit_method'] = $form_state->getValue('wps_submit_method');
    $this->configuration['wps_no_shipping'] = $form_state->getValue('wps_no_shipping');
    $this->configuration['wps_address_override'] = $form_state->getValue('wps_address_override');
    $this->configuration['wps_address_selection'] = $form_state->getValue('wps_address_selection');
    $this->configuration['wps_debug_ipn'] = $form_state->getValue('wps_debug_ipn');
  }

  /**
   * {@inheritdoc}
   */
  public function orderView(OrderInterface $order) {
    $txn_id = db_query("SELECT txn_id FROM {uc_payment_paypal_ipn} WHERE order_id = :id ORDER BY received ASC", [':id' => $order->id()])->fetchField();
    if (empty($txn_id)) {
      $txn_id = $this->t('Unknown');
    }

    $build['#markup'] = $this->t('Transaction ID:<br />@txn_id', ['@txn_id' => $txn_id]);
    return $build;
  }

}
