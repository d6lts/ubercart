(function ($, Drupal) {

/**
 * Adds a throbber to the submit order button on the review order form.
 */
Drupal.behaviors.ucCart = {
  attach: function (context) {
    $(context).find('#uc-cart-checkout-review-form #edit-submit').once('uc_cart').click(function() {
      $(this).clone().insertAfter(this).prop('disabled', true).after('<div class="ajax-progress ajax-progress-throbber"><div class="throbber">&nbsp;</div></div>').end().hide();
      $(context).find('#uc-cart-checkout-review-form #edit-back').prop('disabled', true);
    });
  }
}

})(jQuery, Drupal);
