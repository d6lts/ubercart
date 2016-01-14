<?php

/**
 * @file
 * Contains \Drupal\uc_role\Tests\RoleTest.
 */

namespace Drupal\uc_role\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests the role purchase functionality.
 *
 * @group Ubercart
 */
class RoleTest extends UbercartTestBase {

  public static $modules = array('uc_payment', 'uc_payment_pack', 'uc_role');

  public function testRolePurchaseCheckout() {
    // Add role assignment to the test product.
    $rid = $this->drupalCreateRole(array('access content'));
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('node/' . $this->product->id() . '/edit/features', array('feature' => 'role'), t('Add'));
    $edit = array(
      'role' => $rid,
      'end_override' => TRUE,
      'expire_relative_duration' => 1,
      'expire_relative_granularity' => 'day',
    );
    $this->drupalPostForm(NULL, $edit, t('Save feature'));

    // Check out with the test product.
    $method = $this->createPaymentMethod('other');
    $this->addToCart($this->product);
    $order = $this->checkout();
    uc_payment_enter($order->id(), $method['id'], $order->getTotal());

    // Test that the role was granted.
    // @todo Re-enable when Rules is available.
    // $this->assertTrue($order->getOwner()->hasRole($rid), 'Existing user was granted role.');

    // Test that the email is correct.
    $role = Role::load($rid);
    // @todo Re-enable when Rules is available.
    // $this->assertMailString('subject', $role->label(), 4, 'Role assignment email mentions role in subject line.');

    // Test that the role product / user relation is deleted with the user.
    user_delete($order->getOwnerId());

    // Run cron to ensure deleted users are handled correctly.
    $this->cronRun();
  }
}
