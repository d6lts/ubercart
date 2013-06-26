<?php

/**
 * @file
 * Store administration menu items.
 */

/**
 * Menu callback which provides the store administration overview page.
 */
function uc_store_admin() {
  module_load_include('inc', 'system', 'system.admin');
  // Check for status report errors.
  if (Drupal::service('system.manager')->checkRequirements() && user_access('administer site configuration')) {
    drupal_set_message(t('One or more problems were detected with your Drupal installation. Check the <a href="@status">status report</a> for more information.', array('@status' => url('admin/reports/status'))), 'error');
  }
  $blocks = array();
  if ($system_link = entity_load_multiple_by_properties('menu_link', array('link_path' => 'admin/store', 'module' => 'system'))) {
    $system_link = reset($system_link);
    $query = Drupal::entityQuery('menu_link')
      ->condition('link_path', 'admin/help', '<>')
      ->condition('menu_name', $system_link->menu_name)
      ->condition('plid', $system_link->id())
      ->condition('hidden', 0);
    $result = $query->execute();
    if (!empty($result)) {
      $menu_links = menu_link_load_multiple($result);

      foreach ($menu_links as $item) {
        _menu_link_translate($item);
        if (!$item['access']) {
          continue;
        }
        // The link description, either derived from 'description' in hook_menu()
        // or customized via menu module is used as title attribute.
        if (!empty($item['localized_options']['attributes']['title'])) {
          $item['description'] = $item['localized_options']['attributes']['title'];
          unset($item['localized_options']['attributes']['title']);
        }
        $block = $item;
        $block['content'] = '';
        $block['content'] .= theme('admin_block_content', array('content' => system_admin_menu_block($item)));
        if (!empty($block['content'])) {
          $block['show'] = TRUE;
        }

        // Prepare for sorting as in function _menu_tree_check_access().
        // The weight is offset so it is always positive, with a uniform 5-digits.
        $blocks[(50000 + $item['weight']) . ' ' . $item['title'] . ' ' . $item['mlid']] = $block;
      }
    }
  }

  ksort($blocks);
  $build['blocks'] = array(
    '#theme' => 'admin_page',
    '#blocks' => $blocks,
  );

  if ($results = module_invoke_all('uc_store_status')) {
    foreach ($results as $message) {
      switch ($message['status']) {
        case 'warning': $icon = 'alert.gif'; break;
        case 'error':   $icon = 'error.gif'; break;
        default:        $icon = 'info.gif';
      }
      $icon = theme('image', array('uri' => drupal_get_path('module', 'uc_store') . '/images/' . $icon));

      $rows[] = array(
        array('data' => $icon, 'class' => array('status-icon')),
        array('data' => $message['title'], 'class' => array('status-title')),
        array('data' => $message['desc'], 'class' => array('status-value')),
      );
    }

    $build['status'] = array(
      '#theme' => 'table',
      '#caption' => '<h2>' . t('Store status') . '</h2>',
      '#rows' => $rows,
      '#attributes' => array('class' => array('system-status-report')),
    );
  }

  return $build;
}

/**
 * Displays main reports page.
 */
function uc_store_reports() {
  $menu = menu_get_item('admin/store/reports');
  $content = system_admin_menu_block($menu);

  $build['menu'] = array(
    '#theme' => 'admin_block_content',
    '#content' => $content,
    '#weight' => 5,
  );

  return $build;
}

/**
 * Displays store configuration page.
 */
function uc_store_configuration_page() {
  $menu = menu_get_item('admin/store/settings');
  $content = system_admin_menu_block($menu);

  $build['menu'] = array(
    '#theme' => 'admin_block_content',
    '#content' => $content,
  );

  return $build;
}

/**
 * Form to configure address fields.
 *
 * @see uc_store_address_fields_form_submit()
 * @see theme_uc_store_address_fields_form()
 * @ingroup forms
 */
function uc_store_address_fields_form($form, &$form_state) {
  $form['uc_address_fields']['#tree'] = TRUE;
  $form['uc_address_fields_required']['#tree'] = TRUE;
  $form['uc_address_fields_weight']['#tree'] = TRUE;

  $fields = array(
    'first_name' => t('First name'),
    'last_name' => t('Last name'),
    'company' => t('Company'),
    'street1' => t('Street address 1'),
    'street2' => t('Street address 2'),
    'city' => t('City'),
    'zone' => t('State/Province'),
    'country' => t('Country'),
    'postal_code' => t('Postal code'),
    'phone' => t('Phone number'),
  );
  $current = variable_get('uc_address_fields', drupal_map_assoc(array('first_name', 'last_name', 'phone', 'company', 'street1', 'street2', 'city', 'zone', 'postal_code', 'country')));
  $required = variable_get('uc_address_fields_required', drupal_map_assoc(array('first_name', 'last_name', 'street1', 'city', 'zone', 'postal_code', 'country')));
  $weight = uc_store_address_field_weights();
  foreach ($fields as $field => $label) {
    $form['uc_address_fields'][$field] = array(
      '#type' => 'checkbox',
      '#default_value' => isset($current[$field]) ? TRUE : FALSE,
    );
    $form['uc_address_fields_required'][$field] = array(
      '#type' => 'checkbox',
      '#default_value' => isset($required[$field]) ? TRUE : FALSE,
    );
    $form['uc_address_fields_weight'][$field] = array(
      '#type' => 'weight',
      '#default_value' => (isset($weight[$field])) ? $weight[$field] : 0,
      '#attributes' => array('class' => array('uc-store-address-fields-weight')),
    );
    $form['fields'][$field]['default'] = array(
      '#markup' => $label,
    );
    $form['fields'][$field]['#weight'] = (isset($weight[$field])) ? $weight[$field] : 99;
  }

  $form['#submit'][] = 'uc_store_address_fields_form_submit';
  $form = system_settings_form($form);
  unset($form['#theme']);

  return $form;
}

/**
 * Returns HTML for uc_store_address_fields_form().
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @see uc_store_address_fields_form()
 * @ingroup themeable
 */
function theme_uc_store_address_fields_form($variables) {
  $form = $variables['form'];

  $header = array(t('Field'), t('Enabled'), t('Required'), t('List position'));

  // Sort fields by weight
  uasort($form['fields'], 'element_sort');

  foreach (element_children($form['fields']) as $field) {
    $row = array(
      drupal_render($form['fields'][$field]['default']),
      drupal_render($form['uc_address_fields'][$field]),
      drupal_render($form['uc_address_fields_required'][$field]),
      drupal_render($form['uc_address_fields_weight'][$field]),
    );

    $rows[] = array(
      'data' => $row,
      'class' => array('draggable'),
    );
  }

  drupal_add_tabledrag('uc-store-address-fields-weight-table', 'order', 'sibling', 'uc-store-address-fields-weight');

  $output = theme('table', array(
    'header' => $header,
    'rows' => $rows,
    'attributes' => array('id' => 'uc-store-address-fields-weight-table'),
  ));
  $output .= drupal_render_children($form);

  return $output;
}

/**
 * Saves the address fields settings.
 *
 * @see uc_store_address_fields_form()
 */
function uc_store_address_fields_form_submit($form, &$form_state) {
  $form_state['values']['uc_address_fields'] = array_filter($form_state['values']['uc_address_fields']);
  $form_state['values']['uc_address_fields_required'] = array_filter($form_state['values']['uc_address_fields_required']);
}

/**
 * Implements hook_uc_store_status().
 */
function uc_store_uc_store_status() {
  $messages = array();

  // Check to see if there are any updated CIF files that need to be installed.
  $countries = array();
  $result = db_query("SELECT * FROM {uc_countries}");
  foreach ($result as $country) {
    $countries[t($country->country_name)] = $country;
  }
  uksort($countries, 'strnatcasecmp');
  $files = _uc_country_import_list();

  $updates = array();
  if (is_array($countries)) {
    foreach ($countries as $country) {
      if ($country->version < $files[$country->country_id]['version'] && $country->version > 0) {
        $updates[] = $country->country_name;
      }
      unset($files[$country->country_id]);
    }
  }

  if (count($updates)) {
    $messages[] = array(
      'status' => 'warning',
      'title'  => t('Countries'),
      'desc'   => t('Updates are available for the following installed countries: %countries. You may update these countries on the <a href="@url">country settings</a> page.', array('%countries' => implode(', ', $updates), '@url' => url('admin/store/settings/countries'))),
    );
  }
  else {
    $messages[] = array(
      'status' => 'ok',
      'title'  => t('Countries'),
      'desc'   => t('Country definitions are up-to-date.'),
    );
  }

  // Check to see if the store e-mail address has been set.
  if (!config('uc_store.settings')->get('mail')) {
    $messages[] = array(
      'status' => 'error',
      'title'  => t('Store settings'),
      'desc'   => t('Store e-mail address has not been set. Please enter it <a href="@url">here</a>.', array('@url' => url('admin/store/settings/store'))),
    );
  }
  else {
    $messages[] = array(
      'status' => 'ok',
      'title'  => t('Store settings'),
      'desc'   => t('Store e-mail address is set.'),
    );
  }

  return $messages;
}
