<?php
/**
 * @file
 * Theme functions for uc_product module.
 */

use Drupal\Core\Template\Attribute;

/**
 * Formats a product's model number.
 *
 * @param $variables
 *   An associative array containing:
 *   - model: Product model number, also known as SKU.
 *   - attributes: (optional) Array of attributes to apply to enclosing DIV.
 *
 * @ingroup themeable
 */
function theme_uc_product_model($variables) {
  $model = $variables['model'];
  $attributes = new Attribute($variables['attributes']);
  if (!isset($attributes['class'])) {
    $attributes['class'] = array();
  }
  $attributes['class'][] = "product-info";
  $attributes['class'][] = "model";

  $output = '<div ' . $attributes . '>';
  $output .= '<span class="product-info-label">' . t('SKU') . ':</span> ';
  $output .= '<span class="product-info-value">' . check_plain($model) . '</span>';
  $output .= '</div>';

  return $output;
}

/**
 * Formats a product's price.
 *
 * @param $variables
 *   An associative array containing:
 *   - element: An associative array render element containing:
 *     - #value: Price to be formatted.
 *     - #attributes: (optional) Array of attributes to apply to enclosing DIV.
 *     - #title: (optional) Title to be used as label.
 *
 * @ingroup themeable
 */
function theme_uc_product_price($variables) {
  $element = $variables['element'];
  $price = $element['#value'];
  $attributes = new Attribute($element['#attributes']);
  $attributes['class'][] = 'product-info';
  $label = isset($element['#title']) ? $element['#title'] : '';

  $output = '<div ' . $attributes . '>';
  if ($label) {
    $output .= '<span class="uc-price-label">' . $label . '</span> ';
  }
  $vars = array(
    '#theme' => 'uc_price',
    '#price' => $price,
  );
  if (!empty($element['#suffixes'])) {
    $vars['#suffixes'] = $element['#suffixes'];
  }
  $output .= drupal_render($vars);
  $output .= drupal_render_children($element);
  $output .= '</div>';

  return $output;
}

/**
 * Formats a product's weight.
 *
 * @param $variables
 *   An associative array containing:
 *   - amount: A numerical weight value.
 *   - units: String abbreviation representing the units of measure.
 *   - attributes: (optional) Array of attributes to apply to enclosing DIV.
 *
 * @see uc_weight_format()
 * @ingroup themeable
 */
function theme_uc_product_weight($variables) {
  $amount = $variables['amount'];
  $units = $variables['units'];
  $attributes = new Attribute($variables['attributes']);
  if (!isset($attributes['class'])) {
    $attributes['class'] = array();
  }
  $attributes['class'][] = "product-info";
  $attributes['class'][] = "weight";

  $output = '';
  if ($amount) {
    $output = '<div ' . $attributes . '>';
    $output .= '<span class="product-info-label">' . t('Weight') . ':</span> ';
    $output .= '<span class="product-info-value">' . uc_weight_format($amount, $units) . '</span>';
    $output .= '</div>';
  }

  return $output;
}

/**
 * Formats a product's length, width, and height.
 *
 * @param $variables
 *   An associative array containing:
 *   - length: A numerical length value.
 *   - width: A numerical width value.
 *   - height: A numerical height value.
 *   - units: String abbreviation representing the units of measure.
 *   - attributes: (optional) Array of attributes to apply to enclosing DIV.
 *
 * @see uc_length_format()
 * @ingroup themeable
 */
function theme_uc_product_dimensions($variables) {
  $length = $variables['length'];
  $width = $variables['width'];
  $height = $variables['height'];
  $units = $variables['units'];
  $attributes = new Attribute($variables['attributes']);
  if (!isset($attributes['class'])) {
    $attributes['class'] = array();
  }
  $attributes['class'][] = "product-info";
  $attributes['class'][] = "dimensions";

  $output = '';
  if ($length || $width || $height) {
    $output = '<div ' . $attributes . '>';
    $output .= '<span class="product-info-label">' . t('Dimensions') . ':</span> ';
    $output .= '<span class="product-info-value">' ;
    $output .= uc_length_format($length, $units) . ' × ';
    $output .= uc_length_format($width, $units) . ' × ';
    $output .= uc_length_format($height, $units) . '</span>';
    $output .= '</div>';
  }

  return $output;
}
