(function ($) {
  "use strict";

  /**
   * All of the code for your admin-facing JavaScript source
   * should reside in this file.
   *
   * Note: It has been assumed you will write jQuery code here, so the
   * $ function reference has been prepared for usage within the scope
   * of this function.
   *
   * This enables you to define handlers, for when the DOM is ready:
   *
   * $(function() {
   *
   * });
   *
   * When the window is loaded:
   *
   * $( window ).load(function() {
   *
   * });
   *
   * ...and/or other possibilities.
   *
   * Ideally, it is not considered best practise to attach more than a
   * single DOM-ready or window-load handler for a particular page.
   * Although scripts in the WordPress core, Plugins and Themes may be
   * practising this, we should strive to set a better example in our own work.
   */
  function wp_faculty_refresh_request() {
    console.log('wp_faculty_refresh_request');
    console.log(SA_CORE);


    var data = {
      action: 're_beehiiv_import',
      nonce: $('#RE_BEEHIIV_ajax_import-nonce').val(),
      content_type: $('#wp-faculty-content_type').val(),
      cat: $('#wp-faculty-category').val(),
    };

    jQuery.post(SA_CORE.ajax_url, data, function (response) {
      var progressbar = $('#wp-faculty-progress').find('.cssProgress-bar');
      var percent = parseInt(response.percent);
      var last_id = parseInt(response.last_id);
      var count = parseInt(response.count);
      var text = '(' + last_id + ' / ' + count + ') ' + percent + '%';
      progressbar.css({ 'width': percent + '%' });
      progressbar.find('.cssProgress-label').text(text);
      if (percent < 100) {
          wp_faculty_refresh_request();
      }
      if (percent == 100) {
          progressbar.removeClass('cssProgress-active');
      }
    });
  }
  jQuery(document).ready(function ($) {
    $('#wp-faculty-start-import').on('click', function () {
        var progressbar = $('#wp-faculty-progress').find('.cssProgress-bar');
        progressbar.addClass('cssProgress-active');
        $(this).hide();
        $('#wp-faculty-pause-import').show();
        wp_faculty_refresh_request();
    });
  });
})(jQuery);
