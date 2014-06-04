<?php

/**
 * @file
 * Contains \Drupal\uc_product\Access\ProductAccessCheck.
 */

namespace Drupal\uc_product\Access;

use Drupal\node\NodeInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Access\StaticAccessCheckInterface;

/**
 * Provides an access checker for products.
 */
class ProductAccessCheck implements StaticAccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('_uc_product_is_product');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    if ($request->attributes->has('node')) {
      $entity = $request->attributes->get('node');
      if ($entity instanceof NodeInterface && uc_product_is_product($node)) {
        return static::ALLOW;
      }
    }
    return static::DENY;
  }

}
