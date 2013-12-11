<?php

/**
 * @file
 * Contains \Drupal\uc_cybersource\CybersourceUtilities.
 */

namespace Drupal\uc_cybersource;

/**
 * Utility routines for Cybersource.
 */
class CybersourceUtilities {

  /**
   * Returns the code for the credit card type.
   */
  public static function cardType($cc_number) {
    switch (substr(strval($cc_number), 0, 1)) {
      case '3':
        if (strlen($cc_number) == 14) {
          return '005';  // Diners Club
        }
        elseif (strlen($cc_number) == 15) {
          return '003';  // AmEx
        }
        else {
          return '007';  // JCB
        }
      case '4':
        return '001';  // Visa
      case '5':
        return '002';  // MasterCard
      case '6':
        return '004';  // Discover
    }

    return FALSE;
  }

  /**
   * Returns the meaning of the reason code given by CyberSource.
   */
  public static function reasonResponse($code) {
    switch ($code) {
      case '100':
        return t('Successful transaction.');
      case '102':
        return t('One or more fields in the request are missing or invalid.<br /><b>Possible action:</b> Resend the request with the correct information.');
      case '150':
        return t('<b>Error:</b> General system failure.<br /><b>Possible action:</b> Wait a few minutes and resend the request.');
      case '151':
        return t('<b>Error:</b> The request was received, but a server time-out occurred. This error does not include time-outs between the client and the server.<br /><b>Possible action:</b> To avoid duplicating the order, do not resend the request until you have reviewed the order status in the Business Center.');
      case '152':
        return t('<b>Error:</b> The request was received, but a service did not finish running in time.<br /><b>Possible action:</b> To avoid duplicating the order, do not resend the request until you have reviewed the order status in the Business Center.');
      case '200':
        return t('The authorization request was approved by the issuing bank but declined by CyberSource because it did not pass the Address Verification Service (AVS) check.<br /><b>Possible action:</b> You can capture the authorization, but consider reviewing the order for the possibility of fraud.');
      case '202':
        return t('Expired card.<br /><b>Possible action:</b> Request a different card or other form of payment.');
      case '203':
        return t('General decline of the card. No other information provided by the issuing bank.<br /><b>Possible action:</b> Request a different card or other form of payment.');
      case '204':
        return t('Insufficient funds in the account.<br /><b>Possible action:</b> Request a different card or other form of payment.');
      case '205':
        return t("Stolen or lost card.<br /><b>Possible action:</b> Review the customer's information and determine if you want to request a different card from the customer.");
      case '207':
        return t('Issuing bank unavailable.<br /><b>Possible action:</b> Wait a few minutes and resend the request.');
      case '208':
        return t('Inactive card or card not authorized for card-not-present transactions.<br /><b>Possible action:</b> Request a different card or other form of payment.');
      case '210':
        return t('The card has reached the credit limit.<br /><b>Possible action:</b> Request a different card or other form of payment.');
      case '211':
        return t('The card verification number is invalid.<br /><b>Possible action:</b> Request a different card or other form of payment.');
      case '220':
        return t("The processor declined the request based on a general issue with the customer's account.<br /><b>Possible action:</b> Request a different form of payment.");
      case '221':
        return t('The customer matched an entry on the processor’s negative file.<br /><b>Possible action:</b> Review the order and contact the payment processor.');
      case '222':
        return t("The customer's bank account is frozen.<br /><b>Possible action:</b> Review the order or request a different form of payment.");
      case '230':
        return t('The authorization request was approved by the issuing bank but declined by CyberSource because it did not pass the card verification number check.<br /><b>Possible action:</b> You can capture the authorization, but consider reviewing the order for the possibility of fraud.');
      case '231':
        return t('Invalid account number.<br /><b>Possible action:</b> Request a different card or other form of payment.');
      case '232':
        return t('The card type is not accepted by the payment processor.<br /><b>Possible action:</b> Request a different card or other form of payment. Also, check with CyberSource Customer Support to make sure that your account is configured correctly.');
      case '233':
        return t('The processor declined the request based on an issue with the request itself.<br /><b>Possible action:</b> Request a different form of payment.');
      case '234':
        return t('There is a problem with your CyberSource merchant configuration.<br /><b>Possible action:</b> Do not resend the request. Contact Customer Support to correct the configuration problem.');
      case '236':
        return t('Processor failure.<br /><b>Possible action:</b> Possible action: Wait a few minutes and resend the request.');
      case '240':
        return t('The card type sent is invalid or does not correlate with the credit card number.<br /><b>Possible action:</b> Ask your customer to verify that the card is really the type indicated in your Web store, then resend the request.');
      case '250':
        return t('<b>Error:</b> The request was received, but a time-out occurred with the payment processor.<br /><b>Possible action:</b> To avoid duplicating the transaction, do not resend the request until you have reviewed the transaction status in the Business Center.');
      case '475':
        return t('The customer is enrolled in payer authentication.<br /><b>Possible action:</b> Authenticate the cardholder before continuing with the transaction.');
      case '476':
        return t("The customer cannot be authenticated.<br /><b>Possible action:</b> Review the customer's order.");
      case '520':
        return t('The authorization request was approved by the issuing bank but declined by CyberSource based on your Smart Authorization settings.<br /><b>Possible action:</b> Do not capture the authorization without further review. Review the avsCode, cvResult, and factorCode fields to determine why CyberSource rejected the request.');
    }
  }

  /**
   * Returns the meaning of the code for Address Verification.
   */
  public static function avsResponse($code) {
    switch ($code) {
      case 'A':
        return t('Street address matches, but 5- and 9-digit postal codes do not match.');
      case 'B':
        return t('Street address matches, but postal code not verified. Returned only for non U.S.-issued Visa cards.');
      case 'C':
        return t('Street address and postal code do not match. Returned only for non U.S.-issued Visa cards.');
      case 'D':
        return t('Street address and postal code match. Returned only for non U.S.-issued Visa cards.');
      case 'E':
        return t('AVS data is invalid, or AVS is not allowed for this card type.');
      case 'F':
        return t("Card member's name does not match, but postal code matches. Returned only for the American Express card type.");
      case 'G':
        return t('Non-U.S. issuing bank does not support AVS.');
      case 'H':
        return t("Card member's name does not match. Street address and postal code match. Returned only for the American Express card type.");
      case 'I':
        return t('Address not verified. Returned only for non U.S.-issued Visa cards.');
      case 'K':
        return t("Card member's name matches but billing address and billing postal code do not match. Returned only for the American Express card type.");
      case 'L':
        return t("Card member's name and billing postal code match, but billing address does not match. Returned only for the American Express card type");
      case 'N':
        return t("Street address and postal code do not match. - or - Card member's name, street address and postal code do not match. Returned only for the American Express card type.");
      case 'O':
        return t("Card member's name and billing address match, but billing postal code does not match. Returned only for the American Express card type.");
      case 'P':
        return t('Postal code matches, but street address not verified. Returned only for non-U.S.-issued Visa cards.');
      case 'R':
        return t('System unavailable.');
      case 'S':
        return t('U.S.-issuing bank does not support AVS.');
      case 'T':
        return t("Card member's name does not match, but street address matches. Returned only for the American Express card type.");
      case 'U':
        return t('Address information unavailable. Returned if non-U.S. AVS is not available or if the AVS in a U.S. bank is not functioning properly.');
      case 'W':
        return t('Street address does not match, but 9-digit postal code matches.');
      case 'X':
        return t('Exact match. Street address and 9-digit postal code match.');
      case 'Y':
        return t('Exact match. Street address and 5-digit postal code match.');
      case 'Z':
        return t('Street address does not match, but 5-digit postal code matches.');
      case '1':
        return t('AVS is not supported for this processor or card type.');
      case '2':
        return t('The processor returned an unrecognized value for the AVS response.');
    }
  }

  /**
   * Returns the meaning of the code sent back for CVV verification.
   */
  public static function cvvResponse($code) {
    switch ($code) {
      case 'D':
        return t('Transaction determined suspicious by issuing bank.');
      case 'I':
        return t("Card verification number failed processor's data validation check.");
      case 'M':
        return t('Card verification number matched.');
      case 'N':
        return t('Card verification number not matched.');
      case 'P':
        return t('Card verification number not processed by processor for unspecified reason.');
      case 'S':
        return t('Card verification number is on the card but was not included in the request.');
      case 'U':
        return t('Card verification is not supported by the issuing bank.');
      case 'X':
        return t('Card verification is not supported by the card association.');
      case '1':
        return t('Card verification is not supported for this processor or card type.');
      case '2':
        return t('Unrecognized result code returned by processor for card verification response.');
      case '3':
        return t('No result code returned by processor.');
    }
  }
}
