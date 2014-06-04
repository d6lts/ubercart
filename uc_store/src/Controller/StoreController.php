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
  public function overview() {
    // Check for status report errors.
    if ($this->systemManager->checkRequirements() && $this->currentUser()->hasPermission('administer site configuration')) {
      drupal_set_message($this->t('One or more problems were detected with your Drupal installation. Check the <a href="@status">status report</a> for more information.', array('@status' => url('admin/reports/status'))), 'error');
    }
    $blocks = array();
    // Load all links on admin/store and menu links below it.
    $query = $this->queryFactory->get('menu_link')
      ->condition('link_path', 'admin/store');
    $result = $query->execute();
    $menu_link_storage = $this->entityManager()->getStorage('menu_link');
    if ($system_link = $menu_link_storage->loadMultiple($result)) {
      $system_link = reset($system_link);
      $query = $this->queryFactory->get('menu_link')
        ->condition('link_path', 'admin/help', '<>')
        ->condition('menu_name', $system_link->menu_name)
        ->condition('plid', $system_link->id())
        ->condition('hidden', 0);
      $result = $query->execute();
      if (!empty($result)) {
        $menu_links = $menu_link_storage->loadMultiple($result);
        foreach ($menu_links as $item) {
          _menu_link_translate($item);
          if (!$item['access']) {
            continue;
          }
          // The link description, either derived from 'description' in hook_menu()
          // or customized via menu module is used as title attribute.
          if (!empty($item['localized_options']['attributes']['title'])) {
            $item['description'] = $item['localized_options']['attributes']['title'];
            unset($item['localized_options']['attributes']['title']);
          }
          $block = $item;
          $block['content'] = array(
            '#theme' => 'admin_block_content',
            '#content' => $this->systemManager->getAdminBlock($item),
          );

          if (!empty($block['content']['#content'])) {
            // Prepare for sorting as in function _menu_tree_check_access().
            // The weight is offset so it is always positive, with a uniform 5-digits.
            $blocks[(50000 + $item['weight']) . ' ' . $item['title'] . ' ' . $item['mlid']] = $block;
          }
        }
      }
    }

    ksort($blocks);
    $build['blocks'] = array(
      '#theme' => 'admin_page',
      '#blocks' => $blocks,
    );

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
