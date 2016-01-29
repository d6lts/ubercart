<?php

/**
 * @file
 * Contains \Drupal\uc_2checkout\Plugin\Ubercart\PaymentMethod\TwoCheckout.
 */

namespace Drupal\uc_2checkout\Plugin\Ubercart\PaymentMethod;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\PaymentMethodPluginBase;

/**
 * Defines the 2Checkout payment method.
 *
 * @UbercartPaymentMethod(
 *   id = "2checkout",
 *   name = @Translation("2Checkout"),
 *   redirect = "\Drupal\uc_2checkout\Form\TwoCheckoutForm",
 * )
 */
class TwoCheckout extends PaymentMethodPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getDisplayLabel($label) {
    $build['#attached']['library'][] = 'uc_2checkout/2checkout.styles';
    $build['label'] = array(
      '#plain_text' => $label,
      '#suffix' => '<br />',
    );
    $build['image'] = array(
      '#theme' => 'image',
      '#uri' => drupal_get_path('module', 'uc_2checkout') . '/images/2co_logo.jpg',
      '#alt' => $this->t('2Checkout'),
      '#attributes' => array('class' => array('uc-2checkout-logo')),
    );

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'check' => FALSE,
      'checkout_type' => 'dynamic',
      'currency_code' => '',
      'demo' => TRUE,
      'language' => 'en',
      'method_title' => 'Credit card on a secure server:',
      'notification_url' => '',
      'secret_word' => 'tango',
      'server_url' => 'https://www.2checkout.com/checkout/purchase',
      'sid' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['sid'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Vendor account number'),
      '#description' => $this->t('Your 2Checkout vendor account number.'),
      '#default_value' => $this->configuration['sid'],
      '#size' => 16,
    );
    $form['secret_word'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Secret word for order verification'),
      '#description' => $this->t('The secret word entered in your 2Checkout account Look and Feel settings.'),
      '#default_value' => $this->configuration['secret_word'],
      '#size' => 16,
    );
    $form['demo'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable demo mode, allowing you to process fake orders for testing purposes.'),
      '#default_value' => $this->configuration['demo'],
    );
    $form['language'] = array(
      '#type' => 'select',
      '#title' => $this->t('Language preference'),
      '#description' => $this->t('Adjust language on 2Checkout pages.'),
      '#options' => array(
        'en' => $this->t('English'),
        'sp' => $this->t('Spanish'),
      ),
      '#default_value' => $this->configuration['language'],
    );
    $form['currency_code'] = array(
      '#type' => 'select',
      '#title' => $this->t('Currency for the sale'),
      '#options' => array(
        '' => $this->t('Auto detected by 2Checkout'),
        'USD', 'EUR', 'ARS', 'AUD', 'BRL', 'GBP', 'CAD', 'DKK', 'HKD', 'INR',
        'ILS', 'JPY', 'LTL', 'MYR', 'MXN', 'NZD', 'NOK', 'PHP', 'RON', 'RUB',
        'SGD', 'ZAR', 'SEK', 'CHF', 'TRY', 'AED',
      ),
      '#default_value' => $this->configuration['currency_code'],
    );
    $form['check'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Allow customers to choose to pay by credit card or online check.'),
      '#default_value' => $this->configuration['check'],
    );
    $form['method_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Payment method title'),
      '#default_value' => $this->configuration['method_title'],
    );
    $form['checkout_type'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Checkout type'),
      '#options' => array(
        'dynamic' => $this->t('Dynamic checkout (user is redirected to 2CO)'),
        'direct' => $this->t('Direct checkout (payment page opens in iframe popup)'),
      ),
      '#default_value' => $this->configuration['checkout_type'],
    );
    $form['notification_url'] = array(
      '#type' => 'url',
      '#title' => $this->t('Instant notification settings URL'),
      '#description' => $this->t('Pass this URL to the <a href=":help_url">instant notification settings</a> parameter in your 2Checkout account. This way, any refunds or failed fraud reviews will automatically cancel the Ubercart order.', [':help_url' => Url::fromUri('https://www.2checkout.com/static/va/documentation/INS/index.html')->toString()]),
      '#default_value' => Url::fromRoute('uc_2checkout.notification', [], ['absolute' => TRUE])->toString(),
      '#disabled' => TRUE,
    );
    $form['server_url'] = array(
      '#type' => 'url',
      '#title' => $this->t('2Checkout server URL'),
      '#description' => $this->t('URL used to POST payments to the 2Checkout server.'),
      '#default_value' => Url::fromUri($this->configuration['server_url'])->toString(),
      '#disabled' => TRUE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['check'] = $form_state->getValue('check');
    $this->configuration['checkout_type'] = $form_state->getValue('checkout_type');
    $this->configuration['currency_code'] = $form_state->getValue('currency_code');
    $this->configuration['demo'] = $form_state->getValue('demo');
    $this->configuration['language'] = $form_state->getValue('language');
    $this->configuration['notification_url'] = $form_state->getValue('notification_url');
    $this->configuration['method_title'] = $form_state->getValue('method_title');
    $this->configuration['secret_word'] = $form_state->getValue('secret_word');
    $this->configuration['server_url'] = $form_state->getValue('server_url');
    $this->configuration['sid'] = $form_state->getValue('sid');
  }

  /**
   * {@inheritdoc}
   */
  public function cartDetails(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $build = array();
    $session = \Drupal::service('session');
    if ($this->configuration['check']) {
      $build['pay_method'] = array(
        '#type' => 'select',
        '#title' => $this->t('Select your payment type:'),
        '#default_value' => $session->get('pay_method') == 'CK' ? 'CK' : 'CC',
        '#options' => array(
          'CC' => $this->t('Credit card'),
          'CK' => $this->t('Online check'),
        ),
      );
      $session->remove('pay_method');
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function cartProcess(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $session = \Drupal::service('session');
    if (NULL != $form_state->getValue(['panes', 'payment', 'details', 'pay_method'])) {
      $session->set('pay_method', $form_state->getValue(['panes', 'payment', 'details', 'pay_method']));
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function cartReview(OrderInterface $order) {
    $review = array();

    if ($this->configuration['check']) {
      $review[] = array('title' => $this->t('Credit card/eCheck'), 'data' => NULL);
    }
    else {
      $review[] = array('title' => $this->t('Credit card'), 'data' => NULL);
    }

    return $review;
  }

}
