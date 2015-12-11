<?php

/**
 * @file
 * Contains \Drupal\test_gateway\Plugin\Ubercart\PaymentMethod\TestGateway.
 */

namespace Drupal\test_gateway\Plugin\Ubercart\PaymentMethod;

use Drupal\uc_credit\CreditCardPaymentMethodBase;

/**
 * Defines the test gateway payment method.
 *
 * @UbercartPaymentMethod(
 *   id = "test_gateway",
 *   name = @Translation("Test gateway"),
 * )
 */
class TestGateway extends CreditCardPaymentMethodBase {
}
