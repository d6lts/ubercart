<?php

/**
 * @file
 * Contains \Drupal\uc_payment\Form\OrderPaymentsForm.
 */

namespace Drupal\uc_payment\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\Entity\PaymentMethod;
use Drupal\uc_payment\Plugin\PaymentMethodManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays a list of payments attached to an order.
 */
class OrderPaymentsForm extends FormBase {

  /**
   * The payment method manager.
   *
   * @var \Drupal\uc_payment\Plugin\PaymentMethodManager
   */
  protected $paymentMethodManager;

  /**
   * Constructs an OrderPaymentsForm object.
   *
   * @param \Drupal\uc_payment\Plugin\PaymentMethodManager $payment_method_manager
   *   The payment method plugin manager.
   */
  public function __construct(PaymentMethodManager $payment_method_manager) {
    $this->paymentMethodManager = $payment_method_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.uc_payment.method')
    );
  }

  /**
   * The order that is being viewed.
   *
   * @var \Drupal\uc_order\OrderInterface
   */
  protected $order;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_payment_by_order_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $uc_order = NULL) {
    $this->order = $uc_order;

    $form['#attached']['library'][] = 'uc_payment/uc_payment.styles';

    $total = $this->order->getTotal();
    $payments = uc_payment_load_payments($this->order->id());

    $form['order_total'] = array(
      '#type' => 'item',
      '#title' => $this->t('Order total'),
      '#theme' => 'uc_price',
      '#price' => $total,
    );
    $form['payments'] = array(
      '#type' => 'table',
      '#tree' => TRUE,
      '#header' => array($this->t('Received'), $this->t('User'), $this->t('Method'), $this->t('Amount'), $this->t('Balance'), $this->t('Comment'), $this->t('Action')),
      '#weight' => 10,
    );

    $account = $this->currentUser();
    if ($payments !== FALSE) {
      foreach ($payments as $payment) {
        $form['payments'][$payment->receipt_id]['received'] = array(
          '#markup' => \Drupal::service('date.formatter')->format($payment->received, 'short'),
        );
        $form['payments'][$payment->receipt_id]['user'] = array(
          '#theme' => 'username',
          '#account' => User::load($payment->uid),
        );
        $form['payments'][$payment->receipt_id]['method'] = array(
          '#markup' => ($payment->method == '') ? $this->t('Unknown') : $payment->method,
        );
        $form['payments'][$payment->receipt_id]['amount'] = array(
          '#theme' => 'uc_price',
          '#price' => $payment->amount,
        );
        $total -= $payment->amount;
        $form['payments'][$payment->receipt_id]['balance'] = array(
          '#theme' => 'uc_price',
          '#price' => $total,
        );
        $form['payments'][$payment->receipt_id]['comment'] = array(
          '#markup' => ($payment->comment == '') ? '-' : $payment->comment,
        );

        if ($account->hasPermission('delete payments')) {
          $form['payments'][$payment->receipt_id]['action'] = array(
            '#type' => 'operations',
            '#links' => array(
              'delete' => array(
                'title' => $this->t('Delete'),
                'url' => Url::fromRoute('uc_payments.delete', ['uc_order' => $this->order->id(), 'payment' => $payment->receipt_id]),
              ),
            ),
          );
        }
        else {
          $form['payments'][$payment->receipt_id]['action'] = array(
            '#markup' => '',
          );
        }
      }
    }

    $form['balance'] = array(
      '#type' => 'item',
      '#title' => $this->t('Current balance'),
      '#theme' => 'uc_price',
      '#price' => $total,
    );

    if ($account->hasPermission('manual payments')) {
      $form['payments']['new']['received'] = array(
        '#type' => 'date',
        '#default_value' => array(
          'month' => \Drupal::service('date.formatter')->format(REQUEST_TIME, 'custom', 'n'),
          'day' => \Drupal::service('date.formatter')->format(REQUEST_TIME, 'custom', 'j'),
          'year' => \Drupal::service('date.formatter')->format(REQUEST_TIME, 'custom', 'Y'),
        ),
      );
      $form['payments']['new']['user'] = array(
        '#markup' => '-',
      );

      $options = array();
      foreach (PaymentMethod::loadMultiple() as $method) {
        $options[$method->id()] = $method->label();
      }
      $form['payments']['new']['method'] = array(
        '#type' => 'select',
        '#title' => $this->t('Method'),
        '#title_display' => 'invisible',
        '#options' => $options,
        '#default_value' => $this->order->getPaymentMethodId(),
      );
      $form['payments']['new']['amount'] = array(
        '#type' => 'uc_price',
        '#title' => $this->t('Amount'),
        '#title_display' => 'invisible',
        '#size' => 6,
      );
      $form['payments']['new']['balance'] = array(
        '#markup' => '-',
      );
      $form['payments']['new']['comment'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Comment'),
        '#title_display' => 'invisible',
        '#size' => 32,
        '#maxlength' => 256,
      );
      $form['payments']['new']['action'] = array('#type' => 'actions');
      $form['payments']['new']['action']['action'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Enter'),
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!is_numeric($form_state->getValue(['payments', 'new', 'amount']))) {
      $form_state->setErrorByName('payments][new][amount', $this->t('You must enter a number for the amount.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $payment = $form_state->getValue(['payments', 'new']);
    $received = strtotime($payment['received']['year'] . '-' . $payment['received']['month'] . '-' . $payment['received']['day'] . ' 00:00:00');

    // If the value entered is today, use the exact timestamp instead
    $startofday = mktime(0, 0, 0);

    if ($received == $startofday) {
      $received = REQUEST_TIME;
    }

    uc_payment_enter($this->order->id(), $payment['method'], $payment['amount'], \Drupal::currentUser()->id(), '', $payment['comment'], $received);

    drupal_set_message($this->t('Payment entered.'));
  }

}
