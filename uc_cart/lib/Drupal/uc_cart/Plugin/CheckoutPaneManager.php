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
    'status' => TRUE,
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

    $this->moduleHandler = $module_handler;
    $this->alterInfo('uc_checkout_pane');
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
  public function getPanes($filter = array()) {
    $instances = array();
    foreach ($this->getDefinitions() as $id => $definition) {
      foreach ($filter as $key => $value) {
        if (isset($definition[$key]) && $definition[$key] == $value) {
          continue 2;
        }
      }

      $instance = $this->createInstance($id, $this->paneConfig[$id] ?: array());
      if (!isset($filter['enabled']) || $filter['enabled'] != $instance->isEnabled()) {
        $instances[$id] = $instance;
      }
    }

    uasort($instances, array($this, 'sortHelper'));

    return $instances;
  }

  /**
   * Provides uasort() callback to sort plugins.
   */
  public function sortHelper($a, $b) {
    $a_weight = $a->getWeight();
    $b_weight = $b->getWeight();

    if ($a_weight == $b_weight) {
      return 0;
    }

    return ($a_weight < $b_weight) ? -1 : 1;
  }

}
