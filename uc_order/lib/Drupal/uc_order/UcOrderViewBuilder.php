<?php

/**
 * @file
 * Contains \Drupal\uc_order\UcOrderViewBuilder.
 */

namespace Drupal\uc_order;

use Drupal\Core\Entity\EntityViewBuilder;

/**
 * View builder for orders.
 */
class UcOrderViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildContent(array $entities, array $displays, $view_mode, $langcode = NULL) {
    parent::buildContent($entities, $displays, $view_mode, $langcode);

    foreach ($entities as $order) {
      $panes = _uc_order_pane_list($view_mode);
      foreach ($panes as $pane) {
        if (in_array($view_mode, $pane['show'])) {
          $func = $pane['callback'];
          if (function_exists($func) && ($contents = $func($view_mode, $order)) != NULL) {
            $title = isset($pane['display title']) ? $pane['display title'] : $pane['title'];
            if ($title) {
              $title = array(
                '#markup' => $pane['title'] . ':',
                '#prefix' => '<div class="order-pane-title">',
                '#suffix' => '</div>',
              );
            }
            else {
              $title = array();
            }

            $order->content[$pane['id']] = array(
              '#prefix' => '<div class="order-pane ' . $pane['class'] . '" id="order-pane-' . $pane['id'] . '">',
              '#suffix' => '</div>',
            );

            $order->content[$pane['id']]['title'] = $title;
            $order->content[$pane['id']]['pane'] = $contents;
          }
        }
      }
    }
  }
}
