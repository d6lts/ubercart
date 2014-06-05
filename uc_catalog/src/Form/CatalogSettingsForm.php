<?php

/**
 * @file
 * Contains \Drupal\uc_catalog\Form\CatalogSettingsForm.
 */

namespace Drupal\uc_catalog\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\views\Views;

/**
 * Configure catalog settings for this site.
 */
class CatalogSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'uc_catalog_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->config('uc_catalog.settings');

    $view = Views::getView('uc_catalog');
    $displays = array();
    foreach ($view->display as $display) {
      if ($display->display_plugin == 'page') {
        $displays[$display->id] = $display->display_title;
      }
    }

    $form['uc_catalog_display'] = array(
      '#type' => 'select',
      '#title' => t('Catalog display'),
      '#default_value' => $config->get('display'),
      '#options' => $displays,
    );

    $vid = $config->get('vocabulary');
    if ($vid) {
      $catalog = entity_load('taxonomy_vocabulary', $vid);

      $form['catalog_vid'] = array(
        '#markup' => '<p>' . t('The taxonomy vocabulary <a href="!edit-url">%name</a> is set as the product catalog.', array('!edit-url' => url('admin/structure/taxonomy/manage/' . $catalog->id()), '%name' => $catalog->label())) . '</p>',
      );
    }

    $vocabs = array();
    $vocabularies = entity_load_multiple('taxonomy_vocabulary');
    foreach ($vocabularies as $vid => $vocabulary) {
      $vocabs[$vid] = $vocabulary->label();
    }

    $form['uc_catalog_vid'] = array(
      '#type' => 'select',
      '#title' => t('Catalog vocabulary'),
      '#default_value' => $config->get('vocabulary'),
      '#options' => $vocabs,
    );
    $form['uc_catalog_breadcrumb'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display the catalog breadcrumb'),
      '#default_value' => $config->get('breadcrumb'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->config('uc_catalog.settings')
      ->set('display', $form_state['values']['uc_catalog_display'])
      ->set('vocabulary', $form_state['values']['uc_catalog_vid'])
      ->set('breadcrumb', $form_state['values']['uc_catalog_breadcrumb'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
