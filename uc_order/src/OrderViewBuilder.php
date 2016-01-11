<?php

/**
 * @file
 * Contains \Drupal\uc_order\OrderViewBuilder.
 */

namespace Drupal\uc_order;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * View builder for orders.
 */
class OrderViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $build = parent::getBuildDefaults($entity, $view_mode);
    // For now, the entity has no template itself.
    unset($build['#theme']);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    parent::buildComponents($build, $entities, $displays, $view_mode);

    foreach ($entities as $id => $order) {
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

            $build[$id][$pane['id']] = array(
              '#prefix' => '<div class="order-pane ' . $pane['class'] . '" id="order-pane-' . $pane['id'] . '">',
              '#suffix' => '</div>',
            );

            $build[$id][$pane['id']]['title'] = $title;
            $build[$id][$pane['id']]['pane'] = $contents;
          }
        }
      }
    }
  }
}
