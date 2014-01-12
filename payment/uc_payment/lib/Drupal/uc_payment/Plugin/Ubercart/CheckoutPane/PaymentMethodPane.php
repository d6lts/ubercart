<?php

/**
 * @file
 * Contains \Drupal\uc_payment\Plugin\Ubercart\CheckoutPane\PaymentMethodPane.
 */

namespace Drupal\uc_payment\Plugin\Ubercart\CheckoutPane;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\uc_cart\CheckoutPanePluginBase;
use Drupal\uc_order\UcOrderInterface;
use Drupal\uc_payment\Plugin\PaymentMethodManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Allows the user to select a payment method and preview the line items.
 *
 * @Plugin(
 *   id = "payment",
 *   title = @Translation("Payment method"),
 *   description = @Translation("Select a payment method from the enabled payment modules."),
 *   weight = 6
 * )
 */
class PaymentMethodPane extends CheckoutPanePluginBase implements ContainerFactoryPluginInterface {

  /**
   * The payment method manager.
   *
   * @var \Drupal\uc_payment\Plugin\PaymentMethodManager
   */
  protected $paymentMethodManager;

  /**
   * Constructs a PaymentMethodPane object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\uc_payment\Plugin\PaymentMethodManager $payment_method_manager
   *   The payment method manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, PaymentMethodManager $payment_method_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->paymentMethodManager = $payment_method_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('plugin.manager.uc_payment.method')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function view(UcOrderInterface $order, array $form, array &$form_state) {
    $contents['#attached']['css'][] = drupal_get_path('module', 'uc_payment') . '/css/uc_payment.css';

    if (variable_get('uc_payment_show_order_total_preview', TRUE)) {
      $contents['line_items'] = array(
        '#theme' => 'uc_payment_totals',
        '#order' => $order,
        '#prefix' => '<div id="line-items-div">',
        '#suffix' => '</div>',
        '#weight' => -20,
      );
    }

    // Ensure that the form builder uses #default_value to determine which
    // button should be selected after an ajax submission. This is
    // necessary because the previously selected value may have become
    // unavailable, which would result in an invalid selection.
    unset($form_state['input']['panes']['payment']['payment_method']);

    $options = array();
    foreach (uc_payment_method_list() as $id => $method) {
      // $set = rules_config_load('uc_payment_method_' . $method['id']);
      // if ($set && !$set->execute($order)) {
      //   continue;
      // }

      if ($method['checkout'] && !isset($method['express'])) {
        $options[$id] = $method['title'];
      }
    }

    \Drupal::moduleHandler()->alter('uc_payment_method_checkout', $options, $order);

    if (!$options) {
      $contents['#description'] = t('Checkout cannot be completed without any payment methods enabled. Please contact an administrator to resolve the issue.');
      $options[''] = t('No payment methods available');
    }
    elseif (count($options) > 1) {
      $contents['#description'] = t('Select a payment method from the following options.');
    }

    if (!$order->getPaymentMethodId() || !isset($options[$order->getPaymentMethodId()])) {
      $order->setPaymentMethodId(key($options));
    }

    $contents['payment_method'] = array(
      '#type' => 'radios',
      '#title' => t('Payment method'),
      '#title_display' => 'invisible',
      '#options' => $options,
      '#default_value' => $order->getPaymentMethodId(),
      '#disabled' => count($options) == 1,
      '#required' => TRUE,
      '#ajax' => array(
        'callback' => array($this, 'ajaxRender'),
        'wrapper' => 'payment-details',
        'progress' => array(
          'type' => 'throbber',
        ),
      ),
    );

    $contents['details'] = array(
      '#prefix' => '<div id="payment-details" class="clearfix payment-details-' . $order->getPaymentMethodId() . '">',
      '#markup' => t('Continue with checkout to complete payment.'),
      '#suffix' => '</div>',
    );

    try {
      $details = $this->paymentMethodManager->createFromOrder($order)->cartDetails($order, $form, $form_state);
      if ($details) {
        unset($contents['details']['#markup']);
        $contents['details'] += $details;
      }
    }
    catch (PluginException $e) {
    }

    return $contents;
  }

  /**
   * {@inheritdoc}
   */
  public function process(UcOrderInterface $order, array $form, array &$form_state) {
    if (empty($form_state['values']['panes']['payment']['payment_method'])) {
      form_set_error('panes][payment][payment_method', $form_state, t('You cannot check out without selecting a payment method.'));
      return FALSE;
    }
    $order->setPaymentMethodId($form_state['values']['panes']['payment']['payment_method']);
    $result = $this->paymentMethodManager->createFromOrder($order)->cartProcess($order, $form, $form_state);
    return $result !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function review(UcOrderInterface $order) {
    $line_items = uc_order_load_line_items_display($order);
    foreach ($line_items as $line_item) {
      $review[] = array('title' => $line_item['title'], 'data' => theme('uc_price', array('price' => $line_item['amount'])));
    }
    $method = $this->paymentMethodManager->createFromOrder($order);
    $review[] = array('border' => 'top', 'title' => t('Paying by'), 'data' => $method->cartReviewTitle());
    $result = $method->cartReview($order);
    if (is_array($result)) {
      $review = array_merge($review, $result);
    }
    return $review;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    $form['uc_payment_show_order_total_preview'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show the order total preview on the payment pane.'),
      '#default_value' => variable_get('uc_payment_show_order_total_preview', TRUE),
    );
    return $form;
  }

  /**
   * Ajax callback to re-render the payment method pane.
   */
  function ajaxRender($form, &$form_state) {
    return $form['panes']['payment']['details'];
  }

}
