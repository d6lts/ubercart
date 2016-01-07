<?php

/**
 * @file
 * Contains \Drupal\uc_quote\ShippingQuoteMethodListBuilder.
 */

namespace Drupal\uc_quote;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\uc_quote\Plugin\ShippingQuotePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of shipping quote method entities.
 */
class ShippingQuoteMethodListBuilder extends DraggableListBuilder implements FormInterface {

  /**
   * The shipping quote plugin manager.
   *
   * @var \Drupal\uc_quote\Plugin\ShippingQuotePluginManager
   */
  protected $shippingQuotePluginManager;

  /**
   * Constructs a new ShippingQuoteMethodListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\uc_quote\Plugin\ShippingQuotePluginManager $shipping_quote_plugin_manager
   *   The shipping quote plugin manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, ShippingQuotePluginManager $shipping_quote_plugin_manager) {
    parent::__construct($entity_type, $storage);
    $this->shippingQuotePluginManager= $shipping_quote_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('plugin.manager.uc_quote.method')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_quote_methods_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = array(
      'data' => $this->t('Shipping method'),
    );
    $header['description'] = array(
      'data' => $this->t('Description'),
      'class' => array(RESPONSIVE_PRIORITY_LOW),
    );
    $header['status'] = array(
      'data' => $this->t('Status'),
    );
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $plugin = $this->shippingQuotePluginManager->createInstance($entity->getPluginId(), $entity->getPluginConfiguration());
    $row['label'] = $entity->label();
    $row['description']['#markup'] = $plugin->getDescription();
    $row['status']['#markup'] = $entity->status() ? $this->t('Enabled') : $this->t('Disabled');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('No shipping methods have been configured yet.');
    return $build;
  }

}
