<?php

/**
 * @file
 * Definition of Drupal\uc_order\Plugin\views\argument_validator\CurrentUserOrPermission.
 */

namespace Drupal\uc_order\Plugin\views\argument_validator;

use Drupal\Core\Annotation\Translation;
use Drupal\views\Annotation\ViewsArgumentValidator;
use Drupal\views\Plugin\views\argument_validator\ArgumentValidatorPluginBase;

/**
 * Validate whether an argument is the current user or has a permission.
 *
 * This supports either numeric arguments (UID) or strings (username) and
 * converts either one into the user's UID.  This validator also sets the
 * argument's title to the username.
 *
 * @ViewsArgumentValidator(
 *   id = "user_or_permission",
 *   module = "uc_order",
 *   title = @Translation("Current user or user has permission")
 * )
 */
class CurrentUserOrPermission extends ArgumentValidatorPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['type'] = array('default' => 'uid');
    $options['perm'] = array('default' => 'view all orders');

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    $form['type'] = array(
      '#type' => 'radios',
      '#title' => t('Type of user filter value to allow'),
      '#options' => array(
        'uid' => t('Only allow numeric UIDs'),
        'name' => t('Only allow string usernames'),
        'either' => t('Allow both numeric UIDs and string usernames'),
      ),
      '#default_value' => $this->options['type'],
    );

    $perms = array();
    $module_info = system_get_info('module');

    // Get list of permissions
    foreach (module_implements('permission') as $module) {
      $permissions = module_invoke($module, 'permission');
      foreach ($permissions as $name => $perm) {
        $perms[$module_info[$module]['name']][$name] = strip_tags($perm['title']);
      }
    }

    asort($perms);

    $form['perm'] = array(
      '#type' => 'select',
      '#options' => $perms,
      '#title' => t('Permission'),
      '#default_value' => $this->options['perm'],
      '#description' => t('Users with the selected permission flag will be able to bypass validation.'),
    );
  }

  function validate_argument($argument) {
    $type = $this->options['type'];
    // is_numeric() can return false positives, so we ensure it's an integer.
    // However, is_integer() will always fail, since $argument is a string.
    if (is_numeric($argument) && $argument == (int)$argument) {
      if ($type == 'uid' || $type == 'either') {
        if ($argument == $GLOBALS['user']->id()) {
          // If you assign an object to a variable in PHP, the variable
          // automatically acts as a reference, not a copy, so we use
          // clone to ensure that we don't actually mess with the
          // real global $user object.
          $account = clone $GLOBALS['user'];
        }
        $condition = 'uid';
      }
    }
    else {
      if ($type == 'name' || $type == 'either') {
        $name = !empty($GLOBALS['user']->name) ? $GLOBALS['user']->name : config('user.settings')->get('anonymous');
        if ($argument == $name) {
          $account = clone $GLOBALS['user'];
        }
        $condition = 'name';
      }
    }

    // If we don't have a WHERE clause, the argument is invalid.
    if (empty($condition)) {
      return FALSE;
    }

    if (!isset($account)) {
      $account = db_select('users', 'u')
        ->fields('u', array('uid', 'name'))
        ->condition($condition, $argument)
        ->execute()
        ->fetchObject();
    }
    if (empty($account)) {
      // User not found.
      return FALSE;
    }

    // If the current user is not the account specified by the argument
    // and doesn't have the correct permission, validation fails.
    if ($GLOBALS['user']->id() != $account->id() && !user_access($this->options['perm'])) {
      return FALSE;
    }

    $this->argument->argument = $account->id();
    $this->argument->validated_title = check_plain(user_format_name($account));
    return TRUE;
  }

  function process_summary_arguments(&$args) {
    // If the validation says the input is an username, we should reverse the
    // argument so it works for example for generation summary urls.
    $uids_arg_keys = array_flip($args);
    if ($this->options['type'] == 'name') {
      $users = user_load_multiple($args);
      foreach ($users as $uid => $account) {
        $args[$uids_arg_keys[$uid]] = $account->name;
      }
    }
  }

}
