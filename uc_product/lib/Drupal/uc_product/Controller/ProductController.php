<?php

/**
 * @file
 * Contains \Drupal\uc_product\Controller\ProductController.
 */

namespace Drupal\uc_product\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for product routes.
 */
class ProductController extends ControllerBase {

  /**
   * Displays a list of product classes.
   */
  public function classOverview() {
    $query = \Drupal::entityQuery('node_type')
      ->condition('settings.uc_product.product', TRUE);
    $result = $query->execute();
    $classes = entity_load_multiple('node_type', $result);
    $header = array(t('Class ID'), t('Name'), t('Description'), t('Operations'));
    $rows = array();
    foreach ($classes as $class) {
      $ops = array(l(t('edit'), 'admin/structure/types/manage/' . $class->type));
      if (!$class->isLocked()) {
        $ops[] = l(t('delete'), 'admin/structure/types/manage/' . $class->type . '/delete');
      }
      $rows[] = array(
        check_plain($class->type),
        check_plain($class->name),
        filter_xss_admin($class->description),
        implode(' ', $ops),
      );
    }

    return array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No product classes have been defined yet.'),
    );
  }

}
