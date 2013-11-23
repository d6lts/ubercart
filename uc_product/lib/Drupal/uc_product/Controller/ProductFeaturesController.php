<?php

/**
 * @file
 * Contains \Drupal\uc_product\Controller\ProductFeaturesController.
 */

namespace Drupal\uc_product\Controller;

use Drupal\Core\Access\AccessInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller routines for product feature routes.
 */
class ProductFeaturesController extends ControllerBase {

  /**
   * Displays the product features tab on a product node edit form.
   */
  public function featuresOverview(NodeInterface $node) {
    $header = array(t('Type'), t('Description'), t('Operations'));
    $rows = array();

    $features = uc_product_feature_load_multiple($node->id());
    foreach ($features as $feature) {
      $operations = array(
        'edit' => array('title' => t('Edit'), 'href' => 'node/' . $node->id() . '/edit/features/' . $feature->fid . '/' . $feature->pfid),
        'delete' => array('title' => t('Delete'), 'href' => 'node/' . $node->id() . '/edit/features/' . $feature->fid . '/' . $feature->pfid . '/delete'),
      );
      $rows[] = array(
        array('data' => uc_product_feature_data($feature->fid, 'title')),
        array('data' => $feature->description),
        array('data' => array(
          '#type' => 'operations',
          '#links' => $operations,
        )),
      );
    }

    $build['features'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array('class' => array('uc-product-features')),
      '#empty' => t('No features found for this product.'),
    );

    module_load_include('inc', 'uc_product', 'uc_product.admin');
    $build['add_form'] = drupal_get_form('Drupal\uc_product\Form\ProductFeatureAddForm', $node);

    return $build;
  }

  /**
   * Displays the add feature form.
   */
  public function featureAdd(NodeInterface $node, $fid) {
    $func = uc_product_feature_data($fid, 'callback');
    $form_state['build_info']['args'] = array($node, NULL);
    $form_state['wrapper_callback'] = 'uc_product_feature_form';
    return drupal_build_form($func, $form_state);
  }

  /**
   * Displays the edit feature form.
   */
  public function featureEdit(NodeInterface $node, $fid, $pfid) {
    $func = uc_product_feature_data($fid, 'callback');
    $form_state['build_info']['args'] = array($node, uc_product_feature_load($pfid));
    $form_state['wrapper_callback'] = 'uc_product_feature_form';
    return drupal_build_form($func, $form_state);
  }

  /**
   * Checks access for the feature routes.
   */
  public function checkAccess(Request $request) {
    $node = $request->get('node');

    if (uc_product_is_product($node) && $node->getType() != 'product_kit') {
      if ($this->currentUser()->hasPermission('administer product features')) {
        return AccessInterface::ALLOW;
      }

      if ($this->currentUser()->hasPermission('administer own product features') && $this->currentUser()->id() == $node->getAuthorId()) {
        return AccessInterface::ALLOW;
      }
    }

    return AccessInterface::DENY;
  }

}
