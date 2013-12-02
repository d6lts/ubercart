<?php

/**
 * @file
 * Contains \Drupal\uc_catalog\Controller\CatalogController.
 */

namespace Drupal\uc_catalog\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for catalog routes.
 */
class CatalogController extends ControllerBase {

  /**
   * Repairs the catalog taxonomy field if it is lost or deleted.
   */
  public function repairField() {
    foreach (uc_product_types() as $type) {
      uc_catalog_add_node_type($type);
    }
    uc_catalog_add_image_field();

    drupal_set_message(t('The catalog taxonomy reference field has been repaired.'));

    return $this->redirect('uc_store.admin');
  }

}
