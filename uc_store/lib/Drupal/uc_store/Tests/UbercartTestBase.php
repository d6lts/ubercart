<?php

/**
 * @file
 * Definition of Drupal\uc_store\Tests\UbercartTestBase.
 */

namespace Drupal\uc_store\Tests;

use Drupal\simpletest\WebTestBase;

abstract class UbercartTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('uc_cart');

  /** User with privileges to do everything. */
  protected $adminUser;

  /** Permissions for administrator user. */
  public static $adminPermissions = array(
    'administer store',
    'administer order workflow',
    'administer product classes',
    'administer product features',
    'administer products',
    'administer content types',
    'create product content',
    'delete any product content',
    'edit any product content',
    'create orders',
    'view all orders',
    'edit orders',
    'delete orders',
    'unconditionally delete orders',
  );

  /** Authenticated but unprivileged user. */
  protected $customer;

  /** Test product. */
  protected $product;

  function setUp() {
    parent::setUp();

    // Collect admin permissions.
    $class = get_class($this);
    $adminPermissions = array();
    while ($class) {
      if (property_exists($class, 'adminPermissions')) {
        $adminPermissions = array_merge($adminPermissions, $class::$adminPermissions);
      }
      $class = get_parent_class($class);
    }

    // Create a store administrator user account.
    $this->adminUser = $this->drupalCreateUser($adminPermissions);

    // Create a simple customer user account.
    $this->customer = $this->drupalCreateUser(array('view own orders'));

    // Create a test product.
    $this->product = $this->createProduct(array('uid' => $this->adminUser->id()));
  }

  /**
   * Creates a new product.
   */
  function createProduct($product = array()) {
    // Set the default required fields.
    $weight_units = array('lb', 'kg', 'oz', 'g');
    $length_units = array('in', 'ft', 'cm', 'mm');
    $product += array(
      'type' => 'product',
      'model' => $this->randomName(8),
      'list_price' => mt_rand(1, 9999),
      'cost' => mt_rand(1, 9999),
      'sell_price' => mt_rand(1, 9999),
      'weight' => mt_rand(1, 9999),
      'weight_units' => array_rand(array_flip($weight_units)),
      'length' => mt_rand(1, 9999),
      'width' => mt_rand(1, 9999),
      'height' => mt_rand(1, 9999),
      'length_units' => array_rand(array_flip($length_units)),
      'pkg_qty' => mt_rand(1, 99),
      'default_qty' => 1,
      'ordering' => mt_rand(-25, 25),
      'shippable' => TRUE,
    );

    return $this->drupalCreateNode($product);
  }

  /**
   * Creates an attribute.
   *
   * @param $data
   * @param $save
   */
  public function createAttribute($data = array(), $save = TRUE) {
    $attribute = $data + array(
      'name' => $this->randomName(8),
      'label' => $this->randomName(8),
      'description' => $this->randomName(8),
      'required' => mt_rand(0, 1) ? TRUE : FALSE,
      'display' => mt_rand(0, 3),
      'ordering' => mt_rand(-10, 10),
    );
    $attribute = (object) $attribute;

    if ($save) {
      uc_attribute_save($attribute);
    }
    return $attribute;
  }

  /**
   * Creates an attribute option.
   *
   * @param $data
   * @param $save
   */
  public function createAttributeOption($data = array(), $save = TRUE) {
    $max_aid = db_select('uc_attributes', 'a')
      ->fields('a', array('aid'))
      ->orderBy('aid', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchField();
    $option = $data + array(
      'aid' => $max_aid,
      'name' => $this->randomName(8),
      'cost' => mt_rand(-500, 500),
      'price' => mt_rand(-500, 500),
      'weight' => mt_rand(-500, 500),
      'ordering' => mt_rand(-10, 10),
    );
    $option = (object) $option;

    if ($save) {
      uc_attribute_option_save($option);
    }
    return $option;
  }

  /**
   * Adds a product to the cart.
   */
  function addToCart($product, $options = array()) {
    $this->drupalPostForm('node/' . $product->id(), $options, 'Add to cart');
  }

  /**
   * Creates a new product class.
   *
   * Fix this after adding a proper API call for saving a product class.
   */
  function createProductClass($data = array()) {
    $class = strtolower($this->randomName(12));
    $edit = $data + array(
      'type' => $class,
      'name' => $class,
      'description' => $this->randomName(32),
      'settings[uc_product][product]' => 1,
    );
    $this->drupalPostForm('admin/structure/types/add', $edit, t('Save content type'));

    return node_type_load($class);
  }

  /**
   * Helper function to fill-in required fields on the checkout page.
   *
   * @param $edit
   *   The form-values array to which to add required fields.
   */
  function populateCheckoutForm($edit = array()) {
    foreach (array('billing', 'delivery') as $pane) {
      $prefix = 'panes[' . $pane . ']';
      $key =  $prefix . '[country]';
      $country = empty($edit[$key]) ? config('uc_store.settings')->get('address.country') : $edit[$key];
      $zone_id = db_query_range('SELECT zone_id FROM {uc_zones} WHERE zone_country_id = :country ORDER BY rand()', 0, 1, array('country' => $country))->fetchField();
      $edit += array(
        $prefix . '[first_name]' => $this->randomName(10),
        $prefix . '[last_name]' => $this->randomName(10),
        $prefix . '[street1]' => $this->randomName(10),
        $prefix . '[city]' => $this->randomName(10),
        $prefix . '[zone]' => $zone_id,
        $prefix . '[postal_code]' => mt_rand(10000, 99999),
      );
    }

    // If the email address has not been set, and the user has not logged in,
    // add a primary email address.
    if (!isset($edit['panes[customer][primary_email]']) && !$this->loggedInUser) {
      $edit['panes[customer][primary_email]'] = $this->randomName(8) . '@example.com';
    }

    return $edit;
  }

  /**
   * Executes the checkout process.
   */
  function checkout($edit = array()) {
    $this->drupalPostForm('cart', array(), 'Checkout');
    $this->assertText(
      t('Enter your billing address and information here.'),
      t('Viewed cart page: Billing pane has been displayed.')
    );

    $edit = $this->populateCheckoutForm($edit);

    // Submit the checkout page.
    $this->drupalPostForm('cart/checkout', $edit, t('Review order'));
    $this->assertRaw(t('Your order is almost complete.'));

    // Complete the review page.
    $this->drupalPostForm(NULL, array(), t('Submit order'));

    $order_id = db_query("SELECT order_id FROM {uc_orders} WHERE billing_first_name = :name", array(':name' => $edit['panes[billing][first_name]']))->fetchField();
    if ($order_id) {
      $this->pass(
        t('Order %order_id has been created', array('%order_id' => $order_id))
      );
      $order = uc_order_load($order_id);
    }
    else {
      $this->fail(t('No order was created.'));
      $order = FALSE;
    }

    return $order;
  }

  /**
   * Creates a new order.
   */
  function createOrder($edit = array()) {
    $this->addToCart($this->product);

    $order = $this->checkout();
    if ($order && !empty($edit)) {
      foreach ($edit as $key => $value) {
        $order->$key = $value;
      }
      $order->save();
    }

    return $order;
  }

  /**
   * Assert that an email was sent with a specific subject line.
   *
   * @param $pattern
   *   A regular expression to match the subject against.
   *
   * @return
   *   An array containing the most recently sent matching email,
   *   or FALSE if the subject line did not match anything.
   */
  function findMail($pattern) {
    foreach (array_reverse($this->drupalGetMails()) as $mail) {
      if (preg_match($pattern, $mail['subject'])) {
        $this->pass(t('E-mail found with subject matching %pattern.', array('%pattern' => $pattern)));
        return $mail;
      }
    }

    $this->fail(t('E-mail not found with subject matching %pattern.', array('%pattern' => $pattern)));
    return FALSE;
  }

  /**
   * Helper function to test for text in a drupal ajax response.
   *
   * @param $ajax
   *   The ajax response to test.  Must be an array of ajax commands as returned by drupalPostAjaxForm().
   * @param $text
   *   The text to search for.
   * @param $message
   *   The assertion message.
   * @param $not_exists
   *   TRUE to assert that the text is not present.  FALSE (the default) to assert that it is present.
   * @param $plain
   *   TRUE to check only the plain-text contents of the 'data' keys of each 'insert' command (i.e. what would
   *   be inserted into the page).  FALSE to check the complete, json-encoded ajax response.
   */
  function assertAjaxHelper($ajax, $text, $message = FALSE, $not_exists = FALSE, $plain = TRUE) {
    $content = '';
    if ($plain) {
      foreach ($ajax as $command) {
        if ($command['command'] == 'insert' && !empty($command['data']) && is_string($command['data'])) {
          $content .= $command['data'];
        }
      }
      $content = filter_xss($content, array());
    }
    else {
      $content = drupal_json_encode($ajax);
    }
    if (!$message) {
      $message = !$not_exists ? t('"@text" found in ajax response', array('@text' => $text)) : t('"@text" not found in ajax response', array('@text' => $text));
    }
    $this->assert($not_exists == (strpos($content, $text) === FALSE), $message);
  }

  /**
   * Assert that the specified text is present in the plain text version of the html that would
   * be inserted into the page if this ajax response were executed.
   *
   * @param $ajax
   *   The ajax response to test.  Must be an array of ajax commands as returned by drupalPostAjaxForm().
   * @param $text
   *   The text to search for.
   * @param $message
   *   The assertion message.
   */
  function assertAjaxText($ajax, $text, $message = FALSE) {
    $this->assertAjaxHelper($ajax, $text, $message, FALSE, TRUE);
  }

  /**
   * Assert that the specified text is not present in the plain text version of the html that would
   * be inserted into the page if this ajax response were executed.
   *
   * @param $ajax
   *   The ajax response to test.  Must be an array of ajax commands as returned by drupalPostAjaxForm().
   * @param $text
   *   The text to search for.
   * @param $message
   *   The assertion message.
   */
  function assertNoAjaxText($ajax, $text, $message = FALSE) {
    $this->assertAjaxHelper($ajax, $text, $message, TRUE, TRUE);
  }

  /**
   * Assert that the specified text is present in the raw drupal ajax response.
   *
   * @param $ajax
   *   The ajax response to test.  Must be an array of ajax commands as returned by drupalPostAjaxForm().
   * @param $text
   *   The text to search for.
   * @param $message
   *   The assertion message.
   */
  function assertAjaxRaw($ajax, $text, $message = FALSE) {
    $this>assertAjaxHelper($ajax, $text, $message, FALSE, FALSE);
  }

  /**
   * Assert that the specified text is not present in the raw drupal ajax response.
   *
   * @param $ajax
   *   The ajax response to test.  Must be an array of ajax commands as returned by drupalPostAjaxForm().
   * @param $text
   *   The text to search for.
   * @param $message
   *   The assertion message.
   */
  function assertNoAjaxRaw($ajax, $text, $message = FALSE) {
    $this>assertAjaxHelper($ajax, $text, $message, TRUE, FALSE);
  }

  /**
   * Extends drupalPostAjaxForm() to replace additional content on the page after an ajax submission.
   *
   * DrupalWebTestCase::drupalPostAjaxForm() will only process ajax insertions which don't have a 'selector' attribute,
   * because it's not easy to convert from a jQuery selector to an XPath.  However, ubercart uses many simple,
   * id-based selectors, and these can be converted easily (eg: '#my-identifier' => '//*[@id="my-identifier"]').
   * This helper method post-processes the command array returned by drupalPostAjaxForm() to perform these insertions.
   *
   * @see DrupalWebTestCase::drupalPostAjaxForm()
   */
  protected function ucPostAJAX($path, $edit, $triggering_element, $ajax_path = NULL, array $options = array(), array $headers = array(), $form_html_id = NULL, $ajax_settings = NULL) {
    $commands = parent::drupalPostAjaxForm($path, $edit, $triggering_element, $ajax_path, $options, $headers, $form_html_id, $ajax_settings);
    $dom = new \DOMDocument();
    @$dom->loadHTML($this->drupalGetContent());
    foreach ($commands as $command) {
      if ($command['command'] == 'insert' && isset($command['selector']) && preg_match('/^\#-?[_a-zA-Z]+[_a-zA-Z0-9-]*$/', $command['selector'])) {
        $xpath = new \DOMXPath($dom);
        $wrapperNode = $xpath->query('//*[@id="' . substr($command['selector'], 1) . '"]')->item(0);
        if ($wrapperNode) {
          // ajax.js adds an enclosing DIV to work around a Safari bug.
          $newDom = new \DOMDocument();
          $newDom->loadHTML('<div>' . $command['data'] . '</div>');
          $newNode = $dom->importNode($newDom->documentElement->firstChild->firstChild, TRUE);
          $method = isset($command['method']) ? $command['method'] : $ajax_settings['method'];
          // The "method" is a jQuery DOM manipulation function. Emulate
          // each one using PHP's DOMNode API.
          switch ($method) {
            case 'replaceWith':
              $wrapperNode->parentNode->replaceChild($newNode, $wrapperNode);
              break;
            case 'append':
              $wrapperNode->appendChild($newNode);
              break;
            case 'prepend':
              // If no firstChild, insertBefore() falls back to
              // appendChild().
              $wrapperNode->insertBefore($newNode, $wrapperNode->firstChild);
              break;
            case 'before':
              $wrapperNode->parentNode->insertBefore($newNode, $wrapperNode);
              break;
            case 'after':
              // If no nextSibling, insertBefore() falls back to
              // appendChild().
              $wrapperNode->parentNode->insertBefore($newNode, $wrapperNode->nextSibling);
              break;
            case 'html':
              foreach ($wrapperNode->childNodes as $childNode) {
                $wrapperNode->removeChild($childNode);
              }
              $wrapperNode->appendChild($newNode);
              break;
          }
        }
      }
    }
    $content = $dom->saveHTML();
    $this->drupalSetContent($content);
    $this->verbose('Page content after ajax submission:<hr />' . $this->content);
    return $commands;
  }
}
