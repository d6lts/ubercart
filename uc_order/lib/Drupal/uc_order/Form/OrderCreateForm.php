<?php

/**
 * @file
 * Contains \Drupal\uc_order\Form\OrderCreateForm.
 */

namespace Drupal\uc_order\Form;

use Drupal\Core\Form\FormBase;

/**
 * Creates a new order and redirect to its edit screen.
 */
class OrderCreateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_order_create_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['customer_type'] = array(
      '#type' => 'radios',
      '#options' => array(
        'search' => t('Search for an existing customer.'),
        'create' => t('Create a new customer account.'),
        'none'   => t('No customer account required.'),
      ),
      '#required' => TRUE,
      '#default_value' => 'search',
      '#ajax' => array(
        'callback' => array($this, 'customerSelect'),
        'wrapper'  => 'uc-order-customer',
        'progress' => array('type' => 'throbber'),
      ),
    );

    $form['customer'] = array(
      '#prefix' => '<div id="uc-order-customer">',
      '#suffix' => '</div>',
      '#tree'   => TRUE,
    );

    // Create form elements needed for customer search.
    // Shown only when the 'Search for an existing customer.' radio is selected.
    if (!isset($form_state['values']['customer_type']) ||
        $form_state['values']['customer_type'] == 'search') {

      // Container for customer search fields.
      $form['customer'] += array(
        '#type' => 'fieldset',
        '#title' => t('Customer search'),
        '#description' => t('Enter full or partial information in one or more of the following fields, then press the "Search" button. Search results will match all the provided information.'),
      );
      // Customer first name.
      $form['customer']['first_name'] = array(
        '#type' => 'textfield',
        '#title' => t('First name'),
        '#size' => 24,
        '#maxlength' => 32,
      );
      // Customer last name.
      $form['customer']['last_name'] = array(
        '#type' => 'textfield',
        '#title' => t('Last name'),
        '#size' => 24,
        '#maxlength' => 32,
      );
      // Customer e-mail address.
      $form['customer']['email'] = array(
        '#type' => 'textfield',
        '#title' => t('E-mail'),
        '#size' => 24,
        '#maxlength' => 96,
      );
      // Customer username.
      $form['customer']['username'] = array(
        '#type' => 'textfield',
        '#title' => t('Username'),
        '#size' => 24,
        '#maxlength' => 96,
      );
      $form['customer']['search'] = array(
        '#type' => 'button',
        '#value' => t('Search'),
        '#validate' => array(),
        '#submit' => array(),
        '#ajax' => array(
          'callback' => array($this, 'customerSearch'),
          'wrapper' => 'uc-order-customer-results',
          'progress' => array('type' => 'throbber'),
        ),
      );
      $form['customer']['uid'] = array(
        '#prefix' => '<div id="uc-order-customer-results">',
        '#suffix' => '</div>',
      );

      // Search for existing customer by e-mail address.
      if (isset($form_state['values']['customer']['email'])) {
        $query = db_select('users', 'u')->distinct();
        $query->leftJoin('uc_orders', 'o', 'u.uid = o.uid');
        $query->fields('u', array('uid', 'name', 'mail'))
          ->fields('o', array('billing_first_name', 'billing_last_name'))
          ->condition('u.uid', 0, '>')
          ->condition(db_or()
            ->isNull('o.billing_first_name')
            ->condition('o.billing_first_name', db_like(trim($form_state['values']['customer']['first_name'])) . '%', 'LIKE')
          )
          ->condition(db_or()
            ->isNull('o.billing_last_name')
            ->condition('o.billing_last_name', db_like(trim($form_state['values']['customer']['last_name'])) . '%', 'LIKE')
          )
          ->condition(db_or()
            ->condition('o.primary_email', db_like(trim($form_state['values']['customer']['email'])) . '%', 'LIKE')
            ->condition('u.mail', db_like(trim($form_state['values']['customer']['email'])) . '%', 'LIKE')
          )
          ->condition('u.name', db_like(trim($form_state['values']['customer']['username'])) . '%', 'LIKE')
          ->orderBy('o.created', 'DESC')
          ->range(0, $limit = 11);

        $result = $query->execute();

        $options = array();
        foreach ($result as $user) {
          $name = '';
          if (!empty($user->billing_first_name) && !empty($user->billing_last_name)) {
            $name = $user->billing_first_name . ' ' . $user->billing_last_name . ' ';
          }
          // Options formated as "First Last <email@example.com> (username)".
          $options[$user->uid] = $name . '&lt;' . $user->mail . '&gt;' . ' (' . $user->name . ')';
        }

        $max = FALSE;
        if (count($options) == $limit) {
          array_pop($options);
          $max = TRUE;
        }

        if (!empty($options)) {
          // Display search results.
          $form['customer']['uid'] += array(
            '#type' => 'radios',
            '#title' => t('Select customer'),
            '#description' => $max ? t('More than !limit results found. Refine your search to find other customers.', array('!limit' => $limit - 1)) : '',
            '#options' => $options,
            '#default_value' => key($options),
          );
        }
        else {
          // No search results found.
          $form['customer']['uid'] += array(
            '#markup' => '<p>' . t('Search returned no results.') . '</p>',
          );
        }
      }
    }
    // Create form elements needed for new customer creation.
    // Shown only when the 'Create a new customer account.' radio is selected.
    elseif ($form_state['values']['customer_type'] == 'create') {
      // Container for new customer information.
      $form['customer'] += array(
        '#type'  => 'fieldset',
        '#title' => t('New customer details'),
      );
      // Customer e-mail address.
      $form['customer']['email'] = array(
        '#type' => 'email',
        '#title' => t('Customer e-mail address'),
        '#size' => 24,
        '#maxlength' => 96,
      );
      // Option to notify customer.
      $form['customer']['sendmail'] = array(
        '#type'  => 'checkbox',
        '#title' => t('E-mail account details to customer.'),
      );
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Create order'),
    );

    return $form;
  }

  /**
   * Ajax callback: updates the customer selection fields.
   */
  public function customerSelect($form, &$form_state) {
    return $form['customer'];
  }

  /**
   * Ajax callback: updates the customer search results.
   */
  public function customerSearch($form, &$form_state) {
    return $form['customer']['uid'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    switch ($form_state['values']['customer_type']) {
      case 'search':
        if (empty($form_state['values']['customer']['uid'])) {
          form_set_error('customer][uid', $form_state, t('Please select a customer.'));
        }
        break;

      case 'create':
        $email = trim($form_state['values']['customer']['email']);
        $uid = db_query('SELECT uid FROM {users} WHERE mail LIKE :mail', array(':mail' => $email))->fetchField();
        if ($uid) {
          form_set_error('customer][mail', $form_state, t('An account already exists for that e-mail.'));
        }
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    global $user;

    switch ($form_state['values']['customer_type']) {
      case 'search':
        $uid = $form_state['values']['customer']['uid'];
        break;

      case 'create':
        // Create new account.
        $email = trim($form_state['values']['customer']['email']);
        $fields = array(
          'name' => uc_store_email_to_username($email),
          'mail' => $email,
          'pass' => user_password(),
          'status' => $this->config('uc_cart.settings')->get('new_customer_status_active') ? 1 : 0,
        );
        $account = entity_create('user', $fields);
        $account->save();
        $uid = $account->id();

        if ($form_state['values']['customer']['sendmail']) {
          // Manually set the password so it appears in the e-mail.
          $account->password = $fields['pass'];
          drupal_mail('user', 'register_admin_created', $email, uc_store_mail_recipient_langcode($email), array('account' => $account), uc_store_email_from());
          drupal_set_message(t('A welcome message has been e-mailed to the new user.'));
        }
        break;

      default:
        $uid = 0;
    }

    $order = uc_order_new($uid, 'post_checkout');
    uc_order_comment_save($order->id(), $user->id(), t('Order created by the administration.'), 'admin');

    $form_state['redirect'] = 'admin/store/orders/' . $order->id() . '/edit';
  }

}
