<?php

/**
 * @file
 * Contains \Drupal\uc_file\Form\FileActionForm.
 */

namespace Drupal\uc_file\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form step values.
 */
define('UC_FILE_FORM_ACTION', 1   );


/**
 * Form builder for file products admin.
 */
class FileActionForm extends FormBase {

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

    $module_handler = \Drupal::moduleHandler();
//    $module_handler->loadInclude('uc_file', 'inc', 'uc_file.admin');
    //if ($form_state->get('step') == UC_FILE_FORM_ACTION) {
    //  return $form + \Drupal::formBuilder()->buildForm('Drupal\uc_file\Form\ActionForm', $form, $form_state);
    //}
    //else {
      // Refresh our file list before display.
    uc_file_refresh();  // Rebuilds uc_file table from directory contents! I sure hope it's smart about it...

    // Render everything.

    //  return $form + \Drupal::formBuilder()->buildForm('Drupal\uc_file\Form\ShowForm', $form, $form_state);
    //}
    $form['#attached']['library'][] = 'uc_file/uc_file.styles';

    $form['help'] = array(
      '#prefix' => '<p>',
      '#markup' => $this->t('File downloads can be attached to any Ubercart product as a product feature. For security reasons the <a href=":download_url">file downloads directory</a> is separated from the Drupal <a href=":file_url">file system</a>. Below is the list of files (and their associated Ubercart products, if any) that can be used for file downloads.', [':download_url' => Url::fromRoute('uc_product.settings', [], ['query' => ['destination' => 'admin/store/products/files']])->toString(), ':file_url' => Url::fromRoute('system.file_system_settings')->toString()]),
      '#suffix' => '<p>',
    );

    $form['uc_file_action'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('File options'),
    );

    // Set our default actions.
    $file_actions = array(
      'uc_file_upload' => $this->t('Upload file'),
      'uc_file_delete' => $this->t('Delete file(s)'),
    );

    // Check if any hook_uc_file_action('info', $args) are implemented
    foreach ($module_handler->getImplementations('uc_file_action') as $module) {
      $name = $module . '_uc_file_action';
      $result = $name('info', NULL);
      if (is_array($result)) {
        foreach ($result as $key => $action) {
          if ($key != 'uc_file_delete' && $key != 'uc_file_upload') {
            $file_actions[$key] = $action;
          }
        }
      }
    }

    $form['uc_file_action']['action'] = array(
      '#type' => 'select',
      '#title' => $this->t('Action'),
      '#prefix' => '<div class="duration">',
      '#options' => $file_actions,
      '#suffix' => '</div>',
    );

    $form['uc_file_actions']['actions'] = array('#type' => 'actions');
    $form['uc_file_action']['actions']['submit'] = array(
      '#type' => 'submit',
      '#prefix' => '<div class="duration">',
      '#value' => $this->t('Perform action'),
      '#suffix' => '</div>',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    switch ($form_state->getValue(['uc_file_action', 'action'])) {
      case 'uc_file_delete':
        $file_ids = array();
        if (is_array($form_state->getValue('file_select'))) {
          foreach ($form_state->getValue('file_select') as $fid => $value) {
            if ($value) {
              $file_ids[] = $fid;
            }
          }
        }
        if (count($file_ids) == 0) {
          $form_state->setErrorByName('', $this->t('You must select at least one file to delete.'));
        }
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Increment the form step.
    $form_state->set('step', UC_FILE_FORM_ACTION);
    $form_state->setRebuild();
  }

}
