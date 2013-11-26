<?php

/**
 * @file
 * Contains \Drupal\uc_cart\Form\CartSettingsForm.
 */

namespace Drupal\uc_cart\Form;

use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure general shopping cart settings for this site.
 */
class CartSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'uc_cart_cart_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    //$config = $this->configFactory->get('uc_cart.settings');

    $form['cart-settings'] = array(
      '#type' => 'vertical_tabs',
      '#attached' => array(
        'js' => array(
          'vertical-tabs' => drupal_get_path('module', 'uc_cart') . 'js/uc_cart.admin.js',
        ),
      ),
    );

    $form['general'] = array(
      '#type' => 'details',
      '#title' => t('Basic settings'),
      '#group' => 'cart-settings',
    );

    $form['general']['uc_cart_add_item_msg'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display a message when a customer adds an item to their cart.'),
      '#default_value' => variable_get('uc_cart_add_item_msg', TRUE),
    );
    $form['general']['uc_add_item_redirect'] = array(
      '#type' => 'textfield',
      '#title' => t('Add to cart redirect'),
      '#description' => t('Enter the page to redirect to when a customer adds an item to their cart, or &lt;none&gt; for no redirect.'),
      '#default_value' => variable_get('uc_add_item_redirect', 'cart'),
      '#size' => 32,
      '#field_prefix' => url(NULL, array('absolute' => TRUE)),
    );

    $form['general']['uc_cart_empty_button'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show an "Empty cart" button on the cart page.'),
      '#default_value' => variable_get('uc_cart_empty_button', FALSE),
    );

    $form['general']['uc_minimum_subtotal'] = array(
      '#type' => 'uc_price',
      '#title' => t('Minimum order subtotal'),
      '#description' => t('Customers will not be allowed to check out if the subtotal of items in their cart is less than this amount.'),
      '#default_value' => variable_get('uc_minimum_subtotal', 0),
    );

    $form['lifetime'] = array(
      '#type' => 'details',
      '#title' => t('Cart lifetime'),
      '#description' => t('Set the length of time that products remain in the cart. Cron must be running for this feature to work.'),
      '#group' => 'cart-settings',
    );

    $durations = array(
      'singular' => array(
        'minutes' => t('minute'),
        'hours' => t('hour'),
        'days' => t('day'),
        'weeks' => t('week'),
        'years' => t('year'),
      ),
      'plural' => array(
        'minutes' => t('minutes'),
        'hours' => t('hours'),
        'days' => t('days'),
        'weeks' => t('weeks'),
        'years' => t('years'),
      ),
    );

    $form['lifetime']['anonymous'] = array(
      '#type' => 'details',
      '#title' => t('Anonymous users'),
      '#attributes' => array('class' => array('uc-inline-form', 'clearfix')),
    );
    $form['lifetime']['anonymous']['uc_cart_anon_duration'] = array(
      '#type' => 'select',
      '#title' => t('Duration'),
      '#options' => drupal_map_assoc(range(1, 60)),
      '#default_value' => variable_get('uc_cart_anon_duration', '4'),
    );
    $form['lifetime']['anonymous']['uc_cart_anon_unit'] = array(
      '#type' => 'select',
      '#title' => t('Units'),
      '#options' => array(
        'minutes' => t('Minute(s)'),
        'hours' => t('Hour(s)'),
        'days' => t('Day(s)'),
        'weeks' => t('Week(s)'),
        'years' => t('Year(s)'),
      ),
      '#default_value' => variable_get('uc_cart_anon_unit', 'hours'),
    );

    $form['lifetime']['authenticated'] = array(
      '#type' => 'details',
      '#title' => t('Authenticated users'),
      '#attributes' => array('class' => array('uc-inline-form', 'clearfix')),
    );
    $form['lifetime']['authenticated']['uc_cart_auth_duration'] = array(
      '#type' => 'select',
      '#title' => t('Duration'),
      '#options' => drupal_map_assoc(range(1, 60)),
      '#default_value' => variable_get('uc_cart_auth_duration', '1'),
    );
    $form['lifetime']['authenticated']['uc_cart_auth_unit'] = array(
      '#type' => 'select',
      '#title' => t('Units'),
      '#options' => array(
        'hours' => t('Hour(s)'),
        'days' => t('Day(s)'),
        'weeks' => t('Week(s)'),
        'years' => t('Year(s)'),
      ),
      '#default_value' => variable_get('uc_cart_auth_unit', 'years'),
    );

    $form['continue_shopping'] = array(
      '#type' => 'details',
      '#title' => t('Continue shopping element'),
      '#description' => t('These settings control the <em>continue shopping</em> option on the cart page.'),
      '#group' => 'cart-settings',
    );
    $form['continue_shopping']['uc_continue_shopping_type'] = array(
      '#type' => 'radios',
      '#title' => t('<em>Continue shopping</em> element'),
      '#options' => array(
        'link' => t('Text link'),
        'button' => t('Button'),
        'none' => t('Do not display'),
      ),
      '#default_value' => variable_get('uc_continue_shopping_type', 'link'),
    );
    $form['continue_shopping']['uc_continue_shopping_use_last_url'] = array(
      '#type' => 'checkbox',
      '#title' => t('Make <em>continue shopping</em> go back to the last item that was added to the cart.'),
      '#description' => t('If this is disabled or the item is unavailable, the URL specified below will be used.'),
      '#default_value' => variable_get('uc_continue_shopping_use_last_url', TRUE),
    );
    $form['continue_shopping']['uc_continue_shopping_url'] = array(
      '#type' => 'textfield',
      '#title' => t('Default <em>continue shopping</em> destination'),
      '#default_value' => variable_get('uc_continue_shopping_url', ''),
      '#size' => 32,
      '#field_prefix' => url(NULL, array('absolute' => TRUE)),
    );

    $form['breadcrumb'] = array(
      '#type' => 'details',
      '#title' => t('Cart breadcrumb'),
      '#description' => t('Drupal automatically adds a <em>Home</em> breadcrumb to the cart page, or you can use these settings to specify a custom breadcrumb.'),
      '#group' => 'cart-settings',
    );
    $form['breadcrumb']['uc_cart_breadcrumb_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Cart page breadcrumb text'),
      '#description' => t('Leave blank to use the default <em>Home</em> breadcrumb.'),
      '#default_value' => variable_get('uc_cart_breadcrumb_text', ''),
    );
    $form['breadcrumb']['uc_cart_breadcrumb_url'] = array(
      '#type' => 'textfield',
      '#title' => t('Cart page breadcrumb destination'),
      '#default_value' => variable_get('uc_cart_breadcrumb_url', ''),
      '#size' => 32,
      '#field_prefix' => url(NULL, array('absolute' => TRUE)),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    variable_set('uc_cart_add_item_msg', $form_state['values']['uc_cart_add_item_msg']);
    variable_set('uc_add_item_redirect', $form_state['values']['uc_add_item_redirect']);
    variable_set('uc_cart_empty_button', $form_state['values']['uc_cart_empty_button']);
    variable_set('uc_minimum_subtotal', $form_state['values']['uc_minimum_subtotal']);
    variable_set('uc_cart_anon_duration', $form_state['values']['uc_cart_anon_duration']);
    variable_set('uc_cart_anon_unit', $form_state['values']['uc_cart_anon_unit']);
    variable_set('uc_cart_auth_duration', $form_state['values']['uc_cart_auth_duration']);
    variable_set('uc_cart_auth_unit', $form_state['values']['uc_cart_auth_unit']);
    variable_set('uc_continue_shopping_type', $form_state['values']['uc_continue_shopping_type']);
    variable_set('uc_continue_shopping_use_last_url', $form_state['values']['uc_continue_shopping_use_last_url']);
    variable_set('uc_continue_shopping_url', $form_state['values']['uc_continue_shopping_url']);
    variable_set('uc_cart_breadcrumb_text', $form_state['values']['uc_cart_breadcrumb_text']);
    variable_set('uc_cart_breadcrumb_url', $form_state['values']['uc_cart_breadcrumb_url']);

    parent::submitForm($form, $form_state);
  }

}
