<?php

/**
 * @file
 * File administration menu items.
 */

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

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

/**
 * Displays all files that may be purchased and downloaded for administration.
 *
 * @see uc_file_admin_files_form()
 * @see uc_file_admin_files_form_show_validate()
 * @see uc_file_admin_files_form_show_submit()
 * @see theme_uc_file_admin_files_form_show()
 * @ingroup forms
 */
function uc_file_admin_files_form_show_files($form, FormStateInterface $form_state) {
  $form['#tree'] = TRUE;

  $header = array(
    'filename' => array('data' => t('File'), 'field' => 'f.filename', 'sort' => 'asc'),
    'title' => array('data' => t('Product'), 'field' => 'n.title'),
    'model' => array('data' => t('SKU'), 'field' => 'fp.model')
  );

  // Create pager.
  $query = db_select('uc_files', 'f')
    ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
    ->extend('Drupal\Core\Database\Query\TableSortExtender')
    ->orderByHeader($header)
    ->limit(UC_FILE_PAGER_SIZE);
  $query->leftJoin('uc_file_products', 'fp', 'f.fid = fp.fid');
  $query->leftJoin('uc_product_features', 'pf', 'fp.pfid = pf.pfid');
  $query->leftJoin('node', 'n', 'pf.nid = n.nid');
  $query->addField('n', 'nid');
  $query->addField('f', 'filename');
  $query->addField('n', 'title');
  $query->addField('fp', 'model');
  $query->addField('f', 'fid');
  $query->addField('pf', 'pfid');

  $count_query = db_select('uc_files');
  $count_query->addExpression('COUNT(*)');

  $query->setCountQuery($count_query);
  $result = $query->execute();

  $options = array();
  foreach ($result as $file) {
    $options[$file->fid] = array(
      'filename' => array(
        'data' => SafeMarkup::checkPlain($file->filename),
        'class' => is_dir(uc_file_qualify_file($file->filename)) ? array('uc-file-directory-view') : array(),
      ),
      'title' => Link::createFromRoute($file->title, 'entity.node.canonical', ['node' => $file->nid]),
      'model' => SafeMarkup::checkPlain($file->model),
    );
  }

  // Create checkboxes for each file.
  $form['file_select'] = array(
    '#type' => 'tableselect',
    '#header' => $header,
    '#options' => $options,
    '#empty' => t('No file downloads available.'),
  );

  $form['uc_file_action'] = array(
    '#type' => 'fieldset',
    '#title' => t('File options'),
  );

  // Set our default actions.
  $file_actions = array(
    'uc_file_upload' => t('Upload file'),
    'uc_file_delete' => t('Delete file(s)'),
  );

  // Check if any hook_uc_file_action('info', $args) are implemented
  $module_handler = \Drupal::moduleHandler();
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
    '#title' => t('Action'),
    '#options' => $file_actions,
    '#prefix' => '<div class="duration">',
    '#suffix' => '</div>',
  );

  $form['uc_file_actions']['actions'] = array('#type' => 'actions');
  $form['uc_file_action']['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Perform action'),
    '#prefix' => '<div class="duration">',
    '#suffix' => '</div>',
  );

  return $form;
}

/**
 * Ensures at least one file is selected when deleting.
 *
 * @see uc_file_admin_files_form_show_files()
 * @see uc_file_admin_files_form_show_submit()
 */
function uc_file_admin_files_form_show_validate($form, FormStateInterface $form_state) {

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
        $form_state->setErrorByName('', t('You must select at least one file to delete.'));
      }

      break;
  }

}

/**
 * Moves to the next step of the administration form.
 *
 * @see uc_file_admin_files_form_show_files()
 * @see uc_file_admin_files_form_show_validate()
 */
function uc_file_admin_files_form_show_submit($form, FormStateInterface $form_state) {
  // Increment the form step.
  $form_state->set('step', UC_FILE_FORM_ACTION);
  $form_state->setRebuild();
}

/**
 * Returns HTML for uc_file_admin_files_form_show().
 *
 * @param array $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @see uc_file_admin_files_form_show_files()
 * @ingroup themeable
 */
function theme_uc_file_admin_files_form_show(array $variables) {
  $form = $variables['form'];

  // Render everything.
  $output = '<p>' . t('File downloads can be attached to any Ubercart product as a product feature. For security reasons the <a href=":download_url">file downloads directory</a> is separated from the Drupal <a href=":file_url">file system</a>. Below is the list of files (and their associated Ubercart products, if any) that can be used for file downloads.', [':download_url' => Url::fromRoute('uc_product.admin', [], ['query' => ['destination' => 'admin/store/products/files']])->toString(), ':file_url' => Url::fromRoute('system.file_system_settings')->toString()]) . '</p>';
  $output .= drupal_render($form['uc_file_action']);
  $output .= drupal_render($form['file_select']);
  $output .= theme('pager');
  $output .= drupal_render_children($form);

  return $output;
}
