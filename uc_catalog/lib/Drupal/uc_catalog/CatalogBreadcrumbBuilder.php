<?php

/**
 * @file
 * Contains \Drupal\uc_catalog\CatalogBreadcrumbBuilder.
 */

namespace Drupal\uc_catalog;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Provides a custom breadcrumb builder for catalog node pages.
 */
class CatalogBreadcrumbBuilder extends BreadcrumbBuilderBase {

  /**
   * Configuration object for this builder.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Stores the Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new CatalogBreadcrumbBuilder.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
  -   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The configuration factory.
   */
  public function __construct(EntityManagerInterface $entity_manager, ConfigFactory $configFactory) {
    $this->entityManager = $entity_manager;
    $this->config = $configFactory->get('uc_catalog.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $attributes) {
    if (!empty($attributes[RouteObjectInterface::ROUTE_NAME])) {
      $route_name = $attributes[RouteObjectInterface::ROUTE_NAME];
      if ($route_name == 'node.view' && isset($attributes['node']->taxonomy_catalog)) {
        return $this->catalogBreadcrumb($attributes['node']);
      }
      elseif (substr($route_name, 0, 16) == 'view.uc_catalog.' && isset($attributes['arg_term_node_tid_depth'])) {
        return $this->catalogTermBreadcrumb($attributes['arg_term_node_tid_depth']);
      }
    }
  }

  /**
   * Builds the breadcrumb for a catalog page.
   */
  protected function catalogBreadcrumb($node) {
    $breadcrumb[] = $this->l($this->t('Home'), '<front>');
    $breadcrumb[] = l(t('Catalog'), 'catalog');
    if ($parents = taxonomy_term_load_parents_all($node->taxonomy_catalog->value)) {
      $parents = array_reverse($parents);
      foreach ($parents as $parent) {
        $breadcrumb[] = l($parent->label(), 'catalog/' . $parent->id());
      }
    }
    return $breadcrumb;
  }

  /**
   * Builds the breadcrumb for a catalog term page.
   */
  protected function catalogTermBreadcrumb($tid) {
    $breadcrumb[] = $this->l($this->t('Home'), '<front>');
    $breadcrumb[] = l(t('Catalog'), 'catalog');
    if ($parents = taxonomy_term_load_parents_all($tid)) {
      array_shift($parents);
      $parents = array_reverse($parents);
      foreach ($parents as $parent) {
        $breadcrumb[] = l($parent->label(), 'catalog/' . $parent->id());
      }
    }
    return $breadcrumb;
  }

}
