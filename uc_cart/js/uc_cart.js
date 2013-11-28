/**
 * @file
 * Adds effects and behaviors to elements on the checkout page.
 */

Drupal.behaviors.ucCart = {
  attach: function(context, settings) {
    // Add a throbber to the submit order button on the review order form.
    jQuery('form#uc-cart-checkout-review-form input#edit-submit:not(.ucSubmitOrderThrobber-processed)', context).addClass('ucSubmitOrderThrobber-processed').click(function() {
      jQuery(this).clone().insertAfter(this).prop('disabled', true).after('<div class="ajax-progress ajax-progress-throbber"><div class="throbber">&nbsp;</div></div>').end().hide();
      jQuery('#uc-cart-checkout-review-form #edit-back').prop('disabled', true);
    });
  }
}
