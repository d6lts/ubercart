<?php

/**
 * @file
 * Definition of Drupal\uc_cart\UcCartItemRenderController.
 */

namespace Drupal\uc_cart;

use Drupal\Core\Entity\EntityRenderController;

/**
 * Render controller for cart items.
 */
class UcCartItemRenderController extends EntityRenderController {

  /**
   * Overrides Drupal\Core\Entity\EntityRenderController::buildContent().
   */
  public function buildContent(array $entities = array(), $view_mode = 'full', $langcode = NULL) {
    parent::buildContent($entities, $view_mode, $langcode);

    foreach ($entities as $item) {
      $item->content += module_invoke($item->data['module'], 'uc_cart_display', $item);
    }
  }
}
