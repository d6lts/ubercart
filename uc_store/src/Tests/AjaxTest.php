<?php

/**
 * @file
 * Contains \Drupal\uc_store\Tests\AjaxTest.
 */

namespace Drupal\uc_store\Tests;

use Drupal\uc_country\Entity\Country;

/**
 * Tests Ajax updating of checkout and order pages.
 *
 * @group Ubercart
 */
class AjaxTest extends UbercartTestBase {

  public static $modules = array(/*'rules_admin', */'uc_payment', 'uc_payment_pack');
  public static $adminPermissions = array(/*'administer rules', 'bypass rules access'*/);

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);

    // module_load_include() has to be called after parent::setUp()
    // because the moduler_handler service isn't initialized yet.
    module_load_include('inc', 'uc_store', 'includes/uc_ajax_attach');

    // In order to test zone-based conditions, this particular test class
    // assumes that US is enabled and set as the store country.
    Country::load('US')->enable()->save();
    \Drupal::configFactory()->getEditable('uc_store.settings')->set('address.country', 'US')->save();
  }

  /**
   * Set a zone-based condition for a particular payment method.
   *
   * @param $method
   *   The method to set (e.g. 'check')
   * @param $zone
   *   The zone id (numeric) to check for.
   * @param $negate
   *   TRUE to negate the condition.
   */
  protected function addPaymentZoneCondition($method, $zone, $negate = FALSE) {
    $not = $negate ? 'NOT ' : '';
    $name = 'uc_payment_method_' . $method;
    $label = ucfirst($method) . ' conditions';
    $condition = array(
      'LABEL' => $label,
      'PLUGIN' => 'and',
      'REQUIRES' => array('rules'),
      'USES VARIABLES' => array(
        'order' => array(
          'label' => 'Order',
          'type' => 'uc_order',
        ),
      ),
      'AND' => array(
        array(
          $not . 'data_is' => array(
            'data' => array('order:billing-address:zone'),
            'value' => $zone,
          ),
        ),
      ),
    );
    $newconfig = rules_import(array($name => $condition));
    $oldconfig = rules_config_load($name);
    if ($oldconfig) {
      $newconfig->id = $oldconfig->id;
      unset($newconfig->is_new);
      $newconfig->status = ENTITY_CUSTOM;
    }
    $newconfig->save();
    entity_flush_caches();
    //$this->drupalGet('admin/config/workflow/rules/components/manage/' . $newconfig->id);
  }

  public function testCheckoutAjax() {
    // Enable two payment methods and set a condition on one.
    $this->createPaymentMethod('check');
    // Use randomMachineName() as randomString() has escaping problems when
    // sent over Ajax; see https://www.drupal.org/node/2664320
    $other = $this->createPaymentMethod('other', ['label' => $this->randomMachineName()]);
    // $this->addPaymentZoneCondition($other['id'], 'KS');

    // Specify that the billing zone should update the payment pane.
    $config = _uc_ajax_defaults('checkout');
    $config['panes][billing][address][zone'] = array('payment-pane' => 'payment-pane');
    \Drupal::configFactory()->getEditable('uc_cart.settings')
      ->set('ajax.checkout', $config)
      ->save();

    // Go to the checkout page, verify that the conditional payment method is
    // not available.
    $product = $this->createProduct(array('shippable' => 0));
    $this->addToCart($product);
    $this->drupalPostForm('cart', array('items[0][qty]' => 1), t('Checkout'));
    // @todo Re-enable when shipping quote conditions are available.
    // $this->assertNoEscaped($other['label']);

    // Change the billing zone and verify that payment pane updates.
    $edit = array();
    $edit['panes[billing][zone]'] = 'KS';
    $this->ucPostAjax(NULL, $edit, 'panes[billing][zone]');
    $this->assertEscaped($other['label']);
    $edit['panes[billing][zone]'] = 'AL';
    $this->ucPostAjax(NULL, $edit, 'panes[billing][zone]');
    // Not in Kansas any more...
    // @todo Re-enable when shipping quote conditions are available.
    // $this->assertNoEscaped($other['label']);
  }
}
