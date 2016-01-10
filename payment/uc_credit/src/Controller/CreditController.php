<?php

/**
 * @file
 * Contains \Drupal\uc_credit\Controller\CreditController.
 */

namespace Drupal\uc_credit\Controller;

/**
 * Provides instructions on how to create Cart Links.
 *
 * @return
 *   Form API array with help text.
 */
class CreditController {

  /**
   * Prints the contents of the CVV information popup window.
   */
  public static function cvvInfo() {
    //$build = array(
    //  '#prefix' => '<p>',
    //  '#suffix' => '</p>',
    //);
    //$build['introduction'] = array(
    //  '#prefix' => '<p>',
    //  '#markup' => $this->t("Cart Links allow you to craft links that add products to customer shopping carts and redirect customers to any page on the site. A store owner might use a Cart Link as a 'Buy it now' link in an e-mail, in a blog post, or on any page, either on or off site. These links may be identified with a unique ID, and clicks on these links may be reported to the administrator in order to track the effectiveness of each unique ID. You may track affiliate sales, see basic reports, and make sure malicious users don't create unapproved links."),
    //  '#suffix' => '</p>',
    $output = "<!DOCTYPE html>\n";
    $output .= '<html><head><meta charset="utf-8" /></head><body>';

    $output .= '<b>' . $this->t('What is the CVV?') . '</b><p>' . $this->t('CVV stands for Card Verification Value. This number is used as a security feature to protect you from credit card fraud.  Finding the number on your card is a very simple process.  Just follow the directions below.') . '</p>';
    $cc_types = array(
      'visa' => $this->t('Visa'),
      'mastercard' => $this->t('MasterCard'),
      'discover' => $this->t('Discover')
    );
    foreach ($cc_types as $id => $type) {
      if (variable_get('uc_credit_' . $id, TRUE)) {
        $valid_types[] = $type;
      }
    }
    if (count($valid_types) > 0) {
      $output .= '<br /><b>' . implode(', ', $valid_types) . ':</b><p>';
      //$output .= theme('image', array(
      //  'uri' => drupal_get_path('module', 'uc_credit') . '/images/visa_cvv.jpg',
      //  'attributes' => array('align' => 'left'),
      //));
      $output .= $this->t('The CVV for these cards is found on the back side of the card.  It is only the last three digits on the far right of the signature panel box.');
      $output .= '</p>';
    }

    if (variable_get('uc_credit_amex', TRUE)) {
      $output .= '<br /><p><b>' . $this->t('American Express') . ':</b><p>';
      //$output .= theme('image', array(
      //  'uri' => drupal_get_path('module', 'uc_credit') . '/images/amex_cvv.jpg',
      //  'attributes' => array('align' => 'left'),
      //));
      $output .= $this->t('The CVV on American Express cards is found on the front of the card.  It is a four digit number printed in smaller text on the right side above the credit card number.');
      $output .= '</p>';
    }

    $output .= '<p align="right"><input type="button" onclick="self.close();" value="' . $this->t('Close this window') . '" /></p>';

    $output .= '</body></html>';

    print $output;
//  exit();
  }
}
