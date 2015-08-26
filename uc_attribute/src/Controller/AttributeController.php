<?php

/**
 * @file
 * Contains \Drupal\uc_attribute\Controller\AttributeController.
 */

namespace Drupal\uc_attribute\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Controller routines for product attribute routes.
 */
class AttributeController extends ControllerBase {

  /**
   * Displays a paged list and overview of existing product attributes.
   */
  public function overview() {
    $header = array(
      array('data' => t('Name'), 'field' => 'a.name', 'sort' => 'asc'),
      array('data' => t('Label'), 'field' => 'a.label'),
      t('Required'),
      array('data' => t('List position'), 'field' => 'a.ordering'),
      t('Number of options'),
      t('Display type'),
      array('data' => t('Operations'), 'colspan' => 3),
    );

    $display_types = _uc_attribute_display_types();

    $query = db_select('uc_attributes', 'a')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->fields('a', array('aid', 'name', 'label', 'required', 'ordering', 'display'))
      ->orderByHeader($header)
      ->limit(30);

    $rows = array();

    $result = $query->execute();
    foreach ($result as $attr) {
      $attr->options = db_query('SELECT COUNT(*) FROM {uc_attribute_options} WHERE aid = :aid', [':aid' => $attr->aid])->fetchField();
      if (empty($attr->label)) {
        $attr->label = $attr->name;
      }
      $rows[] = array(
        SafeMarkup::checkPlain($attr->name),
        SafeMarkup::checkPlain($attr->label),
        $attr->required == 1 ? t('Yes') : t('No'),
        $attr->ordering,
        $attr->options,
        $display_types[$attr->display],
        \Drupal::l(t('edit'), new Url('uc_attribute.edit', ['aid' => $attr->aid])),
        \Drupal::l(t('options'), new Url('uc_attribute.options', ['aid' => $attr->aid])),
        \Drupal::l(t('delete'), new Url('uc_attribute.delete', ['aid' => $attr->aid])),
      );
    }

    $build['attributes'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No product attributes have been added yet.'),
    );
    $build['pager'] = array(
      '#type' => 'pager',
    );

    return $build;
  }

}
