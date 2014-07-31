<?php

/**
 * @file
 * Contains \Drupal\uc_product\Form\ProductFeatureDeleteForm.
 */

namespace Drupal\uc_product\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

/**
 * Defines the form for adding a product feature to the features tab.
 */
class ProductFeatureAddForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_product_feature_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    $form['#node'] = $node;

    foreach (\Drupal::moduleHandler()->invokeAll('uc_product_feature') as $feature) {
      $options[$feature['id']] = $feature['title'];
    }
    ksort($options);

    $form['feature'] = array(
      '#type' => 'select',
      '#title' => t('Add a new feature'),
      '#options' => $options,
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Add'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state['redirect'] = 'node/' . $form['#node']->id() . '/edit/features/' . $form_state['values']['feature'] . '/add';
  }

}
