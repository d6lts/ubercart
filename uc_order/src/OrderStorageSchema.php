<?php

/**
 * @file
 * Contains \Drupal\uc_order\OrderStorageSchema.
 */

namespace Drupal\uc_order;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the uc_order schema handler.
 */
class OrderStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    // @todo Create a numeric field type and use that instead.
    $schema['uc_orders']['fields']['order_total']['type'] = 'numeric';
    $schema['uc_orders']['fields']['order_total']['precision'] = 16;
    $schema['uc_orders']['fields']['order_total']['scale'] = 5;

    // Marking the respective fields as NOT NULL makes the indexes more
    // performant.
    $schema['uc_orders']['fields']['uid']['not null'] = TRUE;
    $schema['uc_orders']['fields']['order_status']['not null'] = TRUE;

    $schema['uc_orders']['indexes'] += array(
      'uid' => array('uid'),
      'order_status' => array('order_status'),
    );
    $schema['uc_orders']['foreign keys'] += array(
      'users' => array(
        'table' => 'users',
        'columns' => array('uid' => 'uid'),
      ),
    );

    return $schema;
  }

}
