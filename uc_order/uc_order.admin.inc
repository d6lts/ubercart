<?php

/**
 * @file
 * Order administration menu items.
 */

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\FormStateInterface;

/**
 * Creates a new order for the specified customer, ready for editing.
 */
function uc_order_create_for_user($account) {
  $order = uc_order_new($account->uid, 'post_checkout');
  uc_order_comment_save($order->id(), \Drupal::currentUser()->id(), t('Order created by the administration.'), 'admin');

  drupal_goto('admin/store/orders/' . $order->id() . '/edit');
}

/**
 * Displays a form to select a previously entered address.
 *
 * @see uc_order_address_book_form()
 */
function uc_order_address_book() {
  $uid = intval($_POST['uid']);
  $type = $_POST['type'];
  $func = $_POST['func'];

  $form = \Drupal::formBuilder()->getForm('uc_order_address_book_form', $uid, $type, $func);
  print drupal_render($form);
  exit();
}

/**
 * Presents previously entered addresses as selectable options.
 *
 * @see uc_order_address_book()
 * @ingroup forms
 */
function uc_order_address_book_form($form, FormStateInterface $form_state, $uid = 0, $type = 'billing', $func = '') {
  $select = uc_select_address($uid, $type, $func);

  if ($uid == 0) {
    $form['desc'] = array('#markup' => '<br />' . t('You must select a customer before address<br />information is available.<br />') . '<br />');
  }
  elseif (is_null($select)) {
    $form['desc'] = array('#markup' => '<br />' . t('No addresses found for customer.') . '<br />');
  }
  else {
    $form['addresses'] = uc_select_address($uid, $type, $func, t('Select an address'));
    $form['addresses']['#prefix'] = '<div style="float: left; margin-right: 1em;">';
    $form['addresses']['#suffix'] = '</div>';
  }

  $form['close'] = array(
    '#type' => 'button',
    '#value' => t('Close'),
    '#attributes' => array('onclick' => "return close_address_select('#" . $type . "_address_select');"),
  );

  return $form;
}

/**
 * Presents the customer search results and let one of them be chosen.
 *
 * @see uc_order_select_customer_form()
 */
function uc_order_select_customer($email = NULL) {
  $build = array();
  $options = NULL;

  // Return the search results and let them pick one!
  if (arg(4) == 'search') {
    $first_name = str_replace('*', '%', db_like($_POST['first_name']));
    $last_name = str_replace('*', '%', db_like($_POST['last_name']));
    $email = str_replace('*', '%', db_like($_POST['email']));

    $query = db_select('users', 'u')->distinct();
    $query->leftJoin('uc_orders', 'o', 'u.uid = o.uid');
    $query->fields('u', array('uid', 'mail'))
      ->fields('o', array('billing_first_name', 'billing_last_name'))
      ->condition('u.uid', 0, '>')
      ->orderBy('o.billing_last_name');

    if ($first_name && $first_name !== '%') {
      $query->condition('o.billing_first_name', $first_name, 'LIKE');
    }
    if ($last_name && $last_name !== '%') {
      $query->condition('o.billing_last_name', $last_name, 'LIKE');
    }
    if ($email && $email !== '%') {
      $query->condition(db_or()
        ->condition('o.primary_email', $email, 'LIKE')
        ->condition('u.mail', $email, 'LIKE')
      );
    }

    $result = $query->execute();

    $options = array();
    foreach ($result as $user) {
      if (empty($user->billing_first_name) && empty($user->billing_last_name)) {
        $name = '';
      }
      else {
        $name = $user->billing_last_name . ', ' . $user->billing_first_name . ' ';
      }
      $options[$user->uid . ':' . $user->mail] = $name . '(' . $user->mail . ')';
    }

    if (count($options) == 0) {
      $build['description'] = array('#markup' => '<p>' . t('Search returned no results.') . '</p>');
      $options = NULL;
    }
    else {
      $build['description'] = array('<p>' . t('Search returned the following:') . '</p>');
    }
  }

  // Check to see if the e-mail address for a new user is unique.
  if (arg(5) == 'check') {
    $email = SafeMarkup::checkPlain($_POST['email']);
    $build['email'] = array('#markup' => '');
    $result = db_query("SELECT uid, mail FROM {users_field_data} WHERE mail = :mail", [':mail' => $email]);
    if ($user_field_data = $result->fetchObject()) {
      $build['email']['#markup'] .= t('An account already exists for that e-mail.') . '<br /><br />';
      $build['email']['#markup'] .= '<b>' . t('Use this account now?') . '</b><br />'
        . t('User @uid - @mail', ['@uid' => $user_field_data->uid, '@mail' => $user_field_data->mail])
        . ' <input type="button" onclick="select_existing_customer(' . $user_field_data->uid . ', \''
        . $user_field_data->mail . '\');" value="' . t('Apply') . '" /><br /><br /><hr /><br/>';
    }
    else {
      $name = uc_store_email_to_username($email);

      $fields = array(
        'name' => $name,
        'mail' => $email,
        'pass' => user_password(6),
        'status' => \Drupal::config('uc_cart.settings')->get('new_customer_status_active') ? 1 : 0,
      );

      $account = entity_create('user', $fields);
      $account->save();

      if ($_POST['sendmail'] == 'true') {
        // Manually set the password so it appears in the e-mail.
        $account->password = $fields['pass'];

        // Send the e-mail through the user module.
        \Drupal::service('plugin.manager.mail')->mail('user', 'register_admin_created', $email, uc_store_mail_recipient_langcode($email), array('account' => $account), uc_store_email_from());

        $build['email']['#markup'] .= t('Account details sent to e-mail provided.<br /><br /><strong>Username:</strong> @username<br /><strong>Password:</strong> @password', array('@username' => $fields['name'], '@password' => $fields['pass'])) . '<br /><br />';
      }

      $build['result'] = array(
        '#markup' => '<strong>' . t('Use this account now?') . '</strong><br />'
          . t('User @uid - @mail', array('@uid' => $account->id(), '@mail' => $account->getEmail())) . ' <input type="button" '
          . 'onclick="select_existing_customer(' . $account->id() . ', \''
          . $account->getEmail() . '\');" value="' . t('Apply') . '" /><br /><br /><hr /><br/>',
      );
    }
  }

  $build['customer_select_form'] = \Drupal::formBuilder()->getForm('uc_order_select_customer_form', $options);

  print drupal_render($build);
  exit();
}

/**
 * Form to choose a customer from a list.
 *
 * @see uc_order_select_customer()
 * @ingroup forms
 */
function uc_order_select_customer_form($form, FormStateInterface $form_state, $options = NULL) {
  if (is_null(arg(4))) {
    $form['desc'] = array(
      '#markup' => '<div>' . t('Search for a customer based on these fields.')
                 . '<br />' . t('Use * as a wildcard to match any character.') . '<br />'
                 . '(<em>' . t('Leave a field empty to ignore it in the search.')
                 . '</em>)</div>',
    );

    $form['first_name'] = array(
      '#type' => 'textfield',
      '#title' => t('First name'),
      '#size' => 24,
      '#maxlength' => 32,
    );

    $form['last_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Last name'),
      '#size' => 24,
      '#maxlength' => 32,
    );

    $form['email'] = array(
      '#type' => 'textfield',
      '#title' => t('E-mail'),
      '#size' => 24,
      '#maxlength' => 96,
    );
  }
  elseif (arg(4) == 'search' && !is_null($options)) {
    $form['cust_select'] = array(
      '#type' => 'select',
      '#title' => t('Select a customer'),
      '#size' => 7,
      '#options' => $options,
      '#default_value' => key($options),
      '#attributes' => array('ondblclick' => 'return select_customer_search();'),
    );
  }
  elseif (arg(4) == 'new') {
    $form['desc'] = array(
      '#markup' => '<div>' . t('Enter an e-mail address for the new customer.') . '</div>',
    );

    $form['email'] = array(
      '#type' => 'email',
      '#title' => t('E-mail'),
      '#size' => 24,
      '#maxlength' => 96,
      '#required' => TRUE,
    );
  }

  $form['actions'] = array('#type' => 'actions');
  if (is_null(arg(4))) {
    $form['actions']['search'] = array(
      '#type' => 'submit',
      '#value' => t('Search'),
      '#attributes' => array('onclick' => 'return load_customer_search_results();'),
    );
  }
  elseif (arg(4) == 'search') {
    if (!is_null($options)) {
      $form['actions']['select'] = array(
        '#type' => 'submit',
        '#value' => t('Select'),
        '#attributes' => array('onclick' => 'return select_customer_search();'),
      );
    }
    $form['actions']['back'] = array(
      '#type' => 'submit',
      '#value' => t('Back'),
      '#attributes' => array('onclick' => 'return load_customer_search();'),
    );
  }
  elseif (arg(4) == 'new') {
    $form['sendmail'] = array(
      '#type' => 'checkbox',
      '#title' => t('E-mail customer account details.'),
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#attributes' => array('onclick' => 'return check_new_customer_address();'),
    );
  }

  $form['actions']['close'] = array(
    '#type' => 'submit',
    '#value' => t('Close'),
    '#attributes' => array('onclick' => 'return close_customer_select();'),
  );

  return $form;
}

/**
 * Formats the uc_order_edit_form().
 *
 * @see uc_order_edit_form()
 * @see uc_order_edit_form_validate()
 * @see uc_order_edit_form_submit()
 * @ingroup themeable
 */
function theme_uc_order_edit_form(array $variables) {
  $form = $variables['form'];
  $output = '';

  $panes = _uc_order_pane_list();
  foreach ($panes as $id => $pane) {
    if (in_array('edit', $pane['show'])) {
      $func = $pane['callback'];
      if (function_exists($func) && ($contents = $func('edit-theme', $form['#order'], $form)) != NULL) {
        $output .= '<div class="order-pane ' . $pane['class'] . '" id="order-pane-' . $id . '">';
        $title = isset($pane['display title']) ? $pane['display title'] : $pane['title'];
        if ($title) {
          $output .= '<div class="order-pane-title">' . $title . ':' . '</div>';
        }
        $output .= $contents . '</div>';
      }
    }
  }

  $output .= drupal_render_children($form);

  return $output;
}

/**
 * Form to add a line item to an order.
 *
 * @see uc_order_add_line_item_form_validate()
 * @see uc_order_add_line_item_submit()
 */
function uc_order_add_line_item_form($form, FormStateInterface $form_state, $order, $line_item_id) {
  $func = _uc_line_item_data($line_item_id, 'callback');

  if (!function_exists($func) || ($form = $func('form', $order->id())) == NULL) {
    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Line item title'),
      '#description' => t('Display title of the line item.'),
      '#size' => 32,
      '#maxlength' => 128,
      '#default_value' => _uc_line_item_data($line_item_id, 'title'),
    );
    $form['amount'] = array(
      '#type' => 'uc_price',
      '#title' => t('Line item amount'),
      '#allow_negative' => TRUE,
    );
  }

  $form['order_id'] = array(
    '#type' => 'hidden',
    '#value' => $order->id(),
  );
  $form['line_item_id'] = array(
    '#type' => 'hidden',
    '#value' => $line_item_id,
  );
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Add line item'),
    '#suffix' => \Drupal::l(t('Cancel'), new Url('entity.uc_order.edit_form', ['uc_order' => $order->id()])),
  );

  return $form;
}

/**
 * Validates new line item data.
 *
 * @see uc_order_add_line_item_form()
 */
function uc_order_add_line_item_form_validate($form, FormStateInterface $form_state) {
  $func = _uc_line_item_data($form_state->getValue('line_item_id'), 'callback');
  if (function_exists($func) && ($form = $func('form', $form_state->getValue('order_id'))) != NULL) {
    $func('validate', $form, $form_state);
  }
}

/**
 * Form submission handler for uc_order_add_line_item_form().
 *
 * @see uc_order_add_line_item_form()
 */
function uc_order_add_line_item_form_submit($form, FormStateInterface $form_state) {
  $func = _uc_line_item_data($form_state->getValue('line_item_id'), 'callback');
  if (function_exists($func) && ($form = $func('form', $form_state->getValue('order_id'))) != NULL) {
    $func('submit', $form, $form_state);
  }
  else {
    uc_order_line_item_add($form_state->getValue('order_id'), $form_state->getValue('line_item_id'), $form_state->getValue('title'), $form_state->getValue('amount'));
    drupal_set_message(t('Line item added to order.'));
  }

  $form_state->setRedirect('entity.uc_order.edit_form', ['uc_order' => $form_state->getValue('order_id')]);
}
