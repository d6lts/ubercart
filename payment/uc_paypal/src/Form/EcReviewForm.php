<?php

/**
 * @file
 * Contains \Drupal\uc_paypal\src\Form\EcReviewForm.
 */

namespace Drupal\uc_paypal\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Returns the form for the custom Review Payment screen for Express Checkout.
 */
class EcReviewForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_paypal_ec_review_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $order) {
    $paypal_config = $this->config('uc_paypal.settings');
    if (\Drupal::moduleHandler()->moduleExists('uc_quote') && $paypal_config->get('ec_review_shipping') && $order->isShippable()) {
      uc_checkout_pane_quotes('prepare', $order, NULL);
      $order->line_items = $order->getLineItems();
      $order->save();

      $result = uc_checkout_pane_quotes('view', $order, NULL);
      $form['panes']['quotes'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Shipping cost'),
      );
      $form['panes']['quotes'] += $result['contents'];
      unset($form['panes']['quotes']['quote_button']);

      $form['shippable'] = array('#type' => 'value', '#value' => 'true');
    }

    if ($paypal_config->get('ec_review_company')) {
      $form['delivery_company'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Company'),
        '#description' => $order->isShippable() ? $this->t('Leave blank if shipping to a residence.') : '',
        '#default_value' => $order->delivery_company,
      );
    }

    if ($paypal_config->get('ec_review_phone')) {
      $form['delivery_phone'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Contact phone number'),
        '#default_value' => $order->delivery_phone,
        '#size' => 24,
      );
    }

    if ($paypal_config->get('ec_review_comment')) {
      $form['order_comments'] = array(
        '#type' => 'textarea',
        '#title' => $this->t('Order comments'),
        '#description' => $this->t('Special instructions or notes regarding your order.'),
      );
    }

    if (empty($form)) {
      $this->redirect('uc_cart.ec_submit');
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Continue checkout'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->isValueEmpty('shippable') && $form_state->isValueEmpty(['quotes', 'quote_option'])) {
      $form_state->setErrorByName('shipping', $this->t('You must calculate and select a shipping option.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $paypal_config = $this->config('uc_paypal.settings');
    $session = \Drupal::service('session');
    $order = Order::load($session->get('cart_order'));

    if (!$form_state->isValueEmpty('shippable')) {
      $quote_option = explode('---', $form_state->getValue(['quotes', 'quote_option']));
      $order->quote['method'] = $quote_option[0];
      $order->quote['accessorials'] = $quote_option[1];
      $method = ShippingQuoteMethod::load($quote_option[0]);


      $label = $method['quote']['accessorials'][$quote_option[1]];
//      $label = $method->label();

      $quote_option = $form_state->getValue(['quotes', 'quote_option']);
      $order->quote['rate'] = $form_state->getValue(['quotes', $quote_option, 'rate']);

      $result = db_query("SELECT line_item_id FROM {uc_order_line_items} WHERE order_id = :id AND type = :type", [':id' => $order->id(), ':type' => 'shipping']);
      if ($lid = $result->fetchField()) {
        uc_order_update_line_item($lid, $label, $order->quote['rate']);
      }
      else {
        uc_order_line_item_add($order->id(), 'shipping', $label, $order->quote['rate']);
      }
    }

    if ($paypal_config->get('ec_review_company')) {
      $order->delivery_company = $form_state->getValue('delivery_company');
    }

    if ($paypal_config->get('ec_review_phone')) {
      $order->delivery_phone = $form_state->getValue('delivery_phone');
    }

    if ($paypal_config->get('ec_review_comment')) {
      db_delete('uc_order_comments')
        ->condition('order_id', $order->id())
        ->execute();
      uc_order_comment_save($order->id(), 0, $form_state->getValue('order_comments'), 'order');
    }

    $order->save();

    $form_state->setRedirect('uc_paypal.ec_submit');
  }

}
