<?php

/**
 * @file
 * Contains of \Drupal\uc_cart\CartBreadcrumbBuilder.
 */

namespace Drupal\uc_cart;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderBase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Provides a custom breadcrumb builder for the cart page.
 */
class CartBreadcrumbBuilder extends BreadcrumbBuilderBase {

  /**
   * {@inheritdoc}
   */
  public function build(array $attributes) {
    if (!empty($attributes[RouteObjectInterface::ROUTE_NAME]) && $attributes[RouteObjectInterface::ROUTE_NAME] == 'uc_cart.cart' && ($text = variable_get('uc_cart_breadcrumb_text', ''))) {
      $breadcrumb[] = $this->l($this->t('Home'), '<front>');
      $breadcrumb[] = l($text, variable_get('uc_cart_breadcrumb_url', '<front>'));
      return $breadcrumb;
    }
  }

}
