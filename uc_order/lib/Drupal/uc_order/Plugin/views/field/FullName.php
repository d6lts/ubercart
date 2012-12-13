<?php

/**
 * @file
 * Definition of Drupal\uc_order\Plugin\views\field\FullName.
 */

namespace Drupal\uc_order\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to provide full names.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "uc_order_full_name",
 *   module = "uc_order"
 * )
 */
class FullName extends FieldPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['format'] = array('default' => 'first_last');

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['format'] =  array(
      '#type' => 'select',
      '#title' => t('Format'),
      '#options' => array(
        'first_last' => t('First Last'),
        'last_c_first' => t('Last, First'),
        'last_first' => t('Last First'),
      ),
      '#default_value' => $this->options['format'],
    );
  }

  function render($values) {
    $first = $this->get_value($values);
    $last = $values->{$this->aliases['last_name']};

    switch ($this->options['format']) {
      case 'last_first':
        return $this->sanitizeValue("$last $first");
      case 'last_c_first':
        return $this->sanitizeValue("$last, $first");
      case 'first_last':
        return $this->sanitizeValue("$first $last");
    }
  }
}
