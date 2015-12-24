<?php

/**
 * @file
 * Contains \Drupal\uc_catalog\CatalogBreadcrumbBuilder.
 */

namespace Drupal\uc_catalog;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Link;

/**
 * Provides a custom breadcrumb builder for catalog node pages.
 */
class CatalogBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

  /**
   * Configuration object for this builder.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Stores the entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new CatalogBreadcrumbBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The configuration factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactory $configFactory) {
    $this->entityTypeManager = $entity_type_manager;
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
    $links[] = Link::createFromRoute($this->t('Home'), '<front>');
    $links[] = Link::createFromRoute($this->t('Catalog'), 'view.uc_catalog.page_1');
    if ($parents = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadAllParents($node->taxonomy_catalog->target_id)) {
      $parents = array_reverse($parents);
      foreach ($parents as $parent) {
        $links[] = Link::createFromRoute($parent->label(), 'view.uc_catalog.page_1', ['term_node_tid_depth' => $parent->id()]);
      }
    }

    $breadcrumb = new Breadcrumb();
    $breadcrumb->setLinks($links);

    return $breadcrumb;
  }

  /**
   * Builds the breadcrumb for a catalog term page.
   */
  protected function catalogTermBreadcrumb($tid) {
    $breadcrumb[] = Link::createFromRoute($this->t('Home'), '<front>');
    $breadcrumb[] = Link::createFromRoute($this->t('Catalog'), 'view.uc_catalog.page_1');
    if ($parents = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadAllParents($tid)) {
      array_shift($parents);
      $parents = array_reverse($parents);
      foreach ($parents as $parent) {
        $breadcrumb[] = Link::createFromRoute($parent->label(), 'view.uc_catalog.page_1', ['term_node_tid_depth' => $parent->id()]);
      }
    }
    return $breadcrumb;
  }

}
