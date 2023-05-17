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
  function re_beehiiv_refresh_request() {

    var data = {
      action: 're_beehiiv_import',
      nonce: $('#RE_BEEHIIV_ajax_import-nonce').val(),
      content_type: $('#re-beehiiv-content_type').val(),
      cat: $('#re-beehiiv-category').val(),
    };

    jQuery.post(RE_BEEHIIV_CORE.ajax_url, data, function (response) {
      var progressbar = $('#re-beehiiv-progress').find('.cssProgress-bar');
      var percent = parseInt(response.percent);
      var last_id = parseInt(response.last_id);
      var count = parseInt(response.count);
      var text = '(' + last_id + ' / ' + count + ') ' + percent + '%';
      progressbar.css({ 'width': percent + '%' });
      progressbar.find('.cssProgress-label').text(text);
      if (percent < 100) {
        re_beehiiv_refresh_request();
      }
      if (percent == 100) {
        progressbar.removeClass('cssProgress-active');
      }

      console.log(response);

    }, 'json').fail(function (xhr, textStatus, e) {
      console.log(xhr.responseText);
    });
  }
  jQuery(document).ready(function ($) {
    $('#re-beehiiv-start-import').on('click', function () {

      // Validate the form
      var cat = $('#re-beehiiv-category').val();
      if (cat == '' || cat == null || cat == undefined || cat == '0') {
        alert('Please select a category');
        return false;
      }

      var progressbar = $('#re-beehiiv-progress').find('.cssProgress-bar');
      progressbar.addClass('cssProgress-active');
      $(this).hide();
      $('#re-beehiiv-pause-import').show();
      re_beehiiv_refresh_request();
    });
  });
})(jQuery);
