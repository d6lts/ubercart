<?php

/**
 * @file
 * Contains \Drupal\uc_file\Form\AdminsterFiles.
 */

namespace Drupal\uc_file\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\String;

/**
 * Form step values.
 */
define('UC_FILE_FORM_FILES' , NULL);
define('UC_FILE_FORM_ACTION', 1   );


/**
 * Form builder for file products admin.
 */
class AdministerFiles extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_file_admin_files_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

module_load_include('inc', 'uc_roles', 'uc_roles.admin');
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

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
