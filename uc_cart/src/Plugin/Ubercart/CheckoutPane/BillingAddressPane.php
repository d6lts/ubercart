<?php

/**
 * @file
 * Contains \Drupal\uc_cart\Plugin\Ubercart\CheckoutPane\BillingAddressPane.
 */

namespace Drupal\uc_cart\Plugin\Ubercart\CheckoutPane;

/**
 * Gets the user's billing address.
 *
 * @CheckoutPane(
 *   id = "billing",
 *   title = @Translation("Billing information"),
 *   weight = 4
 * )
 */
class BillingAddressPane extends AddressPaneBase {

  /**
   * {@inheritdoc}
   */
  protected function getDescription() {
    return $this->t('Enter your billing address and information here.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getCopyAddressText() {
    return $this->t('My billing information is the same as my delivery information.');
  }

}
