<?php

/**
 * @file
 * Contains \Drupal\uc_catalog\Plugin\Block\CatalogBlock.
 */

namespace Drupal\uc_catalog\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\uc_catalog\TreeNode;

/**
 * Provides the product catalog block.
 *
 * @Block(
 *   id = "uc_catalog",
 *   admin_label = @Translation("Catalog")
 * )
 */
class CatalogBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'link_title' => FALSE,
      'expanded' => FALSE,
      'product_count' => TRUE,
      'visibility' => array(
        'path' => array(
          'pages' => 'admin*',
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess() {
    return \Drupal::currentUser()->hasPermission('view catalog');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, &$form_state) {
    $form['link_title'] = array(
      '#type' => 'checkbox',
      '#title' => t('Make the block title a link to the top-level catalog page.'),
      '#default_value' => $this->configuration['link_title'],
    );
    $form['expanded'] = array(
      '#type' => 'checkbox',
      '#title' => t('Always expand categories.'),
      '#default_value' => $this->configuration['expanded'],
    );
    $form['product_count'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display product counts.'),
      '#default_value' => $this->configuration['product_count'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, &$form_state) {
    $this->configuration['link_title'] = $form_state['values']['link_title'];
    $this->configuration['expanded'] = $form_state['values']['expanded'];
    $this->configuration['product_count'] = $form_state['values']['product_count'];

    // @todo Remove when catalog block theming is fully converted.
    variable_set('uc_catalog_expand_categories', $form_state['values']['expanded']);
    variable_set('uc_catalog_block_nodecount', $form_state['values']['product_count']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get the vocabulary tree information.
    $vid = config('uc_catalog.settings')->get('vocabulary');
    $tree = taxonomy_get_tree($vid);

    // Then convert it into an actual tree structure.
    $seq = 0;
    $menu_tree = new TreeNode();
    foreach ($tree as $knot) {
      $seq++;
      $knot->sequence = $seq;
      $knothole = new TreeNode($knot);
      // Begin at the root of the tree and find the proper place.
      $menu_tree->add_child($knothole);
    }

    $build['content'] = array(
      '#theme' => 'uc_catalog_block',
      '#menu_tree' => $menu_tree,
    );

    $build['#attached']['css'][] = drupal_get_path('module', 'uc_catalog') . '/css/uc_catalog.css';

    return $build;
  }

}
