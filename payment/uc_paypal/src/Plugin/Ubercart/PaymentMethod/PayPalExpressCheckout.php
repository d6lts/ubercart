<?php

/**
 * @file
 * Contains \Drupal\uc_paypal\Plugin\Ubercart\PaymentMethod\PayPalExpressCheckout.
 */

namespace Drupal\uc_paypal\Plugin\Ubercart\PaymentMethod;

use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;

/**
 * Defines the PayPal Express Checkout payment method.
 *
 * @UbercartPaymentMethod(
 *   id = "paypal_ec",
 *   name = @Translation("PayPal Express Checkout"),
 *   express = "\Drupal\uc_paypal\Form\EcCartButtonForm"
 * )
 */
class PayPalExpressCheckout extends PayPalPaymentMethodPluginBase {

// *   redirect => "\Drupal\uc_2checkout\Form\TwoCheckoutForm",
//  $module_config = \Drupal::config('uc_2checkout.settings');
//  $title = $module_config->get('method_title');
//  $title .= '<br />' . theme('image', array(
//    'uri' => drupal_get_path('module', 'uc_2checkout') . '/images/2co_logo.jpg',
//    'attributes' => array('class' => array('uc-2checkout-logo')),
//  ));

//  'title' => $title1,
//  'review' => t('PayPal'),
//  'callback' => 'uc_payment_method_paypal_ec',

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'ec_landingpage_style' => 'Billing',
      'ec_rqconfirmed_addr' => FALSE,
      'ec_review_shipping' => TRUE,
      'ec_review_company' => TRUE,
      'ec_review_phone' => TRUE,
      'ec_review_comment' => TRUE,
      'wpp_cc_txn_type' => 'auth_capture',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Generic PayPal settings from base class.
    $form = parent::buildConfigurationForm($form, $form_state);

    // Express Checkout specific settings.
    $form['ec_landingpage_style'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Default PayPal landing page'),
      '#options' => array(
        'Billing' => $this->t('Credit card submission form.'),
        'Login' => $this->t('Account login form.'),
      ),
      '#default_value' => $this->configuration['ec_landingpage_style'],
    );
    $form['ec_rqconfirmed_addr'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Require Express Checkout users to use a PayPal confirmed shipping address.'),
      '#default_value' => $this->configuration['ec_rqconfirmed_addr'],
    );
    $form['ec_review_shipping'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable the shipping select form on the Review payment page.'),
      '#default_value' => $this->configuration['ec_review_shipping'],
    );
    $form['ec_review_company'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable the company name box on the Review payment page.'),
      '#default_value' => $this->configuration['ec_review_company'],
    );
    $form['ec_review_phone'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable the contact phone number box on the Review payment page.'),
      '#default_value' => $this->configuration['ec_review_phone'],
    );
    $form['ec_review_comment'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable the comment text box on the Review payment page.'),
      '#default_value' => $this->configuration['ec_review_comment'],
    );
    $form['wpp_cc_txn_type'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Payment action'),
      '#description' => $this->t('"Complete sale" will authorize and capture the funds at the time the payment is processed.<br>"Authorization" will only reserve funds on the card to be captured later through your PayPal account.'),
      '#options' => array(
        // The keys here are constants defined in uc_credit,
        // but uc_credit is not a dependency.
        'auth_capture' => $this->t('Complete sale'),
        'authorize' => $this->t('Authorization'),
      ),
      '#default_value' => $this->configuration['wpp_cc_txn_type'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['ec_landingpage_style'] = $form_state->getValue('ec_landingpage_style');
    $this->configuration['ec_rqconfirmed_addr'] = $form_state->getValue('ec_rqconfirmed_addr');
    $this->configuration['ec_review_shipping'] = $form_state->getValue('ec_review_shipping');
    $this->configuration['ec_review_company'] = $form_state->getValue('ec_review_company');
    $this->configuration['ec_review_phone'] = $form_state->getValue('ec_review_phone');
    $this->configuration['ec_review_comment'] = $form_state->getValue('ec_review_comment');
    $this->configuration['wpp_cc_txn_type'] = $form_state->getValue('wpp_cc_txn_type');
    parent::submitConfigurationForm($form, $form_state);
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
