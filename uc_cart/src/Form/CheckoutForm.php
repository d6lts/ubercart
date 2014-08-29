<?php

/**
 * @file
 * Contains \Drupal\uc_cart\Form\CheckoutForm.
 */

namespace Drupal\uc_cart\Form;

use Drupal\Component\Utility\String;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_cart\Plugin\CheckoutPaneManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The checkout form built up from the enabled checkout panes.
 */
class CheckoutForm extends FormBase {

  /**
   * The checkout pane manager.
   *
   * @var \Drupal\uc_cart\Plugin\CheckoutPaneManager
   */
  protected $checkoutPaneManager;

  /**
   * Constructs a CheckoutController.
   *
   * @param \Drupal\uc_cart\Plugin\CheckoutPaneManager $checkout_pane_manager
   *   The checkout pane plugin manager.
   */
  public function __construct(CheckoutPaneManager $checkout_pane_manager) {
    $this->checkoutPaneManager = $checkout_pane_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.uc_cart.checkout_pane')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_cart_checkout_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $order = NULL) {
    if ($processed = isset($form_state['storage']['order'])) {
      $order = $form_state['storage']['order'];
    }
    else {
      $form_state['storage']['order'] = $order;
    }

    $form['#attributes']['class'][] = 'uc-cart-checkout-form';
    $form['#attached']['css'][] = drupal_get_path('module', 'uc_cart') . '/css/uc_cart.css';
    $form['panes'] = array('#tree' => TRUE);

    $filter = array('enabled' => FALSE);

    // If the order isn't shippable, remove panes with shippable == TRUE.
    if (!$order->isShippable() && \Drupal::config('uc_cart.settings')->get('delivery_not_shippable')) {
      $filter['shippable'] = TRUE;
    }

    $panes = $this->checkoutPaneManager->getPanes($filter);

    // Invoke the 'prepare' op of enabled panes, but only if their 'process' ops
    // have not been invoked on this request (i.e. when rebuilding after AJAX).
    foreach ($panes as $id => $pane) {
      if (empty($form_state['storage']['panes'][$id]['prepared'])) {
        $pane->prepare($order, $form, $form_state);
        $form_state['storage']['panes'][$id]['prepared'] = TRUE;
        $processed = FALSE; // Make sure we save the updated order.
      }
    }

    // Load the line items and save the order. We do this after the 'prepare'
    // callbacks of enabled panes have been invoked, because these may have
    // altered the order.
    if (!$processed) {
      $order->line_items = $order->getLineItems();
      $order->save();
    }

    foreach ($panes as $id => $pane) {
      $form['panes'][$id] = $pane->view($order, $form, $form_state);
      $form['panes'][$id] += array(
        '#type' => 'details',
        '#title' => String::checkPlain($pane->getTitle()),
        '#id' => $id . '-pane',
        '#open' => TRUE,
      );
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['cancel'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#validate' => array(),
      '#limit_validation_errors' => array(),
      '#submit' => array(array($this, 'cancel')),
    );
    $form['actions']['continue'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Review order'),
      '#button_type' => 'primary',
    );

    $form_state->loadInclude('uc_store', 'inc', 'includes/uc_ajax_attach');
    $form['#process'][] = 'uc_ajax_process_form';

    unset($_SESSION['uc_checkout'][$order->id()]);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $order = $form_state['storage']['order'];

    // Update the order "modified" time to prevent timeout on ajax requests.
    $order->modified->value = REQUEST_TIME;

    // Validate/process the cart panes.  A FALSE value results in failed checkout.
    $form_state['checkout_valid'] = TRUE;
    foreach (element_children($form_state['values']['panes']) as $id) {
      $pane = $this->checkoutPaneManager->createInstance($id);
      if ($pane->process($order, $form, $form_state) === FALSE) {
        $form_state['checkout_valid'] = FALSE;
      }
    }

    // Reload line items and save order.
    $order->line_items = $order->getLineItems();
    $order->save();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state['checkout_valid'] === FALSE) {
      $form_state->setRedirect('uc_cart.checkout');
    }
    else {
      $form_state->setRedirect('uc_cart.checkout_review');
      $_SESSION['uc_checkout'][$form_state['storage']['order']->id()]['do_review'] = TRUE;
    }

    unset($form_state['checkout_valid']);
  }

  /**
   * Submit handler for the "Cancel" button on the checkout form.
   */
  public function cancel(array &$form, FormStateInterface $form_state) {
    $order = $form_state['storage']['order'];
    if (isset($_SESSION['cart_order']) && $_SESSION['cart_order'] == $order->id()) {
      uc_order_comment_save($_SESSION['cart_order'], 0, $this->t('Customer canceled this order from the checkout form.'));
      unset($_SESSION['cart_order']);
    }

    unset($_SESSION['uc_checkout'][$order->id()]);
    $form_state->setRedirect('uc_cart.cart');
  }

}
