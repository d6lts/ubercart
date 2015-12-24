<?php

/**
 * @file
 * Contains \Drupal\uc_payment_pack\Plugin\Ubercart\PaymentMethod\CashOnDelivery.
 */

namespace Drupal\uc_payment_pack\Plugin\Ubercart\PaymentMethod;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\PaymentMethodPluginBase;

/**
 * Defines the cash on delivery payment method.
 *
 * @UbercartPaymentMethod(
 *   id = "cod",
 *   name = @Translation("Cash on delivery"),
 * )
 */
class CashOnDelivery extends PaymentMethodPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'policy' => 'Full payment is expected upon delivery or prior to pick-up.',
      'max_order' => 0,
      'delivery_date' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['policy'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Policy message'),
      '#default_value' => $this->configuration['policy'],
      '#description' => $this->t('Help message shown at checkout.'),
    );
    $form['max_order'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Maximum order total eligible for COD'),
      '#default_value' => $this->configuration['max_order'],
      '#description' => $this->t('Set to 0 for no maximum order limit.'),
    );
    $form['delivery_date'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Let customers enter a desired delivery date.'),
      '#default_value' => $this->configuration['delivery_date'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['policy'] = $form_state->getValue('policy');
    $this->configuration['max_order'] = $form_state->getValue('max_order');
    $this->configuration['delivery_date'] = $form_state->getValue('delivery_date');
  }

  /**
   * {@inheritdoc}
   */
  public function cartDetails(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $build['policy'] = array(
      '#markup' => '<p>' . Html::escape($this->configuration['policy']) . '</p>'
    );

    if (($max = $this->configuration['max_order']) > 0 && is_numeric($max)) {
      $build['eligibility'] = array(
        '#markup' => '<p>' . $this->t('Orders totalling more than @amount are <b>not eligible</b> for COD.', ['@amount' => uc_currency_format($max)]) . '</p>'
      );
    }

    if ($this->configuration['delivery_date']) {
      $build += $this->deliveryDateForm($order);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function cartProcess(OrderInterface $order, array $form, FormStateInterface $form_state) {
    if ($this->configuration['delivery_date']) {
      $order->payment_details = $form_state->getValue(['panes', 'payment', 'details']);
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function cartReview(OrderInterface $order) {
    $review = array();

    if ($this->configuration['delivery_date']) {
      $date = uc_date_format(
        $order->payment_details['delivery_month'],
        $order->payment_details['delivery_day'],
        $order->payment_details['delivery_year']
      );
      $review[] = array('title' => $this->t('Delivery date'), 'data' => $date);
    }

    return $review;
  }

  /**
   * {@inheritdoc}
   */
  public function orderView(OrderInterface $order) {
    $build = array();

    if ($this->configuration['delivery_date'] &&
      isset($order->payment_details['delivery_month']) &&
      isset($order->payment_details['delivery_day']) &&
      isset($order->payment_details['delivery_year'])) {
      $build['#markup'] = $this->t('Desired delivery date:') . '<br />' .
        uc_date_format(
          $order->payment_details['delivery_month'],
          $order->payment_details['delivery_day'],
          $order->payment_details['delivery_year']
        );
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function orderEditDetails(OrderInterface $order) {
    $build = array();

    if ($this->configuration['delivery_date']) {
      $build = $this->deliveryDateForm($order);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function orderLoad(OrderInterface $order) {
    $result = db_query('SELECT * FROM {uc_payment_cod} WHERE order_id = :id', [':id' => $order->id()]);
    if ($row = $result->fetchObject()) {
      $order->payment_details = array(
        'delivery_month' => $row->delivery_month,
        'delivery_day'   => $row->delivery_day,
        'delivery_year'  => $row->delivery_year,
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function orderSave(OrderInterface $order) {
    if (isset($order->payment_details['delivery_month']) &&
        isset($order->payment_details['delivery_day']) &&
        isset($order->payment_details['delivery_year'])) {
      db_merge('uc_payment_cod')
        ->key(array('order_id' => $order->id()))
        ->fields(array(
          'delivery_month' => $order->payment_details['delivery_month'],
          'delivery_day'   => $order->payment_details['delivery_day'],
          'delivery_year'  => $order->payment_details['delivery_year'],
        ))
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function orderSubmit(OrderInterface $order) {
    $max = $this->configuration['max_order'];

    if ($max > 0 && $order->getTotal() > $max) {
      $result[] = array(
        'pass' => FALSE,
        'message' => $this->t('Your final order total exceeds the maximum for COD payment.  Please go back and select a different method of payment.')
      );
      return $result;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function orderDelete(OrderInterface $order) {
    db_delete('uc_payment_cod')
      ->condition('order_id', $order->id())
      ->execute();
  }

  /**
   * Collect additional information for the "Cash on Delivery" payment method.
   */
  protected function deliveryDateForm($order) {
    $month = !empty($order->payment_details['delivery_month']) ? $order->payment_details['delivery_month'] : \Drupal::service('date.formatter')->format(REQUEST_TIME, 'custom', 'n');
    $day   = !empty($order->payment_details['delivery_day'])   ? $order->payment_details['delivery_day']   : \Drupal::service('date.formatter')->format(REQUEST_TIME, 'custom', 'j');
    $year  = !empty($order->payment_details['delivery_year'])  ? $order->payment_details['delivery_year']  : \Drupal::service('date.formatter')->format(REQUEST_TIME, 'custom', 'Y');

    $form['description'] = array(
      '#markup' => '<div>' . $this->t('Enter a desired delivery date:') . '</div>',
    );
    $form['delivery_month'] = uc_select_month(NULL, $month);
    $form['delivery_day']   = uc_select_day(NULL, $day);
    $form['delivery_year']  = uc_select_year(NULL, $year, \Drupal::service('date.formatter')->format(REQUEST_TIME, 'custom', 'Y'), \Drupal::service('date.formatter')->format(REQUEST_TIME, 'custom', 'Y') + 1);

    return $form;
  }

}
