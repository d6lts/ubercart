<?php

/**
 * @file
 * Definition of Drupal\uc_store\Plugin\views\field\Weight.
 */

namespace Drupal\uc_store\Plugin\views\field;

use Drupal\views\Plugin\views\field\Numeric;
use Drupal\Component\Annotation\Plugin;

/**
 * Field handler to provide formatted weights.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "uc_weight",
 *   module = "uc_store"
 * )
 */
class Weight extends Numeric {

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['format'] = array('default' => 'uc_weight');

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['format'] =  array(
      '#title' => t('Format'),
      '#type' => 'radios',
      '#options' => array(
        'uc_weight' => t('Ubercart weight'),
        'numeric' => t('Numeric'),
      ),
      '#default_value' => $this->options['format'],
      '#weight' => -1,
    );

    foreach (array('separator', 'format_plural', 'prefix', 'suffix') as $field) {
      $form[$field]['#states']['visible']['input[name="options[format]"]']['value'] = 'numeric';
    }
  }

  function render($values) {
    if ($this->options['format'] == 'uc_weight') {
      $value = $this->get_value($values);

      if (is_null($value) || ($value == 0 && $this->options['empty_zero'])) {
        return '';
      }

      return uc_weight_format($value, $values->{$this->aliases['weight_units']});
    }
    else {
      return parent::render($values);
    }
  }
}
