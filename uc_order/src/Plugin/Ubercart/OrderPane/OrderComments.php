<?php

/**
 * @file
 * Contains \Drupal\uc_order\Plugin\Ubercart\OrderPane\OrderComments.
 */

namespace Drupal\uc_order\Plugin\Ubercart\OrderPane;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\uc_order\Entity\OrderStatus;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_order\OrderPanePluginBase;

/**
 * View the order comments, used for communicating with customers.
 *
 * @UbercartOrderPane(
 *   id = "order_comments",
 *   title = @Translation("Order comments"),
 *   weight = 8,
 * )
 */
class OrderComments extends OrderPanePluginBase {

  /**
   * {@inheritdoc}
   */
  public function view(OrderInterface $order, $view_mode) {
    // @todo Simplify this or replace with Views
    if ($view_mode == 'customer') {
      $comments = uc_order_comments_load($order->id());
      $statuses = OrderStatus::loadMultiple();
      $header = array(t('Date'), t('Status'), t('Message'));
      $rows[] = array(
        array('data' => \Drupal::service('date.formatter')->format($order->created->value, 'uc_store'), 'class' => array('date')),
        array('data' => '-', 'class' => array('status')),
        array('data' => t('Order created.'), 'class' => array('message')),
      );
      if (count($comments) > 0) {
        foreach ($comments as $comment) {
          $rows[] = array(
            array('data' => \Drupal::service('date.formatter')->format($comment->created, 'uc_store'), 'class' => array('date')),
            array('data' => SafeMarkup::checkPlain($statuses[$comment->order_status]->getName()), 'class' => array('status')),
            array('data' => SafeMarkup::checkPlain($comment->message), 'class' => array('message')),
          );
        }
      }
      $build = array(
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#attributes' => array('class' => array('uc-order-comments')),
      );
    }
    else {
      $build = array(
        '#theme' => 'table',
        '#header' => array(
          array('data' => t('Date'), 'class' => array('date')),
          array('data' => t('User'), 'class' => array('user')),
          array('data' => t('Notified'), 'class' => array('notified')),
          array('data' => t('Status'), 'class' => array('status')),
          array('data' => t('Comment'), 'class' => array('message')),
        ),
        '#rows' => array(),
        '#attributes' => array('class' => array('order-pane-table uc-order-comments')),
        '#empty' => t('This order has no comments associated with it.'),
      );
      $comments = uc_order_comments_load($order->id());
      $statuses = OrderStatus::loadMultiple();
      foreach ($comments as $comment) {
        $icon = $comment->notified ? 'true-icon.gif' : 'false-icon.gif';
        $build['#rows'][] = array(
          array('data' => \Drupal::service('date.formatter')->format($comment->created, 'short'), 'class' => array('date')),
          array('data' => array('#theme' => 'uc_uid', '#uid' => $comment->uid), 'class' => array('user')),
          array('data' => array('#theme' => 'image', '#uri' => drupal_get_path('module', 'uc_order') . '/images/' . $icon), 'class' => array('notified')),
          array('data' => SafeMarkup::checkPlain($statuses[$comment->order_status]->getName()), 'class' => array('status')),
          array('data' => SafeMarkup::checkPlain($comment->message), 'class' => array('message')),
        );
      }
    }

    return $build;
  }

}
