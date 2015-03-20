<?php

/**
 * @file
 * Contains \Drupal\uc_catalog\Controller\CatalogOrphanedProducts.
 */

namespace Drupal\uc_catalog\Controller;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Url;

/**
 *
 */
class CatalogOrphanedProducts {

  /**
   * Displays links to all products that have not been categorized.
   *
   * @return
   *   Renderable form array.
   */
  public static function orphans() {
    $build = array();

    if (\Drupal::config('taxonomy.settings')->get('maintain_index_table')) {
      $vid = \Drupal::config('uc_catalog.settings')->get('vocabulary');
      $product_types = uc_product_types();
      $field = FieldStorageConfig::loadByName('node', 'taxonomy_catalog');

      //@todo - figure this out
      // $field is a config object, not an array, so this doesn't work.
      //$types = array_intersect($product_types, $field['bundles']['node']);
      $types = $product_types; //temporary to get this to work at all

      $result = db_query("SELECT DISTINCT n.nid, n.title FROM {node_field_data} n LEFT JOIN (SELECT ti.nid, td.vid FROM {taxonomy_index} ti LEFT JOIN {taxonomy_term_data} td ON ti.tid = td.tid WHERE td.vid = :vid) txnome ON n.nid = txnome.nid WHERE n.type IN (:types[]) AND txnome.vid IS NULL", [':vid' => $vid, ':types[]' => $types]);

      $rows = array();
      while ($node = $result->fetchObject()) {
        $rows[] = \Drupal::l($node->title, new Url('entity.node.edit_form', ['node' => $node->nid], ['query' => ['destination' => 'admin/store/products/orphans']]));
      }

      if (count($rows) > 0) {
        $build['orphans'] = array(
          '#theme' => 'item_list',
          '#items' => $rows,
        );
      }
      else {
        $build['orphans'] = array(
          '#markup' => t('All products are currently listed in the catalog.'),
          '#prefix' => '<p>',
          '#suffix' => '</p>',
        );
      }
    }
    else {
      $build['orphans'] = array(
        '#markup' => t('The node terms index is not being maintained, so Ubercart can not determine which products are not entered into the catalog.'),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      );
    }

    return $build;
  }
}
