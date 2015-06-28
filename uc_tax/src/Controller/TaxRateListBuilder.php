<?php

/**
 * @file
 * Contains \Drupal\uc_tax\Controller\TaxRateListBuilder.
 */

namespace Drupal\uc_tax\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of tax rate configuration entities.
 *
 * Drupal locates the list controller by looking for the "list" entry under
 * "controllers" in our entity type's annotation. We define the path on which
 * the list may be accessed in our module's *.routing.yml file. The key entry
 * to look for is "_entity_list". In *.routing.yml, "_entity_list" specifies
 * an entity type ID. When a user navigates to the URL for that router item,
 * Drupal loads the annotation for that entity type. It looks for the "list"
 * entry under "controllers" for the class to load.
 */
class TaxRateListBuilder extends ConfigEntityListBuilder {

  /**
   * Builds the header row for the entity listing.
   *
   * @return array
   *   A render array structure of header strings.
   *
   * @see Drupal\Core\Entity\EntityListController::render()
   */
  public function buildHeader() {
    $header['label'] = $this->t('Name');
    $header['rate'] = $this->t('Rate');
    $header['jurisdiction'] = $this->t('Jurisdiction');
    $header['shippable'] = $this->t('Taxed products');
    $header['product_types'] = $this->t('Taxed product types');
    $header['line_item_types'] = $this->t('Taxed line items');
    $header['weight'] = $this->t('Weight');

    return $header + parent::buildHeader();
  }

  /**
   * Builds a row for an entity in the entity listing.
   *
   * @param EntityInterface $entity
   *   The entity for which to build the row.
   *
   * @return array
   *   A render array of the table row for displaying the entity.
   *
   * @see Drupal\Core\Entity\EntityListController::render()
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $this->getLabel($entity);
    $row['rate'] = ((float) $entity->getRate() * 100) . '%' ;
    $row['jurisdiction'] = $entity->getJurisdiction();
    $row['shippable'] = $entity->isForShippable() ? $this->t('Shippable products') : $this->t('Any product');
    $row['product_types'] = implode(', ', $entity->getProductTypes());
    $row['line_item_types'] = implode(', ', $entity->getLineItemTypes());
    $row['weight'] = $entity->getWeight();
//    $row['weight'] = array(
//      '#type' => 'weight',
//      '#default_value' => $entity->getWeight(),
//      '#attributes' => array('class' => array('uc-tax-method-weight')),
//    );
      
    //$row['weight']['#attributes'] = array('class' => array('uc-quote-method-weight'));

    return $row + parent::buildRow($entity);
  }

  public function buildOperations(EntityInterface $entity) {
    $build = parent::buildOperations($entity);
    $build['#links']['clone'] = array(
      'title' => $this->t('Clone'), 
      'url' => Url::fromRoute('entity.uc_tax_rate.clone', ['uc_tax_rate' => $entity->id()]),
      'weight' => 10,  // 'edit' is 0, 'delete' is 100
    );

    uasort($build['#links'], 'Drupal\Component\Utility\SortArray::sortByWeightElement');
    return $build;
  }

  /**
   * Adds some descriptive text to our entity list.
   *
   * Typically, there's no need to override render(). You may wish to do so,
   * however, if you want to add markup before or after the table.
   *
   * @return array
   *   Renderable array.
   */
  public function render() {
    $build['description'] = array(
      '#markup' => $this->t("<p>This is a list of the tax rates currently"
        . " defined on your Drupal site.</p><p>You may use the 'Add a tax rate'"
        . " button to add a new rate, or use the widget in the 'Operations'"
        . " column to edit, delete, or clone existing tax rates.</p>"),
    );
    $build += parent::render();
    $build['table']['#empty'] = $this->t('No tax rates have been configured yet.');
    $build['table']['#tabledrag'] = array(array(
      'action' => 'order',
      'relationship' => 'sibling',
      'group' => 'uc-tax-method-weight',
    ));
    return $build;
  }

}
