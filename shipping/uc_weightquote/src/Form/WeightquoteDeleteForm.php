<?php

/**
 * @file
 * Contains \Drupal\uc_weightquote\Form\WeightquoteDeleteForm.
 */

namespace Drupal\uc_weightquote\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Confirms deletion of a weight-based shipping method.
 */
class WeightquoteDeleteForm extends ConfirmFormBase {

  /**
   * The method ID to be deleted.
   */
  protected $methodId;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you want to delete this shipping method?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This will remove the shipping method and the product-specific overrides (if applicable). This action can not be undone.');
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
  public function getCancelUrl() {
    return new Url('uc_quote.methods');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_weightquote_admin_method_confirm_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $mid = NULL) {
    $this->methodId = $mid;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    db_delete('uc_weightquote_methods')
      ->condition('mid', $this->methodId)
      ->execute();
    db_delete('uc_weightquote_products')
      ->condition('mid', $this->methodId)
      ->execute();

    // rules_config_delete(array('get_quote_from_weightquote_' . $mid));

    drupal_set_message(t('Weight quote shipping method deleted.'));
    $form_state['redirect'] = 'admin/store/settings/quotes';
  }

}
