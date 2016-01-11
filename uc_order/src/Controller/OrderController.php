<?php

/**
 * @file
 * Contains \Drupal\uc_order\Controller\OrderController.
 */

namespace Drupal\uc_order\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\uc_order\Entity\Order;
use Drupal\uc_order\OrderInterface;
use Drupal\user\UserInterface;

/**
 * Controller routines for order routes.
 */
class OrderController extends ControllerBase {

  /**
   * Creates an order for the specified user, and redirects to the edit page.
   */
  public function createForUser(UserInterface $user) {
    $order = Order::create([
      'uid' => $user->id(),
      'order_status' => uc_order_state_default('post_checkout'),
    ]);
    $order->save();

    uc_order_comment_save($order->id(), \Drupal::currentUser()->id(), $this->t('Order created by the administration.'), 'admin');

    return $this->redirect('entity.uc_order.edit_form', ['uc_order' => $order->id()]);
  }

  /**
   * Displays an order invoice.
   */
  public function invoice(OrderInterface $uc_order, $print = FALSE) {
    $build = array(
      '#theme' => 'uc_order_invoice',
      '#order' => $uc_order,
      '#op' => $print ? 'print' : 'view',
    );

    if ($print) {
      //@todo fix this
      //drupal_add_http_header('Content-Type', 'text/html; charset=utf-8');
      //print drupal_render(array('#theme' => 'uc_order_invoice_page', 'content' => $build));
      exit();
    }

    return $build;
  }

  /**
   * Displays a log of changes made to an order.
   */
  public function log(OrderInterface $uc_order) {
    $result = db_query("SELECT * FROM {uc_order_log} WHERE order_id = :id ORDER BY created, order_log_id", [':id' => $uc_order->id()]);

    $header = array($this->t('Time'), $this->t('User'), $this->t('Changes'));
    $rows = array();
    foreach ($result as $change) {
      $rows[] = array(
        \Drupal::service('date.formatter')->format($change->created, 'short'),
        array('data' => array('#theme' => 'uc_uid',  '#uid' => $change->uid)),
        array('data' => array('#markup' => $change->changes)),
      );
    }

    $build['log'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No changes have been logged for this order.'),
    );

    return $build;
  }

  /**
   * The title callback for order view routes.
   *
   * @param \Drupal\uc_order\OrderInterface $uc_order
   *   The order that is being viewed.
   *
   * @return string
   *   The page title.
   */
  public function pageTitle(OrderInterface $uc_order) {
    return SafeMarkup::checkPlain($uc_order->label());
  }

}
