<?php

/**
 * @file
 * Contains \Drupal\uc_order\UcOrderAccessController.
 */

namespace Drupal\uc_order;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access controller for Ubercart orders.
 */
class UcOrderAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    switch ($operation) {
      case 'view':
      case 'invoice':
        // Admins can view all orders.
        if ($account->hasPermission('view all orders')) {
          return TRUE;
        }
        // Non-anonymous users can view their own orders and invoices with permission.
        $permission = $operation == 'view' ? 'view own orders' : 'view own invoices';
        if ($account->id() && $account->id() == $entity->getUserId() && $account->hasPermission($permission)) {
          return TRUE;
        }
        return FALSE;
        break;

      case 'update':
        return $account->hasPermission('edit orders');
        break;

      case 'delete':
        if ($account->hasPermission('unconditionally delete orders')) {
          // Unconditional deletion perms are always TRUE.
          return TRUE;
        }
        if ($account->hasPermission('delete orders')) {
          // Only users with unconditional deletion perms can delete completed orders.
          if ($entity->getStateId() == 'completed') {
            return FALSE;
          }
          else {
            // See if any modules have a say in this order's eligibility for deletion.
            $module_handler = \Drupal::moduleHandler();
            foreach ($module_handler->getImplementations('uc_order_can_delete') as $module) {
              $function = $module . '_uc_order_can_delete';
              if ($function($entity) === FALSE) {
                return FALSE;
              }
            }

            return TRUE;
          }
        }
        return FALSE;
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return $account->hasPermission('create orders');
  }

}
