<?php

/**
 * @file
 * Contains \Drupal\uc_product\Plugin\Field\FieldType\UcPriceItem.
 */

namespace Drupal\uc_product\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\NumericItemBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the Ubercart price field type.
 *
 * @FieldType(
 *   id = "uc_price",
 *   label = @Translation("Price"),
 *   description = @Translation("This field stores a price in the database."),
 *   default_widget = "uc_price",
 *   default_formatter = "uc_price"
 * )
 */
class UcPriceItem extends NumericItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('float')
      ->setLabel(t('Amount'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'numeric',
          'precision' => 16,
          'scale' => 5,
          'not null' => FALSE,
        ),
      ),
    );
  }

}
