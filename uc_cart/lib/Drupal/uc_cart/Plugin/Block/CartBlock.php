<?php

/**
 * @file
 * Contains \Drupal\uc_cart\Plugin\Block\CartBlock.
 */

namespace Drupal\uc_cart\Plugin\Block;

use Drupal\block\BlockBase;

/**
 * Provides the shopping cart block.
 *
 * @Block(
 *  id = "uc_cart",
 *  admin_label = @Translation("Shopping cart")
 * )
 */
class CartBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'hide_empty' => FALSE,
      'show_image' => TRUE,
      'collapsible' => TRUE,
      'collapsed' => TRUE,
      'show_help_text' => FALSE,
      'visibility' => array(
        'path' => array(
          'pages' => 'admin*',
        ),
      ),
    );
  }

  /**
   * Overrides \Drupal\block\BlockBase::access().
   */
  public function blockAccess() {
    return user_access('access content');
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   */
  public function blockForm($form, &$form_state) {
    $form['hide_empty'] = array(
      '#type' => 'checkbox',
      '#title' => t('Hide block if cart is empty.'),
      '#default_value' => $this->configuration['hide_empty'],
    );
    $form['show_image'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display the shopping cart icon in the block title.'),
      '#default_value' => $this->configuration['show_image'],
    );
    $form['collapsible'] = array(
      '#type' => 'checkbox',
      '#title' => t('Make the shopping cart block collapsible by clicking the name or arrow.'),
      '#default_value' => $this->configuration['collapsible'],
    );
    $form['collapsed'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display the shopping cart block collapsed by default.'),
      '#default_value' => $this->configuration['collapsed'],
    );
    $form['show_help_text'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display small help text in the shopping cart block.'),
      '#default_value' => $this->configuration['show_help_text'],
    );
    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, &$form_state) {
    $this->configuration['hide_empty'] = $form_state['values']['hide_empty'];
    $this->configuration['show_image'] = $form_state['values']['show_image'];
    $this->configuration['collapsible'] = $form_state['values']['collapsible'];
    $this->configuration['collapsed'] = $form_state['values']['collapsed'];
    $this->configuration['show_help_text'] = $form_state['values']['show_help_text'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $product_count = count(uc_cart_get_contents());

    // Display nothing if the block is set to hide on empty and there are no
    // items in the cart.
    if (!$this->configuration['hide_empty'] || $product_count) {
      // Add the cart block CSS.
      drupal_add_css(drupal_get_path('module', 'uc_cart') . 'css/uc_cart_block.css');

      // If the block is collapsible, add the appropriate JS.
      if ($this->configuration['collapsible']) {
        drupal_add_js(drupal_get_path('module', 'uc_cart') . 'js/uc_cart_block.js');
      }

      // First build the help text.
      $help_text = FALSE;

      if ($this->configuration['show_help_text']) {
        $help_text = t('Click title to display cart contents.');
      }

      $items = FALSE;
      $item_count = 0;
      $total = 0;

      if ($product_count) {
        $display_items = entity_view_multiple(uc_cart_get_contents(), 'cart');
        foreach (element_children($display_items) as $key) {
          $display_item = $display_items[$key];

          if (count(element_children($display_item))) {
            $items[] = array(
              'nid' => $display_item['nid']['#value'],
              'qty' => theme('uc_qty', array('qty' => $display_item['qty']['#default_value'])),
              'title' => $display_item['title']['#markup'],
              'price' => $display_item['#total'],
              'desc' => isset($display_item['description']['#markup']) ? $display_item['description']['#markup'] : FALSE,
            );
            $total += $display_item['#total'];
            $item_count += $display_item['qty']['#default_value'];
          }

        }
      }

      // Build the item count text and cart links.
      $item_text = format_plural($item_count, '<span class="num-items">1</span> Item', '<span class="num-items">@count</span> Items');

      $summary_links = array(
        'cart-block-view-cart' => array(
          'title' => t('View cart'),
          'href' => 'cart',
          'attributes' => array('rel' => 'nofollow'),
        ),
      );

      // Only add the checkout link if checkout is enabled.
      if (variable_get('uc_checkout_enabled', TRUE)) {
        $summary_links['cart-block-checkout'] = array(
          'title' => t('Checkout'),
          'href' => 'cart/checkout',
          'attributes' => array('rel' => 'nofollow'),
        );
      }

      return array(
        '#theme' => 'uc_cart_block_content',
        '#help_text' => $help_text,
        '#items' => $items,
        '#item_count' => $item_count,
        '#item_text' => $item_text,
        '#total' => $total,
        '#summary_links' => $summary_links,
        '#collapsed' => $this->configuration['collapsed'],
      );
    }
  }

}
