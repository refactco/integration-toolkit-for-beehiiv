(function ($) {
  "use strict";
  jQuery(document).ready(function ($) {
    $("#re-beehiiv-auto-import").on("click", function () {
      if (!check_required_fields()) {
        return false;
      }

      $(this).hide();
      $(".re-beehiiv-import-running").show();
      re_beehiiv_start_auto_import();
      // location.reload();
    });

    $("#re-beehiiv-post_type").on("change", function () {
      let post_type = $(this).val();

      if (
        post_type == null ||
        post_type == undefined ||
        post_type == "" ||
        post_type == "0"
      ) {
        $("#re-beehiiv-taxonomy").html(
          `<option value="0">${RE_BEEHIIV_CORE.strings.select_taxonomy}</option>`
        );
        $("#re-beehiiv-taxonomy_term").html(
          `<option value="0">${RE_BEEHIIV_CORE.strings.select_term}</option>`
        );
        $("#re-beehiiv-taxonomy").addClass("hidden");
        $("#re-beehiiv-taxonomy_term").addClass("hidden");
        $("#re-beehiiv-post_tags-taxonomy").html(
          `<option value="0">Select post type first</option>`
        );
        return false;
      }

      let taxonomies = AllTaxonomies[post_type];

      // if taxonomies is empty
      if (taxonomies == null || taxonomies == "") {
        // hide the taxonomy and taxonomy term select
        $("#re-beehiiv-taxonomy").addClass("hidden");
        $("#re-beehiiv-taxonomy_term").addClass("hidden");
        $("#re-beehiiv-post_tags-taxonomy").html(
          `<option value="0">Selected post type has no taxonomies</option>`
        );
        return false;
      }

      // show the taxonomy select
      $("#re-beehiiv-taxonomy").removeClass("hidden");

      // populate the taxonomy select
      let html = `<option value="0">${RE_BEEHIIV_CORE.strings.select_taxonomy}</option>`;

      for (let i = 0; i < taxonomies.length; i++) {
        html +=
          '<option value="' +
          taxonomies[i].name +
          '">' +
          taxonomies[i].label +
          "</option>";
      }

      $("#re-beehiiv-taxonomy").html(html);
      $("#re-beehiiv-post_tags-taxonomy").html(html)
    });

    $("#re-beehiiv-taxonomy").on("change", function () {
      let taxonomy = $(this).val();

      if (
        taxonomy == null ||
        taxonomy == undefined ||
        taxonomy == "" ||
        taxonomy == "0"
      ) {
        $("#re-beehiiv-taxonomy_term").html(
          `<option value="0">${RE_BEEHIIV_CORE.strings.select_term}</option>`
        );
        $("#re-beehiiv-taxonomy_term").addClass("hidden");
        return false;
      }
      let taxonomies = AllTaxonomies[$("#re-beehiiv-post_type").val()];

      if (taxonomies == null || taxonomies == "") {
        // hide the taxonomy and taxonomy term select
        $("#re-beehiiv-taxonomy").addClass("hidden");
        $("#re-beehiiv-taxonomy_term").addClass("hidden");
        return false;
      }

      // show the taxonomy select
      $("#re-beehiiv-taxonomy_term").removeClass("hidden");

      // populate the taxonomy select
      let html = `<option value="0">${RE_BEEHIIV_CORE.strings.select_term}</option>`;

      UpdateTaxonomyTerms();
    });

    $("#re-beehiiv-import-form").on("submit", function (e) {
      e.preventDefault();

      document.querySelectorAll('.re-beehiiv-import-fields--step--title' ).forEach(function(i) {
        i.setAttribute('data-error-count', 0);
      });

      if (!check_required_fields()) {
        return false;
      }

      // If all required fields are filled, form will be submitted
      this.submit();
    });

    $(".re-beehiiv-import-fields--step--title").on("click", function () {
      // hide all accordion content
      $(".re-beehiiv-import-fields--step-content").hide();
      // remove active class from all accordion
      $(".re-beehiiv-import-fields--step").removeClass("active");
      // add active class to current accordion
      $(this).parent().addClass("active");
      // show current accordion content
      $(this).next().show();
    });

    $("#re-beehiiv-import--cancel").on("click", function () {
      is_cancelled = true;
    });

    const $cancel_button = $("#re-beehiiv-import--cancel");
    if ($cancel_button) {
      $cancel_button.hide();

      // $cancel_button.on("click", function () {
      //   is_cancelled = true;

      //   // send ajax request to cancel import
      //   $.ajax({
      //     url: ajaxurl,
      //     type: "POST",
      //     data: {
      //       action: "re_beehiiv_cancel_import",
      //       nonce: RE_BEEHIIV_CORE.progress_bar_nonce,
      //     },
      //     success: function (response) {
      //       if (response.status == 'canceled') {
      //         update_logs_box(response.logs);
      //       }
      //     }
      //   });
      // })
    }

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('cancel')) {
      is_cancelled = true;
    }

    // Progress bar
    const bar = document.querySelector(".bar");
    if (bar && !is_cancelled) {
      update_progress_bar();
    }

    trigger_update_post_statuses();


    const $post_status_selects = [
      're-beehiiv-post_status--confirmed',
      're-beehiiv-post_status--draft',
      're-beehiiv-post_status--archived',
    ]
    const $beehiiv_status = document.querySelectorAll('input[name="re-beehiiv-beehiiv-status[]"]');
    $beehiiv_status.forEach(function(i) {
      i.addEventListener('change', function() {
        trigger_update_post_statuses();
        $post_status_selects.forEach(function(i) {
          const $post_status_select = document.getElementById(i);
          if ($post_status_select) {
            $post_status_select.addEventListener('change', function() {
              const parentFieldset = $post_status_select.closest("fieldset");
              if (parentFieldset && parentFieldset.classList.contains("has-error")) {
                let is_valid = validateInput($post_status_select.id, $post_status_select.type);
                if (!is_valid) {
                  return ;
                }
        
                parentFieldset.classList.remove("has-error");
        
                const parentStep = jQuery(parentFieldset.closest(".re-beehiiv-import-fields--step"));
                const parentStepTitle = parentStep.find(".re-beehiiv-import-fields--step--title");
        
                let countOfErrorsInStep = parentStepTitle.attr("data-error-count");
                countOfErrorsInStep = parseInt(countOfErrorsInStep) - 1;
                parentStepTitle.attr("data-error-count", countOfErrorsInStep);
        
                if (countOfErrorsInStep == 0) {
                  parentStep.removeClass("has-error");
                  parentStepTitle.removeClass("has-error");
                }
              }
            });
          }
        })
      });
    })


    if (document.getElementsByClassName('re-beehiiv-import-fields').length > 0) {
      list_of_required_fields.forEach(function (item) {

        if (item.type == "checkbox" || item.type == "radio") {
          const $checkbox = document.querySelectorAll(
            'input[name="' + item.id + '"]'
          );
          $checkbox.forEach(function (i) {
            i.addEventListener("change", function () {
              const parentFieldset = i.closest("fieldset");
              if (parentFieldset.classList.contains("has-error")) {
                let is_valid = validateInput(item.id, item.type);
                if (!is_valid) {
                  return ;
                }
                parentFieldset.classList.remove("has-error");
                const parentStep = jQuery(parentFieldset.closest(".re-beehiiv-import-fields--step"));
                const parentStepTitle = parentStep.find(".re-beehiiv-import-fields--step--title");
                let countOfErrorsInStep = parentStepTitle.attr("data-error-count");
                countOfErrorsInStep = parseInt(countOfErrorsInStep) - 1;
                parentStepTitle.attr("data-error-count", countOfErrorsInStep);
                
                if (countOfErrorsInStep == 0) {
                  parentStep.removeClass("has-error");
                  parentStepTitle.removeClass("has-error");
                }
              }
            });
          });
        } else if (item.type == "select") {
          const $select = document.getElementById(item.id);
          $select.addEventListener("change", function () {
            const parentFieldset = $select.closest("fieldset");
            if (parentFieldset.classList.contains("has-error")) {
              let is_valid = validateInput(item.id, item.type);
              if (!is_valid) {
                return ;
              }

              parentFieldset.classList.remove("has-error");

              const parentStep = jQuery(parentFieldset.closest(".re-beehiiv-import-fields--step"));
              const parentStepTitle = parentStep.find(".re-beehiiv-import-fields--step--title");

              
              let countOfErrorsInStep = parentStepTitle.attr("data-error-count");
              countOfErrorsInStep = parseInt(countOfErrorsInStep) - 1;
              parentStepTitle.attr("data-error-count", countOfErrorsInStep);
              
              if (countOfErrorsInStep == 0) {
                parentStep.removeClass("has-error");
                parentStepTitle.removeClass("has-error");
              }
            }
          });
        }

      });
    }

  });
})(jQuery);

function UpdateTaxonomyTerms() {
  jQuery("#re-beehiiv-taxonomy_term").html(
    `<option value="0">${RE_BEEHIIV_CORE.strings.select_term}</option>`
  );

  let post_type = jQuery("#re-beehiiv-post_type").val();
  let taxonomy = jQuery("#re-beehiiv-taxonomy").val();

  if (
    post_type == null ||
    post_type == undefined ||
    post_type == "" ||
    post_type == "0" ||
    taxonomy == null ||
    taxonomy == undefined ||
    taxonomy == "" ||
    taxonomy == "0"
  ) {
    return false;
  }

  let Terms = AllTaxonomyTerms[post_type][taxonomy];

  if (Terms == null || Terms == undefined || Terms == "") {
    return false;
  }

  let html = `<option value="0">${RE_BEEHIIV_CORE.strings.select_term}</option>`;

  for (let i = 0; i < Terms.length; i++) {
    html +=
      '<option value="' + Terms[i].term_id + '">' + Terms[i].name + "</option>";
  }

  jQuery("#re-beehiiv-taxonomy_term").html(html);

  return true;
}

const list_of_required_fields = [
  {
    id: "re-beehiiv-content_type[]",
    name: RE_BEEHIIV_CORE.strings.labels.content_type,
    type: "checkbox",
  },
  {
    id: "re-beehiiv-beehiiv-status[]",
    name: RE_BEEHIIV_CORE.strings.labels.beehiiv_status,
    type: "checkbox",
  },
  {
    id: "re-beehiiv-post_type",
    name: RE_BEEHIIV_CORE.strings.labels.post_type,
    type: "select",
  },
  {
    id: "re-beehiiv-post_author",
    name: RE_BEEHIIV_CORE.strings.labels.post_author,
    type: "select",
  },
  {
    id: "re-beehiiv-import_method",
    name: RE_BEEHIIV_CORE.strings.labels.import_method,
    type: "radio",
  },
  {
    id: "re-beehiiv-taxonomy",
    name: RE_BEEHIIV_CORE.strings.labels.taxonomy,
    type: "select",
  },
  {
    id: "re-beehiiv-taxonomy_term",
    name: RE_BEEHIIV_CORE.strings.labels.taxonomy_term,
    type: "select",
  },
];

function check_required_fields() {
  $notice_list = jQuery("#re-beehiiv-import--notices");
  $has_error = false;
  $notice_list.find("ul").html("");

  let $input, $input_value;
  let $tax_flag = false;
  list_of_required_fields.forEach(function (field) {
    if ( field.id == "re-beehiiv-taxonomy_term" ) {
      $input = jQuery(`#re-beehiiv-taxonomy`);
      $input_value = $input.val();
      if ( $input_value == null ||
        $input_value == undefined ||
        $input_value == "" ||
        $input_value == "0") {
        $tax_flag = false;
      } else {
        $input = jQuery(`#re-beehiiv-taxonomy_term`);
        $input_value = $input.val();
        if ( $input_value == null ||
          $input_value == undefined ||
          $input_value == "" ||
          $input_value == "0") {
          $tax_flag = true;
        }
      }
    }

    if ($tax_flag || !validateInput(field.id, field.type)) {
      $has_error = true;

      let error_message = RE_BEEHIIV_CORE.strings.required_fields.replace("{{field_name}}", field.name);

      $notice_list
        .find("ul")
        .append("<li>" + error_message + "</li>");

      const input = ( field.type == "select" ) ? jQuery(`#${field.id}`) : jQuery(`input[name='${field.id}']`);
      const parentFieldset = input.closest("fieldset");
      const parentStep = parentFieldset.closest(".re-beehiiv-import-fields--step");
      const parentStepTitle = parentStep.find(".re-beehiiv-import-fields--step--title");

      parentStep.addClass("has-error");

      let countOfErrorsInStep = parentStepTitle.attr("data-error-count");
      countOfErrorsInStep = parseInt(countOfErrorsInStep) + 1;
      parentStepTitle.attr("data-error-count", countOfErrorsInStep);

      parentFieldset.addClass("has-error");
    }
  });

  if (!validatePostStatuses()) {
    $has_error = true;
    $notice_list
      .find("ul")
      .append("<li>" + 'Select post status for each beehiiv status' + "</li>");
  }

  if ($has_error) {
    $notice_list
      .children(".re-beehiiv-import--notice-error")
      .removeClass("hidden");

    jQuery("html, body").animate(
      {
        scrollTop: $notice_list.offset().top - 100,
      },
      500
    );

    return false;
  } else {
    $notice_list
      .children(".re-beehiiv-import--notice-error")
      .addClass("hidden");
  }

  return true;
}

function validateInput($input_name, $input_type) {
  let $input, $input_value;

  if ($input_name == 're-beehiiv-taxonomy' || $input_name == 're-beehiiv-taxonomy_term') {
    return true;
  }

  switch ($input_type) {      
    case "select":
      $input = jQuery(`#${$input_name}`);
      $input_value = $input.val();
      if (
        $input_value == null ||
        $input_value == undefined ||
        $input_value == "" ||
        $input_value == "0"
      ) {
        return false;
      }
      break;
    case "radio":
    case "checkbox":
      $input_value = jQuery(`input[name='${$input_name}']:checked`).val();
      if (!$input_value) {
        return false;
      }
      break;
    default:
      $input = jQuery(`#${$input_name}`);
      $input_value = $input.val();
      if (
        $input_value == null ||
        $input_value == undefined ||
        $input_value == ""
      ) {
        return false;
      }
      break;
  }

  return true;
}
var is_cancelled = false;
// progress bar update
function update_progress_bar() {
  // ajax call to get the progress
  jQuery.ajax({
    url: RE_BEEHIIV_CORE.ajax_url,
    type: "POST",
    timeout: 30000,
    data: {
      action: "update_progress_bar",
      nonce: RE_BEEHIIV_CORE.progress_bar_nonce,
    },
    success: function (response) {
      if (response === "" || typeof response !== "object") {
        update_progress_bar();
      }

      if (is_cancelled) {
        return;
      }

      const updateBarLength = (percentage = '') => {
        if ( percentage === '') {
          var percentage = ruleOfThree(total, solved);
        }

        if (percentage > 0) {
          jQuery("#re-beehiiv-import--cancel").show();
        }
        percentageTag.textContent = percentage + ' %';
        bar.style.width = percentage + "%";
      };

      const ruleOfThree = (num1, num2) => {
        const proportion = (num2 * 80) / num1;
        let percentage = Math.round(proportion * 10) / 10;
        percentage += 20;

        return percentage;
      };


      const percentageTag = document.querySelector(".percentage");
      const totalTag      = document.querySelector("#total_count");
      const solvedTag     = document.querySelector("#imported_count");
      const bar           = document.querySelector(".bar");

      if (response.data.status === 'getting_data' || response.data.status === 'data_ready') {
        const page        = response.data.page
        const total_pages = response.data.total_pages

        const proportion = (page * 20) / total_pages
        var percentage = Math.round(proportion * 10) / 10;

        update_logs_box(response.logs);
        updateBarLength(percentage);
        setTimeout(update_progress_bar, 2000);
        return;
      }

      let total      = response.data.posts_progress.total_items;
      let left       = response.data.posts_progress.pending_items;
      let solved     = total - left;
      var percentage = ruleOfThree(total, solved)

      if (response.data.status === 'nothing_to_import') {
        let url = new URL(window.location.href);
        url.searchParams.set("notice", "nothing_to_import");
        
        location.href = url.href;
        return;
      } else if (response.data.status === 'stop') {
        return;
      }

      totalTag.textContent      = total;
      solvedTag.textContent     = solved;
      percentageTag.textContent = percentage + ' %';

      update_logs_box(response.logs);

      updateBarLength(percentage);

      if (total !== 0 && total === solved) {
        jQuery("#re-beehiiv-import--cancel").hide();
        return;
      }

      setTimeout(update_progress_bar, 2000);
    },
    error: function (error) {
      console.log(error);
      update_progress_bar();
    },
  });
}

function update_logs_box( logs ) {
  const resultBox = jQuery(".result-log");
  const logBox = jQuery(".result-log #log");
  logBox.html("");

  logs.forEach((log) => {
    let status = log.status.charAt(0).toUpperCase() + log.status.slice(1);
    let time = log.time.split(" ")[1];
    logBox.append(`<div class="log-item"><span class="log-item__time">[${time}] </span><span class="log-item__status log-item__status--${log.status}">${status}</span> <span class="log-item__message">${log.message}</span></div>`);
  });

  resultBox.scrollTop(logBox.height());

  resultBox.removeClass("hidden");
  jQuery(".result-log--title").removeClass("hidden");
}

function trigger_update_post_statuses() {
  const $wrapper = jQuery(".re-beehiiv-post_status--fields");
  const beehiiv_status = [];

  jQuery(`input[name='re-beehiiv-beehiiv-status[]']:checked`).each(function () {
    beehiiv_status.push(jQuery(this).val());
  });

  $wrapper.html("");

  if (!beehiiv_status.length > 0) {
    $wrapper.html("<p>Please select at least one status for selecting beehiiv posts.</p>");
    return;
  }

  // Update $wrapper with the new fields
  beehiiv_status.forEach((status) => {

    // Create the field using AllPostStatuses variable. this variable has multiple status with name and label. add dropdown for each status with AllPostStatuses values
    let label = status.charAt(0).toUpperCase() + status.slice(1);

    if (label === 'Confirmed') {
      label = 'Published'
    }

    // select option if status is equal to 'publish'
    $wrapper.append(`
      <div class="re-beehiiv-post_status--field mb-2">
        <label for="re-beehiiv-post_status--${status}">${label}: </label>
        <select name="re-beehiiv-post_status--${status}" id="re-beehiiv-post_status--${status}">
          <option value="0">Select a status</option>
          ${AllPostStatuses.map((option) => {
            let selected = '';
            if ( status === 'confirmed' ) {
              if ( option.name === 'publish' ) {
                selected = 'selected';
              }
            } else if ( option.name === 'draft' ) {
              selected = 'selected';
            }
            return `<option value="${option.name}" ${selected}>${option.label}</option>`;
          }
        )}
        </select>
      </div>
    `);
  });
}

function validatePostStatuses() {
  const $wrapper = jQuery(".re-beehiiv-post_status--fields");
  const beehiiv_status = [];

  jQuery(`input[name='re-beehiiv-beehiiv-status[]']:checked`).each(function () {
    beehiiv_status.push(jQuery(this).val());
  });

  if (!beehiiv_status.length > 0) {
    return false;
  }

  let isValid = true;

  beehiiv_status.forEach((status) => {
    const $select = jQuery(`#re-beehiiv-post_status--${status}`);

    if ($select.val() == "0") {
      isValid = false;
      const input = $select[0];
      const parentFieldset = jQuery(input.closest("fieldset"));
      const parentStep = jQuery(parentFieldset.closest(".re-beehiiv-import-fields--step"));
      const parentStepTitle = parentStep.find(".re-beehiiv-import-fields--step--title");

      parentStep.addClass("has-error");

      let countOfErrorsInStep = parentStepTitle.attr("data-error-count");
      countOfErrorsInStep = parseInt(countOfErrorsInStep) + 1;
      parentStepTitle.attr("data-error-count", countOfErrorsInStep);

      parentFieldset.addClass("has-error");
    }
  });

  return isValid;
}