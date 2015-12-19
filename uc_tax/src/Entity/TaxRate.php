<?php

/**
 * @file
 * Contains \Drupal\uc_tax\Entity\TaxRate.
 */

namespace Drupal\uc_tax\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\uc_tax\TaxRateInterface;

/**
 * Defines a tax rate configuration entity.
 *
 * @ConfigEntityType(
 *   id = "uc_tax_rate",
 *   label = @Translation("Tax rate"),
 *   handlers = {
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "list_builder" = "Drupal\uc_tax\Controller\TaxRateListBuilder",
 *     "form" = {
 *       "add" = "Drupal\uc_tax\Form\TaxRateAddForm",
 *       "edit" = "Drupal\uc_tax\Form\TaxRateEditForm",
 *       "delete" = "Drupal\uc_tax\Form\TaxRateDeleteForm"
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_prefix = "rate",
 *   admin_permission = "administer taxes",
 *   links = {
 *     "edit-form" = "/admin/store/config/tax/{uc_tax_rate}",
 *     "delete-form" = "/admin/store/config/tax/{uc_tax_rate}/delete",
 *     "clone" = "/admin/store/config/tax/{uc_tax_rate}/clone"
 *   }
 * )
 */
class TaxRate extends ConfigEntityBase implements TaxRateInterface {

  /**
   * The tax rate ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The tax rate label.
   *
   * @var string
   */
  protected $label;

  /**
   * The tax rate.
   *
   * @var float
   */
  protected $rate;

  /**
   * The tax rate weight.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * The taxing authority jurisdiction.
   *
   * @var string
   */
  protected $jurisdiction;

  /**
   * Whether to display prices including tax.
   *
   * @var bool
   */
  protected $display_include;

  /**
   * The text to display next to prices if tax is included.
   *
   * @var string
   */
  protected $inclusion_text;

  /**
   * If the tax applies only to shippable products.
   *
   * @var string
   */
  protected $shippable;

  /**
   * Line item types subject to this tax rate.
   *
   * @var string[]
   */
  protected $line_item_types;

  /**
   * Product item types subject to this tax rate.
   *
   * @var string[]
   */
  protected $product_types;


  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    $this->id = $id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label();
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRate() {
    return $this->rate;
  }

  /**
   * {@inheritdoc}
   */
  public function setRate($rate) {
    $this->rate = $rate;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getJurisdiction() {
    return $this->jurisdiction;
  }

  /**
   * {@inheritdoc}
   */
  public function setJurisdiction($jurisdiction) {
    $this->jurisdiction = $jurisdiction;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProductTypes() {
    return $this->product_types;
  }

  /**
   * {@inheritdoc}
   */
  public function setProductTypes(array $product_types) {
    $this->product_types = $product_types;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLineItemTypes() {
    return $this->line_item_types;
  }

  /**
   * {@inheritdoc}
   */
  public function setLineItemTypes(array $line_item_types) {
    $this->line_item_types = $line_item_types;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isIncludedInPrice() {
    return $this->display_include;
  }

  /**
   * {@inheritdoc}
   */
  public function setIncludedInPrice($included) {
    $this->display_include = $included;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getInclusionText() {
    return $this->inclusion_text;
  }

  /**
   * {@inheritdoc}
   */
  public function setInclusionText($inclusion_text) {
    $this->inclusion_text = $inclusion_text;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isForShippable() {
    return $this->shippable;
  }

  /**
   * {@inheritdoc}
   */
  public function setForShippable($shippable) {
    $this->shippable = $shippable;
    return $this;
  }

}
