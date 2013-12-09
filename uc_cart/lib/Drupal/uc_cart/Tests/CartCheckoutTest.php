<?php

/**
 * @file
 * Contains \Drupal\uc_cart\Tests\CartCheckoutTest.
 */

namespace Drupal\uc_cart\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Tests the cart and checkout functionality.
 */
class CartCheckoutTest extends UbercartTestBase {

  public static $modules = array('uc_payment', 'uc_payment_pack', 'uc_roles');

  public static function getInfo() {
    return array(
      'name' => 'Cart and checkout',
      'description' => 'Ensures the cart and checkout process is functioning for both anonymous and authenticated users.',
      'group' => 'Ubercart',
    );
  }

  public function setUp() {
    parent::setUp();

    // Ensure test mails are logged.
    \Drupal::config('system.mail')
      ->set('interface.uc_order', 'Drupal\Core\Mail\VariableLog')
      ->save();
  }

  public function testCartAPI() {
    // Test the empty cart.
    $items = uc_cart_get_contents();
    $this->assertEqual($items, array(), 'Cart is an empty array.');

    // Add an item to the cart.
    uc_cart_add_item($this->product->id());

    $items = uc_cart_get_contents();
    $this->assertEqual(count($items), 1, 'Cart contains one item.');
    $item = reset($items);
    $this->assertEqual($item->nid->value, $this->product->id(), 'Cart item nid is correct.');
    $this->assertEqual($item->qty->value, 1, 'Cart item quantity is correct.');

    // Add more of the same item.
    $qty = mt_rand(1, 100);
    uc_cart_add_item($this->product->id(), $qty);

    $items = uc_cart_get_contents();
    $this->assertEqual(count($items), 1, 'Updated cart contains one item.');
    $item = reset($items);
    $this->assertEqual($item->qty->value, $qty + 1, 'Updated cart item quantity is correct.');

    // Set the quantity and data.
    $qty = mt_rand(1, 100);
    $item->qty->value = $qty;
    $item->data['updated'] = TRUE;
    $item->save();

    $items = uc_cart_get_contents();
    $item = reset($items);
    $this->assertEqual($item->qty->value, $qty, 'Set cart item quantity is correct.');
    $this->assertTrue($item->data['updated'], 'Set cart item data is correct.');

    // Add an item with different data to the cart.
    uc_cart_add_item($this->product->id(), 1, array('test' => TRUE));

    $items = uc_cart_get_contents();
    $this->assertEqual(count($items), 2, 'Updated cart contains two items.');

    // Remove the items.
    foreach ($items as $item) {
      $item->delete();
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

  public function testCart() {
    \Drupal::moduleHandler()->install(array('uc_cart_entity_test'));

    // Test the empty cart.
    $this->drupalGet('cart');
    $this->assertText('There are no products in your shopping cart.');

    // Add an item to the cart.
    $this->addToCart($this->product);
    $this->assertText($this->product->label() . ' added to your shopping cart.');
    $this->assertText('hook_uc_cart_item_insert fired');

    // Test the cart page.
    $this->drupalGet('cart');
    $this->assertText($this->product->label(), t('The product is in the cart.'));
    $this->assertFieldByName('items[0][qty]', 1, t('The product quantity is 1.'));

    // Add the item again.
    $this->addToCart($this->product);
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
    $this->addToCart($this->product);
    $this->drupalPostForm('cart', array(), t('Remove'));
    $this->assertText($this->product->label() . ' removed from your shopping cart.');
    $this->assertText('There are no products in your shopping cart.');
    $this->assertText('hook_uc_cart_item_delete fired');

    // Test the empty cart button.
    $this->addToCart($this->product);
    $this->drupalGet('cart');
    $this->assertNoText('Empty cart');
    variable_set('uc_cart_empty_button', TRUE);
    $this->drupalPostForm('cart', array(), t('Empty cart'));
    $this->drupalPostForm(NULL, array(), t('Confirm'));
    $this->assertText('There are no products in your shopping cart.');
    $this->assertText('hook_uc_cart_item_delete fired');
  }

  public function testCartMerge() {
    // Add an item to the cart as an anonymous user.
    $this->drupalLogin($this->customer);
    $this->addToCart($this->product);
    $this->assertText($this->product->label() . ' added to your shopping cart.');
    $this->drupalLogout();

    // Add an item to the cart as an anonymous user.
    $this->addToCart($this->product);
    $this->assertText($this->product->label() . ' added to your shopping cart.');

    // Log in and check the items are merged.
    $this->drupalLogin($this->customer);
    $this->drupalGet('cart');
    $this->assertText($this->product->label(), t('The product remains in the cart after logging in.'));
    $this->assertFieldByName('items[0][qty]', 2, t('The product quantity is 2.'));
  }

  public function testDeletedCartItem() {
    // Add a product to the cart, then delete the node.
    $this->addToCart($this->product);
    $this->product->delete();

    // Test that the cart is empty.
    $this->drupalGet('cart');
    $this->assertText('There are no products in your shopping cart.');
    $this->assertIdentical(uc_cart_get_contents(), array(), 'There are no items in the cart.');
  }

  // public function testMaximumQuantityRule() {
  //   // Enable the example maximum quantity rule.
  //   $rule = rules_config_load('uc_cart_maximum_product_qty');
  //   $rule->active = TRUE;
  //   $rule->save();

  //   // Try to add more items than allowed to the cart.
  //   $this->addToCart($this->product);
  //   $this->drupalPostForm('cart', array('items[0][qty]' => 11), t('Update cart'));

  //   // Test the restriction was applied.
  //   $this->assertText('You are only allowed to order a maximum of 10 of ' . $this->product->label() . '.');
  //   $this->assertFieldByName('items[0][qty]', 10);
  // }

  public function testAuthenticatedCheckout() {
    $this->drupalLogin($this->customer);
    $this->addToCart($this->product);
    $this->checkout();
    $this->assertRaw('Your order is complete!');
    $this->assertRaw('While logged in');

    // Check that cart is now empty.
    $this->drupalGet('cart');
    $this->assertText('There are no products in your shopping cart.');
  }

  public function testAnonymousCheckoutAccountGenerated() {
    $this->addToCart($this->product);
    $this->checkout();
    $this->assertRaw('Your order is complete!');

    // Test new account email.
    $mail = $this->drupalGetMails(array('id' => 'user_register_no_approval_required'));
    $mail = array_pop($mail);
    $account = $mail['params']['account'];
    $this->assertTrue(!empty($account->name->value), 'New username is not empty.');
    $this->assertTrue(!empty($account->password), 'New password is not empty.');
    $this->assertTrue(strpos($mail['body'], $account->name->value) !== FALSE, 'Mail body contains username.');

    // Test invoice email.
    $mail = $this->drupalGetMails(array('subject' => 'Your Order at Ubercart'));
    $mail = array_pop($mail);
    $this->assertTrue(strpos($mail['body'], $account->name->value) !== FALSE, 'Invoice body contains username.');
    $this->assertTrue(strpos($mail['body'], $account->password) !== FALSE, 'Invoice body contains password.');

    // We can check the password now we know it.
    $this->assertText($account->name->value, 'Username is shown on screen.');
    $this->assertText($account->password, 'Password is shown on screen.');

    // Check that cart is now empty.
    $this->drupalGet('cart');
    $this->assertText('There are no products in your shopping cart.');

    // Check that the password works.
    $edit = array(
      'name' => $account->name->value,
      'pass' => $account->password,
    );
    $this->drupalPostForm('user', $edit, t('Log in'));
  }

  public function testAnonymousCheckoutAccountProvided() {
    $settings = array(
      // Allow customer to specify username and password.
      'uc_cart_new_account_name' => TRUE,
      'uc_cart_new_account_password' => TRUE,
    );
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('admin/store/settings/checkout', $settings, t('Save configuration'));
    $this->drupalLogout();

    $username = $this->randomName(20);
    $password = $this->randomName(20);

    $this->addToCart($this->product);
    $this->checkout(array(
      'panes[customer][new_account][name]' => $username,
      'panes[customer][new_account][pass]' => $password,
      'panes[customer][new_account][pass_confirm]' => $password,
    ));
    $this->assertRaw('Your order is complete!');
    $this->assertText($username, 'Username is shown on screen.');
    $this->assertNoText($password, 'Password is not shown on screen.');

    // Test new account email.
    $mail = $this->drupalGetMails(array('id' => 'user_register_no_approval_required'));
    $mail = array_pop($mail);
    $this->assertTrue(strpos($mail['body'], $username) !== FALSE, 'Mail body contains username.');

    // Test invoice email.
    $mail = $this->drupalGetMails(array('subject' => 'Your Order at Ubercart'));
    $mail = array_pop($mail);
    $this->assertTrue(strpos($mail['body'], $username) !== FALSE, 'Invoice body contains username.');
    $this->assertFalse(strpos($mail['body'], $password) !== FALSE, 'Invoice body does not contain password.');

    // Check that cart is now empty.
    $this->drupalGet('cart');
    $this->assertText('There are no products in your shopping cart.');

    // Check that the password works.
    $edit = array(
      'name' => $username,
      'pass' => $password,
    );
    $this->drupalPostForm('user', $edit, t('Log in'));
  }

  public function testAnonymousCheckoutAccountExists() {
    $this->addToCart($this->product);
    $this->checkout(array('panes[customer][primary_email]' => $this->customer->getEmail()));
    $this->assertRaw('Your order is complete!');
    $this->assertRaw('order has been attached to the account we found');

    // Check that cart is now empty.
    $this->drupalGet('cart');
    $this->assertText('There are no products in your shopping cart.');
  }

  public function testCheckoutNewUsername() {
    // Configure the checkout for this test.
    $this->drupalLogin($this->adminUser);
    $settings = array(
      // Allow customer to specify username.
      'uc_cart_new_account_name' => TRUE,
      // Disable address panes.
      'panes[delivery][status]' => FALSE,
      'panes[billing][status]' => FALSE,
    );
    $this->drupalPostForm('admin/store/settings/checkout', $settings, t('Save configuration'));
    $this->drupalLogout();

    // Test with an account that already exists.
    $this->addToCart($this->product);
    $edit = array(
      'panes[customer][primary_email]' => $this->randomName(8) . '@example.com',
      'panes[customer][new_account][name]' => $this->adminUser->name->value,
    );
    $this->drupalPostForm('cart/checkout', $edit, 'Review order');
    $this->assertText('The username ' . $this->adminUser->name->value . ' is already taken.');

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

  public function testCheckoutBlockedUser() {
    // Block user after checkout.
    $settings = array(
      'uc_new_customer_status_active' => FALSE,
    );
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('admin/store/settings/checkout', $settings, t('Save configuration'));
    $this->drupalLogout();

    // Test as anonymous user.
    $this->addToCart($this->product);
    $this->checkout();
    $this->assertRaw('Your order is complete!');

    // Test new account email.
    $mail = $this->drupalGetMails(array('id' => 'user_register_pending_approval'));
    $this->assertTrue(!empty($mail), 'Blocked user email found.');
    $mail = $this->drupalGetMails(array('id' => 'user_register_no_approval_required'));
    $this->assertTrue(empty($mail), 'No unblocked user email found.');
  }

  public function testCheckoutLogin() {
    // Log in after checkout.
    $settings = array(
      'uc_new_customer_login' => TRUE,
    );
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('admin/store/settings/checkout', $settings, t('Save configuration'));
    $this->drupalLogout();

    // Test checkout.
    $this->addToCart($this->product);
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

  public function testCheckoutComplete() {
    // Payment notification is received first.
    $order_data = array('primary_email' => 'simpletest@ubercart.org');
    $order = $this->createOrder($order_data);
    uc_payment_enter($order->id(), 'SimpleTest', $order->getTotal());
    $output = uc_cart_complete_sale($order);

    // Check that a new account was created.
    $this->assertTrue(strpos($output['#message'], 'new account has been created') !== FALSE, 'Checkout message mentions new account.');

    // 3 e-mails: new account, customer invoice, admin invoice
    $this->assertMailString('subject', 'Account details', 3, 'New account email was sent');
    $this->assertMailString('subject', 'Your Order at Ubercart', 3, 'Customer invoice was sent');
    $this->assertMailString('subject', 'New Order at Ubercart', 3, 'Admin notification was sent');

    $mails = $this->drupalGetMails();
    $password = $mails[0]['params']['account']->password;
    $this->assertTrue(!empty($password), 'New password is not empty.');

    \Drupal::state()->set('system.test_email_collector', array());

    // Different user, sees the checkout page first.
    $order_data = array('primary_email' => 'simpletest2@ubercart.org');
    $order = $this->createOrder($order_data);
    $output = uc_cart_complete_sale($order, TRUE);
    uc_payment_enter($order->id(), 'SimpleTest', $order->getTotal());

    // 3 e-mails: new account, customer invoice, admin invoice
    $this->assertMailString('subject', 'Account details', 3, 'New account email was sent');
    $this->assertMailString('subject', 'Your Order at Ubercart', 3, 'Customer invoice was sent');
    $this->assertMailString('subject', 'New Order at Ubercart', 3, 'Admin notification was sent');

    $mails = $this->drupalGetMails();
    $password = $mails[0]['params']['account']->password;
    $this->assertTrue(!empty($password), 'New password is not empty.');

    \Drupal::state()->set('system.test_email_collector', array());

    // Same user, new order.
    $order = $this->createOrder($order_data);
    $output = uc_cart_complete_sale($order, TRUE);
    uc_payment_enter($order->id(), 'SimpleTest', $order->getTotal());

    // Check that no new account was created.
    $this->assertTrue(strpos($output['#message'], 'order has been attached to the account') !== FALSE, 'Checkout message mentions existing account.');

    // 2 e-mails: customer invoice, admin invoice
    $this->assertNoMailString('subject', 'Account details', 3, 'New account email was sent');
    $this->assertMailString('subject', 'Your Order at Ubercart', 3, 'Customer invoice was sent');
    $this->assertMailString('subject', 'New Order at Ubercart', 3, 'Admin notification was sent');
  }

  public function testCheckoutRoleAssignment() {
    // Add role assignment to the test product.
    $rid = $this->drupalCreateRole(array('access content'));
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('node/' . $this->product->id() . '/edit/features', array('feature' => 'role'), t('Add'));
    $this->drupalPostForm(NULL, array('uc_roles_role' => $rid), t('Save feature'));

    // Process an anonymous, shippable order.
    $order = $this->createOrder();
    $order->products[1]->data['shippable'] = TRUE;
    $order->save();
    uc_payment_enter($order->id(), 'SimpleTest', $order->getTotal());

    // Find the order uid.
    $uid = db_query("SELECT uid FROM {uc_orders} ORDER BY order_id DESC")->fetchField();
    $account = user_load($uid);
    // @todo Re-enable when Rules is available.
    // $this->assertTrue($account->hasRole($rid), 'New user was granted role.');
    $order = uc_order_load($order->id());
    $this->assertEqual($order->getStatusId(), 'payment_received', 'Shippable order was set to payment received.');

    // 4 e-mails: new account, customer invoice, admin invoice, role assignment
    $this->assertMailString('subject', 'Account details', 4, 'New account email was sent');
    $this->assertMailString('subject', 'Your Order at Ubercart', 4, 'Customer invoice was sent');
    $this->assertMailString('subject', 'New Order at Ubercart', 4, 'Admin notification was sent');
    // @todo Re-enable when Rules is available.
    // $this->assertMailString('subject', 'role granted', 4, 'Role assignment notification was sent');

    \Drupal::state()->set('system.test_email_collector', array());

    // Test again with an existing email address and a non-shippable order.
    $order = $this->createOrder(array(
      'primary_email' => $this->customer->getEmail(),
    ));
    $order->products[2]->data['shippable'] = FALSE;
    $order->save();
    uc_payment_enter($order->id(), 'SimpleTest', $order->getTotal());
    $account = user_load($this->customer->id());
    // @todo Re-enable when Rules is available.
    // $this->assertTrue($account->hasRole($rid), 'Existing user was granted role.');
    $order = uc_order_load($order->id());
    $this->assertEqual($order->getStatusId(), 'completed', 'Non-shippable order was set to completed.');

    // 3 e-mails: customer invoice, admin invoice, role assignment
    $this->assertNoMailString('subject', 'Account details', 4, 'New account email was sent');
    $this->assertMailString('subject', 'Your Order at Ubercart', 4, 'Customer invoice was sent');
    $this->assertMailString('subject', 'New Order at Ubercart', 4, 'Admin notification was sent');
    // @todo Re-enable when Rules is available.
    // $this->assertMailString('subject', 'role granted', 4, 'Role assignment notification was sent');
  }

  /**
   * Tests that cart orders are marked abandoned after a timeout.
   */
  public function testCartOrderTimeout() {
    $this->addToCart($this->product);
    $this->drupalPostForm('cart', array(), 'Checkout');
    $this->assertText(
      t('Enter your billing address and information here.'),
      t('Viewed cart page: Billing pane has been displayed.')
    );

    // Build the panes.
    $zone_id = db_query_range('SELECT zone_id FROM {uc_zones} WHERE zone_country_id = :country ORDER BY rand()', 0, 1, array('country' => config('uc_store.settings')->get('address.country')))->fetchField();
    $oldname = $this->randomName(10);
    $edit = array(
      'panes[delivery][first_name]' => $oldname,
      'panes[delivery][last_name]' => $this->randomName(10),
      'panes[delivery][street1]' => $this->randomName(10),
      'panes[delivery][city]' => $this->randomName(10),
      'panes[delivery][zone]' => $zone_id,
      'panes[delivery][postal_code]' => mt_rand(10000, 99999),

      'panes[billing][first_name]' => $this->randomName(10),
      'panes[billing][last_name]' => $this->randomName(10),
      'panes[billing][street1]' => $this->randomName(10),
      'panes[billing][city]' => $this->randomName(10),
      'panes[billing][zone]' => $zone_id,
      'panes[billing][postal_code]' => mt_rand(10000, 99999),
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
      $this->addToCart($this->product);
      $this->drupalPostForm('cart', array(), 'Checkout');
      $this->assertRaw($oldname, 'Customer name was unchanged.');
      $this->drupalPostForm('cart/checkout', $edit, t('Review order'));
      $new_order_id = db_query("SELECT order_id FROM {uc_orders} WHERE delivery_first_name = :name", array(':name' => $edit['panes[delivery][first_name]']))->fetchField();
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
      $edit['panes[delivery][first_name]'] = $newname;
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

  public function testCustomerInformationCheckoutPane() {
    // Log in as a customer and add an item to the cart.
    $this->drupalLogin($this->customer);
    $this->addToCart($this->product);
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
