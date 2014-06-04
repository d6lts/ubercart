<?php

/**
 * @file
 * Contains \Drupal\uc_roles\Tests\RolesTest.
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

  public function testRolePurchaseCheckout() {
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
    // @todo Re-enable when Rules is available.
    // $this->assertTrue($order->getUser()->hasRole($rid), 'Existing user was granted role.');

    // Test that the email is correct.
    $role = entity_load('user_role', $rid);
    // @todo Re-enable when Rules is available.
    // $this->assertMailString('subject', $role->label(), 4, 'Role assignment email mentions role in subject line.');

    // Delete the user.
    user_delete($order->getUserId());

    // Run cron to ensure deleted users are handled correctly.
    $this->cronRun();
  }
}
