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
  function re_beehiiv_start_manual_import() {

    var data = {
      action: 're_beehiiv_start_manual_import',
      nonce: $('#RE_BEEHIIV_ajax_import-nonce').val(),
      content_type: $('#re-beehiiv-content_type').val(),
      post_type: $('#re-beehiiv-post_type').val(),
      taxonomy: $('#re-beehiiv-taxonomy').val(),
      term: $('#re-beehiiv-taxonomy_term').val(),
      post_status: $('#re-beehiiv-post_status').val(),
      update_existing: $('#re-beehiiv-update_existing') ? 'yes' : 'no',
      exclude_draft: $('#re-beehiiv-exclude_draft').is(':checked') ? 'yes' : 'no',
    };

    jQuery.post(RE_BEEHIIV_CORE.ajax_url, data, function (response) {
      
    }, 'json').fail(function (xhr, textStatus, e) {
      console.log(xhr.responseText);
    });
  }

  function re_beehiiv_start_auto_import() {
    var data = {
      action: 're_beehiiv_start_auto_import',
      nonce: $('#RE_BEEHIIV_ajax_import-nonce').val(),
      content_type: $('#re-beehiiv-content_type').val(),
      post_type: $('#re-beehiiv-post_type').val(),
      taxonomy: $('#re-beehiiv-taxonomy').val(),
      term: $('#re-beehiiv-taxonomy_term').val(),
      post_status: $('#re-beehiiv-post_status').val(),
      update_existing: $('#re-beehiiv-update_existing') ? 'yes' : 'no',
      exclude_draft: $('#re-beehiiv-exclude_draft').is(':checked') ? 'yes' : 'no',
      cron_time: $('#re-beehiiv-cron_time').val(),
    };

    jQuery.post(RE_BEEHIIV_CORE.ajax_url, data, function (response) {

    }, 'json').fail(function (xhr, textStatus, e) {
      console.log(xhr.responseText);
    });
  }

  function check_required_fields() {
    let post_type = $('#re-beehiiv-post_type').val();
    let taxonomy = $('#re-beehiiv-taxonomy').val();
    let taxonomy_term = $('#re-beehiiv-taxonomy_term').val();
    let content_type = $('#re-beehiiv-content_type').val();

    let data = [post_type, content_type, taxonomy, taxonomy_term];
    if (data.includes(null) || data.includes(undefined) || data.includes('') || data.includes('0')) {
      alert('Please select all the required fields.');
      return false;
    }

    return true;
  }


  jQuery(document).ready(function ($) {
    $('#re-beehiiv-start-import').on('click', function () {

      if (!check_required_fields()) {
        return false;
      }

      $(this).hide();
      $('.re-beehiiv-import-running').show();
      re_beehiiv_start_manual_import();
      location.reload();
    });

    $('#re-beehiiv-auto-import').on('click', function () {
        
        if (!check_required_fields()) {
          return false;
        }
  
        $(this).hide();
        $('.re-beehiiv-import-running').show();
        re_beehiiv_start_auto_import();
        location.reload();
    })

    $('#re-beehiiv-post_type').on('change', function() {
      let post_type = $(this).val();

      if (post_type == null || post_type == undefined || post_type == '' || post_type == '0') {
        $('#re-beehiiv-taxonomy').html('<option value="0">Select taxonomy</option>');
        $('#re-beehiiv-taxonomy_term').html('<option value="0">Select taxonomy term</option>');
        $('#re-beehiiv-taxonomy').addClass('hidden');
        $('#re-beehiiv-taxonomy_term').parent().addClass('hidden');
        return false;
      }

      let taxonomies = AllTaxonomies[post_type];

      // if taxomies is empty
      if (taxonomies == null || taxonomies == '') {
        // hide the taxonomy and taxonomy term select
        $('#re-beehiiv-taxonomy').addClass('hidden');
        $('#re-beehiiv-taxonomy_term').parent().addClass('hidden');
        return false;
      }

      // show the taxonomy select
      $('#re-beehiiv-taxonomy').removeClass('hidden');
      $('#re-beehiiv-taxonomy_term').removeClass('hidden');

      // populate the taxonomy select
      let html = '<option value="0">Select taxonomy</option>';

      for (let i = 0; i < taxonomies.length; i++) {
        html += '<option value="' + taxonomies[i].name + '">' + taxonomies[i].label + '</option>';
      }

      $('#re-beehiiv-taxonomy').html(html);
    });

    $('#re-beehiiv-taxonomy').on('change', function() {
      let taxonomy = $(this).val();

      if (taxonomy == null || taxonomy == undefined || taxonomy == '' || taxonomy == '0') {
        $('#re-beehiiv-taxonomy_term').html('<option value="0">Select taxonomy term</option>');
        $('#re-beehiiv-taxonomy_term').parent().addClass('hidden');
        return false;
      }
      let taxonomies = AllTaxonomies[$('#re-beehiiv-post_type').val()];

      if (taxonomies == null || taxonomies == '') {
        // hide the taxonomy and taxonomy term select
        $('#re-beehiiv-taxonomy').parent().addClass('hidden');
        $('#re-beehiiv-taxonomy_term').parent().addClass('hidden');
        return false;
      }

      // show the taxonomy select
      $('#re-beehiiv-taxonomy_term').parent().removeClass('hidden');

      // populate the taxonomy select
      let html = '<option value="0">Select taxonomy term</option>';

      UpdateTaxonomyTerms();
    });
  });
})(jQuery);

function toggleSection(el) {
  el.parentNode.classList.toggle('open');
}

function UpdateTaxonomyTerms() {
  jQuery('#re-beehiiv-taxonomy_term').html('<option value="0">Select taxonomy term</option>');

  let post_type = jQuery('#re-beehiiv-post_type').val();
  let taxonomy = jQuery('#re-beehiiv-taxonomy').val();

  if (post_type == null || post_type == undefined || post_type == '' || post_type == '0' || taxonomy == null || taxonomy == undefined || taxonomy == '' || taxonomy == '0') {
    return false;
  }

  let Terms = AllTaxonomyTerms[post_type][taxonomy];

  if (Terms == null || Terms == undefined || Terms == '') {
    return false;
  }

  let html = '<option value="0">Select taxonomy term</option>';

  for (let i = 0; i < Terms.length; i++) {
    html += '<option value="' + Terms[i].term_id + '">' + Terms[i].name + '</option>';
  }

  jQuery('#re-beehiiv-taxonomy_term').html(html);

  return true;
}
