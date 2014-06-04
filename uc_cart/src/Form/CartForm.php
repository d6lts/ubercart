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
    $form['#attached']['css'][] = drupal_get_path('module', 'uc_cart') . '/css/uc_cart.css';
    $cart_config = \Drupal::config('uc_cart.settings');

    $form['items'] = array(
      '#type' => 'table',
      '#tree' => TRUE,
      '#header' => array(
        'remove' => array(
          'data' => $this->t('Remove'),
          'class' => array('remove'),
        ),
        'image' => array(
          'data' => $this->t('Products'),
          'class' => array('image'),
        ),
        'desc' => array(
          'data' => '',
          'class' => array('desc'),
        ),
        'qty' => array(
          'data' => array('#theme' => 'uc_qty_label'),
          'class' => array('qty'),
        ),
        'total' => array(
          'data' => $this->t('Total'),
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
        $form['items'][$i]['desc']['title'] = $item['title'];
        $form['items'][$i]['desc']['description'] = $item['description'];
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
      '#prefix' => '<span id="subtotal-title">' . $this->t('Subtotal') . ':</span> ',
      '#price' => $subtotal,
      '#wrapper_attributes' => array(
        'colspan' => 5,
        'class' => array('subtotal'),
      ),
    );

    $form['actions'] = array('#type' => 'actions');

    // If the continue shopping element is enabled...
    if (($cs_type = $cart_config->get('continue_shopping_type')) !== 'none') {
      // Add the element to the form based on the element type.
      if ($cart_config->get('continue_shopping_type') == 'link') {
        $form['actions']['continue_shopping'] = array(
          '#markup' => l($this->t('Continue shopping'), $this->continueShoppingUrl()),
        );
      }
      elseif ($cart_config->get('continue_shopping_type') == 'button') {
        $form['actions']['continue_shopping'] = array(
          '#type' => 'submit',
          '#value' => $this->t('Continue shopping'),
          '#submit' => array(array($this, 'submitForm'), array($this, 'continueShopping')),
        );
      }
    }

    // Add the empty cart button if enabled.
    if ($cart_config->get('empty_button')) {
      $form['actions']['empty'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Empty cart'),
        '#submit' => array(array($this, 'emptyCart')),
      );
    }

    // Add the control buttons for updating and proceeding to checkout.
    $form['actions']['update'] = array(
      '#type' => 'submit',
      '#name' => 'update-cart',
      '#value' => $this->t('Update cart'),
      '#submit' => array(array($this, 'submitForm'), array($this, 'displayUpdateMessage')),
    );
    $form['actions']['checkout'] = array(
      '#theme' => 'uc_cart_checkout_buttons',
    );
    if ($cart_config->get('checkout_enabled')) {
      $form['actions']['checkout']['checkout'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Checkout'),
        '#button_type' => 'primary',
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
      drupal_set_message($this->t('<strong>!product-title</strong> removed from your shopping cart.', array('!product-title' => $form['data'][$item]['title']['#value'])));
    }

    // Update the items in the shopping cart based on the form values, but only
    // if a qty has changed.
    $module_handler = \Drupal::moduleHandler();
    foreach ($form_state['values']['items'] as $key => $item) {
      if (isset($form['items'][$key]['qty']['#default_value']) && $form['items'][$key]['qty']['#default_value'] != $item['qty']) {
        $module_handler->invoke($item['module'], 'uc_update_cart_item', array($item['nid'], unserialize($item['data']), $item['qty']));
      }
    }
  }

  /**
   * Displays "cart updated" message for the cart form.
   */
  public function displayUpdateMessage(array &$form, array &$form_state) {
    drupal_set_message($this->t('Your cart has been updated.'));
  }

  /**
   * Continue shopping redirect for the cart form.
   */
  public function continueShopping(array &$form, array &$form_state) {
    $form_state['redirect'] = $this->continueShoppingUrl();
  }

  /**
   * Empty cart redirect for the cart form.
   */
  public function emptyCart(array &$form, array &$form_state) {
    $form_state['redirect_route']['route_name'] = 'uc_cart.empty';
  }

  /**
   * Checkout redirect for the cart form.
   */
  public function checkout(array &$form, array &$form_state) {
    $form_state['redirect_route']['route_name'] = 'uc_cart.checkout';
  }

  /**
   * Returns the URL redirect for the continue shopping element.
   *
   * @return string
   *   The URL that will be used for the continue shopping element.
   */
  protected function continueShoppingUrl() {
    $cart_config = \Drupal::config('uc_cart.settings');
    $url = '';

    // Use the last URL if enabled and available.
    if ($cart_config->get('continue_shopping_use_last_url') && isset($_SESSION['uc_cart_last_url'])) {
      $url = $_SESSION['uc_cart_last_url'];
    }

    // If the URL is still empty, fall back to the default.
    if (empty($url)) {
      $url = $cart_config->get('continue_shopping_url');
    }

    unset($_SESSION['uc_cart_last_url']);

    return $url;
  }

}
