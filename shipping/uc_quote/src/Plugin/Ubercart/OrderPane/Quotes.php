<?php

/**
 * @file
 * Contains \Drupal\uc_quote\Plugin\Ubercart\OrderPane\Quotes.
 */

namespace Drupal\uc_quote\Plugin\Ubercart\OrderPane;

use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\EditableOrderPanePluginBase;
use Drupal\uc_order\OrderInterface;

/**
 * Get a shipping quote for the order from a quoting module.
 *
 * @UbercartOrderPane(
 *   id = "quotes",
 *   title = @Translation("Shipping quote"),
 *   weight = 7,
 * )
 */
class Quotes extends EditableOrderPanePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getClasses() {
    return 'pos-left';
  }

  /**
   * {@inheritdoc}
   */
  public function view(OrderInterface $order, $view_mode) {
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $form['quote_button'] = array(
      '#type' => 'submit',
      '#value' => t('Get shipping quotes'),
      '#submit' => array(array($this, 'retrieveQuotes')),
      '#ajax' => array(
        'callback' => 'uc_quote_replace_order_quotes',
        'wrapper' => 'quote',
        'effect' => 'slide',
        'progress' => array(
          'type' => 'bar',
          'message' => t('Receiving quotes...'),
        ),
      ),
    );
    $form['quotes'] = array(
      '#tree' => TRUE,
      '#prefix' => '<div id="quote">',
      '#suffix' => '</div>',
    );

    if ($form_state->get('quote_requested')) {
      // Rebuild form products, from uc_order_edit_form_submit()
      foreach ($form_state->getValue('products') as $product) {
        if (!isset($product['remove']) && intval($product['qty']) > 0) {
          foreach (array('qty', 'title', 'model', 'weight', 'weight_units', 'cost', 'price') as $field) {
            $order->products[$product['order_product_id']]->$field = $product[$field];
          }
        }
      }

      $form['quotes'] += uc_quote_build_quote_form($order);

      $form['quotes']['add_quote'] = array(
        '#type' => 'submit',
        '#value' => t('Apply to order'),
        '#submit' => array(array($this, 'applyQuote')),
        '#ajax' => array(
          'callback' => 'uc_quote_order_update_rates',
          'effect' => 'fade',
          'progress' => array(
            'type' => 'throbber',
            'message' => t('Applying quotes...'),
          ),
        ),
      );
    }

    $form_state->set(['uc_ajax', 'uc_quote', 'delivery][delivery_country'], array(
      'quote' => 'uc_quote_replace_order_quotes',
    ));

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(OrderInterface $order, array &$form, FormStateInterface $form_state) {
  }

  /**
   * Form submission handler to retrieve quotes.
   */
  public function retrieveQuotes($form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    $form_state->set('quote_requested', $element['#value'] == $form['quotes']['quote_button']['#value']);
    $form_state->setRebuild();
  }

  /**
   * Ajax callback: Manually applies a shipping quote to an order.
   */
  public function applyQuote($form, FormStateInterface $form_state) {
    if ($form_state->hasValue(['quotes', 'quote_option'])) {
      if ($order = $form_state->get('order')) {
        $quote_option = explode('---', $form_state->getValue(['quotes', 'quote_option']));
        $order->quote['method'] = $quote_option[0];
        $order->quote['accessorials'] = $quote_option[1];
        $method = ShippingQuoteMethod::load($quote_option[0]);
        $label = $method->label();

        $quote_option = $form_state->getValue(['quotes', 'quote_option']);
        $order->quote['rate'] = $form_state->getValue(['quotes', $quote_option, 'rate']);

        $result = db_query("SELECT line_item_id FROM {uc_order_line_items} WHERE order_id = :id AND type = :type", [':id' => $order->id(), ':type' => 'shipping']);
        if ($lid = $result->fetchField()) {
          uc_order_update_line_item($lid,
            $label,
            $order->quote['rate']
          );
          $form_state->set('uc_quote', array(
            'lid' => $lid,
            'title' => $label,
            'amount' => $order->quote['rate'],
          ));
        }
        else {
          uc_order_line_item_add($order->id(), 'shipping',
            $label,
            $order->quote['rate']
          );
        }

        // Save selected shipping
        uc_quote_uc_order_update($order);

        // Update line items.
        $order->line_items = $order->getLineItems();

        // @todo Still needed?
        $form_state->set('order', $order);

        $form_state->setRebuild();
        $form_state->set('quote_requested', FALSE);
      }
    }
  }

}
