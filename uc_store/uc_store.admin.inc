<?php

/**
 * @file
 * Store administration menu items.
 */

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
  if (!\Drupal::config('uc_store.settings')->get('mail')) {
    $messages[] = array(
      'status' => 'error',
      'title'  => t('Store settings'),
      'desc'   => t('Store e-mail address has not been set. Please enter it <a href="@url">here</a>.', array('@url' => \Drupal::url('uc_store.settings_form'))),
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
