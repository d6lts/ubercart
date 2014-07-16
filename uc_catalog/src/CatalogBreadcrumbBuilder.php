<?php

/**
 * @file
 * Contains \Drupal\uc_catalog\CatalogBreadcrumbBuilder.
 */

namespace Drupal\uc_catalog;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a custom breadcrumb builder for catalog node pages.
 */
class CatalogBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;
  use LinkGeneratorTrait;

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
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The configuration factory.
   */
  public function __construct(EntityManagerInterface $entity_manager, ConfigFactory $configFactory) {
    $this->entityManager = $entity_manager;
    $this->config = $configFactory->get('uc_catalog.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $route_name = $route_match->getRouteName();
    return $route_name == 'node.view' && $route_match->getParameter('node') && isset($route_match->getParameter('node')->taxonomy_catalog)
        || (substr($route_name, 0, 16) == 'view.uc_catalog.' && $route_match->getParameter('arg_term_node_tid_depth'));
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $route_name = $route_match->getRouteName();
    if ($route_name == 'node.view' && $route_match->getParameter('node') && isset($route_match->getParameter('node')->taxonomy_catalog)) {
      return $this->catalogBreadcrumb($route_match->getParameter('node'));
    }
    elseif (substr($route_name, 0, 16) == 'view.uc_catalog.' && $route_match->getParameter('arg_term_node_tid_depth')) {
      return $this->catalogTermBreadcrumb($route_match->getParameter('arg_term_node_tid_depth'));
    }
  }

  /**
   * Builds the breadcrumb for a catalog page.
   */
  protected function catalogBreadcrumb($node) {
    $breadcrumb[] = $this->l($this->t('Home'), '<front>');
    $breadcrumb[] = l(t('Catalog'), 'catalog');
    if ($parents = taxonomy_term_load_parents_all($node->taxonomy_catalog->target_id)) {
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
