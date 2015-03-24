<?php

/**
 * @file
 * Contains \Drupal\uc_cart_links\Controller\CartLinksReports.
 */

namespace Drupal\uc_cart_links\Controller;

use Drupal\Component\Utility\SafeMarkup;

/**
 * Displays the Cart Links report.
 *
 * @return
 *   Renderable form array.
 */
class CartLinksReports {

  /**
   * {@inheritdoc}
   */
  public static function buildReport() {
    $header = array(
      array('data' => t('ID'), 'field' => 'cart_link_id'),
      array('data' => t('Clicks'), 'field' => 'clicks'),
      array('data' => t('Last click'), 'field' => 'last_click', 'sort' => 'desc'),
    );

    $query = db_select('uc_cart_link_clicks')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->fields('uc_cart_link_clicks')
      ->limit(25)
      ->element(1)
      ->orderByHeader($header);

    $rows = array();
    $result = $query->execute();
    foreach ($result as $data) {
      $rows[] = array(
        SafeMarkup::checkPlain($data->cart_link_id),
        $data->clicks,
        \Drupal::service('date.formatter')->format($data->last_click, 'short'),
      );
    }

    $build['report'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No Cart Links have been tracked yet.'),
    );
    $build['pager'] = array(
      '#theme' => 'pager',
      '#element' => 1,
    );

    return $build;
  }
}
