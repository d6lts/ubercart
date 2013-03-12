<?php

/**
 * @file
 * Definition of Drupal\uc_product\Plugin\views\field\DisplayPrice.
 */

namespace Drupal\uc_product\Plugin\views\field;

use Drupal\uc_store\Plugin\views\field\Price;
use Drupal\Component\Annotation\Plugin;

/**
 * Field handler to provide formatted display prices.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "uc_product_display_price",
 *   module = "uc_product"
 * )
 */
class DisplayPrice extends Price {

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['label']['default'] = t('Price');

    return $options;
  }

  function get_value($values, $field = NULL) {
    $nid = parent::get_value($values, $field);
    if (!is_null($nid)) {
      // !TODO Refactor so that all variants are loaded at once in the pre_render hook.
      $node = node_view(node_load($nid), 'teaser');
      return $node['display_price']['#value'];
    }
  }

  public function clickSort($order) {
    $params = $this->options['group_type'] != 'group' ? array('function' => $this->options['group_type']) : array();
    $this->query->addOrderBy(NULL, NULL, $order, 'sell_price', $params);
  }

}
