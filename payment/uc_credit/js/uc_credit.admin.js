/**
 * @file
 * Utility functions to display settings summaries on vertical tabs.
 */

(function ($) {

  'use strict';

  /**
   * Provide the summary information for the credit card settings vertical tabs.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the credit card settings summaries.
   */
  Drupal.behaviors.creditAdminFieldsetSummaries = {
    attach: function (context) {
      $('details#edit-cc-security', context).drupalSetSummary(function (context) {
        return Drupal.t('Encryption key path') + ': '
          + $('#edit-uc-credit-encryption-path', context).val();
      });
    }
  };

})(jQuery);
