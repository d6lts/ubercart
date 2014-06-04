<?php

/**
 * @file
 * Contains \Drupal\uc_order\Form\OrderDeleteForm.
 */

namespace Drupal\uc_order\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;

/**
 * Provides a form for deleting a feed.
 */
class OrderDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete order @order_id?', array('@order_id' => $this->entity->id()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'view.uc_orders.page_1',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();
    drupal_set_message(t('Order @order_id completely removed from the database.', array('@order_id' => $this->entity->id())));
    $form_state['redirect'] = 'admin/store/orders/view';
  }

}
