<?php

/**
 * @file
 * Contains \Drupal\uc_store\Controller\StoreController.
 */

namespace Drupal\uc_store\Controller;

use Drupal\system\Controller\SystemController;

/**
 * Returns responses for Ubercart store routes.
 */
class StoreController extends SystemController {

  /**
   * {@inheritdoc}
   */
  public function overview($link_id = 'uc_store.admin.store') {
    $build['blocks'] = parent::overview($link_id);

    if ($results = \Drupal::moduleHandler()->invokeAll('uc_store_status')) {
      foreach ($results as $message) {
        switch ($message['status']) {
          case 'warning': $icon = 'alert.gif'; break;
          case 'error':   $icon = 'error.gif'; break;
          default:        $icon = 'info.gif';
        }
        $icon = array(
          '#theme' => 'image',
          '#uri' => drupal_get_path('module', 'uc_store') . '/images/' . $icon,
        );
        $rows[] = array(
          array('data' => $icon, 'class' => array('status-icon')),
          array('data' => $message['title'], 'class' => array('status-title')),
          array('data' => $message['desc'], 'class' => array('status-value')),
        );
      }

      $build['status'] = array(
        '#theme' => 'table',
        '#caption' => '<h2>' . t('Store status') . '</h2>',
        '#rows' => $rows,
        '#attributes' => array('class' => array('system-status-report')),
      );
    }

    return $build;
  }

}
