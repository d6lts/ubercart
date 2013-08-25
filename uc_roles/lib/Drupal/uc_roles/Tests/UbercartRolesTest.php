<?php

/**
 * @file
 * Definition of Drupal\uc_roles\Tests\UbercartRolesTest.
 */

namespace Drupal\uc_roles\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Tests the role purchase functionality.
 */
class UbercartRolesTest extends UbercartTestBase {

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
    $this->drupalPost('node/' . $this->product->nid . '/edit/features', array('feature' => 'role'), t('Add'));
    $edit = array(
      'uc_roles_role' => $rid,
      'end_override' => TRUE,
      'uc_roles_expire_relative_duration' => 1,
      'uc_roles_expire_relative_granularity' => 'day',
    );
    $this->drupalPost(NULL, $edit, t('Save feature'));

    // Check out with the test product.
    $this->drupalPost('node/' . $this->product->nid, array(), t('Add to cart'));
    $order = $this->checkout();
    uc_payment_enter($order->id(), 'other', $order->order_total);

    // Test that the role was granted.
    $account = user_load($order->uid);
    $this->assertTrue(isset($account->roles[$rid]), 'Existing user was granted role.');

    // Test that the email is correct.
    $mail = $this->findMail('/Ubercart: ' . preg_quote($account->roles[$rid]) . ' role granted/');

    // Delete the user.
    user_delete($order->uid);

    // Run cron to ensure deleted users are handled correctly.
    $this->drupalLogout();
    $this->cronRun();
  }
}
