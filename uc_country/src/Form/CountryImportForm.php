<?php

/**
 * @file
 * Contains \Drupal\uc_country\Form\CountryImportForm.
 */

namespace Drupal\uc_country\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_country\Controller\CountryController;

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
    $countries = [];
    $result = db_query('SELECT country_id, country_name, country_iso_code_3, version FROM {uc_countries}');
    foreach ($result as $country) {
      $countries[t($country->country_name)] = $country;
    }
    uksort($countries, 'strnatcasecmp');
    $files = uc_country_import_list();

    $header = array(t('Country'), t('Code'), t('Version'), t('Operations'));
    $rows = [];
    if (is_array($countries)) {
      foreach ($countries as $country) {
        // Each row has country name, ISO code, version, and enable/disable/remove/update operations.
        $row = array(
          'country' => t($country->country_name),
          'country_iso_3' => $country->country_iso_code_3,
          'version' => array('data' => abs($country->version), 'align' => 'center')
        );

        $ops = [];
        if ($country->version < $files[$country->country_id]['version'] && $country->version > 0) {
          // Provide visual indicator that version needs to be updated.
          $row['version']['data'] .= '*';
          $caption = t('An asterisk "*" next to the version indicates the country file is not current and should be updated.');
          $ops['update'] = array(
            'title' => $this->t('Update'),
            'url' => Url::fromRoute('uc_country.update', ['country_id' => $country->country_id, 'version' =>  $files[$country->country_id]['version']]),
          );
        }
        if ($country->version < 0) {
          $ops['enable'] = array(
            'title' => $this->t('Enable'),
            'url' => Url::fromRoute('uc_country.enable', ['country_id' => $country->country_id]),
          );
        }
        else {
          $ops['disable'] = array(
            'title' => $this->t('Disable'),
            'url' => Url::fromRoute('uc_country.disable', ['country_id' => $country->country_id]),
          );
        }
        $ops['remove'] = array(
          'title' => $this->t('Remove'),
          'url' => Url::fromRoute('uc_country.remove', ['country_id' => $country->country_id]),
        );
        $row[] = array(
          'data' => array(
            '#type' => 'operations',
            '#links' => $ops,
          ),
        );

        $rows[] = $row;
        unset($files[$country->country_id]);
      }
    }

    $import_list = [];
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
        '#markup' => '<p>' . t('To import new country data, select it in the list and click the import button. If you hold down the Ctrl key, you may select and install more than one country at a time.') . '</p>',
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
      '#caption' => isset($caption) ? $caption : NULL,
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
        drupal_set_message(t('Country file @file imported.', ['@file' => $file]));
      }
      else {
        drupal_set_message(t('Country file @file could not import or had no install function.', ['@file' => $file]), 'error');
      }
    }

    parent::submitForm($form, $form_state);
  }
}
