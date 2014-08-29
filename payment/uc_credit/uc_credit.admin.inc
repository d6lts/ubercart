<?php

/**
 * @file
 * Credit administration menu items.
 */

use Drupal\Component\Utility\String;
use Drupal\Core\Form\FormStateInterface;

/**
 * Credit card settings form.
 *
 * @see uc_credit_settings_form_validate()
 * @see uc_credit_settings_form_submit()
 */
function uc_credit_settings_form($form, FormStateInterface $form_state) {
  $config = \Drupal::config('uc_credit.settings');
  $account = \Drupal::currentUser();
  if (!$account->hasPermission('administer credit cards')) {
    $form['notice'] = array(
      '#markup' => '<div>' . t('You must have access to <b>administer credit cards</b> to adjust these settings.') . '</div>',
    );
    return $form;
  }

  $gateways = _uc_payment_gateway_list('credit');
  if (!count($gateways)) {
    $form['notice'] = array(
      '#markup' => '<div>' . t('Please enable a credit card gateway module for your chosen payment provider.') . '</div>',
    );
    return $form;
  }

  $form['uc_credit'] = array(
    '#type' => 'vertical_tabs',
    '#attached' => array(
      'js' => array(
        'vertical-tabs' => drupal_get_path('module', 'uc_credit') . '/js/uc_credit.admin.js',
      ),
    ),
  );

  $form['cc_basic'] = array(
    '#type' => 'details',
    '#title' => t('Basic settings'),
    '#group' => 'uc_credit',
  );
  $options = array();
  foreach ($gateways as $id => $gateway) {
    $options[$id] = $gateway['title'];
  }
  $form['cc_basic']['uc_payment_credit_gateway'] = array(
    '#type' => 'radios',
    '#title' => t('Default gateway'),
    '#options' => $options,
    '#default_value' => uc_credit_default_gateway(),
  );
  $form['cc_basic']['uc_credit_validate_numbers'] = array(
    '#type' => 'checkbox',
    '#title' => t('Validate credit card numbers at checkout.'),
    '#description' => t('Invalid card numbers will show an error message to the user so they can correct it.'),
    '#default_value' => variable_get('uc_credit_validate_numbers', TRUE),
  );

  // Form elements that deal specifically with card number security.
  $form['cc_security'] = array(
    '#type' => 'details',
    '#title' => t('Security settings'),
    '#description' => t('You are responsible for the security of your website, including the protection of credit card numbers.  Please be aware that choosing some settings in this section may decrease the security of credit card data on your website and increase your liability for damages in the case of fraud.'),
    '#group' => 'uc_credit',
  );
  $form['cc_security']['uc_credit_encryption_path'] = array(
    '#type' => 'textfield',
    '#title' => t('Encryption key directory'),
    '#description' => t('The card type, expiration date and last four digits of the card number are encrypted and stored temporarily while the customer is in the process of checking out.<br /><b>You must enable encryption</b> by following the <a href="!url">encryption instructions</a> in order to accept credit card payments.<br />In short, you must enter the path of a directory outside of your document root where the encryption key may be stored.<br />Relative paths will be resolved relative to the Drupal installation directory.<br />Once this directory is set, you should not change it.', array('!url' => 'http://drupal.org/node/1309226')),
    '#default_value' => uc_credit_encryption_key() ? $config->get('encryption_path') : t('Not configured.'),
  );

  // Form elements that deal with the type of data requested at checkout.
  $form['cc_fields'] = array(
    '#type' => 'details',
    '#title' => t('Credit card fields'),
    '#description' => t('Specify what information to collect from customers in addition to the card number.'),
    '#group' => 'uc_credit',
    '#weight' => 10,
  );
  $form['cc_fields']['uc_credit_cvv_enabled'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable CVV text field on checkout form.'),
    '#description' => t('The CVV is an added security measure on credit cards. On Visa, Mastercard, and Discover cards it is a three digit number, and on AmEx cards it is a four digit number. If your credit card processor or payment gateway requires this information, you should enable this feature here.'),
    '#default_value' => variable_get('uc_credit_cvv_enabled', TRUE),
  );
  $form['cc_fields']['uc_credit_owner_enabled'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable card owner text field on checkout form.'),
    '#default_value' => variable_get('uc_credit_owner_enabled', FALSE),
  );
  $form['cc_fields']['uc_credit_start_enabled'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable card start date on checkout form.'),
    '#default_value' => variable_get('uc_credit_start_enabled', FALSE),
  );
  $form['cc_fields']['uc_credit_issue_enabled'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable card issue number text field on checkout form.'),
    '#default_value' => variable_get('uc_credit_issue_enabled', FALSE),
  );
  $form['cc_fields']['uc_credit_bank_enabled'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable issuing bank text field on checkout form.'),
    '#default_value' => variable_get('uc_credit_bank_enabled', FALSE),
  );
  $form['cc_fields']['uc_credit_type_enabled'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable card type selection on checkout form.'),
    '#description' => t('If enabled, specify in the textarea below which card options to populate the select box with.'),
    '#default_value' => variable_get('uc_credit_type_enabled', FALSE),
  );
  $form['cc_fields']['uc_credit_accepted_types'] = array(
    '#type' => 'textarea',
    '#title' => t('Card type select box options'),
    '#description' => t('Enter one card type per line. These fields will populate the card type select box if it is enabled.'),
    '#default_value' => variable_get('uc_credit_accepted_types', implode("\r\n", array(t('Visa'), t('Mastercard'), t('Discover'), t('American Express')))),
  );

  // Form elements that deal with card types accepted.
  $form['cc_fields']['cc_types'] = array(
    '#type' => 'details',
    '#title' => t('Card types'),
    '#description' => t('Use the checkboxes to specify which card types you accept for payment. Selected card types will show their icons in the payment method selection list and be used for card number validation.'),
  );
  $form['cc_fields']['cc_types']['uc_credit_visa'] = array(
    '#type' => 'checkbox',
    '#title' => t('Visa'),
    '#default_value' => variable_get('uc_credit_visa', TRUE),
  );
  $form['cc_fields']['cc_types']['uc_credit_mastercard'] = array(
    '#type' => 'checkbox',
    '#title' => t('Mastercard'),
    '#default_value' => variable_get('uc_credit_mastercard', TRUE),
  );
  $form['cc_fields']['cc_types']['uc_credit_discover'] = array(
    '#type' => 'checkbox',
    '#title' => t('Discover'),
    '#default_value' => variable_get('uc_credit_discover', TRUE),
  );
  $form['cc_fields']['cc_types']['uc_credit_amex'] = array(
    '#type' => 'checkbox',
    '#title' => t('American Express'),
    '#default_value' => variable_get('uc_credit_amex', TRUE),
  );

  // Form elements that deal with credit card messages to customers.
  $form['cc_messages'] = array(
    '#type' => 'details',
    '#title' => t('Customer messages'),
    '#description' => t('Here you can alter messages displayed to customers using credit cards.'),
    '#group' => 'uc_credit',
    '#weight' => 10,
  );
  $form['cc_messages']['uc_credit_policy'] = array(
    '#type' => 'textarea',
    '#title' => t('Credit card payment policy'),
    '#description' => t('Instructions for customers on the checkout page above the credit card fields.'),
    '#default_value' => variable_get('uc_credit_policy', t('Your billing information must match the billing address for the credit card entered below or we will be unable to process your payment.')),
    '#rows' => 3,
  );
  $form['cc_messages']['uc_credit_fail_message'] = array(
    '#type' => 'textarea',
    '#title' => t('Card processing failure message'),
    '#description' => t('Error message displayed to customers when an attempted payment fails at checkout.'),
    '#default_value' => variable_get('uc_credit_fail_message', t('We were unable to process your credit card payment. Please verify your details and try again.  If the problem persists, contact us to complete your order.')),
  );

  $txn_types = array(
    UC_CREDIT_AUTH_ONLY => t('Authorization only'),
    UC_CREDIT_AUTH_CAPTURE => t('Authorize and capture immediately'),
    UC_CREDIT_REFERENCE_SET => t('Set a reference only'),
  );

  foreach ($gateways as $id => $gateway) {
    $form['gateways'][$id] = array(
      '#type' => 'details',
      '#title' => String::checkPlain($gateway['title']),
      '#group' => 'uc_credit',
      '#weight' => 5,
    );
    $form['gateways'][$id]['uc_pg_' . $id . '_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable this payment gateway for use.'),
      '#default_value' => variable_get('uc_pg_' . $id . '_enabled', TRUE),
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
      '#title' => t('Default credit transaction type'),
      '#description' => t('Only available transaction types are listed. The default will be used unless an administrator chooses otherwise through the terminal.'),
      '#options' => $options,
      '#default_value' => variable_get('uc_pg_' . $id . '_cc_txn_type', UC_CREDIT_AUTH_CAPTURE),
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
    drupal_set_message(t('Credit card security settings must be configured in the security settings tab.'), 'warning');
  }

  $form['#validate'][] = 'uc_credit_settings_form_validate';
  $form['#submit'][]   = 'uc_credit_settings_form_submit';

  return $form;
}

/**
 * Validates the encryption key directory and key file.
 *
 * Checks that the encryption key directory has been specified, that it
 * exists, and is readable. and writeable so
 *
 * @see uc_credit_settings_form()
 * @see uc_credit_settings_form_submit()
 */
function uc_credit_settings_form_validate($form, FormStateInterface $form_state) {
  // Trim trailing whitespace and any trailing / or \ from the key path name.
  $key_path = rtrim(trim($form_state['values']['uc_credit_encryption_path']), '/\\');

  // Test to see if a path was entered.
  if (empty($key_path)) {
    $form_state->setErrorByName('uc_credit_encryption_path', t('Key path must be specified in security settings tab.'));
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
          $form_state->setErrorByName('uc_credit_encryption_path', t('Key file already exists in directory, but it contains an invalid key.'));
        }
        else {
          // Key file exists and is valid, save result of trim() back into
          // $form_state and proceed to submit handler.
          $form_state['values']['uc_credit_encryption_path'] = $key_path;
          return;
        }
      }
    }
    else {
      $form_state->setErrorByName('uc_credit_encryption_path', t('Key file already exists in directory, but is not readable. Please verify the file permissions.'));
    }
  }

  // Check if directory exists and is writeable.
  if (is_dir($key_path)) {
    // The entered directory is valid and in need of a key file.
    // Flag this condition for the submit handler.
    $form_state['uc_credit']['update_cc_encrypt_dir'] = TRUE;

    // Can we open for writing?
    $file = @fopen($key_path . '/encrypt.test', 'w');
    if ($file === FALSE) {
      $form_state->setErrorByName('uc_credit_encryption_path', t('Cannot write to directory, please verify the directory permissions.'));
      unset($form_state['uc_credit']['update_cc_encrypt_dir']);
    }
    else {
      // Can we actually write?
      if (@fwrite($file, '0123456789') === FALSE) {
        $form_state->setErrorByName('uc_credit_encryption_path', t('Cannot write to directory, please verify the directory permissions.'));
        unset($form_state['uc_credit']['update_cc_encrypt_dir']);
        fclose($file);
      }
      else {
        // Can we read now?
        fclose($file);
        $file = @fopen($key_path . '/encrypt.test', 'r');
        if ($file === FALSE) {
          $form_state->setErrorByName('uc_credit_encryption_path', t('Cannot read from directory, please verify the directory permissions.'));
          unset($form_state['uc_credit']['update_cc_encrypt_dir']);
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
    $form_state->setErrorByName('uc_credit_encryption_path', t('You have specified a non-existent directory.'));
  }

  // If validation succeeds, save result of trim() back into $form_state.
  $form_state['values']['uc_credit_encryption_path'] = $key_path;
}

/**
 * Creates the encryption key file if it doesn't already exist.
 *
 * @see uc_credit_settings_form()
 * @see uc_credit_settings_form_validate()
 */
function uc_credit_settings_form_submit($form, FormStateInterface $form_state) {
  // Check to see if we need to create a key file.
  if (isset($form_state['uc_credit']['update_cc_encrypt_dir']) &&
      $form_state['uc_credit']['update_cc_encrypt_dir'] === TRUE) {

    $key_path = $form_state['values']['uc_credit_encryption_path'];
    $key_file = $key_path . '/' . UC_CREDIT_KEYFILE_NAME;

    if (!file_exists($key_file)) {
      if (!$file = fopen($key_file, 'wb')) {
        drupal_set_message(t('Credit card encryption key file creation failed for file @file. Check your filepath settings and directory permissions.', array('@file' => $key_file)), 'error');
        watchdog('uc_credit', 'Credit card encryption key file creation failed for file @file. Check your filepath settings and directory permissions.', array('@file' => $key_file), WATCHDOG_ERROR);
      }
      else {
        // Replacement key generation suggested by Barry Jaspan
        // for increased security.
        fwrite($file, md5(\Drupal::csrfToken()->get(serialize($_REQUEST) . serialize($_SERVER) . REQUEST_TIME)));
        fclose($file);

        drupal_set_message(t('Credit card encryption key file generated. Card data will now be encrypted.'));
        watchdog('uc_credit', 'Credit card encryption key file generated. Card data will now be encrypted.');
      }
    }
  }
}
