<?php

/**
 * @file
 * Order administration menu items.
 */

/**
 * Themes the order status table in the order workflow settings.
 *
 * @see uc_order_workflow_form()
 * @ingroup themeable
 */
function theme_uc_order_status_table($variables) {
  $form = $variables['form'];

  $header = array(t('ID'), t('Title'), t('List position'), t('State'), t('Remove'));

  foreach (element_children($form) as $state_id) {
    $rows[] = array(
      drupal_render($form[$state_id]['id']),
      drupal_render($form[$state_id]['title']),
      drupal_render($form[$state_id]['weight']),
      drupal_render($form[$state_id]['state']),
      array('data' => drupal_render($form[$state_id]['remove']), 'align' => 'center'),
    );
  }

  return theme('table', array('header' => $header, 'rows' => $rows));
}

/**
 * Creates a new order for the specified customer, ready for editing.
 */
function uc_order_create_for_user($account) {
  global $user;

  $order = uc_order_new($account->uid, 'post_checkout');
  uc_order_comment_save($order->id(), $user->id(), t('Order created by the administration.'), 'admin');

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

  $form = drupal_get_form('uc_order_address_book_form', $uid, $type, $func);
  print drupal_render($form);
  exit();
}

/**
 * Presents previously entered addresses as selectable options.
 *
 * @see uc_order_address_book()
 * @ingroup forms
 */
function uc_order_address_book_form($form, &$form_state, $uid = 0, $type = 'billing', $func = '') {
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
    $email = check_plain($_POST['email']);
    $build['email'] = array('#markup' => '');
    if (!valid_email_address($email)) {
      $build['email']['#markup'] .= t('Invalid e-mail address.') . '<br />';
    }
    $result = db_query("SELECT uid, mail FROM {users} WHERE mail = :mail", array(':mail' => $email));
    if ($user = $result->fetchObject()) {
      $build['email']['#markup'] .= t('An account already exists for that e-mail.') . '<br /><br />';
      $build['email']['#markup'] .= '<b>' . t('Use this account now?') . '</b><br />'
        . t('User @uid - @mail', array('@uid' => $user->uid, '@mail' => $user->mail)) . ' <input type="button" '
        . 'onclick="select_existing_customer(' . $user->uid . ', \''
        . $user->mail . '\');" value="' . t('Apply') . '" /><br /><br /><hr /><br/>';
    }
    else {
      $name = uc_store_email_to_username($email);

      $fields = array(
        'name' => $name,
        'mail' => $email,
        'pass' => user_password(6),
        'status' => variable_get('uc_new_customer_status_active', TRUE) ? 1 : 0,
      );

      $account = entity_create('user', $fields);
      $account->save();

      if ($_POST['sendmail'] == 'true') {
        // Manually set the password so it appears in the e-mail.
        $account->password = $fields['pass'];

        // Send the e-mail through the user module.
        drupal_mail('user', 'register_admin_created', $email, uc_store_mail_recipient_langcode($email), array('account' => $account), uc_store_email_from());

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

  $build['customer_select_form'] = drupal_get_form('uc_order_select_customer_form', $options);

  print drupal_render($build);
  exit();
}

/**
 * Form to choose a customer from a list.
 *
 * @see uc_order_select_customer()
 * @ingroup forms
 */
function uc_order_select_customer_form($form, &$form_state, $options = NULL) {
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
      '#type' => 'textfield',
      '#title' => t('E-mail'),
      '#size' => 24,
      '#maxlength' => 96,
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
function theme_uc_order_edit_form($variables) {
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

  $last = '<div class="order-pane abs-left">' . drupal_render($form['order_id']) . drupal_render($form['order_modified'])
    . drupal_render($form['form_id']) . drupal_render($form['form_token'])
    . drupal_render($form['form_build_id'])
    . drupal_render($form['actions'])
    . '</div>';

  $output .= drupal_render_children($form) . $last;

  return $output;
}

/**
 * Handles order delete button action.
 *
 * @see uc_order_edit_form()
 */
function uc_order_edit_form_delete($form, &$form_state) {
  $form_state['redirect'] = 'admin/store/orders/' . $form_state['values']['order_id'] . '/delete';
}

/**
 * Form to add a line item to an order.
 *
 * @see uc_order_add_line_item_form_validate()
 * @see uc_order_add_line_item_submit()
 */
function uc_order_add_line_item_form($form, &$form_state, $order, $line_item_id) {
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
    '#suffix' => l(t('Cancel'), 'admin/store/orders/' . $order->id() . '/edit'),
  );

  return $form;
}

/**
 * Validates new line item data.
 *
 * @see uc_order_add_line_item_form()
 */
function uc_order_add_line_item_form_validate($form, &$form_state) {
  $func = _uc_line_item_data($form_state['values']['line_item_id'], 'callback');
  if (function_exists($func) && ($form = $func('form', $form_state['values']['order_id'])) != NULL) {
    $func('validate', $form, $form_state);
  }
}

/**
 * Form submission handler for uc_order_add_line_item_form().
 *
 * @see uc_order_add_line_item_form()
 */
function uc_order_add_line_item_form_submit($form, &$form_state) {
  $func = _uc_line_item_data($form_state['values']['line_item_id'], 'callback');
  if (function_exists($func) && ($form = $func('form', $form_state['values']['order_id'])) != NULL) {
    $func('submit', $form, $form_state);
  }
  else {
    uc_order_line_item_add($form_state['values']['order_id'], $form_state['values']['line_item_id'], $form_state['values']['title'], $form_state['values']['amount']);
    drupal_set_message(t('Line item added to order.'));
  }

  $form_state['redirect'] = 'admin/store/orders/' . $form_state['values']['order_id'] . '/edit';
}

/**
 * Confirmation form to delete an order.
 *
 * @see uc_order_delete_confirm_form_submit()
 * @ingroup forms
 */
function uc_order_delete_confirm_form($form, &$form_state, $order) {
  if (!uc_order_can_delete($order)) {
    drupal_set_message(t('It is not possible to delete order @id.', array('@id' => $order->id())));
    drupal_goto('admin/store/orders');
  }

  $form['order_id'] = array(
    '#type' => 'value',
    '#value' => $order->id()
  );

  return confirm_form($form, t('Are you sure you want to delete order @order_id?', array('@order_id' => $order->id())), 'admin/store/orders', NULL, t('Delete'));
}

/**
 * Form submission handler for uc_order_delete_confirm_form().
 *
 * @see uc_order_delete_confirm_form()
 */
function uc_order_delete_confirm_form_submit($form, &$form_state) {
  // Delete the specified order.
  uc_order_delete($form_state['values']['order_id']);

  // Display a message to the user and return to the order admin page.
  drupal_set_message(t('Order @order_id completely removed from the database.', array('@order_id' => $form_state['values']['order_id'])));

  $form_state['redirect'] = 'admin/store/orders';
}
