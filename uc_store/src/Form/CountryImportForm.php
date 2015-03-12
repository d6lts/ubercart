<?php

/**
 * @file
 * Contains \Drupal\uc_store\Form\CountryImportForm.
 */

namespace Drupal\uc_store\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_store\Controller\CountryController;

/**
 * Imports settings from a country file.
 */
class CountryImportForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'uc_country_import_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $countries = array();
    $result = db_query("SELECT * FROM {uc_countries}");
    foreach ($result as $country) {
      $countries[t($country->country_name)] = $country;
    }
    uksort($countries, 'strnatcasecmp');
    $files = uc_country_import_list();

    $header = array(t('Country'), t('Code'), t('Version'), t('Operations'));
    $rows = array();
    if (is_array($countries)) {
      foreach ($countries as $country) {
        $row = array(
          t($country->country_name),
          $country->country_iso_code_3,
          array('data' => abs($country->version), 'align' => 'center')
        );

        $ops = array();
        if ($country->version < 0) {
          $ops[] = \Drupal::l(t('enable'), new Url('uc_countries.enable', ['country_id' => $country->country_id]));
        }
        else {
          $ops[] = \Drupal::l(t('disable'), new Url('uc_countries.disable', ['country_id' => $country->country_id]));
        }
        if ($country->version < $files[$country->country_id]['version'] && $country->version > 0) {
          $ops[] = \Drupal::l(t('update'), new Url('uc_countries.update', ['country_id' => $country->country_id, 'version' =>  $files[$country->country_id]['version']]));
        }
        $ops[] = \Drupal::l(t('remove'), new Url('uc_countries.remove', ['country_id' => $country->country_id]));
        $row[] = implode(' ', $ops);

        $rows[] = $row;
        unset($files[$country->country_id]);
      }
    }

    $import_list = array();
    foreach ($files as $file) {
      $import_list[$file['file']] = $file['file'];
    }

    if (!empty($import_list)) {
      ksort($import_list);

      $form['country_import'] = array(
        '#title' => t('Import countries'),
        '#type' => 'details',
        '#collapsed' => TRUE,
        '#collapsible' => TRUE,
      );

      $form['country_import']['text'] = array(
        '#markup' => '<p>' . t('To import new country data, select it in the list and click the import button. If you are using a custom or contributed import file, it must be placed in the Ubercart folder uc_store/countries.') . '</p>',
      );
      $form['country_import']['import_file'] = array(
        '#type' => 'select',
        '#title' => t('Country'),
        '#options' => $import_list,
        '#multiple' => TRUE,
        '#size' => min(10, count($import_list)),
      );
      $form['country_import']['actions'] = array('#type' => 'actions');
      $form['country_import']['actions']['import_button'] = array(
        '#type' => 'submit',
        '#value' => t('Import'),
      );
    }

    $form['country_table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No countries installed.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $files = $form_state->getValue('import_file');

    foreach ($files as $file) {
      $controller = new CountryController();
      if ($controller->import($file)) {
        drupal_set_message(t('Country file @file imported.', array('@file' => $file)));
      }
      else {
        drupal_set_message(t('Country file @file could not import or had no install function.', array('@file' => $file)), 'error');
      }
    }

    parent::submitForm($form, $form_state);
  }
}
