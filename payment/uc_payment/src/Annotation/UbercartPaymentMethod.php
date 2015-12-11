<?php

/**
 * @file
 * Contains \Drupal\uc_payment\Annotation\UbercartPaymentMethod.
 */

namespace Drupal\uc_payment\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an Ubercart payment method annotation object.
 *
 * @Annotation
 */
class UbercartPaymentMethod extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human readable name of the payment method.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $name;

  /**
   * If TRUE, the plugin will be hidden from the UI.
   *
   * @var boolean
   */
  public $no_ui = FALSE;

}
