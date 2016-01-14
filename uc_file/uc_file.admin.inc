<?php

/**
 * @file
 * File administration menu items.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Form step values.
 */
define('UC_FILE_FORM_ACTION', 1   );

/**
 * Form builder for file products admin.
 *
 * @see uc_file_admin_files_form_show_files()
 * @see uc_file_admin_files_form_action()
 * @ingroup forms
 */
function uc_file_admin_files_form($form, FormStateInterface $form_state) {

  if ($form_state->get('step') == UC_FILE_FORM_ACTION) {
      return array(
        '#validate' => array('uc_file_admin_files_form_action_validate'),
        '#submit'   => array('uc_file_admin_files_form_action_submit'),
      ) + $form + uc_file_admin_files_form_action($form, $form_state);
  }
  else {
    // Refresh our file list before display.
    uc_file_refresh();

    return array(
      '#theme'  => 'uc_file_admin_files_form_show',
      '#validate' => array('uc_file_admin_files_form_show_validate'),
      '#submit' => array('uc_file_admin_files_form_show_submit'),
    ) + $form + uc_file_admin_files_form_show_files($form, $form_state);
  }
}
