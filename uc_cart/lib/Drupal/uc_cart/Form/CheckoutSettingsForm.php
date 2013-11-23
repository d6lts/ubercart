<?php

/**
 * @file
 * Contains \Drupal\uc_cart\Form\CheckoutSettingsForm.
 */

namespace Drupal\uc_cart\Form;

use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure general checkout settings for this site.
 */
class CheckoutSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'uc_cart_checkout_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    //$config = $this->configFactory->get('uc_cart.settings');

    $form['checkout-settings'] = array(
      '#type' => 'vertical_tabs',
      '#attached' => array(
        'js' => array(
          'vertical-tabs' => drupal_get_path('module', 'uc_cart') . '/uc_cart.admin.js',
        ),
      ),
    );

    $form['checkout'] = array(
      '#type' => 'details',
      '#title' => t('Basic settings'),
      '#group' => 'checkout-settings',
    );
    $form['checkout']['uc_checkout_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable checkout.'),
      '#description' => t('Disable this to use only third party checkout services, such as PayPal Express Checkout or Google Checkout.'),
      '#default_value' => variable_get('uc_checkout_enabled', TRUE),
    );

    if (!module_exists('rules')) {
      $form['checkout']['uc_checkout_email_customer'] = array(
        '#type' => 'checkbox',
        '#title' => t('Send e-mail invoice to customer after checkout.'),
        '#default_value' => variable_get('uc_checkout_email_customer', TRUE),
      );
      $form['checkout']['uc_checkout_email_admin'] = array(
        '#type' => 'checkbox',
        '#title' => t('Send e-mail order notification to admin after checkout.'),
        '#default_value' => variable_get('uc_checkout_email_admin', TRUE),
      );
    }

    $form['anonymous'] = array(
      '#type' => 'details',
      '#title' => t('Anonymous checkout'),
      '#group' => 'checkout-settings',
    );
    $form['anonymous']['uc_checkout_anonymous'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable anonymous checkout.'),
      '#description' => t('Disable this to force users to log in before the checkout page.'),
      '#default_value' => variable_get('uc_checkout_anonymous', TRUE),
    );
    $anon_state = array('visible' => array('input[name="uc_checkout_anonymous"]' => array('checked' => TRUE)));
    $form['anonymous']['uc_cart_mail_existing'] = array(
      '#type' => 'checkbox',
      '#title' => t("Allow anonymous customers to use an existing account's email address."),
      '#default_value' => variable_get('uc_cart_mail_existing', TRUE),
      '#description' => t('If enabled, orders will be attached to the account matching the email address. If disabled, anonymous users using a registered email address must log in or use a different email address.'),
      '#states' => $anon_state,
    );
    $form['anonymous']['uc_cart_email_validation'] = array(
      '#type' => 'checkbox',
      '#title' => t('Require e-mail confirmation for anonymous customers.'),
      '#default_value' => variable_get('uc_cart_email_validation', FALSE),
      '#states' => $anon_state,
    );
    $form['anonymous']['uc_cart_new_account_name'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow new customers to specify a username.'),
      '#default_value' => variable_get('uc_cart_new_account_name', FALSE),
      '#states' => $anon_state,
    );
    $form['anonymous']['uc_cart_new_account_password'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow new customers to specify a password.'),
      '#default_value' => variable_get('uc_cart_new_account_password', FALSE),
      '#states' => $anon_state,
    );
    $form['anonymous']['uc_new_customer_email'] = array(
      '#type' => 'checkbox',
      '#title' => t('Send new customers a separate e-mail with their account details.'),
      '#default_value' => variable_get('uc_new_customer_email', TRUE),
      '#states' => $anon_state,
    );
    $form['anonymous']['uc_new_customer_login'] = array(
      '#type' => 'checkbox',
      '#title' => t('Log in new customers after checkout.'),
      '#default_value' => variable_get('uc_new_customer_login', FALSE),
      '#states' => $anon_state,
    );
    $form['anonymous']['uc_new_customer_status_active'] = array(
      '#type' => 'checkbox',
      '#title' => t('Set new customer accounts to active.'),
      '#description' => t('Uncheck to create new accounts but make them blocked.'),
      '#default_value' => variable_get('uc_new_customer_status_active', TRUE),
      '#states' => $anon_state,
    );

    $panes = _uc_checkout_pane_list();
    $form['checkout']['panes'] = array(
      '#type' => 'table',
      '#header' => array(t('Pane'), t('List position')),
      '#tabledrag' => array(
        array('order', 'sibling', 'uc-checkout-pane-weight'),
      ),
    );
    foreach ($panes as $id => $pane) {
      $form['checkout']['panes'][$id]['#attributes']['class'][] = 'draggable';
      $form['checkout']['panes'][$id]['status'] = array(
        '#type' => 'checkbox',
        '#title' => check_plain($pane['title']),
        '#default_value' => variable_get('uc_pane_' . $id . '_enabled', $pane['enabled']),
      );
      $form['checkout']['panes'][$id]['weight'] = array(
        '#type' => 'weight',
        '#title' => t('Weight for @title', array('@title' => $pane['title'])),
        '#title_display' => 'invisible',
        '#default_value' => variable_get('uc_pane_' . $id . '_weight', $pane['weight']),
        '#attributes' => array(
          'class' => array('uc-checkout-pane-weight'),
        ),
      );
      $form['checkout']['panes'][$id]['#weight'] = variable_get('uc_pane_' . $id . '_weight', $pane['weight']);

      $null = NULL;
      $pane_settings = $pane['callback']('settings', $null, array());
      if (is_array($pane_settings)) {
        $form['pane_' . $id] = $pane_settings + array(
          '#type' => 'details',
          '#title' => t('@pane pane', array('@pane' => $pane['title'])),
          '#group' => 'checkout-settings',
        );
      }
    }

    $form['checkout']['uc_cart_default_same_address'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use the same address for billing and delivery by default.'),
      '#default_value' => variable_get('uc_cart_default_same_address', FALSE),
    );
    $form['checkout']['uc_cart_delivery_not_shippable'] = array(
      '#type' => 'checkbox',
      '#title' => t('Hide delivery information when carts have no shippable items.'),
      '#default_value' => variable_get('uc_cart_delivery_not_shippable', TRUE),
    );

    $form['instructions'] = array(
      '#type' => 'details',
      '#title' => t('Instruction messages'),
      '#group' => 'checkout-settings',
    );
    $form['instructions']['uc_checkout_instructions'] = array(
      '#type' => 'textarea',
      '#title' => t('Checkout instructions'),
      '#description' => t('Provide instructions for customers at the top of the checkout screen.'),
      '#default_value' => variable_get('uc_checkout_instructions', ''),
      '#rows' => 3,
    );
    $form['instructions']['uc_checkout_review_instructions'] = array(
      '#type' => 'textarea',
      '#title' => t('Checkout review instructions'),
      '#description' => t('Provide instructions for customers at the top of the checkout review screen.'),
      '#default_value' => variable_get('uc_checkout_review_instructions', uc_get_message('review_instructions')),
      '#rows' => 3,
    );

    $form['completion_messages'] = array(
      '#type' => 'details',
      '#title' => t('Completion messages'),
      '#group' => 'checkout-settings',
    );
    $form['completion_messages']['uc_cart_checkout_complete_page'] = array(
      '#type' => 'textfield',
      '#title' => t('Alternate checkout completion page'),
      '#description' => t('Leave blank to use the default completion page (recommended).'),
      '#default_value' => variable_get('uc_cart_checkout_complete_page', ''),
      '#field_prefix' => url(NULL, array('absolute' => TRUE)),
      '#size' => 16,
    );
    $form['completion_messages']['uc_msg_order_submit'] = array(
      '#type' => 'textarea',
      '#title' => t('Message header'),
      '#description' => t('Header for message displayed after a user checks out.'),
      '#default_value' => variable_get('uc_msg_order_submit', uc_get_message('completion_message')),
      '#rows' => 3,
    );
    $form['completion_messages']['uc_msg_order_logged_in'] = array(
      '#type' => 'textarea',
      '#title' => t('Logged in users'),
      '#description' => t('Message displayed upon checkout for a user who is logged in.'),
      '#default_value' => variable_get('uc_msg_order_logged_in', uc_get_message('completion_logged_in')),
      '#rows' => 3,
    );
    $form['completion_messages']['uc_msg_order_existing_user'] = array(
      '#type' => 'textarea',
      '#title' => t('Existing users'),
      '#description' => t("Message displayed upon checkout for a user who has an account but wasn't logged in."),
      '#default_value' => variable_get('uc_msg_order_existing_user', uc_get_message('completion_existing_user')),
      '#rows' => 3,
      '#states' => $anon_state,
    );
    $form['completion_messages']['uc_msg_order_new_user'] = array(
      '#type' => 'textarea',
      '#title' => t('New users'),
      '#description' => t("Message displayed upon checkout for a new user whose account was just created. You may use the special tokens !new_username for the username of a newly created account and !new_password for that account's password."),
      '#default_value' => variable_get('uc_msg_order_new_user', uc_get_message('completion_new_user')),
      '#rows' => 3,
      '#states' => $anon_state,
    );
    $form['completion_messages']['uc_msg_order_new_user_logged_in'] = array(
      '#type' => 'textarea',
      '#title' => t('New logged in users'),
      '#description' => t('Message displayed upon checkout for a new user whose account was just created and also <em>"Login users when new customer accounts are created at checkout."</em> is set on the <a href="!user_login_setting_ur">checkout settings</a>.', array('!user_login_setting_ur' => 'admin/store/settings/checkout')),
      '#default_value' => variable_get('uc_msg_order_new_user_logged_in', uc_get_message('completion_new_user_logged_in')),
      '#rows' => 3,
      '#states' => $anon_state,
    );
    $form['completion_messages']['uc_msg_continue_shopping'] = array(
      '#type' => 'textarea',
      '#title' => t('Continue shopping message'),
      '#description' => t('Message displayed upon checkout to direct customers to another part of your site.'),
      '#default_value' => variable_get('uc_msg_continue_shopping', uc_get_message('continue_shopping')),
      '#rows' => 3,
    );

    if (module_exists('token')) {
      $form['completion_messages']['token_tree'] = array(
        '#markup' => theme('token_tree', array('token_types' => array('uc_order', 'site', 'store'))),
      );
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    variable_set('uc_checkout_enabled', $form_state['values']['uc_checkout_enabled']);

    if (!module_exists('rules')) {
      variable_set('uc_checkout_email_customer', $form_state['values']['uc_checkout_email_customer']);
      variable_set('uc_checkout_email_admin', $form_state['values']['uc_checkout_email_admin']);
    }

    variable_set('uc_checkout_anonymous', $form_state['values']['uc_checkout_anonymous']);
    variable_set('uc_cart_mail_existing', $form_state['values']['uc_cart_mail_existing']);
    variable_set('uc_cart_email_validation', $form_state['values']['uc_cart_email_validation']);
    variable_set('uc_cart_new_account_name', $form_state['values']['uc_cart_new_account_name']);
    variable_set('uc_cart_new_account_password', $form_state['values']['uc_cart_new_account_password']);
    variable_set('uc_new_customer_email', $form_state['values']['uc_new_customer_email']);
    variable_set('uc_new_customer_login', $form_state['values']['uc_new_customer_login']);
    variable_set('uc_new_customer_status_active', $form_state['values']['uc_new_customer_status_active']);

    foreach (element_children($form['checkout']['panes']) as $id) {
      variable_set('uc_pane_' . $id . '_enabled', $form_state['values']['panes'][$id]['status']);
      variable_set('uc_pane_' . $id . '_weight', $form_state['values']['panes'][$id]['weight']);

      // TODO: handle (or remove) checkout pane settings
    }

    variable_set('uc_cart_default_same_address', $form_state['values']['uc_cart_default_same_address']);
    variable_set('uc_cart_delivery_not_shippable', $form_state['values']['uc_cart_delivery_not_shippable']);
    variable_set('uc_checkout_instructions', $form_state['values']['uc_checkout_instructions']);
    variable_set('uc_checkout_review_instructions', $form_state['values']['uc_checkout_review_instructions']);
    variable_set('uc_cart_checkout_complete_page', $form_state['values']['uc_cart_checkout_complete_page']);
    variable_set('uc_msg_order_submit', $form_state['values']['uc_msg_order_submit']);
    variable_set('uc_msg_order_logged_in', $form_state['values']['uc_msg_order_logged_in']);
    variable_set('uc_msg_order_existing_user', $form_state['values']['uc_msg_order_existing_user']);
    variable_set('uc_msg_order_new_user', $form_state['values']['uc_msg_order_new_user']);
    variable_set('uc_msg_order_new_user_logged_in', $form_state['values']['uc_msg_order_new_user_logged_in']);
    variable_set('uc_msg_continue_shopping', $form_state['values']['uc_msg_continue_shopping']);

    parent::submitForm($form, $form_state);
  }

}
