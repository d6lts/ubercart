<?php

/**
 * @file
 * Contains \Drupal\uc_payment_pack\Plugin\Ubercart\PaymentMethod\Check.
 */

namespace Drupal\uc_payment_pack\Plugin\Ubercart\PaymentMethod;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\PaymentMethodPluginBase;
use Drupal\uc_store\Address;

/**
 * Defines the check payment method.
 *
 * @PaymentMethod(
 *   id = "check",
 *   name = @Translation("Check", context = "cheque"),
 *   title = @Translation("Check or money order"),
 *   checkout = TRUE,
 *   no_gateway = TRUE,
 *   configurable = TRUE,
 *   weight = 1,
 * )
 */
class Check extends PaymentMethodPluginBase {

  /**
   * {@inheritdoc}
   */
  public function cartDetails(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $check_config = \Drupal::config('uc_payment_pack.check.settings');

    $build['instructions'] = array(
      '#markup' => t('Checks should be made out to:')
    );

    if (!$check_config->get('mailing_street1')) {
      $build['address'] = array(
        '#markup' => uc_store_address(),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      );
    }
    else {
      $address = new Address();
      $address->first_name = $check_config->get('mailing_name');
      $address->company = $check_config->get('mailing_company');
      $address->street1 = $check_config->get('mailing_street1');
      $address->street1 = $check_config->get('mailing_street2');
      $address->city = $check_config->get('mailing_city');
      $address->zone = $check_config->get('mailing_zone');
      $address->postal_code = $check_config->get('mailing_postal_code');
      $address->country = $check_config->get('mailing_country');
      $build['address'] = array(
        '#markup' => (string) $address,
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      );
    }

    $build['policy'] = array(
      '#markup' => '<p>' . $check_config->get('policy') . '</p>'
    );

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function cartReview(OrderInterface $order) {
    $check_config = \Drupal::config('uc_payment_pack.check.settings');

    if (!$check_config->get('mailing_street1')) {
      $review[] = array(
        'title' => t('Mail to'),
        'data' => uc_store_address(),
      );
    }
    else {
      $address = new Address();
      $address->first_name = $check_config->get('mailing_name');
      $address->company = $check_config->get('mailing_company');
      $address->street1 = $check_config->get('mailing_street1');
      $address->street1 = $check_config->get('mailing_street2');
      $address->city = $check_config->get('mailing_city');
      $address->zone = $check_config->get('mailing_zone');
      $address->postal_code = $check_config->get('mailing_postal_code');
      $address->country = $check_config->get('mailing_country');

      $review[] = array(
        'title' => t('Mail to'),
        'data' => (string) $address,
      );
    }

    return $review;
  }

  /**
   * {@inheritdoc}
   */
  public function orderView(OrderInterface $order) {
    $build = array('#suffix' => '<br />');

    $result = db_query('SELECT clear_date FROM {uc_payment_check} WHERE order_id = :id ', [':id' => $order->id()]);
    if ($clear_date = $result->fetchField()) {
      $build['#markup'] = t('Clear Date:') . ' ' . \Drupal::service('date.formatter')->format($clear_date, 'uc_store');
    }
    else {
      $build['#markup'] = \Drupal::l(t('Receive Check'), new Url('uc_payment_pack.check.receive', ['uc_order' => $order->id()]));
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function customerView(OrderInterface $order) {
    $build = array();

    $result = db_query('SELECT clear_date FROM {uc_payment_check} WHERE order_id = :id ', [':id' => $order->id()]);
    if ($clear_date = $result->fetchField()) {
      $build['#markup'] = t('Check received') . '<br />' .
        t('Expected clear date:') . '<br />' . \Drupal::service('date.formatter')->format($clear_date, 'uc_store');
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm() {
    return \Drupal\uc_payment_pack\Form\CheckSettingsForm::create(\Drupal::getContainer());
  }

}
