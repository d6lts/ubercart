<?php

/**
 * @file
 * Contains \Drupal\uc_cart\Plugin\CheckoutPaneManager.
 */

namespace Drupal\uc_cart\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Factory\ContainerFactory;
use Drupal\uc_store\Plugin\Discovery\InfoHookDecorator;

/**
 * Manages discovery and instantiation of checkout panes.
 */
class CheckoutPaneManager extends DefaultPluginManager {

  /**
   * Configuration for the checkout panes.
   */
  protected $paneConfig;

  /**
   * {@inheritdoc}
   */
  protected $defaults = array(
    'enabled' => TRUE,
    'weight' => 0,
  );

  /**
   * Constructs a CheckoutPaneManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManager $language_manager, ModuleHandlerInterface $module_handler) {
    $this->discovery = new AnnotatedClassDiscovery('Plugin/Ubercart/CheckoutPane', $namespaces, 'Drupal\Component\Annotation\Plugin');
    $this->discovery = new InfoHookDecorator($this->discovery, 'uc_checkout_pane', 'Drupal\uc_cart\Plugin\Ubercart\CheckoutPane\LegacyCheckoutPane');
    $this->factory = new ContainerFactory($this);

    $this->alterInfo($module_handler, 'uc_checkout_pane');
    $this->setCacheBackend($cache_backend, $language_manager, 'uc_checkout_panes');

    $this->paneConfig = \Drupal::config('uc_cart.settings')->get('panes');
  }

  /**
   * Gets instances of checkout pane plugins, optionally filtered.
   *
   * @param array $filter
   *   An array of definition keys to filter by.
   *
   * @return array
   *   An array of checkout pane plugin instances.
   */
  public function createInstances($filter = array()) {
    $definitions = $this->getDefinitions($filter);

    $instances = array();
    foreach (array_keys($definitions) as $id) {
      $instances[$id] = $this->createInstance($id);
    }
    return $instances;
  }

  /**
   * Gets the definition of checkout pane plugins, optionally filtered.
   *
   * @param array $filter
   *   An array of definition keys to filter by.
   *
   * @return array
   *   An array of checkout pane plugin definitions.
   */
  public function getDefinitions($filter = array()) {
    $panes = parent::getDefinitions();

    foreach ($panes as $id => $pane) {
      foreach ($filter as $key => $value) {
        if (isset($panes[$id][$key]) && $panes[$id][$key] == $value) {
          unset($panes[$id]);
        }
      }
    }

    uasort($panes, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    return $panes;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    if (isset($this->paneConfig[$plugin_id]['status'])) {
      $definition['enabled'] = $this->paneConfig[$plugin_id]['status'];
    }
    if (isset($this->paneConfig[$plugin_id]['weight'])) {
      $definition['weight'] = $this->paneConfig[$plugin_id]['weight'];
    }
  }

}
