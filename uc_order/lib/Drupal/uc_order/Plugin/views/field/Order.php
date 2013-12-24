<?php

/**
 * @file
 * Contains \Drupal\uc_order\Plugin\views\field\Order.
 */

namespace Drupal\uc_order\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Field handler to provide simple renderer that allows linking to an order.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("uc_order")
 */
class Order extends FieldPluginBase {

  /**
   * Override init function to provide generic option to link to user.
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    if (!empty($this->options['link_to_order'])) {
      $this->additional_fields['order_id'] = array('table' => 'uc_orders', 'field' => 'order_id');
      $this->additional_fields['uid'] = array('table' => 'uc_orders', 'field' => 'uid');
    }
  }

  /**
   * Overrides FieldPluginBase::defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_order'] = array('default' => FALSE, 'bool' => TRUE);
    return $options;
  }

  /**
   * Overrides FieldPluginBase::buildOptionsForm().
   *
   * Provide link to order option
   */
  public function buildOptionsForm(&$form, &$form_state) {
    $form['link_to_order'] = array(
      '#title' => t('Link this field to the order view page'),
      '#description' => t("Enable to override this field's links."),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link_to_order'],
    );
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Renders whatever the data is as a link to the order.
   *
   * Data should be made XSS safe prior to calling this function.
   */
  protected function render_link($data, $values) {
    $account = \Drupal::currentUser();
    if (!empty($this->options['link_to_order'])) {
      $this->options['alter']['make_link'] = FALSE;

      if ($account->hasPermission('view all orders')) {
        $path = 'admin/store/orders/' . $this->getValue($values, 'order_id');
      }
      elseif ($account->hasPermission('view own orders') && $this->getValue($values, 'uid') == $GLOBALS['user']->id()) {
        $path = 'user/' . $GLOBALS['user']->id() . '/orders/' . $this->getValue($values, 'order_id');
      }
      else {
        $path = FALSE;
      }

      if ($path && $data !== NULL && $data !== '') {
        $this->options['alter']['make_link'] = TRUE;
        $this->options['alter']['path'] = $path;
      }
    }
    return $data;
  }

  /**
   * Overrides FieldPluginBase::render().
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    return $this->render_link($this->sanitizeValue($value), $values);
  }

}
