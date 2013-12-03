<?php

/**
 * @file
 * Contains \Drupal\uc_cart\UcCartItemViewBuilder.
 */

namespace Drupal\uc_cart;

use Drupal\Core\Entity\EntityViewBuilder;

/**
 * View builder for cart items.
 */
class UcCartItemViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildContent(array $entities, array $displays, $view_mode, $langcode = NULL) {
    parent::buildContent($entities, $displays, $view_mode, $langcode);

    foreach ($entities as $item) {
      $item->content += module_invoke($item->data['module'], 'uc_cart_display', $item);
    }
  }
}
