<?php

/**
 * @file
 * Contains \Drupal\uc_product\Plugin\field\formatter\UcProductImageFormatter.
 */

namespace Drupal\uc_product\Plugin\field\formatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Field\FieldItemListInterface;
use Drupal\image\Plugin\field\formatter\ImageFormatterBase;

/**
 * Plugin implementation of the 'uc_product_image' formatter.
 *
 * @FieldFormatter(
 *   id = "uc_product_image",
 *   label = @Translation("Ubercart product images"),
 *   field_types = {
 *     "image"
 *   },
 *   settings = {
 *     "first_image_style" = "uc_product",
 *     "other_image_style" = "uc_thumbnail",
 *     "image_link" = "file"
 *   }
 * )
 */
class UcProductImageFormatter extends ImageFormatterBase {

  /**
   * Implements \Drupal\field\Plugin\Type\Formatter\FormatterInterface::settingsForm().
   */
  public function settingsForm(array $form, array &$form_state) {
    $image_styles = image_style_options(FALSE);
    $element['first_image_style'] = array(
      '#title' => t('First image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('first_image_style'),
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
    );
    $element['other_image_style'] = array(
      '#title' => t('Subsequent image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('other_image_style'),
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
    );

    $link_types = array(
      'content' => t('Content'),
      'file' => t('File'),
    );
    $element['image_link'] = array(
      '#title' => t('Link image to'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_link'),
      '#empty_option' => t('Nothing'),
      '#options' => $link_types,
    );

    return $element;
  }

  /**
   * Implements \Drupal\field\Plugin\Type\Formatter\FormatterInterface::settingsSummary().
   */
  public function settingsSummary() {
    $summary = array();

    $image_styles = image_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($image_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    $image_style_setting = $this->getSetting('first_image_style');
    if (!isset($image_styles[$image_style_setting])) {
      $image_styles[$image_style_setting] = t('Original image');
    }
    $summary[] = t('First image style: @style', array('@style' => $image_styles[$image_style_setting]));

    $image_style_setting = $this->getSetting('other_image_style');
    if (!isset($image_styles[$image_style_setting])) {
      $image_styles[$image_style_setting] = t('Original image');
    }
    $summary[] = t('Subsequent image style: @style', array('@style' => $image_styles[$image_style_setting]));

    $link_types = array(
      'content' => t('Linked to content'),
      'file' => t('Linked to file'),
    );
    // Display this setting only if image is linked.
    $image_link_setting = $this->getSetting('image_link');
    if (isset($link_types[$image_link_setting])) {
      $summary[] = $link_types[$image_link_setting];
    }

    return $summary;
  }

  /**
   * Implements \Drupal\field\Plugin\Type\Formatter\FormatterInterface::viewElements().
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    $image_link_setting = $this->getSetting('image_link');
    // Check if the formatter involves a link.
    if ($image_link_setting == 'content') {
      $uri = $items->getEntity()->uri();
    }
    elseif ($image_link_setting == 'file') {
      $link_file = TRUE;
    }

    $first_style = $this->getSetting('first_image_style');
    $other_style = $this->getSetting('other_image_style');
    foreach ($items as $delta => $item) {
      if ($item->entity) {
        if (isset($link_file)) {
          $image_uri = $item->entity->getFileUri();
          $uri = array(
            'path' => file_create_url($image_uri),
            'options' => array(),
          );
        }
        $elements[$delta] = array(
          '#theme' => 'image_formatter',
          '#item' => $item->getValue(TRUE),
          '#image_style' => $delta == 0 ? $first_style : $other_style,
          '#path' => isset($uri) ? $uri : '',
        );
      }
    }

    return $elements;
  }

}
