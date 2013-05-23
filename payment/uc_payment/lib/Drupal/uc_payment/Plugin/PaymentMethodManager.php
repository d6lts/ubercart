<?php

/**
 * Contains \Drupal\uc_payment\Plugin\PaymentMethodManager.
 */

namespace Drupal\uc_payment\Plugin;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Discovery\ProcessDecorator;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\Core\Plugin\Discovery\HookDiscovery;
use Drupal\Component\Plugin\Factory\DefaultFactory;

/**
 * Manages discovery and instantiation of payment methods.
 */
class PaymentMethodManager extends PluginManagerBase {

  /**
   * Constructs a new \Drupal\uc_payment\Plugin\PaymentMethodManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler) {
    $this->discovery = new HookDiscovery($module_handler, 'uc_payment_method');
    $this->discovery = new ProcessDecorator($this->discovery, array($this, 'processDefinition'));
    $this->discovery = new AlterDecorator($this->discovery, 'uc_payment_method');
    $this->discovery = new CacheDecorator($this->discovery, 'uc_payment:method');
    $this->factory = new DefaultFactory($this->discovery);
  }

  /**
   * Overrides \Drupal\Component\Plugin\PluginManagerBase::processDefinition().
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    $definition['checkout'] = variable_get('uc_payment_method_' . $plugin_id . '_checkout', $definition['checkout']);
    $definition['weight'] = variable_get('uc_payment_method_' . $plugin_id . '_weight', $definition['weight']);
  }

}
