<?php

/**
 * @file
 * Contains \Drupal\uc_store\EncryptionInterface.
 */

namespace Drupal\uc_store;


/**
 * Provides common interface for encryption methods.
 */
interface EncryptionInterface {

  /**
   * Encrypts plaintext.
   *
   * @param string $key
   *   Key used for encryption.
   * @param string $source
   *   Plaintext. Text string to be encrypted.
   * @param int $sourcelen
   *   Minimum plaintext length. Plaintext $source which is shorter than
   *   $sourcelen will be padded by appending spaces.
   *
   * @return string
   *   Cyphertext. Text string containing encrypted $source.
   */
  public function encrypt($key, $source, $sourcelen);

  /**
   * Decrypts cyphertext.
   *
   * @param string $key
   *   Key used for encryption.
   * @param string $source
   *   Cyphertext. Text string containing encrypted $source.
   *
   * @return string
   *   Plaintext. Text string to be encrypted.
   */
  public function decrypt($key, $source);

  /**
   * Accessor for errors property.
   *
   * @return array
   *   Array of text strings containing error messages.
   */
  public function getErrors();

  /**
   * Mutator for errors property.
   *
   * @param array $errors
   *   Array of text strings containing error messages.
   */
  public function setErrors(array $errors);
}
