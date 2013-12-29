<?php

/**
 * @file
 * Contains Drupal\uc_store\Plugin\Discovery\InfoHookDecorator.
 */

namespace Drupal\uc_store\Plugin\Discovery;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\Plugin\Discovery\InfoHookDecorator as BaseInfoHookDecorator;

/**
 * Allows info hook implementations to enhance discovered plugin definitions.
 */
class InfoHookDecorator extends BaseInfoHookDecorator {

  /**
   * The name of the class that will be instantiated for legacy plugins.
   *
   * @var string
   */
  protected $legacyClass;

  /**
   * Constructs a InfoHookDecorator object.
   *
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface $decorated
   *   The object implementing DiscoveryInterface that is being decorated.
   * @param string $hook
   *   The name of the info hook to be invoked by this discovery instance.
   * @param string $class
   *   The name of the class to be instantiated for legacy plugins.
   */
  public function __construct(DiscoveryInterface $decorated, $hook, $class) {
    $this->decorated = $decorated;
    $this->hook = $hook;
    $this->legacyClass = $class;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = $this->decorated->getDefinitions();
    foreach (\Drupal::moduleHandler()->getImplementations($this->hook) as $module) {
      $function = $module . '_' . $this->hook;
      $result = $function($definitions);
      if (is_array($result)) {
        foreach ($result as $plugin_id => $definition) {
          $definition += array(
            'id' => $plugin_id,
            'provider' => $module,
            'class' => $this->legacyClass,
          );
          $definitions[$definition['id']] = $definition;
        }
      }
    }
    return $definitions;
  }

}
