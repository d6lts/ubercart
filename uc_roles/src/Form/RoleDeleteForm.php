<?php

/**
 * @file
 * Contains \Drupal\uc_roles\Form\RoleDeleteForm.
 */

namespace Drupal\uc_roles\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Form builder for role expirations.
 */
class RoleDeleteForm extends ConfirmFormBase {

  /**
   * The attribute to be deleted.
   */
  protected $attribute;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Delete expiration of %role_name role for the user !user?', array(
      '!user' => theme('username', array(
        'account' => $account,
        'name' => SafeMarkup::checkPlain($account->getUsername()),
        'link_path' => 'entity.user.canonical' . $account->id(),
      )),
      '%role_name' => $role_name,
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Deleting the expiration will give !user privileges set by the %role_name role indefinitely unless manually removed.', array(
          '!user' => theme('username', array(
            'account' => $account,
            'name' => SafeMarkup::checkPlain($account->getUsername()),
            'link_path' => 'entity.user.canonical' . $account->id(),
          )),
          '%role_name' => $role_name,
        ));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Yes');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('No');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('uc_roles.expiration');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_roles_deletion_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $user = NULL, $role = NULL) {
    $expiration = db_query("SELECT expiration FROM {uc_roles_expirations} WHERE uid = :uid AND rid = :rid", [':uid' => $user->id(), ':rid' => $role])->fetchField();
    if ($expiration) {

      $role_name = _uc_roles_get_name($role);

      $form['user'] = array('#type' => 'value', '#value' => $user->getUsername());
      $form['uid'] = array('#type' => 'value', '#value' => $user->id());
      $form['role'] = array('#type' => 'value', '#value' => $role_name);
      $form['rid'] = array('#type' => 'value', '#value' => $role);
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    uc_roles_delete(user_load($form_state->getValue('uid')), $form_state->getValue('rid'));

    $form_state->setRedirect('uc_roles.expiration');
  }
}
