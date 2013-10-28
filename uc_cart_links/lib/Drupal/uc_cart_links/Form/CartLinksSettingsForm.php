<?php

/**
 * @file
 * Contains \Drupal\uc_cart_links\Form\CartLinksSettingsForm.
 */

namespace Drupal\uc_cart_links\Form;

use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure general shopping cart settings for this site.
 */
class CartLinksSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'uc_cart_links_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    //$config = $this->configFactory->get('uc_cart_links.settings');

    $form['uc_cart_links_add_show'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display the cart link product action when you add a product to your cart.'),
      '#default_value' => variable_get('uc_cart_links_add_show', FALSE),
    );
    $form['uc_cart_links_track'] = array(
      '#type' => 'checkbox',
      '#title' => t('Track clicks through Cart Links that specify tracking IDs.'),
      '#default_value' => variable_get('uc_cart_links_track', TRUE),
    );
    $form['uc_cart_links_empty'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow Cart Links to empty customer carts.'),
      '#default_value' => variable_get('uc_cart_links_empty', TRUE),
    );
    $form['uc_cart_links_messages'] = array(
      '#type' => 'textarea',
      '#title' => t('Cart Links messages'),
      '#description' => t('Enter messages available to the Cart Links API for display through a link. Separate messages with a line break. Each message should have a numeric key and text value, separated by "|". For example: 1337|Message text.'),
      '#default_value' => variable_get('uc_cart_links_messages', ''),
    );
    $form['uc_cart_links_restrictions'] = array(
      '#type' => 'textarea',
      '#title' => t('Cart Links restrictions'),
      '#description' => t('To restrict what Cart Links may be used on your site, enter all valid Cart Links in this textbox.  Separate links with a line break. Leave blank to permit any cart link.'),
      '#default_value' => variable_get('uc_cart_links_restrictions', ''),
    );
    $form['uc_cart_links_invalid_page'] = array(
      '#type' => 'textfield',
      '#title' => t('Invalid link redirect page'),
      '#description' => t('Enter the URL to redirect to when an invalid cart link is used.'),
      '#default_value' => variable_get('uc_cart_links_invalid_page', ''),
      '#size' => 32,
      '#field_prefix' => url(NULL, array('absolute' => TRUE)),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    variable_set('uc_cart_links_add_show', $form_state['values']['uc_cart_links_add_show']);
    variable_set('uc_cart_links_track', $form_state['values']['uc_cart_links_track']);
    variable_set('uc_cart_links_empty', $form_state['values']['uc_cart_links_empty']);
    variable_set('uc_cart_links_messages', $form_state['values']['uc_cart_links_messages']);
    variable_set('uc_cart_links_restrictions', $form_state['values']['uc_cart_links_restrictions']);
    variable_set('uc_cart_links_invalid_page', $form_state['values']['uc_cart_links_invalid_page']);

    parent::submitForm($form, $form_state);
  }

}
