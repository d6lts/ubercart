<?php

/**
 * Contains \Drupal\uc_payment\Plugin\PaymentMethodManager.
 */

namespace Drupal\uc_payment\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Factory\ContainerFactory;
use Drupal\uc_order\UcOrderInterface;
use Drupal\uc_store\Plugin\Discovery\InfoHookDecorator;

/**
 * Manages discovery and instantiation of payment methods.
 */
class PaymentMethodManager extends DefaultPluginManager {

  /**
   * Configuration for the payment methods.
   */
  protected $methodConfig;

  /**
   * {@inheritdoc}
   */
  protected $defaults = array(
    'status' => TRUE,
    'weight' => 0,
  );

  /**
   * Constructs a PaymentMethodManager object.
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
    $this->discovery = new AnnotatedClassDiscovery('Plugin/Ubercart/PaymentMethod', $namespaces, 'Drupal\Component\Annotation\Plugin');
    $this->discovery = new InfoHookDecorator($this->discovery, 'uc_payment_method', 'Drupal\uc_payment\Plugin\Ubercart\PaymentMethod\LegacyPaymentMethod');
    $this->factory = new ContainerFactory($this);

    $this->moduleHandler = $module_handler;
    $this->alterInfo('uc_payment_method');
    $this->setCacheBackend($cache_backend, $language_manager, 'uc_payment_methods');

    $this->methodConfig = \Drupal::config('uc_payment.settings')->get('methods');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $methods = parent::getDefinitions();

    uasort($methods, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    return $methods;
  }

  /**
   * Overrides \Drupal\Component\Plugin\PluginManagerBase::processDefinition().
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    if (isset($this->methodConfig[$plugin_id]['status'])) {
      $definition['checkout'] = $this->methodConfig[$plugin_id]['status'];
    }
    if (isset($this->methodConfig[$plugin_id]['weight'])) {
      $definition['weight'] = $this->methodConfig[$plugin_id]['weight'];
    }
 }

  /**
   * Returns an instance of the payment method plugin for a specific order.
   *
   * @param \Drupal\uc_order\UcOrderInterface $order
   *   The order from which the plugin should be instantiated.
   *
   * @return \Drupal\uc_payment\PaymentMethodPluginInterface
   *   A fully configured plugin instance.
   */
  public function createFromOrder(UcOrderInterface $order) {
    return $this->createInstance($order->getPaymentMethodId());
  }

  /**
   * Populates a key-value pair of available payment methods.
   *
   * @return array
   *   An array of payment method labels, keyed by ID.
   */
  public function listOptions() {
    $options = array();
    foreach ($this->getDefinitions() as $key => $definition) {
      $options[$key] = $definition['name'];
    }
    return $options;
  }

}
