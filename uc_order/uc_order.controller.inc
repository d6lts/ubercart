<?php
/**
 * @file
 * Contains controller classes for uc_order and uc_order_product entities.
 */

/**
 * Controller class for the uc_order_product entity.
 */
class UcOrderProductController extends EntityAPIController {

  /**
   * Overrides EntityAPIController::buildContent().
   */
  public function buildContent($product, $view_mode = 'full', $langcode = NULL, $content = array()) {
    $content['qty'] = array(
      '#theme' => 'uc_qty',
      '#qty' => $product->qty,
      '#cell_attributes' => array('class' => array('qty')),
    );
    $node = node_load($product->nid);
    $title = node_access('view', $node) ? l($product->title, 'node/' . $node->nid) : check_plain($product->title);
    $content['product'] = array(
      '#markup' => $title . uc_product_get_description($product),
      '#cell_attributes' => array('class' => array('product')),
    );
    $content['model'] = array(
      '#markup' => check_plain($product->model),
      '#cell_attributes' => array('class' => array('sku')),
    );
    if (user_access('administer products')) {
      $content['cost'] = array(
        '#theme' => 'uc_price',
        '#price' => $product->cost,
        '#cell_attributes' => array('class' => array('cost')),
      );
    }
    $content['price'] = array(
      '#theme' => 'uc_price',
      '#price' => $product->price,
      '#suffixes' => array(),
      '#cell_attributes' => array('class' => array('price')),
    );
    $content['total'] = array(
      '#theme' => 'uc_price',
      '#price' => $product->price * $product->qty,
      '#suffixes' => array(),
      '#cell_attributes' => array('class' => array('total')),
    );

    return parent::buildContent($product, $view_mode, $langcode, $content);
  }

  /**
   * Overrides EntityAPIController::save().
   */
  public function save($product, DatabaseTransaction $transaction = NULL) {
    // Product kits, particularly, shouldn't actually be added to an order,
    // but instead they cause other products to be added.
    if (isset($product->skip_save) && $product->skip_save == TRUE) {
      return;
    }

    if (empty($product->weight_units)) {
      if (empty($product->nid)) {
        $product->weight_units = variable_get('uc_weight_unit', 'lb');
      }
      else {
        $units = db_query("SELECT weight_units FROM {node} n JOIN {uc_products} p ON n.vid = p.vid WHERE n.nid = :nid", array(':nid' => $product->nid))->fetchField();
        $product->weight_units = empty($units) ? variable_get('uc_weight_unit', 'lb') : $units;
      }
    }
    return parent::save($product, $transaction);
  }
}
