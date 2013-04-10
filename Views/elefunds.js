(function($) {
  $(document).on('elefunds_enabled', function() {
    $('.totalamount > p > strong').addClass('elefunds_line_through');
  });
  $(document).on('elefunds_disabled', function() {
    $('.totalamount > p > strong').removeClass('elefunds_line_through');
  });
})(window.jQuery);

