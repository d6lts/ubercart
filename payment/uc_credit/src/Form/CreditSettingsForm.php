<?php

/**
 * @file
 * Contains \Drupal\uc_credit\Form\CreditSettingsForm.
 */

namespace Drupal\uc_credit\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Credit card settings form.
 */
class CreditSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_credit_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $credit_config = $this->config('uc_credit.settings');
    $account = $this->currentUser();
    if (!$account->hasPermission('administer credit cards')) {
      $form['notice'] = array(
        '#markup' => '<div>' . $this->t('You must have access to <b>administer credit cards</b> to adjust these settings.') . '</div>',
      );
      return $form;
    }

    $gateways = _uc_payment_gateway_list('credit');
    if (!count($gateways)) {
      $form['notice'] = array(
        '#markup' => '<div>' . $this->t('Please enable a credit card gateway module for your chosen payment provider.') . '</div>',
      );
// @todo - This is commented out to test this form without a gateway
//      return $form;
    }

    $form['uc_credit'] = array(
      '#type' => 'vertical_tabs',
      '#attached' => array(
        'library' => array(
          'uc_credit/uc_credit.scripts',
        ),
      ),
    );

    $form['cc_basic'] = array(
      '#type' => 'details',
      '#title' => $this->t('Basic settings'),
      '#group' => 'uc_credit',
    );
    $options = array();
    foreach ($gateways as $id => $gateway) {
      $options[$id] = $gateway['title'];
    }
    $form['cc_basic']['uc_payment_credit_gateway'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Default gateway'),
      '#options' => $options,
      '#default_value' => uc_credit_default_gateway(),
    );
    $form['cc_basic']['uc_credit_validate_numbers'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Validate credit card numbers at checkout.'),
      '#description' => $this->t('Invalid card numbers will show an error message to the user so they can correct it.'),
      '#default_value' => $credit_config->get('validate_numbers'),
    );

    // Form elements that deal specifically with card number security.
    $form['cc_security'] = array(
      '#type' => 'details',
      '#title' => $this->t('Security settings'),
      '#description' => $this->t('You are responsible for the security of your website, including the protection of credit card numbers.  Please be aware that choosing some settings in this section may decrease the security of credit card data on your website and increase your liability for damages in the case of fraud.'),
      '#group' => 'uc_credit',
    );
    $form['cc_security']['uc_credit_encryption_path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Encryption key directory'),
      '#description' => $this->t('The card type, expiration date and last four digits of the card number are encrypted and stored temporarily while the customer is in the process of checking out.<br /><b>You must enable encryption</b> by following the <a href="!url">encryption instructions</a> in order to accept credit card payments.<br />In short, you must enter the path of a directory outside of your document root where the encryption key may be stored.<br />Relative paths will be resolved relative to the Drupal installation directory.<br />Once this directory is set, you should not change it.', ['!url' => 'http://drupal.org/node/1309226']),
      '#default_value' => uc_credit_encryption_key() ? $credit_config->get('encryption_path') : $this->t('Not configured.'),
    );

    // Form elements that deal with the type of data requested at checkout.
    $form['cc_fields'] = array(
      '#type' => 'details',
      '#title' => $this->t('Credit card fields'),
      '#description' => $this->t('Specify what information to collect from customers in addition to the card number.'),
      '#group' => 'uc_credit',
      '#weight' => 10,
    );
    $form['cc_fields']['uc_credit_cvv_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable CVV text field on checkout form.'),
      '#description' => $this->t('The CVV is an added security measure on credit cards. On Visa, Mastercard, and Discover cards it is a three digit number, and on AmEx cards it is a four digit number. If your credit card processor or payment gateway requires this information, you should enable this feature here.'),
      '#default_value' => $credit_config->get('cvv_enabled'),
    );
    $form['cc_fields']['uc_credit_owner_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable card owner text field on checkout form.'),
      '#default_value' => $credit_config->get('owner_enabled'),
    );
    $form['cc_fields']['uc_credit_start_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable card start date on checkout form.'),
      '#default_value' => $credit_config->get('start_enabled'),
    );
    $form['cc_fields']['uc_credit_issue_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable card issue number text field on checkout form.'),
      '#default_value' => $credit_config->get('issue_enabled'),
    );
    $form['cc_fields']['uc_credit_bank_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable issuing bank text field on checkout form.'),
      '#default_value' => $credit_config->get('bank_enabled'),
    );
    $form['cc_fields']['uc_credit_type_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable card type selection on checkout form.'),
      '#description' => $this->t('If enabled, specify in the textarea below which card options to populate the select box with.'),
      '#default_value' => $credit_config->get('type_enabled'),
    );
    $form['cc_fields']['uc_credit_accepted_types'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Card type select box options'),
      '#description' => $this->t('Enter one card type per line. These fields will populate the card type select box if it is enabled.'),
      '#default_value' => implode("\r\n", $credit_config->get('accepted_types')),
    );

    // Form elements that deal with card types accepted.
    $form['cc_fields']['cc_types'] = array(
      '#type' => 'details',
      '#title' => $this->t('Card types'),
      '#description' => $this->t('Use the checkboxes to specify which card types you accept for payment. Selected card types will show their icons in the payment method selection list and be used for card number validation.'),
    );
    $form['cc_fields']['cc_types']['uc_credit_visa'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Visa'),
      '#default_value' => $credit_config->get('visa'),
    );
    $form['cc_fields']['cc_types']['uc_credit_mastercard'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Mastercard'),
      '#default_value' => $credit_config->get('mastercard'),
    );
    $form['cc_fields']['cc_types']['uc_credit_discover'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Discover'),
      '#default_value' => $credit_config->get('discover'),
    );
    $form['cc_fields']['cc_types']['uc_credit_amex'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('American Express'),
      '#default_value' => $credit_config->get('amex'),
    );

    // Form elements that deal with credit card messages to customers.
    $form['cc_messages'] = array(
      '#type' => 'details',
      '#title' => $this->t('Customer messages'),
      '#description' => $this->t('Here you can alter messages displayed to customers using credit cards.'),
      '#group' => 'uc_credit',
      '#weight' => 10,
    );
    $form['cc_messages']['uc_credit_policy'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Credit card payment policy'),
      '#description' => $this->t('Instructions for customers on the checkout page above the credit card fields.'),
      '#default_value' => $credit_config->get('policy'),
      '#rows' => 3,
    );
    $form['cc_messages']['uc_credit_fail_message'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Card processing failure message'),
      '#description' => $this->t('Error message displayed to customers when an attempted payment fails at checkout.'),
      '#default_value' => $credit_config->get('fail_message'),
    );

    $txn_types = array(
      UC_CREDIT_AUTH_ONLY => $this->t('Authorization only'),
      UC_CREDIT_AUTH_CAPTURE => $this->t('Authorize and capture immediately'),
      UC_CREDIT_REFERENCE_SET => $this->t('Set a reference only'),
    );

    foreach ($gateways as $id => $gateway) {
      $form['gateways'][$id] = array(
        '#type' => 'details',
        '#title' => SafeMarkup::checkPlain($gateway['title']),
        '#group' => 'uc_credit',
        '#weight' => 5,
      );
      $form['gateways'][$id]['uc_pg_' . $id . '_enabled'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Enable this payment gateway for use.'),
        '#default_value' => $credit_config->get('uc_pg_' . $id . '_enabled'),
        '#weight' => -10,
      );

      // Get the transaction types associated with this gateway.
      $gateway_types = uc_credit_gateway_txn_types($id);
      $options = array();
      foreach ($txn_types as $type => $title) {
        if (in_array($type, $gateway_types)) {
          $options[$type] = $title;
        }
      }
      $form['gateways'][$id]['uc_pg_' . $id . '_cc_txn_type'] = array(
        '#type' => 'radios',
        '#title' => $this->t('Default credit transaction type'),
        '#description' => $this->t('Only available transaction types are listed. The default will be used unless an administrator chooses otherwise through the terminal.'),
        '#options' => $options,
        '#default_value' => $credit_config->get('uc_pg_' . $id . '_cc_txn_type'),
        '#weight' => -5,
      );

      if (isset($gateway['settings']) && function_exists($gateway['settings'])) {
        $gateway_settings = $gateway['settings'](array(), $form_state);
        if (is_array($gateway_settings)) {
          $form['gateways'][$id] += $gateway_settings;
        }
      }
    }

    if (empty($_POST) && !uc_credit_encryption_key()) {
      drupal_set_message($this->t('Credit card security settings must be configured in the security settings tab.'), 'warning');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check that the encryption key directory has been specified, that it
    // exists, and that it is readable.

    // Trim trailing whitespace and any trailing / or \ from the key path name.
    $key_path = rtrim(trim($form_state->getValue('uc_credit_encryption_path')), '/\\');

    // Test to see if a path was entered.
    if (empty($key_path)) {
      $form_state->setErrorByName('uc_credit_encryption_path', $this->t('Key path must be specified in security settings tab.'));
    }

    // Construct complete key file path.
    $key_file = $key_path . '/' . UC_CREDIT_KEYFILE_NAME;

    // Shortcut - test to see if we already have a usable key file.
    if (file_exists($key_file)) {
      if (is_readable($key_file)) {
        // Test contents - must contain 32-character hexadecimal string.
        $key = uc_credit_encryption_key();
        if ($key) {
          if (!preg_match("([0-9a-fA-F]{32})", $key)) {
            $form_state->setErrorByName('uc_credit_encryption_path', $this->t('Key file already exists in directory, but it contains an invalid key.'));
          }
          else {
            // Key file exists and is valid, save result of trim() back into
            // $form_state and proceed to submit handler.
            $form_state->setValue('uc_credit_encryption_path', $key_path);
            return;
          }
        }
      }
      else {
        $form_state->setErrorByName('uc_credit_encryption_path', $this->t('Key file already exists in directory, but is not readable. Please verify the file permissions.'));
      }
    }

    // Check if directory exists and is writeable.
    if (is_dir($key_path)) {
      // The entered directory is valid and in need of a key file.
      // Flag this condition for the submit handler.
      $form_state->setValue('update_cc_encrypt_dir', TRUE);

      // Can we open for writing?
      $file = @fopen($key_path . '/encrypt.test', 'w');
      if ($file === FALSE) {
        $form_state->setErrorByName('uc_credit_encryption_path', $this->t('Cannot write to directory, please verify the directory permissions.'));
        $form_state->setValue('update_cc_encrypt_dir', FALSE);
      }
      else {
        // Can we actually write?
        if (@fwrite($file, '0123456789') === FALSE) {
          $form_state->setErrorByName('uc_credit_encryption_path', $this->t('Cannot write to directory, please verify the directory permissions.'));
          $form_state->setValue('update_cc_encrypt_dir', FALSE);
          fclose($file);
        }
        else {
          // Can we read now?
          fclose($file);
          $file = @fopen($key_path . '/encrypt.test', 'r');
          if ($file === FALSE) {
            $form_state->setErrorByName('uc_credit_encryption_path', $this->t('Cannot read from directory, please verify the directory permissions.'));
            $form_state->setValue('update_cc_encrypt_dir', FALSE);
          }
          else {
            fclose($file);
          }
        }
        unlink($key_path . '/encrypt.test');
      }
    }
    else {
      // Directory doesn't exist.
      $form_state->setErrorByName('uc_credit_encryption_path', $this->t('You have specified a non-existent directory.'));
    }

    // If validation succeeds, save result of trim() back into $form_state.
    $form_state->setValue('uc_credit_encryption_path', $key_path);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Check to see if we need to create an encryption key file.
    if ($form_state->getValue('update_cc_encrypt_dir')) {
      $key_path = $form_state->getValue('uc_credit_encryption_path');
      $key_file = $key_path . '/' . UC_CREDIT_KEYFILE_NAME;

      if (!file_exists($key_file)) {
        if (!$file = fopen($key_file, 'wb')) {
          drupal_set_message($this->t('Credit card encryption key file creation failed for file @file. Check your filepath settings and directory permissions.', ['@file' => $key_file]), 'error');
          $this->logger('uc_credit')->error('Credit card encryption key file creation failed for file @file. Check your filepath settings and directory permissions.', ['@file' => $key_file]);
        }
        else {
          // Replacement key generation suggested by Barry Jaspan
          // for increased security.
          fwrite($file, md5(\Drupal::csrfToken()->get(serialize($_REQUEST) . serialize($_SERVER) . REQUEST_TIME)));
          fclose($file);

          drupal_set_message($this->t('Credit card encryption key file generated. Card data will now be encrypted.'));
          $this->logger('uc_credit')->notice('Credit card encryption key file generated. Card data will now be encrypted.');
        }
      }
    }

    // Need to use configFactory() and getEditable() here, because this form is
    // wrapped by PaymentMethodSettingsForm so $this->getEditableConfigNames()
    // never gets called
    $credit_config = \Drupal::configFactory()->getEditable('uc_credit.settings');
    $credit_config
      ->set('validate_numbers', $form_state->getValue('uc_credit_validate_numbers'))
      ->set('encryption_path', $form_state->getValue('uc_credit_encryption_path'))
      ->set('cvv_enabled', $form_state->getValue('uc_credit_cvv_enabled'))
      ->set('owner_enabled', $form_state->getValue('uc_credit_owner_enabled'))
      ->set('start_enabled', $form_state->getValue('uc_credit_start_enabled'))
      ->set('issue_enabled', $form_state->getValue('uc_credit_issue_enabled'))
      ->set('bank_enabled', $form_state->getValue('uc_credit_bank_enabled'))
      ->set('type_enabled', $form_state->getValue('uc_credit_type_enabled'))
      ->set('policy', $form_state->getValue('uc_credit_policy'))
      ->set('accepted_types', explode("\r\n", $form_state->getValue('uc_credit_accepted_types')))
      ->save();
  }
}
