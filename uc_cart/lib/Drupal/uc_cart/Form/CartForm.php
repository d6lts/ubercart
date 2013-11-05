<?php

/**
 * @file
 * Contains \Drupal\uc_cart\Form\CartForm..
 */

namespace Drupal\uc_cart\Form;

use Drupal\Core\Form\FormBase;

/**
 * Displays the contents of the customer's cart.
 *
 * Handles simple or complex objects. Some cart items may have a list of
 * products that they represent. These are displayed but are not able to
 * be changed by the customer.
 */
class CartForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_cart_view_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $items = NULL) {
    $form['#attached']['css'][] = drupal_get_path('module', 'uc_cart') . '/uc_cart.css';

    $form['items'] = array(
      '#type' => 'table',
      '#tree' => TRUE,
      '#header' => array(
        'remove' => array(
          'data' => t('Remove'),
          'class' => array('remove'),
        ),
        'image' => array(
          'data' => t('Products'),
          'class' => array('image'),
        ),
        'desc' => array(
          'data' => '',
          'class' => array('desc'),
        ),
        'qty' => array(
          'data' => theme('uc_qty_label'),
          'class' => array('qty'),
        ),
        'total' => array(
          'data' => t('Total'),
          'class' => array('price'),
        ),
      ),
    );

    $form['data'] = array(
      '#tree' => TRUE,
      '#parents' => array('items'),
    );

    $i = 0;
    $subtotal = 0;
    $display_items = entity_view_multiple($items, 'cart');
    foreach (element_children($display_items) as $key) {
      $item = $display_items[$key];
      if (element_children($item)) {
        $form['items'][$i]['remove'] = $item['remove'];
        $form['items'][$i]['remove']['#name'] = 'remove-' . $i;
        $form['items'][$i]['image'] = uc_product_get_picture($item['nid']['#value'], 'uc_cart');
        $form['items'][$i]['desc']['#markup'] = $item['title']['#markup'] . $item['description']['#markup'];
        $form['items'][$i]['qty'] = $item['qty'];
        $form['items'][$i]['total'] = array(
          '#theme' => 'uc_price',
          '#price' => $item['#total'],
          '#wrapper_attributes' => array('class' => 'total'),
        );
        if (!empty($item['#suffixes'])) {
          $form['items'][$i]['total']['#suffixes'] = $item['#suffixes'];
        }

        $form['data'][$i]['module'] = $item['module'];
        $form['data'][$i]['nid'] = $item['nid'];
        $form['data'][$i]['data'] = $item['data'];
        $form['data'][$i]['title'] = array(
          '#type' => 'value',
          '#value' => $item['title']['#markup'],
        );

        $subtotal += $item['#total'];
      }
      $i++;
    }

    $form['items'][]['total'] = array(
      '#theme' => 'uc_price',
      '#prefix' => '<span id="subtotal-title">' . t('Subtotal:') . '</span> ',
      '#price' => $subtotal,
      '#wrapper_attributes' => array(
        'colspan' => 5,
        'class' => array('subtotal'),
      ),
    );

    $form['actions'] = array('#type' => 'actions');

    // If the continue shopping element is enabled...
    if (($cs_type = variable_get('uc_continue_shopping_type', 'link')) !== 'none') {
      // Add the element to the form based on the element type.
      if (variable_get('uc_continue_shopping_type', 'link') == 'link') {
        $form['actions']['continue_shopping'] = array(
          '#markup' => l(t('Continue shopping'), uc_cart_continue_shopping_url()),
        );
      }
      elseif (variable_get('uc_continue_shopping_type', 'link') == 'button') {
        $form['actions']['continue_shopping'] = array(
          '#type' => 'submit',
          '#value' => t('Continue shopping'),
          '#submit' => array(array($this, 'submitForm'), array($this, 'continueShopping')),
        );
      }
    }

    // Add the empty cart button if enabled.
    if (variable_get('uc_cart_empty_button', FALSE)) {
      $form['actions']['empty'] = array(
        '#type' => 'submit',
        '#value' => t('Empty cart'),
        '#submit' => array(array($this, 'emptyCart')),
      );
    }

    // Add the control buttons for updating and proceeding to checkout.
    $form['actions']['update'] = array(
      '#type' => 'submit',
      '#name' => 'update-cart',
      '#value' => t('Update cart'),
      '#submit' => array(array($this, 'submitForm'), array($this, 'displayUpdateMessage')),
    );
    $form['actions']['checkout'] = array(
      '#theme' => 'uc_cart_checkout_buttons',
    );
    if (variable_get('uc_checkout_enabled', TRUE)) {
      $form['actions']['checkout']['checkout'] = array(
        '#type' => 'submit',
        '#value' => t('Checkout'),
        '#submit' => array(array($this, 'submitForm'), array($this, 'checkout')),
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    // If a remove button was clicked, set the quantity for that item to 0.
    if (substr($form_state['triggering_element']['#name'], 0, 7) == 'remove-') {
      $item = substr($form_state['triggering_element']['#name'], 7);
      $form_state['values']['items'][$item]['qty'] = 0;
      drupal_set_message(t('<strong>!product-title</strong> removed from your shopping cart.', array('!product-title' => $form['data'][$item]['title']['#value'])));
    }

    // Update the items in the shopping cart based on the form values, but only
    // if a qty has changed.
    foreach ($form['items'] as $key => $item) {
      if (isset($item['qty']['#default_value']) && $item['qty']['#default_value'] != $form_state['values']['items'][$key]['qty']) {
        uc_cart_update_item_object((object)$form_state['values']);
      }
    }
  }

  /**
   * Displays "cart updated" message for the cart form.
   */
  public function displayUpdateMessage(array &$form, array &$form_state) {
    drupal_set_message(t('Your cart has been updated.'));
  }

  /**
   * Continue shopping redirect for the cart form.
   */
  public function continueShopping(array &$form, array &$form_state) {
    $form_state['redirect'] = uc_cart_continue_shopping_url();
  }

  /**
   * Empty cart redirect for the cart form.
   */
  public function emptyCart(array &$form, array &$form_state) {
    $form_state['redirect'] = 'cart/empty';
  }

  /**
   * Checkout redirect for the cart form.
   */
  public function checkout(array &$form, array &$form_state) {
    $form_state['redirect'] = 'cart/checkout';
  }

}
