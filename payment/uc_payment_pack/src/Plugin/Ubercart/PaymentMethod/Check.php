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

/**
 * Defines the check payment method.
 *
 * @Plugin(
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
      $build['address'] = array(
        '#markup' => uc_address_format(
          $check_config->get('mailing_name'),
          NULL,
          $check_config->get('mailing_company'),
          $check_config->get('mailing_street1'),
          $check_config->get('mailing_street2'),
          $check_config->get('mailing_city'),
          $check_config->get('mailing_zone'),
          $check_config->get('mailing_postal_code'),
          $check_config->get('mailing_country')
        ),
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
      $review[] = array(
        'title' => t('Mail to'),
        'data' => uc_address_format(
          $check_config->get('mailing_name'),
          NULL,
          $check_config->get('mailing_company'),
          $check_config->get('mailing_street1'),
          $check_config->get('mailing_street2'),
          $check_config->get('mailing_city'),
          $check_config->get('mailing_zone'),
          $check_config->get('mailing_postal_code'),
          $check_config->get('mailing_country')
        )
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
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $check_config = \Drupal::config('uc_payment_pack.check.settings');

    $form['check_address_info'] = array(
      '#markup' => '<div>' . t('Set the mailing address to display to customers who choose this payment method during checkout.') . '</div>',
    );
    $form['uc_check_mailing_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Contact'),
      '#description' => t('Direct checks to a person or department.'),
      '#default_value' => $check_config->get('mailing_name'),
    );
    $form['uc_check_address'] = array(
      '#type' => 'uc_address',
      '#default_value' => array(
        'uc_check_mailing_company' => $check_config->get('mailing_company'),
        'uc_check_mailing_street1' => $check_config->get('mailing_street1'),
        'uc_check_mailing_street2' => $check_config->get('mailing_street2'),
        'uc_check_mailing_city' => $check_config->get('mailing_city'),
        'uc_check_mailing_zone' => $check_config->get('mailing_zone'),
        'uc_check_mailing_country' => $form_state->hasValue('uc_check_mailing_country') ? $form_state->getValue('uc_check_mailing_country') : $check_config->get('uc_check_mailing_country'),
        'uc_check_mailing_postal_code' => $check_config->get('mailing_postal_code'),
      ),
      '#required' => FALSE,
      '#key_prefix' => 'uc_check_mailing',
    );
    $form['uc_check_policy'] = array(
      '#type' => 'textarea',
      '#title' => t('Check payment policy', [], ['context' => 'cheque']),
      '#description' => t('Instructions for customers on the checkout page.'),
      '#default_value' => $check_config->get('policy'),
      '#rows' => 3,
    );
    return $form;
  }

}
