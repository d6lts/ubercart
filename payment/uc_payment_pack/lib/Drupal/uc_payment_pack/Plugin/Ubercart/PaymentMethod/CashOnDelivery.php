<?php

/**
 * @file
 * Contains \Drupal\uc_payment_pack\Plugin\Ubercart\PaymentMethod\CashOnDelivery.
 */

namespace Drupal\uc_payment_pack\Plugin\Ubercart\PaymentMethod;

use Drupal\uc_order\UcOrderInterface;
use Drupal\uc_payment\PaymentMethodPluginBase;

/**
 * Defines the cash on delivery payment method.
 *
 * @Plugin(
 *   id = "cod",
 *   name = @Translation("Cash on delivery"),
 *   title = @Translation("Cash on delivery"),
 *   checkout = FALSE,
 *   no_gateway = TRUE,
 *   configurable = TRUE,
 *   weight = 1,
 * )
 */
class CashOnDelivery extends PaymentMethodPluginBase {

  /**
   * {@inheritdoc}
   */
  public function cartDetails(UcOrderInterface $order, array $form, array &$form_state) {
    $cod_config = \Drupal::config('uc_cod.settings');

    $build['policy'] = array(
      '#markup' => '<p>' . $cod_config->get('policy') . '</p>'
    );

    if (($max = $cod_config->get('max_order')) > 0 && is_numeric($max)) {
      $build['eligibility'] = array(
        '#markup' => '<p>' . t('Orders totalling more than !number are <b>not eligible</b> for COD.', array('!number' => uc_currency_format($max))) . '</p>'
      );
    }

    if ($cod_config->get('delivery_date')) {
      $build += $this->deliveryDateForm($order);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function cartProcess(UcOrderInterface $order, array $form, array &$form_state) {
    $cod_config = \Drupal::config('uc_cod.settings');

    if ($cod_config->get('delivery_date')) {
      $order->payment_details = $form_state['values']['panes']['payment']['details'];
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function cartReview(UcOrderInterface $order) {
    $cod_config = \Drupal::config('uc_cod.settings');

    $review = array();

    if ($cod_config->get('delivery_date')) {
      $date = uc_date_format(
        $order->payment_details['delivery_month'],
        $order->payment_details['delivery_day'],
        $order->payment_details['delivery_year']
      );
      $review[] = array('title' => t('Delivery date'), 'data' => $date);
    }

    return $review;
  }

  /**
   * {@inheritdoc}
   */
  public function orderView(UcOrderInterface $order) {
    $cod_config = \Drupal::config('uc_cod.settings');

    $build = array();

    if ($cod_config->get('delivery_date') &&
      isset($order->payment_details['delivery_month']) &&
      isset($order->payment_details['delivery_day']) &&
      isset($order->payment_details['delivery_year'])) {
      $build['#markup'] = t('Desired delivery date:') . '<br />' .
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
  public function orderEditDetails(UcOrderInterface $order) {
    $cod_config = \Drupal::config('uc_cod.settings');

    $build = array();

    if ($cod_config->get('delivery_date')) {
      $build = $this->deliveryDateForm($order);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function orderLoad(UcOrderInterface $order) {
    $result = db_query('SELECT * FROM {uc_payment_cod} WHERE order_id = :id', array(':id' => $order->id()));
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
  public function orderSave(UcOrderInterface $order) {
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
  public function orderSubmit(UcOrderInterface $order) {
    $cod_config = \Drupal::config('uc_cod.settings');
    $max = $cod_config->get('max_order');

    if ($max > 0 && $order->getTotal() > $max) {
      $result[] = array(
        'pass' => FALSE,
        'message' => t('Your final order total exceeds the maximum for COD payment.  Please go back and select a different method of payment.')
      );
      return $result;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function orderDelete(UcOrderInterface $order) {
    db_delete('uc_payment_cod')
      ->condition('order_id', $order->id())
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $cod_config = \Drupal::config('uc_cod.settings');

    $form['uc_cod_policy'] = array(
      '#type' => 'textarea',
      '#title' => t('Policy message'),
      '#default_value' => $cod_config->get('policy'),
      '#description' => t('Help message shown at checkout.'),
    );
    $form['uc_cod_max_order'] = array(
      '#type' => 'textfield',
      '#title' => t('Maximum order total eligible for COD'),
      '#default_value' => $cod_config->get('max_order'),
      '#description' => t('Set to 0 for no maximum order limit.'),
    );
    $form['uc_cod_delivery_date'] = array(
      '#type' => 'checkbox',
      '#title' => t('Let customers enter a desired delivery date.'),
      '#default_value' => $cod_config->get('delivery_date'),
    );
    return $form;
  }

  /**
   * Collect additional information for the "Cash on Delivery" payment method.
   */
  protected function deliveryDateForm($order) {
    $month = !empty($order->payment_details['delivery_month']) ? $order->payment_details['delivery_month'] : format_date(REQUEST_TIME, 'custom', 'n');
    $day   = !empty($order->payment_details['delivery_day'])   ? $order->payment_details['delivery_day']   : format_date(REQUEST_TIME, 'custom', 'j');
    $year  = !empty($order->payment_details['delivery_year'])  ? $order->payment_details['delivery_year']  : format_date(REQUEST_TIME, 'custom', 'Y');

    $form['description'] = array(
      '#markup' => '<div>' . t('Enter a desired delivery date:') . '</div>',
    );
    $form['delivery_month'] = uc_select_month(NULL, $month);
    $form['delivery_day']   = uc_select_day(NULL, $day);
    $form['delivery_year']  = uc_select_year(NULL, $year, format_date(REQUEST_TIME, 'custom', 'Y'), format_date(REQUEST_TIME, 'custom', 'Y') + 1);

    return $form;
  }

}
