<?php

/**
 * @file
 * Contains \Drupal\uc_role\Form\RoleSettingsForm.
 */

namespace Drupal\uc_role\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * Grants roles upon accepted payment of products.
 *
 * The uc_role module will grant specified roles upon purchase of specified
 * products. Granted roles can be set to have a expiration date. Users can also
 * be notified of the roles they are granted and when the roles will
 * expire/need to be renewed/etc.
 */
class RoleSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'uc_role_feature_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'uc_role.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $default_role_choices = user_role_names(TRUE);
    unset($default_role_choices[DRUPAL_AUTHENTICATED_RID]);
    $roles_config = $this->config('uc_role.settings');

    if (!count($default_role_choices)) {
      $form['no_roles'] = array(
        '#markup' => t('You need to <a href="!url">create new roles</a> before any can be added as product features.', array('!url' => \Drupal::url('user.role_add', [], ['query' => ['destination' => 'admin/store/settings/products']]))),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      );

      return $form;
    }

    $form['uc_role_default_role'] = array(
      '#type' => 'select',
      '#title' => t('Default role'),
      '#default_value' => $roles_config->get('default_role'),
      '#description' => t('The default role Ubercart grants on specified products.'),
      '#options' => _uc_role_get_choices(),
    );
    $form['uc_role_default_role_choices'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Product roles'),
      '#default_value' => $roles_config->get('default_role_choices'),
      '#multiple' => TRUE,
      '#description' => t('These are roles that Ubercart can grant to customers who purchase specified products. If you leave all roles unchecked, they will all be eligible for adding to a product.'),
      '#options' => $default_role_choices,
    );

    $form['role_lifetime'] = array(
      '#type' => 'fieldset',
      '#title' => t('Default role expiration'),
    );

    $form['role_lifetime']['uc_role_default_end_expiration'] = array(
      '#type' => 'select',
      '#title' => t('Expiration type'),
      '#options' => array(
        'rel' => t('Relative to purchase date'),
        'abs' => t('Fixed date'),
      ),
      '#default_value' => $roles_config->get('default_end_expiration'),
    );
    $form['role_lifetime']['uc_role_default_length'] = array(
      '#type' => 'textfield',
      '#default_value' => ($roles_config->get('default_granularity') == 'never') ? NULL : $roles_config->get('default_length'),
      '#size' => 4,
      '#maxlength' => 4,
      '#prefix' => '<div class="expiration">',
      '#suffix' => '</div>',
      '#states' => array(
        'visible' => array('select[name="uc_role_default_end_expiration"]' => array('value' => 'rel')),
        'invisible' => array('select[name="uc_role_default_granularity"]' => array('value' => 'never')),
      ),
    );
    $form['role_lifetime']['uc_role_default_granularity'] = array(
      '#type' => 'select',
      '#default_value' => $roles_config->get('default_granularity'),
      '#options' => array(
        'never' => t('never'),
        'day' => t('day(s)'),
        'week' => t('week(s)'),
        'month' => t('month(s)'),
        'year' => t('year(s)')
      ),
      '#description' => t('From the time the role was purchased.'),
      '#prefix' => '<div class="expiration">',
      '#suffix' => '</div>',
      '#states' => array(
        'visible' => array('select[name="uc_role_default_end_expiration"]' => array('value' => 'rel')),
      ),
    );
    $form['role_lifetime']['absolute'] = array(
      '#type' => 'container',
      '#states' => array(
        'visible' => array('select[name="uc_role_default_end_expiration"]' => array('value' => 'abs')),
      ),
    );
    $form['role_lifetime']['absolute']['uc_role_default_end_time'] = array(
      '#type' => 'date',
      '#description' => t('Expire the role at the beginning of this day.'),
      '#default_value' => $roles_config->get('default_end_time'),
    );
    $form['role_lifetime']['uc_role_default_by_quantity'] = array(
      '#type' => 'checkbox',
      '#title' => t('Multiply by quantity'),
      '#description' => t('Check if the role duration should be multiplied by the quantity purchased.'),
      '#default_value' => $roles_config->get('default_by_quantity'),
    );
    $form['reminder']['uc_role_reminder_length'] = array(
      '#type' => 'textfield',
      '#title' => t('Time before reminder'),
      '#default_value' => ($roles_config->get('reminder_granularity') == 'never') ? NULL : $roles_config->get('reminder_length'),
      '#size' => 4,
      '#maxlength' => 4,
      '#prefix' => '<div class="expiration">',
      '#suffix' => '</div>',
      '#states' => array(
        'disabled' => array('select[name="uc_role_reminder_granularity"]' => array('value' => 'never')),
      ),
    );
    $form['reminder']['uc_role_reminder_granularity'] = array(
      '#type' => 'select',
      '#default_value' => $roles_config->get('reminder_granularity'),
      '#options' => array(
        'never' => t('never'),
        'day' => t('day(s)'),
        'week' => t('week(s)'),
        'month' => t('month(s)'),
        'year' => t('year(s)')
      ),
      '#description' => t('The amount of time before a role expiration takes place that a customer is notified of its expiration.'),
      '#prefix' => '<div class="expiration">',
      '#suffix' => '</div>',
    );
    $form['uc_role_default_show_expiration'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show expirations on user page'),
      '#default_value' => $roles_config->get('default_show_expiration'),
      '#description' => t('If users have any role expirations they will be displayed on their account page.'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $roles_config = $this->config('uc_role.settings');
    $roles_config
      ->set('default_role', $form_state->getValue('uc_role_default_role'))
      ->set('default_role_choices', $form_state->getValue('uc_role_default_role_choices'))
      ->set('default_end_expiration', $form_state->getValue('uc_role_default_end_expiration'))
      ->set('default_length', $form_state->getValue('uc_role_default_length'))
      ->set('default_granularity', $form_state->getValue('uc_role_default_granularity'))
      ->set('default_end_time', $form_state->getValue('uc_role_default_end_time'))
      ->set('default_by_quantity', $form_state->getValue('uc_role_default_by_quantity'))
      ->set('reminder_length', $form_state->getValue('uc_role_reminder_length'))
      ->set('reminder_granularity', $form_state->getValue('uc_role_reminder_granularity'))
      ->set('default_show_expiration', $form_state->getValue('uc_role_default_show_expiration'))
      ->set('default_expiration_header', $form_state->getValue('uc_role_default_expiration_header'))
      ->set('default_expiration_title', $form_state->getValue('uc_role_default_expiration_title'))
      ->set('default_expiration_message', $form_state->getValue('uc_role_default_expiration_message'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
