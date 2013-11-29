<?php

/**
 * @file
 * Theme functions for the uc_cart module.
 */

use Drupal\Core\Template\Attribute;

/**
 * Themes the shopping cart block title.
 *
 * @param $variables
 *   An associative array containing:
 *   - title: The text to use for the title of the block.
 *   - show_icon: TRUE if the cart icon is to be displayed.
 *   - empty: TRUE if the cart is empty.
 *   - collapsible: TRUE if the cart block is collapsible.
 *   - collapsed: TRUE if the cart block is collapsed initially.
 *
 * @ingroup themeable
 */
function theme_uc_cart_block_title($variables) {
  $output = '';

  // Add in the cart image if specified.
  if ($variables['show_icon']) {
    $icon_attributes['title'][] = t('View your shopping cart.');
    $icon_attributes['class'][] = 'cart-block-icon';
    if ($variables['cart_empty']) {
      $icon_attributes['class'][] = 'empty';
    }
    $output .= l('<span ' . new Attribute($icon_attributes) . '></span>', 'cart', array('html' => TRUE));
  }

  $attributes = array();
  if ($variables['collapsible']) {
    $attributes['title'] = t('Show/hide shopping cart contents.');
    $attributes['class'][] = 'cart-block-arrow';
    if ($variables['collapsed']) {
      $attributes['class'][] = 'collapsed';
    }
  }
  $output .= '<span ' . new Attribute($attributes) . '>' . $variables['title'] . '</span>';

  return $output;
}

/**
 * Themes the cart form.
 *
 * Outputs a hidden copy of the update cart button first, so pressing Enter
 * updates the cart instead of removing an item.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @see \Drupal\uc_cart\Form\CartForm
 * @ingroup themeable
 */
function theme_uc_cart_view_form($variables) {
  $form = &$variables['form'];

  $output = '<div class="uc-default-submit">';
  $output .= drupal_render($form['actions']['update']);
  $output .= '</div>';
  $form['actions']['update']['#printed'] = FALSE;

  $output .= drupal_render_children($form);

  return $output;
}

/**
 * Themes the cart checkout button(s).
 *
 * @param $variables
 *   An associative array containing:
 *   - buttons: A render element representing the form buttons.
 *
 * @see \Drupal\uc_cart\Form\CartForm
 * @ingroup themeable
 */
function theme_uc_cart_checkout_buttons($variables) {
  $output = '';

  if ($buttons = element_children($variables['buttons'])) {
    // Render the first button.
    $button = array_shift($buttons);
    $output = drupal_render($variables['buttons'][$button]);

    // Render any remaining buttons inside a separate container.
    if ($buttons) {
      $output .= '<div class="uc-cart-checkout-button-container clearfix">';

      // Render the second button.
      $output .= '<div class="uc-cart-checkout-button">';
      $output .= '<div class="uc-cart-checkout-button-separator">' . t('- or -') . '</div>';
      $button = array_shift($buttons);
      $output .= drupal_render($variables['buttons'][$button]);
      $output .= '</div>';

      // Render any remaining buttons.
      foreach ($buttons as $button) {
        $output .= '<div class="uc-cart-checkout-button">';
        $output .= drupal_render($variables['buttons'][$button]);
        $output .= '</div>';
      }

      $output .= '</div>';
    }
  }

  return $output;
}

/**
 * Returns the text displayed for an empty shopping cart.
 *
 * @ingroup themeable
 */
function theme_uc_empty_cart() {
  return '<p class="uc-cart-empty">' . t('There are no products in your shopping cart.') . '</p>';
}

/**
 * Themes the sale completion page.
 *
 * @param $variables
 *   An associative array containing:
 *   - message: Message containing order number info, account info, and link to
 *     continue shopping.
 *
 * @ingroup themeable
 */
function theme_uc_cart_complete_sale($variables) {
  return $variables['message'];
}
