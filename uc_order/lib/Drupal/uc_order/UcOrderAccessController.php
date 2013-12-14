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
        if (user_access('view all orders', $account)) {
          return TRUE;
        }
        // Non-anonymous users can view their own orders and invoices with permission.
        $permission = $operation == 'view' ? 'view own orders' : 'view own invoices';
        if ($account->id() && $account->id() == $entity->getUserId() && user_access($permission, $account)) {
          return TRUE;
        }
        return FALSE;
        break;

      case 'update':
        return user_access('edit orders', $account);
        break;

      case 'delete':
        if (user_access('unconditionally delete orders', $account)) {
          // Unconditional deletion perms are always TRUE.
          return TRUE;
        }
        if (user_access('delete orders', $account)) {
          // Only users with unconditional deletion perms can delete completed orders.
          if ($entity->getStateId() == 'completed') {
            return FALSE;
          }
          else {
            $can_delete = TRUE;
            // See if any modules have a say in this order's eligibility for deletion.
            $module_handler = \Drupal::moduleHandler();
            foreach ($module_handler->getImplementations('uc_order') as $module) {
              $function = $module . '_uc_order';
              if (function_exists($function) && $function('can_delete', $entity, NULL) === FALSE) {
                $can_delete = FALSE;
                break;
              }
            }

            return $can_delete;
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
    return user_access('create orders', $account);
  }

}
