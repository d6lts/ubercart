<?php

/**
 * @file
 * Contains \Drupal\uc_product\Plugin\Field\FieldFormatter\UcDimensionsFormatter.
 */

namespace Drupal\uc_product\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the Ubercart dimensions formatter.
 *
 * @FieldFormatter(
 *   id = "uc_dimensions",
 *   label = @Translation("Dimensions"),
 *   field_types = {
 *     "uc_dimensions",
 *   }
 * )
 */
class UcDimensionsFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $dimensions = array();
      foreach (array('length', 'width', 'height') as $dimension) {
        if ($item->$dimension) {
          $dimensions[] = uc_length_format($item->$dimension, $item->units);
        }
      }
      if ($dimensions) {
        $elements[$delta] = array('#markup' => implode(' × ', $dimensions));
      }
    }

    return $elements;
  }

}
