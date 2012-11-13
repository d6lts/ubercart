<?php

/**
 * @file
 * Definition of Drupal\uc_store\Mail\UbercartMail.
 */

namespace Drupal\uc_store\Mail;

use Drupal\Core\Mail\PhpMail;

/**
 * Modifies the Drupal mail system to send HTML emails.
 */
class UbercartMail extends PhpMail {

  /**
   * Concatenates the e-mail body for HTML mails.
   *
   * @param $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return
   *   The formatted $message.
   */
  public function format(array $message) {
    $message['body'] = implode("\n\n", $message['body']);
    return $message;
  }

}
