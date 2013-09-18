<?php

/**
 * @file
 * Definition of Drupal\uc_cart\Tests\UbercartCartCheckoutTest.
 */

namespace Drupal\uc_cart\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;
use stdClass;

/**
 * Tests the cart and checkout functionality.
 */
class UbercartCartCheckoutTest extends UbercartTestBase {

  public static $modules = array('uc_payment', 'uc_payment_pack', 'uc_roles');

  public static function getInfo() {
    return array(
      'name' => 'Cart and checkout',
      'description' => 'Ensures the cart and checkout process is functioning for both anonymous and authenticated users.',
      'group' => 'Ubercart',
    );
  }

  function testCartAPI() {
    // Test the empty cart.
    $items = uc_cart_get_contents();
    $this->assertEqual($items, array(), 'Cart is an empty array.');

    // Add an item to the cart.
    uc_cart_add_item($this->product->id());

    $items = uc_cart_get_contents();
    $this->assertEqual(count($items), 1, 'Cart contains one item.');
    $item = reset($items);
    $this->assertEqual($item->nid, $this->product->id(), 'Cart item nid is correct.');
    $this->assertEqual($item->qty, 1, 'Cart item quantity is correct.');

    // Add more of the same item.
    $qty = mt_rand(1, 100);
    uc_cart_add_item($this->product->id(), $qty);

    $items = uc_cart_get_contents();
    $this->assertEqual(count($items), 1, 'Updated cart contains one item.');
    $item = reset($items);
    $this->assertEqual($item->qty, $qty + 1, 'Updated cart item quantity is correct.');

    // Set the quantity and data.
    $qty = mt_rand(1, 100);
    $item->qty = $qty;
    $item->data['updated'] = TRUE;
    uc_cart_update_item($item);

    $items = uc_cart_get_contents();
    $item = reset($items);
    $this->assertEqual($item->qty, $qty, 'Set cart item quantity is correct.');
    $this->assertTrue($item->data['updated'], 'Set cart item data is correct.');

    // Add an item with different data to the cart.
    uc_cart_add_item($this->product->id(), 1, array('test' => TRUE));

    $items = uc_cart_get_contents();
    $this->assertEqual(count($items), 2, 'Updated cart contains two items.');

    // Remove the items.
    foreach ($items as $item) {
      uc_cart_remove_item($item->nid, NULL, $item->data);
    }
    // @TODO: remove the need for this
    uc_cart_get_contents(NULL, 'rebuild');

    $items = uc_cart_get_contents();
    $this->assertEqual(count($items), 0, 'Cart is empty after removal.');

    // Empty the cart.
    uc_cart_add_item($this->product->id());
    uc_cart_empty();

    $items = uc_cart_get_contents();
    $this->assertEqual($items, array(), 'Cart is emptied correctly.');
  }

  function testCart() {
    module_enable(array('uc_cart_entity_test'));

    // Test the empty cart.
    $this->drupalGet('cart');
    $this->assertText('There are no products in your shopping cart.');

    // Add an item to the cart.
    $this->drupalPostForm('node/' . $this->product->id(), array(), t('Add to cart'));
    $this->assertText($this->product->label() . ' added to your shopping cart.');
    $this->assertText('hook_uc_cart_item_insert fired');

    // Test the cart page.
    $this->drupalGet('cart');
    $this->assertText($this->product->label(), t('The product is in the cart.'));
    $this->assertFieldByName('items[0][qty]', 1, t('The product quantity is 1.'));

    // Add the item again.
    $this->drupalPostForm('node/' . $this->product->id(), array(), t('Add to cart'));
    $this->assertText('Your item(s) have been updated.');
    $this->assertText('hook_uc_cart_item_update fired');

    // Test the cart page again.
    $this->drupalGet('cart');
    $this->assertFieldByName('items[0][qty]', 2, t('The product quantity is 2.'));

    // Update the quantity.
    $qty = mt_rand(3, 100);
    $this->drupalPostForm('cart', array('items[0][qty]' => $qty), t('Update cart'));
    $this->assertText('Your cart has been updated.');
    $this->assertFieldByName('items[0][qty]', $qty, t('The product quantity was updated.'));
    $this->assertText('hook_uc_cart_item_update fired');

    // Update the quantity to zero.
    $this->drupalPostForm('cart', array('items[0][qty]' => 0), t('Update cart'));
    $this->assertText('Your cart has been updated.');
    $this->assertText('There are no products in your shopping cart.');
    $this->assertText('hook_uc_cart_item_delete fired');

    // Test the remove item button.
    $this->drupalPostForm('node/' . $this->product->id(), array(), t('Add to cart'));
    $this->drupalPostForm('cart', array(), t('Remove'));
    $this->assertText($this->product->label() . ' removed from your shopping cart.');
    $this->assertText('There are no products in your shopping cart.');
    $this->assertText('hook_uc_cart_item_delete fired');
  }

  function testCartMerge() {
    // Add an item to the cart as an anonymous user.
    $this->drupalLogin($this->customer);
    $this->drupalPostForm('node/' . $this->product->id(), array(), t('Add to cart'));
    $this->assertText($this->product->label() . ' added to your shopping cart.');
    $this->drupalLogout();

    // Add an item to the cart as an anonymous user.
    $this->drupalPostForm('node/' . $this->product->id(), array(), t('Add to cart'));
    $this->assertText($this->product->label() . ' added to your shopping cart.');

    // Log in and check the items are merged.
    $this->drupalLogin($this->customer);
    $this->drupalGet('cart');
    $this->assertText($this->product->label(), t('The product remains in the cart after logging in.'));
    $this->assertFieldByName('items[0][qty]', 2, t('The product quantity is 2.'));
  }

  function testDeletedCartItem() {
    // Add a product to the cart, then delete the node.
    $this->drupalPostForm('node/' . $this->product->id(), array(), t('Add to cart'));
    $this->product->delete();

    // Test that the cart is empty.
    $this->drupalGet('cart');
    $this->assertText('There are no products in your shopping cart.');
    $this->assertEqual(uc_cart_get_total_qty(), 0, 'There are no items in the cart.');
  }

  // function testMaximumQuantityRule() {
  //   // Enable the example maximum quantity rule.
  //   $rule = rules_config_load('uc_cart_maximum_product_qty');
  //   $rule->active = TRUE;
  //   $rule->save();

  //   // Try to add more items than allowed to the cart.
  //   $this->drupalPostForm('node/' . $this->product->id(), array(), t('Add to cart'));
  //   $this->drupalPostForm('cart', array('items[0][qty]' => 11), t('Update cart'));

  //   // Test the restriction was applied.
  //   $this->assertText('You are only allowed to order a maximum of 10 of ' . $this->product->label() . '.');
  //   $this->assertFieldByName('items[0][qty]', 10);
  // }

  function testBasicCheckout() {
    $this->drupalPostForm('node/' . $this->product->id(), array(), t('Add to cart'));
    $this->checkout();
    $this->assertRaw('Your order is complete!');
  }

  function testCheckout() {
    // Allow customer to specify username and password, but don't log in after checkout.
    $settings = array(
      'uc_cart_new_account_name' => TRUE,
      'uc_cart_new_account_password' => TRUE,
      'uc_new_customer_login' => FALSE,
    );
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('admin/store/settings/checkout', $settings, t('Save configuration'));
    $this->drupalLogout();

    $new_user = new stdClass();
    $new_user->name = $this->randomName(20);
    $new_user->pass_raw = $this->randomName(20);

    // Test as anonymous user.
    $this->drupalPostForm('node/' . $this->product->id(), array(), t('Add to cart'));
    $this->checkout(array(
      'panes[customer][new_account][name]' => $new_user->name,
      'panes[customer][new_account][pass]' => $new_user->pass_raw,
      'panes[customer][new_account][pass_confirm]' => $new_user->pass_raw,
    ));
    $this->assertRaw('Your order is complete!');
    $this->assertText($new_user->name, 'Username is shown on screen.');
    $this->assertNoText($new_user->pass_raw, 'Password is not shown on screen.');

    // Check that cart is now empty.
    $this->drupalGet('cart');
    $this->assertText('There are no products in your shopping cart.');

    // Test new account email.
    $mail = $this->drupalGetMails(array('id' => 'user_register_no_approval_required'));
    $mail = array_pop($mail);
    $this->assertTrue(strpos($mail['body'], $new_user->name) !== FALSE, 'Mail body contains username.');

    // Test invoice email.
    $mail = $this->drupalGetMails(array('subject' => 'Your Order at Ubercart'));
    $mail = array_pop($mail);
    $this->assertTrue(strpos($mail['body'], $new_user->name) !== FALSE, 'Invoice body contains username.');
    $this->assertFalse(strpos($mail['body'], $new_user->pass_raw) !== FALSE, 'Mail body does not contain password.');

    // Check that the password works.
    $edit = array(
      'name' => $new_user->name,
      'pass' => $new_user->pass_raw
    );
    $this->drupalPostForm('user', $edit, t('Log in'));

    // Test again as authenticated user.
    $this->drupalPostForm('node/' . $this->product->id(), array(), t('Add to cart'));
    $this->checkout();
    $this->assertRaw('Your order is complete!');
    $this->assertRaw('While logged in');

    // Test again with generated username and password.
    $this->drupalLogout();
    $this->drupalPostForm('node/' . $this->product->id(), array(), t('Add to cart'));
    $this->checkout();
    $this->assertRaw('Your order is complete!');

    // Test new account email.
    $mail = $this->drupalGetMails(array('id' => 'user_register_no_approval_required'));
    $mail = array_pop($mail);
    $new_user = new stdClass();
    $new_user->name = $mail['params']['account']->name;
    $new_user->pass_raw = $mail['params']['account']->password;
    $this->assertTrue(!empty($new_user->name), 'New username is not empty.');
    $this->assertTrue(!empty($new_user->pass_raw), 'New password is not empty.');
    $this->assertTrue(strpos($mail['body'], $new_user->name) !== FALSE, 'Mail body contains username.');

    // Test invoice email.
    $mail = $this->drupalGetMails(array('subject' => 'Your Order at Ubercart'));
    $mail = array_pop($mail);
    $this->assertTrue(strpos($mail['body'], $new_user->name) !== FALSE, 'Invoice body contains username.');
    $this->assertTrue(strpos($mail['body'], $new_user->pass_raw) !== FALSE, 'Invoice body contains password.');

    // We can check the password now we know it.
    $this->assertText($new_user->name, 'Username is shown on screen.');
    $this->assertText($new_user->pass_raw, 'Password is shown on screen.');

    // Check that the password works.
    $edit = array(
      'name' => $new_user->name,
      'pass' => $new_user->pass_raw
    );
    $this->drupalPostForm('user', $edit, t('Log in'));

    // Test again with an existing email address
    $this->drupalLogout();
    $this->drupalPostForm('node/' . $this->product->id(), array(), t('Add to cart'));
    $this->checkout(array('panes[customer][primary_email]' => $this->customer->getEmail()));
    $this->assertRaw('Your order is complete!');
    $this->assertRaw('order has been attached to the account we found');
  }

  function testCheckoutNewUsername() {
    // Configure the checkout for this test.
    $this->drupalLogin($this->adminUser);
    $settings = array(
      // Allow customer to specify username.
      'uc_cart_new_account_name' => TRUE,
      // Disable address panes.
      'uc_pane_delivery_enabled' => FALSE,
      'uc_pane_billing_enabled' => FALSE,
    );
    $this->drupalPostForm('admin/store/settings/checkout/panes', $settings, t('Save configuration'));
    $this->drupalLogout();

    // Test with an account that already exists.
    $this->drupalPostForm('node/' . $this->product->id(), array(), t('Add to cart'));
    $edit = array(
      'panes[customer][primary_email]' => $this->randomName(8) . '@example.com',
      'panes[customer][new_account][name]' => $this->adminUser->name,
    );
    $this->drupalPostForm('cart/checkout', $edit, 'Review order');
    $this->assertText('The username ' . $this->adminUser->name . ' is already taken.');

    // Let the account be automatically created instead.
    $edit = array(
      'panes[customer][primary_email]' => $this->randomName(8) . '@example.com',
      'panes[customer][new_account][name]' => '',
    );
    $this->drupalPostForm('cart/checkout', $edit, 'Review order');
    $this->drupalPostForm(NULL, array(), 'Submit order');
    $this->assertText('Your order is complete!');
    $this->assertText('A new account has been created');
  }

  function testCheckoutBlockedUser() {
    // Block user after checkout.
    $settings = array(
      'uc_new_customer_status_active' => FALSE,
    );
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('admin/store/settings/checkout', $settings, t('Save configuration'));
    $this->drupalLogout();

    // Test as anonymous user.
    $this->drupalPostForm('node/' . $this->product->id(), array(), t('Add to cart'));
    $this->checkout();
    $this->assertRaw('Your order is complete!');

    // Test new account email.
    $mail = $this->drupalGetMails(array('id' => 'user_register_pending_approval'));
    $this->assertTrue(!empty($mail), 'Blocked user email found.');
    $mail = $this->drupalGetMails(array('id' => 'user_register_no_approval_required'));
    $this->assertTrue(empty($mail), 'No unblocked user email found.');
  }

  function testCheckoutLogin() {
    // Log in after checkout.
    $settings = array(
      'uc_new_customer_login' => TRUE,
    );
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('admin/store/settings/checkout', $settings, t('Save configuration'));
    $this->drupalLogout();

    // Test checkout.
    $this->drupalPostForm('node/' . $this->product->id(), array(), t('Add to cart'));
    $this->checkout();
    $this->assertRaw('Your order is complete!');
    $this->assertRaw('you are already logged in');

    // Confirm login.
    $this->drupalGet('<front>');
    $this->assertText('My account', 'User is logged in.');

    // Check that cart is now empty.
    $this->drupalGet('cart');
    $this->assertText('There are no products in your shopping cart.');
  }

  function testCheckoutComplete() {
    // Payment notification is received first.
    $order_data = array('primary_email' => 'simpletest@ubercart.org');
    $order = $this->createOrder($order_data);
    uc_payment_enter($order->id(), 'SimpleTest', $order->getTotal());
    $output = uc_cart_complete_sale($order);

    // Check that a new account was created.
    $this->assertTrue(strpos($output['#message'], 'new account has been created') !== FALSE, 'Checkout message mentions new account.');

    // 2 e-mails: new account, customer invoice
    $mails = $this->drupalGetMails();
    $this->assertEqual(count($mails), 2, '2 e-mails were sent.');
    \Drupal::state()->set('system.test_email_collector', array());

    $password = $mails[0]['params']['account']->password;
    $this->assertTrue(!empty($password), 'New password is not empty.');
    // In D7, new account emails do not contain the password.
    //$this->assertTrue(strpos($mails[0]['body'], $password) !== FALSE, 'Mail body contains password.');

    // Different user, sees the checkout page first.
    $order_data = array('primary_email' => 'simpletest2@ubercart.org');
    $order = $this->createOrder($order_data);
    $output = uc_cart_complete_sale($order, TRUE);
    uc_payment_enter($order->id(), 'SimpleTest', $order->getTotal());

    // 2 e-mails: new account, customer invoice
    $mails = $this->drupalGetMails();
    $this->assertEqual(count($mails), 2, '2 e-mails were sent.');
    \Drupal::state()->set('system.test_email_collector', array());

    $password = $mails[0]['params']['account']->password;
    $this->assertTrue(!empty($password), 'New password is not empty.');
    // In D7, new account emails do not contain the password.
    //$this->assertTrue(strpos($mails[0]['body'], $password) !== FALSE, 'Mail body contains password.');

    // Same user, new order.
    $order = $this->createOrder($order_data);
    $output = uc_cart_complete_sale($order, TRUE);
    uc_payment_enter($order->id(), 'SimpleTest', $order->getTotal());

    // Check that no new account was created.
    $this->assertTrue(strpos($output['#message'], 'order has been attached to the account') !== FALSE, 'Checkout message mentions existing account.');

    // 1 e-mail: customer invoice
    $mails = $this->drupalGetMails();
    $this->assertEqual(count($mails), 1, '1 e-mail was sent.');
  }

  function testCheckoutRoleAssignment() {
    // Add role assignment to the test product.
    $rid = $this->drupalCreateRole(array('access content'));
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('node/' . $this->product->id() . '/edit/features', array('feature' => 'role'), t('Add'));
    $this->drupalPostForm(NULL, array('uc_roles_role' => $rid), t('Save feature'));

    // Process an anonymous, shippable order.
    $item = clone $this->product;
    $item->qty = 1;
    $item->price = $item->sell_price;
    $item->data = array('shippable' => TRUE);
    $order = $this->createOrder(array(
      'products' => array($item),
    ));
    uc_payment_enter($order->id(), 'SimpleTest', $order->getTotal());

    // Find the order uid.
    $uid = db_query("SELECT uid FROM {uc_orders} ORDER BY order_id DESC")->fetchField();
    $account = user_load($uid);
    $this->assertTrue(isset($account->roles[$rid]), 'New user was granted role.');
    $order = uc_order_load($order->id());
    $this->assertEqual($order->getStatusId(), 'payment_received', 'Shippable order was set to payment received.');

    // 3 e-mails: new account, customer invoice, role assignment
    $mails = $this->drupalGetMails();
    $this->assertEqual(count($mails), 3, '3 e-mails were sent.');
    \Drupal::state()->set('system.test_email_collector', array());

    // Test again with an existing email address and a non-shippable order.
    $item->data = array('shippable' => FALSE);
    $order = $this->createOrder(array(
      'primary_email' => $this->customer->getEmail(),
      'products' => array($item),
    ));
    uc_payment_enter($order->id(), 'SimpleTest', $order->getTotal());
    $account = user_load($this->customer->uid);
    $this->assertTrue(isset($account->roles[$rid]), 'Existing user was granted role.');
    $order = uc_order_load($order->id());
    $this->assertEqual($order->getStatusId(), 'completed', 'Non-shippable order was set to completed.');

    // 2 e-mails: customer invoice, role assignment
    $mails = $this->drupalGetMails();
    $this->assertEqual(count($mails), 2, '2 e-mails were sent.');
    \Drupal::state()->set('system.test_email_collector', array());
  }

  /**
   * Tests that cart orders are marked abandoned after a timeout.
   */
  function testCartOrderTimeout() {
    $this->drupalPostForm('node/' . $this->product->id(), array(), t('Add to cart'));
    $this->drupalPostForm('cart', array(), 'Checkout');
    $this->assertText(
      t('Enter your billing address and information here.'),
      t('Viewed cart page: Billing pane has been displayed.')
    );

    // Build the panes.
    $zone_id = db_query_range('SELECT zone_id FROM {uc_zones} WHERE zone_country_id = :country ORDER BY rand()', 0, 1, array('country' => config('uc_store.settings')->get('address.country')))->fetchField();
    $oldname = $this->randomName(10);
    $edit = array(
      'panes[delivery][delivery_first_name]' => $oldname,
      'panes[delivery][delivery_last_name]' => $this->randomName(10),
      'panes[delivery][delivery_street1]' => $this->randomName(10),
      'panes[delivery][delivery_city]' => $this->randomName(10),
      'panes[delivery][delivery_zone]' => $zone_id,
      'panes[delivery][delivery_postal_code]' => mt_rand(10000, 99999),

      'panes[billing][billing_first_name]' => $this->randomName(10),
      'panes[billing][billing_last_name]' => $this->randomName(10),
      'panes[billing][billing_street1]' => $this->randomName(10),
      'panes[billing][billing_city]' => $this->randomName(10),
      'panes[billing][billing_zone]' => $zone_id,
      'panes[billing][billing_postal_code]' => mt_rand(10000, 99999),
    );

    // If the email address has not been set, and the user has not logged in,
    // add a primary email address.
    if (!isset($edit['panes[customer][primary_email]']) && !$this->loggedInUser) {
      $edit['panes[customer][primary_email]'] = $this->randomName(8) . '@example.com';
    }

    // Submit the checkout page.
    $this->drupalPostForm('cart/checkout', $edit, t('Review order'));

    $order_id = db_query("SELECT order_id FROM {uc_orders} WHERE delivery_first_name = :name", array(':name' => $oldname))->fetchField();
    if ($order_id) {
      // Go to a different page, then back to order - make sure order_id is the same.
      $this->drupalGet('<front>');
      $this->drupalPostForm('node/' . $this->product->id(), array(), t('Add to cart'));
      $this->drupalPostForm('cart', array(), 'Checkout');
      $this->assertRaw($oldname, 'Customer name was unchanged.');
      $this->drupalPostForm('cart/checkout', $edit, t('Review order'));
      $new_order_id = db_query("SELECT order_id FROM {uc_orders} WHERE delivery_first_name = :name", array(':name' => $edit['panes[delivery][delivery_first_name]']))->fetchField();
      $this->assertEqual($order_id, $new_order_id, 'Original order_id was reused.');

      // Jump 10 minutes into the future.
      db_update('uc_orders')
        ->fields(array(
            'modified' => time() - UC_CART_ORDER_TIMEOUT - 1,
          ))
        ->condition('order_id', $order_id)
        ->execute();
      $old_order = uc_order_load($order_id);

      // Go to a different page, then back to order - verify that we are using a new order.
      $this->drupalGet('<front>');
      $this->drupalPostForm('cart', array(), 'Checkout');
      $this->assertNoRaw($oldname, 'Customer name was cleared after timeout.');
      $newname = $this->randomName(10);
      $edit['panes[delivery][delivery_first_name]'] = $newname;
      $this->drupalPostForm('cart/checkout', $edit, t('Review order'));
      $new_order_id = db_query("SELECT order_id FROM {uc_orders} WHERE delivery_first_name = :name", array(':name' => $newname))->fetchField();
      $this->assertNotEqual($order_id, $new_order_id, 'New order was created after timeout.');

      // Verify that the status of old order is abandoned.
      $old_order = uc_order_load($order_id, TRUE);
      $this->assertEqual($old_order->getStatusId(), 'abandoned', 'Original order was marked abandoned.');
    }
    else {
      $this->fail('No order was created.');
    }
  }

  function testCustomerInformationCheckoutPane() {
    // Log in as a customer and add an item to the cart.
    $this->drupalLogin($this->customer);
    $this->drupalPostForm('node/' . $this->product->id(), array(), t('Add to cart'));
    $this->drupalPostForm('cart', array(), 'Checkout');

    // Test the customer information pane.
    $mail = $this->customer->getEmail();
    $this->assertText('Customer information');
    $this->assertText('Order information will be sent to your account e-mail listed below.');
    $this->assertText('E-mail address: ' . $mail);

    // Use the 'edit' link to change the email address on the account.
    $new_mail = $this->randomName() . '@example.com';
    $this->clickLink('edit');
    $data = array(
      'current_pass' => $this->customer->pass_raw,
      'mail' => $new_mail,
    );
    $this->drupalPostForm(NULL, $data, 'Save');

    // Test the updated email address.
    $this->assertText('Order information will be sent to your account e-mail listed below.');
    $this->assertNoText('E-mail address: ' . $mail);
    $this->assertText('E-mail address: ' . $new_mail);
  }

}
