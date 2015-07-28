<?php

/**
 * @file
 * Contains \Drupal\uc_cart\Controller\Cart.
 */

namespace Drupal\uc_cart\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Xss;



/**
 * Handles all things concerning Ubercart's shopping cart.
 *
 * The Ubercart cart system functions much like the e-commerce cart at its base
 * level... in fact, most carts do.  This module handles the cart display,
 * adding items to a cart, and checking out.  The module enables the cart,
 * products, and checkout to be extensible.
 */
class Cart extends ControllerBase implements CartInterface {

  /**
   * Time in seconds after which a cart order is deemed abandoned.
   */
  const ORDER_TIMEOUT = 86400; // 24 hours

  /**
   * Time in seconds after which the checkout page is deemed abandoned.
   */
  const CHECKOUT_TIMEOUT = 1800; // 30 minutes


  /**
   * {@inheritdoc}
   */
  public function completeSale($order, $login = FALSE) {
    // Empty that cart...
    $this->emptyCart();

    // Force the order to load from the DB instead of the entity cache.
    // @todo Remove this once uc_payment_enter() can modify order objects?
    // @todo Should we be overwriting $order with this newly-loaded db_order?
    $db_order = $this->entityManager()->getStorage('uc_order')->loadUnchanged($order->id());
    $order->data = $db_order->data;

    // Ensure that user creation and triggers are only run once.
    if (empty($order->data->complete_sale)) {
      $this->completeSaleAccount($order);

      // Move an order's status from "In checkout" to "Pending".
      if ($order->getStateId() == 'in_checkout') {
        $order->setStatusId(uc_order_state_default('post_checkout'));
      }

      $order->save();

      // Invoke the checkout complete trigger and hook.
      $account = $order->getUser();
      $this->moduleHandler()->invokeAll('uc_checkout_complete', array($order, $account));
      // rules_invoke_event('uc_checkout_complete', $order);
    }

    $type = $order->data->complete_sale;

    // Log in new users, if requested.
    if ($type == 'new_user' && $login && $this->currentUser()->isAnonymous()) {
      $type = 'new_user_logged_in';
      user_login_finalize($order->getUser());
    }

    $message = $this->config('uc_cart.messages')->get($type);
    $message = \Drupal::token()->replace($message, array('uc_order' => $order));

    $variables['!new_username'] = isset($order->data->new_user_name) ? $order->data->new_user_name : '';
    $variables['!new_password'] = isset($order->password) ? $order->password : t('Your password');
    $message = strtr($message, $variables);

    return array(
      '#theme' => 'uc_cart_complete_sale',
      '#message' => Xss::filterAdmin($message),
      '#order' => $order,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function completeSaleAccount($order) {
    // Order already has a user ID, so the user was logged in during checkout.
    if ($order->getUserId()) {
      $order->data->complete_sale = 'logged_in';
      return;
    }

    // Email address matches an existing account.
    if ($account = user_load_by_mail($order->getEmail())) {
      $order->setUserId($account->id());
      $order->data->complete_sale = 'existing_user';
      return;
    }

    // Set up a new user.
    $cart_config = $this->config('uc_cart.settings');
    $fields = array(
      'name' => uc_store_email_to_username($order->getEmail()),
      'mail' => $order->getEmail(),
      'init' => $order->getEmail(),
      'pass' => user_password(),
      'roles' => array(),
      'status' => $cart_config->get('new_customer_status_active') ? 1 : 0,
    );

    // Override the username, if specified.
    if (isset($order->data->new_user_name)) {
      $fields['name'] = $order->data->new_user_name;
    }

    // Create the account.
    $account = \Drupal\user\Entity\User::create($fields);
    $account->save();

    // Override the password, if specified.
    if (isset($order->data->new_user_hash)) {
      db_query('UPDATE {users_field_data} SET pass = :hash WHERE uid = :uid', [':hash' => $order->data->new_user_hash, ':uid' => $account->id()]);
      $account->password = t('Your password');
    }
    else {
      $account->password = $fields['pass'];
      $order->password = $fields['pass'];
    }

    // Send the customer their account details if enabled.
    if ($cart_config->get('new_customer_email')) {
      $type = $cart_config->get('new_customer_status_active') ? 'register_no_approval_required' : 'register_pending_approval';
      \Drupal::service('plugin.manager.mail')->mail('user', $type, $order->getEmail(), uc_store_mail_recipient_langcode($order->getEmail()), array('account' => $account), uc_store_email_from());
    }

    $order->setUserId($account->id());
    $order->data->new_user_name = $fields['name'];
    $order->data->complete_sale =  'new_user';
  }

  /**
   * {@inheritdoc}
   */
  public function getId($create = TRUE) {
    $user = $this->currentUser();
    $session = \Drupal::service('session');

    if ($user->isAuthenticated()) {
      return $user->id();
    }
    elseif (!$session->has('uc_cart_id') && $create) {
      $session->set('uc_cart_id', md5(uniqid(rand(), TRUE)));
    }

    return $session->has('uc_cart_id') ? $session->get('uc_cart_id') : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getContents($cid = NULL, $action = NULL) {
    static $items = array();

    $cid = $cid ? $cid : $this->getId(FALSE);

    // If we didn't get a cid, return empty.
    if (!$cid) {
      return array();
    }

    if (!isset($items[$cid]) || $action == 'rebuild') {
      $items[$cid] = array();

      $result = \Drupal::entityQuery('uc_cart_item')
        ->condition('cart_id', $cid)
        ->sort('cart_item_id', 'ASC')
        ->execute();

      if (!empty($result)) {
        $storage = $this->entityManager()->getStorage('uc_cart_item');
        $storage->resetCache(array_keys($result));
        $items[$cid] = $storage->loadMultiple(array_keys($result));
      }

      // Allow other modules a chance to alter the fully loaded cart object.
      $this->moduleHandler()->alter('uc_cart', $items[$cid]);

      if ($action == 'rebuild') {
        // Mark the current cart order (if any) as needing to be rebuilt.  We only
        // do this if the cart is being explicitly rebuilt (i.e. after an item is
        // added, removed or altered).
        $session = \Drupal::service('session');
        $session->set('uc_cart_order_rebuild', TRUE);

        // When there are no longer any items in the cart, the anonymous cart ID is
        // no longer required. To guard against unsetting the session ID in the
        // middle of an uc_cart_add_item() call, we only do this on rebuild.
        // See issue 858816 for details.
        if (empty($items[$cid]) && $session->has('uc_cart_id') && $session->get('uc_cart_id') == $cid) {
          $session->remove('uc_cart_id');
        }
      }
    }

    return $items[$cid];
  }

  /**
   * {@inheritdoc}
   */
  public function addItem($nid, $qty = 1, $data = NULL, $cid = NULL, $msg = TRUE, $check_redirect = TRUE, $rebuild = TRUE) {
    $cid = $cid ? $cid : $this->getId();
    $node = node_load($nid);

    if (is_null($data) || !isset($data['module'])) {
      $data['module'] = 'uc_product';
    }

    // Invoke hook_uc_add_to_cart() to give other modules a chance to affect the process.
    $result = $this->moduleHandler()->invokeAll('uc_add_to_cart', array($nid, $qty, $data));
    if (is_array($result) && !empty($result)) {
      foreach ($result as $row) {
        if ($row['success'] === FALSE) {
          // Module implementing the hook does NOT want this item added!
          if (isset($row['message']) && !empty($row['message'])) {
            $message = $row['message'];
          }
          else {
            $message = t('Sorry, that item is not available for purchase at this time.');
          }
          if (isset($row['silent']) && ($row['silent'] === TRUE)) {
            if ($check_redirect) {
              return $this->getAddItemRedirect();
            }
          }
          else {
            drupal_set_message($message, 'error');
          }
          // Stay on this page.
          $query = \Drupal::request()->query;
          return Url::fromRoute('<current>', [], ['query' => UrlHelper::filterQueryParameters($query->all())]);
        }
      }
    }

    // Now we can go ahead and add the item because either:
    //   1) No modules implemented hook_uc_add_to_cart(), or
    //   2) All modules implementing that hook want this item added.
    $result = \Drupal::entityQuery('uc_cart_item')
      ->condition('cart_id', $cid)
      ->condition('nid', $nid)
      ->condition('data', serialize($data))
      ->execute();

    if (empty($result)) {
      // If the item isn't in the cart yet, add it.
      $item_entity = \Drupal\uc_cart\Entity\CartItem::create(array(
        'cart_id' => $cid,
        'nid' => $nid,
        'qty' => $qty,
        'data' => $data,
      ));
      $item_entity->save();
      if ($msg) {
        drupal_set_message(t('<strong>@product-title</strong> added to <a href="@url">your shopping cart</a>.', ['@product-title' => $node->label(), '@url' => $this->url('uc_cart.cart')]));
      }
    }
    else {
      // If it is in the cart, update the item instead.
      if ($msg) {
        drupal_set_message(t('Your item(s) have been updated.'));
      }
      $item_entity = \Drupal\uc_cart\Entity\CartItem::load(current(array_keys($result)));
      $qty += $item_entity->qty->value;
      $this->moduleHandler()->invoke($data['module'], 'uc_update_cart_item', array($nid, $data, min($qty, 999999), $cid));
    }

    // If specified, rebuild the cached cart items array.
    if ($rebuild) {
      $this->getContents($cid, 'rebuild');
    }

    // If specified, compute a Url to redirect to.
    if ($check_redirect) {
      return $this->getAddItemRedirect();
    }
    else {
      // Just to be clear about it...
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAddItemRedirect() {
    // Check for destination= query string
    $query = \Drupal::request()->query;
    $destination = $query->get('destination');
    if (!empty($destination)) {
      return Url::fromUri('base:' . $destination);
    }

    // Save current Url to session before redirecting
    // so we can go "back" here from the cart.
    $session = \Drupal::service('session');
    $session->set('uc_cart_last_url', Url::fromRoute('<current>')->toString());
    $redirect = $this->config('uc_cart.settings')->get('add_item_redirect');
    if ($redirect != '<none>') {
      return Url::fromUri('base:' . $redirect);
    }
    else {
      return Url::fromRoute('<current>', [], ['query' => UrlHelper::filterQueryParameters($query->all())]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function emptyCart($cart_id = NULL) {
    $cart_id = $cart_id ? $cart_id : $this->getId(FALSE);

    if (!$cart_id) {
      return;
    }

    $result = \Drupal::entityQuery('uc_cart_item')
      ->condition('cart_id', $cart_id)
      ->execute();

    if (!empty($result)) {
      $storage = $this->entityManager()->getStorage('uc_cart_item');
      $entities = $storage->loadMultiple(array_keys($result));
      $storage->delete($entities);
    }

    // Remove cached cart.
    $this->getContents($cart_id, 'rebuild');
  }

  /**
   * {@inheritdoc}
   */
  public function isShippable($cart_id = NULL) {
    $items = $this->getContents($cart_id);

    foreach ($items as $item) {
      if (uc_order_product_is_shippable($item)) {
        return TRUE;
      }
    }

    return FALSE;
  }
}
