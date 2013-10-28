<?php

/**
 * @file
 * Contains \Drupal\uc_cart_links\Form\CartLinksForm.
 */

namespace Drupal\uc_cart_links\Form;

use Drupal\Component\Utility\Url;
use Drupal\Core\Form\ConfirmFormBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Preprocesses a cart link, confirming with the user for destructive actions.
 */
class CartLinksForm extends ConfirmFormBase {

  /**
   * The cart link actions.
   */
  protected $actions;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('The current contents of your shopping cart will be lost. Are you sure you want to continue?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_cart_links_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $actions = NULL) {
    $this->actions = $actions;

    // Fail if the link is restricted.
    $data = variable_get('uc_cart_links_restrictions', '');
    if (!empty($data)) {
      $restrictions = explode("\n", variable_get('uc_cart_links_restrictions', ''));
      $restrictions = array_map('trim', $restrictions);

      if (!empty($restrictions) && !in_array($this->actions, $restrictions)) {
        $url = variable_get('uc_cart_links_invalid_page', '');
        if (empty($url)) {
          $url = '<front>';
        }
        unset($_GET['destination']);
        return new RedirectResponse(url($url, array('absolute' => TRUE)));
      }
    }

    // Confirm with the user if the form contains a destructive action.
    $items = uc_cart_get_contents();
    if (variable_get('uc_cart_links_empty', TRUE) && !empty($items)) {
      $actions = explode('-', urldecode($this->actions));
      foreach ($actions as $action) {
        $action = drupal_substr($action, 0, 1);
        if ($action == 'e' || $action == 'E') {
          $form = parent::buildForm($form, $form_state);
          $form['actions']['cancel']['#href'] = variable_get('uc_cart_links_invalid_page', '');
          return $form;
        }
      }
    }

    // No destructive actions, so process the link immediately.
    return $this->submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $actions = explode('-', urldecode($this->actions));
    $rebuild_cart = FALSE;
    $messages = array();
    $id = t('(not specified)');

    foreach ($actions as $action) {
      switch (drupal_substr($action, 0, 1)) {
        // Set the ID of the Cart Link.
        case 'i':
        case 'I':
          $id = drupal_substr($action, 1, 32);
          break;

        // Add a product to the cart.
        case 'p':
        case 'P':
          // Set the default product variables.
          $p = array('nid' => 0, 'qty' => 1, 'data' => array());
          $msg = TRUE;

          // Parse the product action to adjust the product variables.
          $parts = explode('_', $action);
          foreach ($parts as $part) {
            switch (drupal_substr($part, 0, 1)) {
              // Set the product node ID: p23
              case 'p':
              case 'P':
                $p['nid'] = intval(drupal_substr($part, 1));
                break;
              // Set the quantity to add to cart: _q2
              case 'q':
              case 'Q':
                $p['qty'] = intval(drupal_substr($part, 1));
                break;
              // Set an attribute/option for the product: _a3o6
              case 'a':
              case 'A':
                $attribute = intval(drupal_substr($part, 1, stripos($part, 'o') - 1));
                $option = (string) drupal_substr($part, stripos($part, 'o') + 1);
                if (!isset($p['attributes'][$attribute])) {
                  $p['attributes'][$attribute] = $option;
                }
                else {
                  // Multiple options for this attribute implies checkbox
                  // attribute, which we must store as an array
                  if (is_array($p['attributes'][$attribute])) {
                    // Already an array, just append this new option
                    $p['attributes'][$attribute][$option] = $option;
                  }
                  else {
                    // Set but not an array, means we already have at least one
                    // option, so put that into an array with this new option
                    $p['attributes'][$attribute] = array(
                      $p['attributes'][$attribute] => $p['attributes'][$attribute],
                      $option => $option
                    );
                  }
                }
                break;
              // Suppress the add to cart message: _s
              case 's':
              case 'S':
                $msg = FALSE;
                break;
            }
          }

          // Add the item to the cart, suppressing the default redirect.
          if ($p['nid'] > 0 && $p['qty'] > 0) {
            // If it's a product kit, we need black magic to make everything work
            // right. In other words, we have to simulate FAPI's form values.
            $node = node_load($p['nid']);
            if ($node->status) {
              if (isset($node->products) && is_array($node->products)) {
                foreach ($node->products as $nid => $product) {
                  $p['data']['products'][$nid] = array(
                    'nid' => $nid,
                    'qty' => $product->qty,
                  );
                }
              }
              uc_cart_add_item($p['nid'], $p['qty'], $p['data'] + module_invoke_all('uc_add_to_cart_data', $p), NULL, $msg, FALSE, FALSE);
              $rebuild_cart = TRUE;
            }
            else {
              watchdog('uc_cart_link', 'Cart Link on %url tried to add an unpublished product to the cart.', array('%url' => $_SERVER['HTTP_REFERER']), WATCHDOG_ERROR);
            }
          }
          break;

        // Empty the shopping cart.
        case 'e':
        case 'E':
          if (variable_get('uc_cart_links_empty', TRUE)) {
            uc_cart_empty();
          }
          break;

        // Display a pre-configured message.
        case 'm':
        case 'M':
          // Load the messages if they haven't been loaded yet.
          if (empty($messages)) {
            $data = explode("\n", variable_get('uc_cart_links_messages', ''));
            foreach ($data as $message) {
              list($mkey, $mdata) = explode('|', $message, 2);
              $messages[trim($mkey)] = trim($mdata);
            }
          }

          // Parse the message key and display it if it exists.
          $mkey = intval(drupal_substr($action, 1));
          if (!empty($messages[$mkey])) {
            drupal_set_message($messages[$mkey]);
          }
          break;
      }

      // Rebuild the cart cache if necessary.
      if ($rebuild_cart) {
        uc_cart_get_contents(NULL, 'rebuild');
      }
    }

    if (variable_get('uc_cart_links_track', TRUE)) {
      db_merge('uc_cart_link_clicks')
        ->key(array('cart_link_id' => (string) $id))
        ->fields(array(
          'clicks' => 1,
          'last_click' => REQUEST_TIME,
        ))
        ->expression('clicks', 'clicks + :i', array(':i' => 1))
        ->execute();
    }

    $_SESSION['uc_cart_last_url'] = $_SERVER['HTTP_REFERER'];

    $query = $this->getRequest()->query;
    if ($query->has('destination')) {
      $options = Url::parse($query->get('destination'));
      $path = $options['path'];
    }
    else {
      $path = 'cart';
      $options = array();
    }
    $options += array('absolute' => TRUE);

    // Form redirect is for confirmed links.
    $form_state['redirect'] = array($path, $options);

    // RedirectResponse is for unconfirmed links.
    return new RedirectResponse(url($path, $options));
  }

}
