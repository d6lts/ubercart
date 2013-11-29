(function ($) {

/**
 * Sets the behavior to (un)collapse the cart block on a click
 */
Drupal.behaviors.uc_cart_block = {
  attach: function (context) {
    $(context).find('.cart-block-arrow').once('uc_cart_block').click(function() {
      $(context).find('.cart-block-arrow, .cart-block-items').toggleClass('collapsed');
    });
  }
}

})(jQuery);
