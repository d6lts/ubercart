<?php

/**
 * @file
 * Definition of Drupal\uc_roles\Tests\RolesTest.
 */

namespace Drupal\uc_roles\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Tests the role purchase functionality.
 */
class RolesTest extends UbercartTestBase {

  public static $modules = array('uc_payment', 'uc_payment_pack', 'uc_roles');

  public static function getInfo() {
    return array(
      'name' => 'Roles',
      'description' => 'Ensures that the purchase of roles functions correctly.',
      'group' => 'Ubercart',
    );
  }

  function testRolePurchaseCheckout() {
    // Add role assignment to the test product.
    $rid = $this->drupalCreateRole(array('access content'));
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('node/' . $this->product->id() . '/edit/features', array('feature' => 'role'), t('Add'));
    $edit = array(
      'uc_roles_role' => $rid,
      'end_override' => TRUE,
      'uc_roles_expire_relative_duration' => 1,
      'uc_roles_expire_relative_granularity' => 'day',
    );
    $this->drupalPostForm(NULL, $edit, t('Save feature'));

    // Check out with the test product.
    $this->addToCart($this->product);
    $order = $this->checkout();
    uc_payment_enter($order->id(), 'other', $order->getTotal());

    // Test that the role was granted.
    $account = $order->getUser();
    $this->assertTrue(isset($account->roles[$rid]), 'Existing user was granted role.');

    // Test that the email is correct.
    $mail = $this->findMail('/Ubercart: ' . preg_quote($account->roles[$rid]) . ' role granted/');

    // Delete the user.
    user_delete($order->getUserId());

    // Run cron to ensure deleted users are handled correctly.
    $this->drupalLogout();
    $this->cronRun();
  }
}
