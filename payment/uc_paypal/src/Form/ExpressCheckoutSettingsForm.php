<?php

/**
 * @file
 * Contains \Drupal\uc_paypal\src\Form\ExpressCheckoutSettingsForm.
 */

namespace Drupal\uc_paypal\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Configure PayPal Express Checkout settings for this site.
 */
class ExpressCheckoutSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_paypal_express_checkout_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $paypal_config = $this->config('uc_paypal.settings');

    $form['wps_email'] = array(
      '#type' => 'email',
      '#title' => $this->t('PayPal e-mail address'),
      '#description' => $this->t('The e-mail address you use for the PayPal account you want to receive payments.'),
      '#default_value' => $paypal_config->get('wps_email'),
    );
    $form['wpp_currency'] = array(
      '#type' => 'select',
      '#title' => $this->t('Currency code'),
      '#description' => $this->t('Transactions can only be processed in one of the listed currencies.'),
      '#options' => _uc_paypal_currency_array(),
      '#default_value' => $paypal_config->get('wpp_currency'),
    );
    $form['wpp_server'] = array(
      '#type' => 'select',
      '#title' => $this->t('API server'),
      '#description' => $this->t('Sign up for and use a Sandbox account for testing.'),
      '#options' => array(
        'https://api-3t.sandbox.paypal.com/nvp' => $this->t('Sandbox'),
        'https://api-3t.paypal.com/nvp' => $this->t('Live'),
      ),
      '#default_value' => $paypal_config->get('wpp_server'),
    );
    $form['api'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('API credentials'),
      '#description' => $this->t('@link for information on obtaining credentials.  You need to acquire an API Signature.  If you have already requested API credentials, you can review your settings under the API Access section of your PayPal profile.', ['@link' => Link::fromTextAndUrl($this->t('Click here'), Url::fromUri('https://www.paypal.com/IntegrationCenter/ic_certificate.html'))->toString()]),
    );
    $form['api']['api_username'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('API username'),
      '#default_value' => $paypal_config->get('api_username'),
    );
    $form['api']['api_password'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('API password'),
      '#default_value' => $paypal_config->get('api_password'),
    );
    $form['api']['api_signature'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Signature'),
      '#default_value' => $paypal_config->get('api_signature'),
    );
    $form['ec']['ec_landingpage_style'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Default PayPal landing page'),
      '#options' => array(
        'Billing' => $this->t('Credit card submission form.'),
        'Login' => $this->t('Account login form.'),
      ),
      '#default_value' => $paypal_config->get('ec_landingpage_style'),
    );
    $form['ec']['ec_rqconfirmed_addr'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Require Express Checkout users to use a PayPal confirmed shipping address.'),
      '#default_value' => $paypal_config->get('ec_rqconfirmed_addr'),
    );
    $form['ec']['ec_review_shipping'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable the shipping select form on the Review payment page.'),
      '#default_value' => $paypal_config->get('ec_review_shipping'),
    );
    $form['ec']['ec_review_company'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable the company name box on the Review payment page.'),
      '#default_value' => $paypal_config->get('ec_review_company'),
    );
    $form['ec']['ec_review_phone'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable the contact phone number box on the Review payment page.'),
      '#default_value' => $paypal_config->get('ec_review_phone'),
    );
    $form['ec']['ec_review_comment'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable the comment text box on the Review payment page.'),
      '#default_value' => $paypal_config->get('ec_review_comment'),
    );
    $form['ec']['wpp_cc_txn_type'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Payment action'),
      '#description' => $this->t('"Complete sale" will authorize and capture the funds at the time the payment is processed.<br>"Authorization" will only reserve funds on the card to be captured later through your PayPal account.'),
      '#options' => array(
        // The keys here are constants defined in uc_credit,
        // but uc_credit is not a dependency.
        'auth_capture' => $this->t('Complete sale'),
        'authorize' => $this->t('Authorization'),
      ),
      '#default_value' => $paypal_config->get('wpp_cc_txn_type'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $paypal_config = $this->configFactory()->getEditable('uc_paypal.settings');
    $paypal_config
      ->set('wps_email', $form_state->getValue('wps_email'))
      ->set('wpp_currency', $form_state->getValue('wpp_currency'))
      ->set('wpp_server', $form_state->getValue('wpp_server'))
      ->set('api_username', $form_state->getValue('api_username'))
      ->set('api_password', $form_state->getValue('api_password'))
      ->set('api_signature', $form_state->getValue('api_signature'))
      ->set('ec_landingpage_style', $form_state->getValue('ec_landingpage_style'))
      ->set('ec_rqconfirmed_addr', $form_state->getValue('ec_rqconfirmed_addr'))
      ->set('ec_review_shipping', $form_state->getValue('ec_review_shipping'))
      ->set('ec_review_company', $form_state->getValue('ec_review_company'))
      ->set('ec_review_phone', $form_state->getValue('ec_review_phone'))
      ->set('ec_review_comment', $form_state->getValue('ec_review_comment'))
      ->set('wpp_cc_txn_type', $form_state->getValue('wpp_cc_txn_type'))
      ->save();
  }

}
