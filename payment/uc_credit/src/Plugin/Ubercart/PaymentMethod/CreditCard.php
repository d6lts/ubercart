<?php

/**
 * @file
 * Contains \Drupal\uc_credit\Plugin\Ubercart\PaymentMethod\CreditCard.
 */

namespace Drupal\uc_credit\Plugin\Ubercart\PaymentMethod;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\PaymentMethodPluginBase;
use Drupal\uc_store\Encryption;

/**
 * Defines the check payment method.
 *
 * @PaymentMethod(
 *   id = "credit",
 *   name = @Translation("Credit card"),
 *   title = @Translation("Credit card"),
 *   checkout = TRUE,
 *   no_gateway = FALSE,
 *   configurable = TRUE,
 *   weight = 2,
 * )
 */
class CreditCard extends PaymentMethodPluginBase {

  /**
   * {@inheritdoc}
   */
  public function cartDetails(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $details = uc_payment_method_credit_form(array(), $form_state, $order);
    return $details;
  }

  /**
   * {@inheritdoc}
   */
  public function cartReview(OrderInterface $order) {
    $credit_config = \Drupal::config('uc_credit.settings');
    if ($credit_config->get('uc_credit_type_enabled')) {
      $review[] = array('title' => t('Card type'), 'data' => SafeMarkup::checkPlain($order->payment_details['cc_type']));
    }
    if ($credit_config->get('uc_credit_owner_enabled')) {
      $review[] = array('title' => t('Card owner'), 'data' => SafeMarkup::checkPlain($order->payment_details['cc_owner']));
    }
    $review[] = array('title' => t('Card number'), 'data' => uc_credit_display_number($order->payment_details['cc_number']));
    if ($credit_config->get('uc_credit_start_enabled')) {
      $start = $order->payment_details['cc_start_month'] . '/' . $order->payment_details['cc_start_year'];
      $review[] = array('title' => t('Start date'), 'data' => strlen($start) > 1 ? $start : '');
    }
    $review[] = array('title' => t('Expiration'), 'data' => $order->payment_details['cc_exp_month'] . '/' . $order->payment_details['cc_exp_year']);
    if ($credit_config->get('uc_credit_issue_enabled')) {
      $review[] = array('title' => t('Issue number'), 'data' => $order->payment_details['cc_issue']);
    }
    if ($credit_config->get('uc_credit_bank_enabled')) {
      $review[] = array('title' => t('Issuing bank'), 'data' => SafeMarkup::checkPlain($order->payment_details['cc_bank']));
    }
    return $review;
  }

  /**
   * {@inheritdoc}
   */
  public function orderView(OrderInterface $order) {
      $build = array();

      // Add the hidden span for the CC details if possible.
      $account = \Drupal::currentUser();
      if ($account->hasPermission('view cc details')) {
        $rows = array();

        if (!empty($order->payment_details['cc_type'])) {
          $rows[] = t('Card type') . ': ' . SafeMarkup::checkPlain($order->payment_details['cc_type']);
        }

        if (!empty($order->payment_details['cc_owner'])) {
          $rows[] = t('Card owner') . ': ' . SafeMarkup::checkPlain($order->payment_details['cc_owner']);
        }

        if (!empty($order->payment_details['cc_number'])) {
          $rows[] = t('Card number') . ': ' . uc_credit_display_number($order->payment_details['cc_number']);
        }

        if (!empty($order->payment_details['cc_start_month']) && !empty($order->payment_details['cc_start_year'])) {
          $rows[] = t('Start date') . ': ' . $order->payment_details['cc_start_month'] . '/' . $order->payment_details['cc_start_year'];
        }

        if (!empty($order->payment_details['cc_exp_month']) && !empty($order->payment_details['cc_exp_year'])) {
          $rows[] = t('Expiration') . ': ' . $order->payment_details['cc_exp_month'] . '/' . $order->payment_details['cc_exp_year'];
        }

        if (!empty($order->payment_details['cc_issue'])) {
          $rows[] = t('Issue number') . ': ' . SafeMarkup::checkPlain($order->payment_details['cc_issue']);
        }

        if (!empty($order->payment_details['cc_bank'])) {
          $rows[] = t('Issuing bank') . ': ' . SafeMarkup::checkPlain($order->payment_details['cc_bank']);
        }

        $build['cc_info'] = array(
          '#prefix' => '<a href="#" onclick="jQuery(this).hide().next().show();">' . t('Show card details') . '</a><div style="display: none;">',
          '#markup' => implode('<br />', $rows),
          '#suffix' => '</div>',
        );

        // Add the form to process the card if applicable.
        if ($account->hasPermission('process credit cards')) {
          $build['terminal'] = \Drupal::formBuilder()->getForm('uc_credit_order_view_form', $order->id());
        }
      }

      return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function customerView(OrderInterface $order) {
      $build = array();

      if (!empty($order->payment_details['cc_number'])) {
        $build['#markup'] = t('Card number') . ':<br />' . uc_credit_display_number($order->payment_details['cc_number']);
      }

      return $build;

  }

  /**
   * {@inheritdoc}
   */
  public function orderEditDetails(OrderInterface $order) {
    return t('Use the terminal available through the<br />%button button on the View tab to<br />process credit card payments.', array('%button' => t('Process card')));
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm() {
    return \Drupal\uc_credit\Form\CreditSettingsForm::create(\Drupal::getContainer());
  }

  /**
   * {@inheritdoc}
   */
  public function cartProcess(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $credit_config = \Drupal::config('uc_credit.settings');
    if (!$form_state->hasValue(['panes', 'payment', 'details', 'cc_number'])) {
      return;
    }
    // Fetch the CC details from the $_POST directly.
    $cc_data = $form_state->getValue(['panes', 'payment', 'details']);

    $cc_data['cc_number'] = str_replace(' ', '', $cc_data['cc_number']);

    array_walk($cc_data, '\Drupal\Component\Utility\SafeMarkup::checkPlain');

    // Recover cached CC data in
    // $form_state->getValue(['panes', 'payment', 'details']) if it exists.
    if ($form_state->hasValue(['panes', 'payment', 'details', 'payment_details_data'])) {
      $cache = uc_credit_cache('save', $form_state->getValue(['panes', 'payment', 'details', 'payment_details_data']));
    }

    // Account for partial CC numbers when masked by the system.
    if (substr($cc_data['cc_number'], 0, strlen(t('(Last4)'))) == t('(Last4)')) {
      // Recover the number from the encrypted data in the form if truncated.
      if (isset($cache['cc_number'])) {
        $cc_data['cc_number'] = $cache['cc_number'];
      }
      else {
        $cc_data['cc_number'] = '';
      }
    }

    // Account for masked CVV numbers.
    if (!empty($cc_data['cc_cvv']) && $cc_data['cc_cvv'] == str_repeat('-', strlen($cc_data['cc_cvv']))) {
      // Recover the number from the encrypted data in $_POST if truncated.
      if (isset($cache['cc_cvv'])) {
        $cc_data['cc_cvv'] = $cache['cc_cvv'];
      }
      else {
        $cc_data['cc_cvv'] = '';
      }
    }

    // Go ahead and put the CC data in the payment details array.
    $order->payment_details = $cc_data;

    // Default our value for validation.
    $return = TRUE;

    // Make sure an owner value was entered.
    if ($credit_config->get('uc_credit_owner_enabled') && empty($cc_data['cc_owner'])) {
      $form_state->setErrorByName('panes][payment][details][cc_owner', t('Enter the owner name as it appears on the card.'));
      $return = FALSE;
    }

    // Validate the CC number if that's turned on/check for non-digits.
    if (($credit_config->get('uc_credit_validate_numbers') && !_uc_credit_valid_card_number($cc_data['cc_number']))
      || !ctype_digit($cc_data['cc_number'])) {
      $form_state->setErrorByName('panes][payment][details][cc_number', t('You have entered an invalid credit card number.'));
      $return = FALSE;
    }

    // Validate the start date (if entered).
    if ($credit_config->get('uc_credit_start_enabled') && !_uc_credit_valid_card_start($cc_data['cc_start_month'], $cc_data['cc_start_year'])) {
      $form_state->setErrorByName('panes][payment][details][cc_start_month', t('The start date you entered is invalid.'));
      $form_state->setErrorByName('panes][payment][details][cc_start_year');
      $return = FALSE;
    }

    // Validate the card expiration date.
    if (!_uc_credit_valid_card_expiration($cc_data['cc_exp_month'], $cc_data['cc_exp_year'])) {
      $form_state->setErrorByName('panes][payment][details][cc_exp_month', t('The credit card you entered has expired.'));
      $form_state->setErrorByName('panes][payment][details][cc_exp_year');
      $return = FALSE;
    }

    // Validate the issue number (if entered).  With issue numbers, '01' is
    // different from '1', but is_numeric() is still appropriate.
    if ($credit_config->get('uc_credit_issue_enabled') && !_uc_credit_valid_card_issue($cc_data['cc_issue'])) {
      $form_state->setErrorByName('panes][payment][details][cc_issue', t('The issue number you entered is invalid.'));
      $return = FALSE;
    }

    // Validate the CVV number if enabled.
    if ($credit_config->get('uc_credit_cvv_enabled') && !_uc_credit_valid_cvv($cc_data['cc_cvv'])) {
      $form_state->setErrorByName('panes][payment][details][cc_cvv', t('You have entered an invalid CVV number.'));
      $return = FALSE;
    }

    // Validate the bank name if enabled.
    if ($credit_config->get('uc_credit_bank_enabled') && empty($cc_data['cc_bank'])) {
      $form_state->setErrorByName('panes][payment][details][cc_bank', t('You must enter the issuing bank for that card.'));
      $return = FALSE;
    }

    // Initialize the encryption key and class.
    $key = uc_credit_encryption_key();
    $crypt = new Encryption();

    // Store the encrypted details in the session for the next pageload.
    // We are using base64_encode() because the encrypt function works with a
    // limited set of characters, not supporting the full Unicode character
    // set or even extended ASCII characters that may be present.
    // base64_encode() converts everything to a subset of ASCII, ensuring that
    // the encryption algorithm does not mangle names.
    $_SESSION['sescrd'] = $crypt->encrypt($key, base64_encode(serialize($order->payment_details)));

    // Log any errors to the watchdog.
    uc_store_encryption_errors($crypt, 'uc_credit');

    // If we're going to the review screen, set a variable that lets us know
    // we're paying by CC.
    if ($return) {
      $_SESSION['cc_pay'] = TRUE;
    }

    return $return;
  }

}
