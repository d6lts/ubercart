/**
 * @file
 * Utility functions to display settings summaries on vertical tabs.
 */

(function ($) {

Drupal.behaviors.ucProductFieldsetSummaries = {
  attach: function (context) {
    $('fieldset#edit-uc-product', context).drupalSetSummary(function(context) {
      var vals = [];
      $('input:checked', context).next('label').each(function() {
        vals.push(Drupal.checkPlain($(this).text()));
      });
      if (!$('#edit-uc-product-shippable', context).is(':checked')) {
        vals.unshift(Drupal.t('Not shippable'));
      }
      return vals.join(', ');
    });
  }
};

})(jQuery);
