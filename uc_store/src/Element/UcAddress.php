<?php

/**
 * @file
 * Contains \Drupal\uc_store\Element\UcAddress.
 */

namespace Drupal\uc_store\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\uc_store\Address;

/**
 * Provides a form element for Ubercart address input.
 *
 * @FormElement("uc_address")
 */
class UcAddress extends Element\FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#required' => TRUE,
      '#process' => array(
        array($class, 'processAddress'),
      ),
      '#attributes' => array('class' => array('uc-store-address-field')),
      '#theme_wrappers' => array('container'),
      '#key_prefix' => '',
      '#hidden' => FALSE,
    );
  }

  /**
   * #process callback for address fields.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic input element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processAddress(&$element, FormStateInterface $form_state, &$complete_form) {
    $labels = array(
      'first_name' => t('First name'),
      'last_name' => t('Last name'),
      'company' => t('Company'),
      'street1' => t('Street address'),
      'street2' => t(''),
      'city' => t('City'),
      'zone' => t('State/Province'),
      'country' => t('Country'),
      'postal_code' => t('Postal code'),
      'phone' => t('Phone number'),
      'email' => t('E-mail'),
    );

    $element['#tree'] = TRUE;
    $prefix = $element['#key_prefix'] ? ($element['#key_prefix'] . '_') : '';
    $config = \Drupal::config('uc_store.settings')->get('address_fields');

    if ($form_state->has('uc_address')) {
      // Use submitted Ajax values.
      $value = $form_state->get('uc_address');
    }
    elseif (is_array($element['#value']) || is_object($element['#value'])) {
      // Use provided default value.
      $value = $element['#value'];
    }
    else {
      $value = array();
    }

    $country_names = \Drupal::service('country_manager')->getEnabledList();
    $country_keys = array_keys($country_names);

    // Force the selected country to a valid one, so the zone dropdown matches.
    if (is_object($value)) {
      if (isset($value->{$prefix . 'country'}) && !isset($country_names[$value->{$prefix . 'country'}])) {
        $value->{$prefix . 'country'} = $country_keys[0];
      }
    }
    else {
      if (isset($value[$prefix . 'country']) && !isset($country_names[$value[$prefix . 'country']])) {
        $value[$prefix . 'country'] = $country_keys[0];
      }
    }

    // Iterating on the Address object excludes non-public properties, which
    // is exactly what we want to do.
    $address = new Address();
    foreach ($address as $base_field => $field_value) {
      $field = $prefix . $base_field;
      if (is_object($value) ? !property_exists($value, $field) : !isset($value[$field])) {
        // if (!isset($value[$field])) {
        continue;
      }

      switch ($base_field) {
        case 'country':
          $subelement = array(
            '#type' => 'select',
            '#options' => $country_names,
            '#ajax' => array(
              'callback' => array(get_class(), 'updateZone'),
              'wrapper' => 'uc-store-address-' . str_replace('_', '-', $prefix) . 'zone-wrapper',
              'progress' => array(
                'type' => 'throbber',
              ),
            ),
            '#element_validate' => array(
              array(get_class(), 'validateCountry'),
            ),
            '#key_prefix' => $element['#key_prefix'],
          );
          break;

        case 'zone':
          $subelement = array(
            '#prefix' => '<div id="uc-store-address-' . str_replace('_', '-', $prefix) . 'zone-wrapper">',
            '#suffix' => '</div>',
          );

          $country_id = is_object($value) ? $value->{$prefix . 'country'} : $value[$prefix . 'country'];
          $zones = \Drupal::service('country_manager')->getZoneList($country_id);
          if (!empty($zones)) {
            natcasesort($zones);
            $subelement += array(
              '#type' => 'select',
              '#options' => $zones,
              '#empty_value' => 0,
            );
          }
          else {
            $subelement += array(
              '#type' => 'hidden',
              '#value' => 0,
              '#required' => FALSE,
            );
          }
          break;

        case 'postal_code':
          $subelement = array(
            '#type' => 'textfield',
            '#size' => 10,
            '#maxlength' => 10,
          );
          break;

        case 'phone':
          $subelement = array(
            '#type' => 'textfield',
            '#size' => 16,
            '#maxlength' => 32,
          );
          break;

        default:
          $subelement = array(
            '#type' => 'textfield',
            '#size' => 32,
          );
      }

      // Copy JavaScript states from the parent element.
      if (isset($element['#states'])) {
        $subelement['#states'] = $element['#states'];
      }

      // Set common values for all address fields.
      $element[$field] = $subelement + array(
        '#title' => $labels[$base_field] ? $labels[$base_field] : '&nbsp;',
        '#default_value' => is_object($value) ? $value->$field : $value[$field],
        '#parents' => array_merge(array_slice($element['#parents'], 0, -1), array($field)),
        '#pre_render' => array_merge(array(array(get_class(), 'preRenderAddressField')), \Drupal::service('element_info')->getInfoProperty($subelement['#type'], '#pre_render', array())),
        '#access' => !$element['#hidden'] && !empty($config[$base_field]['status']),
        '#required' => $element['#required'] && !empty($config[$base_field]['required']),
        '#weight' => isset($config[$base_field]['weight']) ? $config[$base_field]['weight'] : 0,
      );
    }
    return $element;
  }

  /**
   * Element validation callback for country field.
   *
   * Store the current address for use when rebuilding the form.
   */
  public static function validateCountry($element, FormStateInterface $form_state) {
    $address = NestedArray::getValue($form_state->getValues(), array_slice($element['#parents'], 0, -1));
    $form_state->set('uc_address', $form_state->has('uc_address') ? array_merge($form_state->get('uc_address'), $address) : $address);
  }

  /**
   * Ajax callback: updates the zone select box when the country is changed.
   */
  public static function updateZone($form, FormStateInterface $form_state) {
    $element = &$form;
    $triggering_element = $form_state->getTriggeringElement();
    foreach (array_slice($triggering_element['#array_parents'], 0, -1) as $field) {
      $element = &$element[$field];
    }
    $prefix = empty($element['#key_prefix']) ? '' : ($element['#key_prefix'] . '_');
    return $element[$prefix . 'zone'];
  }

  /**
   * Prerenders address field elements to move the required marker when needed.
   */
  public static function preRenderAddressField($element) {
    if (!empty($element['#required'])) {
      $marker = array(
        '#theme' => 'form_required_marker',
        '#element' => $element,
      );
      $element['#title'] = drupal_render($marker) . ' ' . $element['#title'];
      unset($element['#required']);
    }
    return $element;
  }

}
