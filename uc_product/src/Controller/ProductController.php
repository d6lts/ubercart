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
    $classes = entity_load_multiple_by_properties('node_type', array(
      'third_party_settings.uc_product.product' => TRUE,
    ));
    $header = array(t('Class ID'), t('Name'), t('Description'), t('Operations'));
    $rows = array();
    foreach ($classes as $class) {
      $links = array();
      $links['edit'] = array(
        'title' => t('Edit'),
        'href' => 'admin/structure/types/manage/' . $class->type,
        'query' => array(
          'destination' => 'admin/store/products/classes',
        ),
      );
      if (!$class->isLocked()) {
        $links['delete'] = array(
          'title' => t('Delete'),
          'href' => 'admin/structure/types/manage/' . $class->type . '/delete',
          'query' => array(
            'destination' => 'admin/store/products/classes',
          ),
        );
      }
      $rows[] = array(
        String::checkPlain($class->type),
        String::checkPlain($class->name),
        Xss::filterAdmin($class->description),
        array(
          'data' => array(
            '#type' => 'operations',
            '#links' => $links,
          )
        ),
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
