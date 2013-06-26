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
