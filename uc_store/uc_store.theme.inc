<?php

/**
 * @file
 * Theme functions for the uc_store module.
 */

use Drupal\user\Entity\User;

/**
 * Displays a username in the standard format and with consistent markup.
 *
 * @param array $variables
 *   An associative array containing:
 *   - uid: A user ID value.
 *
 * @return string
 *   Formatted HTML.
 *
 * @ingroup themeable
 */
function theme_uc_uid(array $variables) {
  if ($variables['uid']) {
    $username = array(
      '#theme' => 'username',
      '#account' => User::load($variables['uid']),
    );
    return drupal_render($username);
  }
  else {
    return '-';
  }
}
