<?php

/**
 * @file
 * Contains \Drupal\uc_roles\Form\RoleFeatureForm.
 */

namespace Drupal\uc_roles\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

/**
 * Creates or edits a role feature for a product.
 */
class RoleFeatureForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_roles_feature_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL, $feature = NULL) {
    $models = uc_product_get_models($node->id());

    // Check if editing or adding to set default values.
    if (!empty($feature)) {
      $product_role = db_query("SELECT * FROM {uc_roles_products} WHERE pfid = :pfid", array(':pfid' => $feature['pfid']))->fetchObject();

      $default_model = $product_role->model;
      $default_role = $product_role->rid;
      $default_qty = $product_role->duration;
      $default_granularity = $product_role->granularity;
      $default_shippable = $product_role->shippable;
      $default_by_quantity = $product_role->by_quantity;
      if ($product_role->end_time) {
        $end_time = array(
          'day' => date('j', $product_role->end_time),
          'month' => date('n', $product_role->end_time),
          'year' => date('Y', $product_role->end_time),
        );
        $default_end_type = 'abs';
      }
      else {
        $temp = _uc_roles_get_expiration($default_qty, $default_granularity);
        $end_time = array(
          'day' => date('j', $temp),
          'month' => date('n', $temp),
          'year' => date('Y', $temp),
        );
        $default_end_type = 'rel';
      }

      $form['pfid'] = array(
        '#type' => 'value',
        '#value' => $feature['pfid'],
      );
      $form['rpid'] = array(
        '#type' => 'value',
        '#value' => $product_role->rpid,
      );

      $default_end_override = $product_role->end_override;
    }
    else {
      $default_model = 0;
      $default_role = variable_get('uc_roles_default_role', NULL);
      $default_qty = (variable_get('uc_roles_default_granularity', 'never') == 'never') ? NULL : variable_get('uc_roles_default_length', NULL);
      $default_granularity = variable_get('uc_roles_default_granularity', 'never');
      $default_shippable = $node->shippable;
      $default_by_quantity = variable_get('uc_roles_default_by_quantity', FALSE);
      $end_time = variable_get('uc_roles_default_end_time', array(
        'day' => date('j'),
        'month' => date('n'),
        'year' => date('Y'),
      ));
      $default_end_type = variable_get('uc_roles_default_end_expiration', 'rel');
      $default_end_override = FALSE;
    }

    $roles = _uc_roles_get_choices();
    if (!count($roles)) {
      // No actions can be done. Remove submit buttons.
      unset($form['buttons']);

      $form['no_roles'] = array(
        '#markup' => t('You need to <a href="!url">create new roles</a> before any can be added as product features.', array('!url' => url('admin/people/permissions/roles', array('query' => array('destination' => 'admin/store/settings/products'))))),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      );

      return $form;
    }

    $form['nid'] = array(
      '#type' => 'value',
      '#value' => $node->id(),
    );
    $form['uc_roles_model'] = array(
      '#type' => 'select',
      '#title' => t('SKU'),
      '#default_value' => $default_model,
      '#description' => t('This is the SKU of the product that will grant the role.'),
      '#options' => $models,
    );
    $form['uc_roles_role'] = array(
      '#type' => 'select',
      '#title' => t('Role'),
      '#default_value' => $default_role,
      '#description' => t('This is the role the customer will receive after purchasing the product.'),
      '#options' => $roles,
    );
    $form['uc_roles_shippable'] = array(
      '#type' => 'checkbox',
      '#title' => t('Shippable product'),
      '#default_value' => $default_shippable,
      '#description' => t('Check if this product SKU that uses role assignment is associated with a shippable product.'),
    );

    $form['end_override'] = array(
      '#type' => 'checkbox',
      '#title' => t('Override the <a href="!url">default role expiration</a>.', array('!url' => url('admin/store/settings/products'))),
      '#default_value' => $default_end_override,
    );

    $form['role_lifetime'] = array(
      '#type' => 'fieldset',
      '#title' => t('Role expiration'),
      '#states' => array(
        'visible' => array('input[name="end_override"]' => array('checked' => TRUE)),
      ),
    );
    $form['role_lifetime']['expiration'] = array(
      '#type' => 'select',
      '#title' => t('Expiration type'),
      '#options' => array(
        'rel' => t('Relative to purchase date'),
        'abs' => t('Fixed date'),
      ),
      '#default_value' => $default_end_type,
    );
    $form['role_lifetime']['uc_roles_expire_relative_duration'] = array(
      '#type' => 'textfield',
      '#default_value' => $default_qty,
      '#size' => 4,
      '#maxlength' => 4,
      '#prefix' => '<div class="expiration">',
      '#suffix' => '</div>',
      '#states' => array(
        'visible' => array('select[name="expiration"]' => array('value' => 'rel')),
        'invisible' => array('select[name="uc_roles_expire_relative_granularity"]' => array('value' => 'never')),
      ),
    );
    $form['role_lifetime']['uc_roles_expire_relative_granularity'] = array(
      '#type' => 'select',
      '#options' => array(
        'never' => t('never'),
        'day' => t('day(s)'),
        'week' => t('week(s)'),
        'month' => t('month(s)'),
        'year' => t('year(s)')
      ),
      '#default_value' => $default_granularity,
      '#description' => t('From the time the role was purchased.'),
      '#prefix' => '<div class="expiration">',
      '#suffix' => '</div>',
      '#states' => array(
        'visible' => array('select[name="expiration"]' => array('value' => 'rel')),
      ),
    );
    $form['role_lifetime']['absolute'] = array(
      '#type' => 'container',
      '#states' => array(
        'visible' => array('select[name="expiration"]' => array('value' => 'abs')),
      ),
    );
    $form['role_lifetime']['absolute']['uc_roles_expire_absolute'] = array(
      '#type' => 'date',
      '#description' => t('Expire the role at the beginning of this day.'),
    );
    if ($end_time) {
      $form['role_lifetime']['absolute']['uc_roles_expire_absolute']['#default_value'] = $end_time;
    }
    $form['role_lifetime']['uc_roles_by_quantity'] = array(
      '#type' => 'checkbox',
      '#title' => t('Multiply by quantity'),
      '#default_value' => $default_by_quantity,
      '#description' => t('Check if the role duration should be multiplied by the quantity purchased.'),
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save feature'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Invalid quantity?
    if ($form_state['values']['expiration'] === 'abs') {
      $form_state['values']['uc_roles_expire_absolute'] = mktime(0, 0, 0,
        $form_state['values']['uc_roles_expire_absolute']['month'],
        $form_state['values']['uc_roles_expire_absolute']['day'],
        $form_state['values']['uc_roles_expire_absolute']['year']
      );

      if ($form_state['values']['uc_roles_expire_absolute'] <= REQUEST_TIME) {
        form_set_error('uc_roles_expire_absolute', $form_state, t('The specified date !date has already occurred. Please choose another.', array('!date' => format_date($form_state['values']['uc_roles_expire_absolute']))));
      }
    }
    else {
      if ($form_state['values']['uc_roles_expire_relative_granularity'] != 'never' && intval($form_state['values']['uc_roles_expire_relative_duration']) < 1) {
        form_set_error('uc_roles_expire_relative_duration', $form_state, t('The amount of time must be a positive integer.'));
      }
    }

    // No roles?
    if (empty($form_state['values']['uc_roles_role'])) {
      form_set_error('uc_roles_role', $form_state, t('You must have a role to assign. You may need to <a href="!role_url">create a new role</a> or perhaps <a href="!feature_url">set role assignment defaults</a>.', array('!role_url' => url('admin/people/permissions/roles'), '!feature_url' => url('admin/store/settings/products'))));
    }

    // This role already set on this SKU?
    if (!isset($form_state['values']['pfid']) && ($product_roles = db_query("SELECT * FROM {uc_roles_products} WHERE nid = :nid AND model = :model AND rid = :rid", array(':nid' => $form_state['values']['nid'], ':model' => $form_state['values']['uc_roles_model'], ':rid' => $form_state['values']['uc_roles_role']))->fetchObject())) {
      form_set_error('uc_roles_role', $form_state, t('The combination of SKU and role already exists for this product.'));
      form_set_error('uc_roles_model', $form_state, ' ');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $product_role = array(
      'pfid'        => isset($form_state['values']['pfid']) ? $form_state['values']['pfid'] : NULL,
      'rpid'        => isset($form_state['values']['rpid']) ? $form_state['values']['rpid'] : NULL,
      'nid'         => $form_state['values']['nid'],
      'model'       => $form_state['values']['uc_roles_model'],
      'rid'         => $form_state['values']['uc_roles_role'],
      'duration'    => $form_state['values']['uc_roles_expire_relative_granularity'] != 'never' ? $form_state['values']['uc_roles_expire_relative_duration'] : NULL,
      'granularity' => $form_state['values']['uc_roles_expire_relative_granularity'],
      'by_quantity' => $form_state['values']['uc_roles_by_quantity'],
      'shippable'   => $form_state['values']['uc_roles_shippable'],

      // We should be setting NULL, but drupal_write_record() ...
      'end_override' => $form_state['values']['end_override'],
      'end_time'     => $form_state['values']['expiration'  ] === 'abs' ? $form_state['values']['uc_roles_expire_absolute'] : NULL,
    );

    $description = empty($product_role['model']) ? t('<strong>SKU:</strong> Any<br />') : t('<strong>SKU:</strong> !sku<br />', array('!sku' => $product_role['model']));
    $description .=  t('<strong>Role:</strong> @role_name<br />', array('@role_name' => _uc_roles_get_name($product_role['rid'])));

    if ($product_role['end_override']) {
      if ($product_role['end_time']) {
        $description .= t('<strong>Expiration:</strong> !date<br />', array('!date' => format_date($product_role['end_time'])));
      }
      else {
        switch ($product_role['granularity']) {
          case 'never':
            $description .= t('<strong>Expiration:</strong> never<br />');
            break;
          case 'day':
            $description .= t('<strong>Expiration:</strong> !qty day(s)<br />', array('!qty' => $product_role['duration']));
            break;
          case 'week':
            $description .= t('<strong>Expiration:</strong> !qty week(s)<br />', array('!qty' => $product_role['duration']));
            break;
          case 'month':
            $description .= t('<strong>Expiration:</strong> !qty month(s)<br />', array('!qty' => $product_role['duration']));
            break;
          case 'year':
            $description .= t('<strong>Expiration:</strong> !qty year(s)<br />', array('!qty' => $product_role['duration']));
            break;
          default:
            break;
        }
      }
    }
    else {
      $description .= t('<strong>Expiration:</strong> !link (not overridden)<br />', array('!link' => l(t('Global expiration'), 'admin/store/settings/products')));
    }
    $description .= $product_role['shippable'] ? t('<strong>Shippable:</strong> Yes<br />') : t('<strong>Shippable:</strong> No<br />');
    $description .= $product_role['by_quantity'] ? t('<strong>Multiply by quantity:</strong> Yes') : t('<strong>Multiply by quantity:</strong> No');

    $data = array(
      'pfid' => $product_role['pfid'],
      'nid' => $product_role['nid'],
      'fid' => 'role',
      'description' => $description,
    );

    $form_state['redirect'] = uc_product_feature_save($data);

    $product_role['pfid'] = $data['pfid'];

    // Insert or update uc_file_product table.
    foreach (array('duration', 'granularity', 'end_time') as $property) {
      $product_role[$property] = $product_role[$property] === NULL ? 0 : $product_role[$property];
    }

    $key = array();
    if ($product_role['rpid']) {
      $key = 'rpid';
    }

    drupal_write_record('uc_roles_products', $product_role, $key);
  }

}
