<?php

/**
 * @file
 * Callbacks for the default Ubercart checkout panes plus helper functions.
 *
 * Checkout panes are defined using hook_uc_checkout_pane() and use a callback
 * to handle the different processes involved in completing the checkout form.
 * The default checkout panes are defined in uc_cart_uc_checkout_pane() in
 * uc_cart.module.
 */

use Drupal\uc_store\Address;

/**
 * Gets the delivery information.
 */
function uc_checkout_pane_delivery($op, $order, $form = NULL, &$form_state = NULL) {
  $description = t('Enter your delivery address and information here.');
  $copy = t('My delivery information is the same as my billing information.');
  return uc_checkout_pane_address('delivery', $op, $order, $form_state, $description, $copy);
}

/**
 * Gets the billing information.
 */
function uc_checkout_pane_billing($op, $order, $form = NULL, &$form_state = NULL) {
  $description = t('Enter your billing address and information here.');
  $copy = t('My billing information is the same as my delivery information.');
  return uc_checkout_pane_address('billing', $op, $order, $form_state, $description, $copy);
}

/**
 * Generic address pane handler.
 */
function uc_checkout_pane_address($pane, $op, $order, &$form_state, $description, $copy) {
  $user = \Drupal::currentUser();

  // Source pane for "copy address" checkbox.
  static $source;
  if (!isset($source)) {
    $source = $pane;
  }
 
  $cart_config = \Drupal::config('uc_cart.settings');
  switch ($op) {
    case 'view':
      if ($source != $pane) {
        $contents['copy_address'] = array(
          '#type' => 'checkbox',
          '#title' => $copy,
          '#default_value' => $cart_config->get('default_same_address'),
          '#ajax' => array(
            'callback' => 'uc_checkout_pane_address_render',
            'wrapper' => $pane . '-address-pane',
            'progress' => array(
              'type' => 'throbber',
            ),
          ),
        );
      }

      if ($user->isAuthenticated() && $addresses = uc_select_addresses($user->id(), $pane)) {
        $contents['select_address'] = array(
          '#type' => 'select',
          '#title' => t('Saved addresses'),
          '#options' => $addresses['#options'],
          '#ajax' => array(
            'callback' => 'uc_checkout_pane_address_render',
            'wrapper' => $pane . '-address-pane',
            'progress' => array(
              'type' => 'throbber',
            ),
          ),
          '#states' => array(
            'invisible' => array(
              'input[name="panes[' . $pane . '][copy_address]"]' => array('checked' => TRUE),
            ),
          ),
        );
      }

      $contents['address'] = array(
        '#type' => 'uc_address',
        '#default_value' => $order->getAddress($pane),
        '#prefix' => '<div id="' . $pane . '-address-pane">',
        '#suffix' => '</div>',
      );

      if (isset($form_state['values']['panes'][$pane]['copy_address'])) {
        $contents['address']['#hidden'] = !empty($form_state['values']['panes'][$pane]['copy_address']);
      }
      elseif (isset($contents['copy_address'])) {
        $contents['address']['#hidden'] = $cart_config->get('default_same_address');
      }

      if (isset($form_state['triggering_element'])) {
        $element = &$form_state['triggering_element'];

        if ($element['#name'] == "panes[$pane][copy_address]") {
          $address = &$form_state['values']['panes'][$source];
          foreach ($address as $field => $value) {
            if (substr($field, 0, strlen($source)) == $source) {
              $field = str_replace($source, $pane, $field);
              $form_state['input']['panes'][$pane][$field] = $value;
              $order->$field = $value;
            }
          }
        }

        if ($element['#name'] == "panes[$pane][select_address]") {
          $address = $addresses[$element['#value']];
          foreach ($address as $field => $value) {
            $form_state['input']['panes'][$pane][$pane . '_' . $field] = $value;
            $order->{$pane . '_' . $field} = $value;
          }
        }

        // Forget any previous Ajax submissions, as we send new default values.
        unset($form_state['uc_address']);
      }

      return array('description' => $description, 'contents' => $contents);

    case 'process':
      $address = new Address;
      $panes = &$form_state['values']['panes'];
      foreach ($panes[$pane] as $field => $value) {
        if (isset($address->$field)) {
          if (!empty($panes[$pane]['copy_address'])) {
            $address->$field = $panes[$source][$field];
          }
          else {
            $address->$field = $value;
          }
        }
      }
      $order->setAddress($pane, $address);
      return TRUE;

    case 'review':
      $address = $order->getAddress($pane);
      $review[] = array('title' => t('Address'), 'data' => $address);
      if (uc_address_field_enabled('phone') && !empty($address->phone)) {
        $review[] = array('title' => t('Phone'), 'data' => check_plain($address->phone));
      }
      return $review;
  }
}

/**
 * Ajax callback to re-render the full address element.
 */
function uc_checkout_pane_address_render($form, &$form_state) {
  $element = &$form;
  foreach (array_slice($form_state['triggering_element']['#array_parents'], 0, -1) as $field) {
    $element = &$element[$field];
  }
  return $element['address'];
}

/**
 * Formats the cart contents table on the checkout page.
 *
 * @param $variables
 *   An associative array containing:
 *   - show_subtotal: TRUE or FALSE indicating if you want a subtotal row
 *     displayed in the table.
 *   - items: An associative array of cart item information containing:
 *     - qty: Quantity in cart.
 *     - title: Item title.
 *     - price: Item price.
 *     - desc: Item description.
 *
 * @return
 *   The HTML output for the cart review table.
 *
 * @ingroup themeable
 */
function theme_uc_cart_review_table($variables) {
  $items = $variables['items'];
  $show_subtotal = $variables['show_subtotal'];

  $subtotal = 0;

  // Set up table header.
  $header = array(
    array('data' => theme('uc_qty_label'), 'class' => array('qty')),
    array('data' => t('Products'), 'class' => array('products')),
    array('data' => t('Price'), 'class' => array('price')),
  );

  // Set up table rows.
  $display_items = entity_view_multiple($items, 'cart');
  if (!empty($display_items)) {
    foreach (element_children($display_items) as $key) {
      $display_item = $display_items[$key];
      $subtotal += $display_item['total']['#price'];
      $rows[] = array(
        array('data' => $display_item['qty'], 'class' => array('qty')),
        array('data' => $display_item['product'], 'class' => array('products')),
        array('data' => $display_item['total'], 'class' => array('price')),
      );
    }
  }

  // Add the subtotal as the final row.
  if ($show_subtotal) {
    $rows[] = array(
      'data' => array(
        // One cell
        array(
          'data' => array(
            '#theme' => 'uc_price',
            '#prefix' => '<span id="subtotal-title">' . t('Subtotal:') . '</span> ',
            '#price' => $subtotal,
          ),
          // Cell attributes
          'colspan' => 3,
          'class' => array('subtotal'),
        ),
      ),
      // Row attributes
      'class' => array('subtotal'),
    );
  }

  return theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('class' => array('cart-review'))));
}
