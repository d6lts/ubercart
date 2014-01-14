<?php

/**
 * @file
 * Contains \Drupal\uc_cart\Plugin\Ubercart\CheckoutPane\CustomerInfoPane.
 */

namespace Drupal\uc_cart\Plugin\Ubercart\CheckoutPane;

use Drupal\uc_cart\CheckoutPanePluginBase;
use Drupal\uc_order\UcOrderInterface;

/**
 * Gets the user's email address for login.
 *
 * @Plugin(
 *   id = "customer",
 *   title = @Translation("Customer information"),
 *   weight = 2,
 * )
 */
class CustomerInfoPane extends CheckoutPanePluginBase {

  /**
   * {@inheritdoc}
   */
  public function view(UcOrderInterface $order, array $form, array &$form_state) {
    $user = \Drupal::currentUser();
    $cart_config = \Drupal::config('uc_cart.settings');

    if ($user->isAuthenticated()) {
      $email = $user->getEmail();
      $contents['#description'] = t('Order information will be sent to your account e-mail listed below.');
      $contents['primary_email'] = array('#type' => 'hidden', '#value' => $email);
      $contents['email_text'] = array(
        '#markup' => '<div>' . t('<b>E-mail address:</b> @email (<a href="!url">edit</a>)', array('@email' => $email, '!url' => url('user/' . $user->id() . '/edit', array('query' => drupal_get_destination())))) . '</div>',
      );
    }
    else {
      $email = $order->getEmail();
      $contents['#description'] = t('Enter a valid email address for this order or <a href="!url">click here</a> to login with an existing account and return to checkout.', array('!url' => url('user/login', array('query' => drupal_get_destination()))));
      $contents['primary_email'] = array(
        '#type' => 'email',
        '#title' => t('E-mail address'),
        '#default_value' => $email,
        '#required' => TRUE,
      );
    }

    if ($cart_config->get('email_validation') && $user->isAnonymous()) {
      $contents['primary_email_confirm'] = array(
        '#type' => 'email',
        '#title' => t('Confirm e-mail address'),
        '#default_value' => $email,
        '#required' => TRUE,
      );
    }

    if ($user->isAnonymous()) {
      $contents['new_account'] = array();

      if ($cart_config->get('new_account_name')) {
        $contents['new_account']['name'] = array(
          '#type' => 'textfield',
          '#title' => t('Username'),
          '#default_value' => isset($order->data['new_user']['name']) ? $order->data['new_user']['name'] : '',
          '#maxlength' => 60,
          '#size' => 32,
        );
      }
      if ($cart_config->get('new_account_password')) {
        $contents['new_account']['pass'] = array(
          '#type' => 'password',
          '#title' => t('Password'),
          '#maxlength' => 32,
          '#size' => 32,
        );
        $contents['new_account']['pass_confirm'] = array(
          '#type' => 'password',
          '#title' => t('Confirm password'),
          '#description' => t('Passwords must match to proceed.'),
          '#maxlength' => 32,
          '#size' => 32,
        );
      }

      if (!empty($contents['new_account'])) {
        $array = array(
          '#type' => 'details',
          '#title' => t('New account details'),
          '#description' => $cart_config->get('new_account_details', t('<b>Optional.</b> New customers may supply custom account details.<br />We will create these for you if no values are entered.')),
          '#collapsible' => FALSE,
        );
        $contents['new_account'] = array_merge($array, $contents['new_account']);
      }
    }

    return $contents;
  }

  /**
   * {@inheritdoc}
   */
  public function process(UcOrderInterface $order, array $form, array &$form_state) {
    $user = \Drupal::currentUser();
    $cart_config = \Drupal::config('uc_cart.settings');

    $pane = $form_state['values']['panes']['customer'];
    $order->setEmail($pane['primary_email']);

    if ($cart_config->get('email_validation') && $user->isAnonymous() &&
      $pane['primary_email'] !== $pane['primary_email_confirm']) {
      form_set_error('panes][customer][primary_email_confirm', $form_state, t('The e-mail address did not match.'));
    }

    // Invalidate if an account already exists for this e-mail address, and the user is not logged into that account
    if (!$cart_config->get('mail_existing') && $user->isAnonymous() && !empty($pane['primary_email'])) {
      if (db_query("SELECT uid FROM {users} WHERE mail LIKE :mail", array(':mail' => $pane['primary_email']))->fetchField() > 0) {
        form_set_error('panes][customer][primary_email', $form_state, t('An account already exists for your e-mail address. You will either need to login with this e-mail address or use a different e-mail address.'));
      }
    }

    // If new users can specify names or passwords then...
    if (($cart_config->get('new_account_name') ||
        $cart_config->get('new_account_password')) &&
      $user->isAnonymous()) {
      // Skip if an account already exists for this e-mail address.
      if ($cart_config->get('mail_existing') && db_query("SELECT uid FROM {users} WHERE mail LIKE :mail", array(':mail' => $pane['primary_email']))->fetchField() > 0) {
        drupal_set_message(t('An account already exists for your e-mail address. The new account details you entered will be disregarded.'));
      }
      else {
        // Validate the username.
        if ($cart_config->get('new_account_name') && !empty($pane['new_account']['name'])) {
          $message = user_validate_name($pane['new_account']['name']);
          if (!empty($message)) {
            form_set_error('panes][customer][new_account][name', $form_state, $message);
          }
          elseif (db_query("SELECT uid FROM {users} WHERE name LIKE :name", array(':name' => $pane['new_account']['name']))->fetchField()) {
            form_set_error('panes][customer][new_account][name', $form_state, t('The username %name is already taken. Please enter a different name or leave the field blank for your username to be your e-mail address.', array('%name' => $pane['new_account']['name'])));
          }
          else {
            $order->data['new_user']['name'] = $pane['new_account']['name'];
          }
        }
        // Validate the password.
        if ($cart_config->get('new_account_password')) {
          if (strcmp($pane['new_account']['pass'], $pane['new_account']['pass_confirm'])) {
            form_set_error('panes][customer][new_account][pass_confirm', $form_state, t('The passwords you entered did not match. Please try again.'));
          }
          if (!empty($pane['new_account']['pass'])) {
            $order->data['new_user']['hash'] = \Drupal::service('password')->hash(trim($pane['new_account']['pass']));
          }
        }
      }
    }

    if ($user->isAuthenticated()) {
      $order->setUserId($user->id());
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function review(UcOrderInterface $order) {
    $review[] = array('title' => t('E-mail'), 'data' => check_plain($order->getEmail()));
    return $review;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    $cart_config = \Drupal::config('uc_cart.settings');

    $form['uc_cart_new_account_details'] = array(
      '#type' => 'textarea',
      '#title' => t('New account details help message'),
      '#description' => t('Enter the help message displayed in the new account details fieldset when shown.'),
      '#default_value' => $cart_config->get('new_account_details', t('<b>Optional.</b> New customers may supply custom account details.<br />We will create these for you if no values are entered.')),
    );

    return $form;
  }

}
