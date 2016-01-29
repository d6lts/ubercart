<?php

/**
 * @file
 * Contains \Drupal\uc_credit\Controller\CreditController.
 */

namespace Drupal\uc_credit\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Utility functions for credit card payment methods.
 */
class CreditController extends ControllerBase {

  /**
   * Displays the contents of the CVV information popup window.
   *
   * @return string
   *   HTML markup for a page.
   */
  public function cvvInfo() {

    $build['#attached']['library'][] = 'uc_credit/uc_credit.styles';
    // @todo: Move the embedded CSS below into uc_credit.css.
    $build['title'] = array(
      '#prefix' => '<strong>',
      '#markup' => $this->t('What is the CVV?'),
      '#suffix' => '</strong>',
    );
    $build['definition'] = array(
      '#prefix' => '<p>',
      '#markup' => $this->t('CVV stands for "Card Verification Value". This number is used as a security feature to protect you from credit card fraud. Finding the number on your card is a very simple process. Just follow the directions below.'),
      '#suffix' => '</p>',
    );

    $credit_config = $this->config('uc_credit.settings');
    $cc_types = array(
      'visa' => $this->t('Visa'),
      'mastercard' => $this->t('MasterCard'),
      'discover' => $this->t('Discover'),
    );
    foreach ($cc_types as $type => $label) {
      if ($credit_config->get($type)) {
        $valid_types[] = $label;
      }
    }
    if (count($valid_types) > 0) {
      $build['types'] = array(
        '#prefix' => '<br /><strong>',
        '#markup' => implode(', ', $valid_types),
        '#suffix' => ':</strong>',
      );
      $build['image'] = array(
        '#theme' => 'image',
        '#uri' => drupal_get_path('module', 'uc_credit') . '/images/visa_cvv.jpg',
        '#alt' => 'MasterCard/Visa/Discover CVV location',
        '#attributes' => array('align' => 'left'),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      );
      $build['where'] = array(
        '#prefix' => '<p>',
        '#markup' => $this->t('The CVV for these cards is found on the back side of the card. It is only the last three digits on the far right of the signature panel box.'),
        '#suffix' => '</p>',
      );
    }

    if ($credit_config->get('amex')) {
      $build['types-amex'] = array(
        '#prefix' => '<br /><strong>',
        '#markup' => $this->t('American Express'),
        '#suffix' => ':</strong>',
      );
      $build['image-amex'] = array(
        '#theme' => 'image',
        '#uri' => drupal_get_path('module', 'uc_credit') . '/images/amex_cvv.jpg',
        '#alt' => 'Amex CVV location',
        '#attributes' => array('align' => 'left'),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      );
      $build['where-amex'] = array(
        '#prefix' => '<p>',
        '#markup' => $this->t('The CVV on American Express cards is found on the front of the card. It is a four digit number printed in smaller text on the right side above the credit card number.'),
        '#suffix' => '</p>',
      );
    }

    $build['close'] = array(
      '#type' => 'button',
      '#prefix' => '<p align="right">',
      '#value' => $this->t('Close this window'),
      '#attributes' => array('onclick' => 'self.close();'),
      '#suffix' => '</p>',
    );

    $renderer = \Drupal::service('bare_html_page_renderer');
    // @todo: Make our own theme function to use instead of 'page'?
    return $renderer->renderBarePage($build, $this->t('CVV Info'), 'page');
  }
}
