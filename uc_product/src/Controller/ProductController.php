<?php

/**
 * @file
 * Contains \Drupal\uc_product\Controller\ProductController.
 */

namespace Drupal\uc_product\Controller;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Xss;
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
        String::checkPlain($class->type),
        String::checkPlain($class->name),
        Xss::filterAdmin($class->description),
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

  /**
   * Sets up the default image field for products.
   */
  public function setImageDefaults() {
    uc_product_add_default_image_field();

    drupal_set_message(t('Default image support configured for Ubercart products.'));

    return $this->redirect('uc_store.admin');
  }

}
