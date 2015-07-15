<?php

/**
 * @file
 * Contains \Drupal\uc_payment\Annotation\PaymentMethod.
 */

namespace Drupal\uc_payment\Annotation;

use Drupal\Component\Annotation\Plugin;


/**
 * Defines a payment method annotation object.
 *
 * @Annotation
 */
class PaymentMethod extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The administrative label of the payment method.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $name;

  /**
   * The administrative label of the payment method.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $title;

  /**
   * If the plugin is enabled by default for use at checkout.
   *
   * @var boolean
   */
  public $checkout;

  /**
   * If the plugin requires a payment gateway.
   *
   * @var boolean
   */
  public $no_gateway;

  /**
   * The class name of the plugin configuration form.
   *
   * @var string
   */
  public $settings_form;

  /**
   * The plugin weight.
   *
   * @var integer
   */
  public $weight;

}
