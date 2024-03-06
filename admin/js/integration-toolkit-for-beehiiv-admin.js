(function ($) {
  "use strict";
  jQuery(document).ready(function ($) {

    tippy('#step1_content_type', {
      content: "Choose the content subscription level you'd like to import. 'Free' pertains to content available without subscription fees, while 'Premium' is exclusive paid content",
      allowHTML: true,
    });

    tippy('#step1_post_status', {
      content: "Select the visibility status of the posts within. 'Published' posts are live on, 'Archived' posts are stored but not visible to the audience, and 'Draft' posts are unpublished content.",
      allowHTML: true,
    });

    tippy('#step2_post_type', {
      content: "Define how you'd like the imported content to be categorized within your WordPress site. 'Post Type' determines the format of your content, such as a blog post, page, or custom post type. 'Taxonomy' allows you to classify your content into categories and tags for easy searching and organization.",
      allowHTML: true,
    });


    tippy('#step2_post_author', {
      content: "Choose a WordPress user to be designated as the author of the imported content. This user will be credited for the posts and will have edit rights over them.",
      allowHTML: true,
    });

    tippy('#step2_post_tags', {
      content: "Tags help organize and categorize your content. This setting allows you to pull tags associated with your content and assign them to specific taxonomies and terms within WordPress.",
      allowHTML: true,
    });

    tippy('#step2_post_status', {
      content: "Define how the imported content should appear on your WordPress site. For example, whether it should be immediately visible, archived, or saved as a draft.",
      allowHTML: true,
    });
    
    tippy('#step2_import_method', {
      content: "Select how you'd like to handle the incoming content. 'Import new items' will only add new content, 'Update existing items' will overwrite existing content with updates from, and 'Do both' will import new items while updating any matching existing content.",
      allowHTML: true,
    });
    
    tippy('#step2_cron_time', {
      content: "Schedule the automatic importing process by specifying how often the system should check for new content.",
      allowHTML: true,
    });

    $("#integration-toolkit-for-beehiiv-auto-import").on("click", function () {
      if (!check_required_fields()) {
        return false;
      }

      $(this).hide();
      $(".integration-toolkit-for-beehiiv-import-running").show();
      integration_toolkit_for_beehiiv_start_auto_import();
      // location.reload();
    });

    $("#integration-toolkit-for-beehiiv-post_type").on("change", function () {
      let post_type = $(this).val();

      if (
        post_type == null ||
        post_type == undefined ||
        post_type == "" ||
        post_type == "0"
      ) {
        $("#integration-toolkit-for-beehiiv-taxonomy").html(
          `<option value="0">${INTEGRATION_TOOLKIT_FOR_BEEHIIV_CORE.strings.select_taxonomy}</option>`
        );
        $("#integration-toolkit-for-beehiiv-taxonomy_term").html(
          `<option value="0">${INTEGRATION_TOOLKIT_FOR_BEEHIIV_CORE.strings.select_term}</option>`
        );
        $("#integration-toolkit-for-beehiiv-taxonomy").addClass("hidden");
        $("#integration-toolkit-for-beehiiv-taxonomy_term").addClass("hidden");
        $("#integration-toolkit-for-beehiiv-post_tags-taxonomy").html(
          `<option value="0">Select post type first</option>`
        );
        return false;
      }

      let taxonomies = AllTaxonomies[post_type];

      // if taxonomies is empty
      if (taxonomies == null || taxonomies == "") {
        // hide the taxonomy and taxonomy term select
        $("#integration-toolkit-for-beehiiv-taxonomy").addClass("hidden");
        $("#integration-toolkit-for-beehiiv-taxonomy_term").addClass("hidden");
        $("#integration-toolkit-for-beehiiv-post_tags-taxonomy").html(
          `<option value="0">Selected post type has no taxonomies</option>`
        );
        return false;
      }

      // show the taxonomy select
      $("#integration-toolkit-for-beehiiv-taxonomy").removeClass("hidden");

      // populate the taxonomy select
      let html = `<option value="0">${INTEGRATION_TOOLKIT_FOR_BEEHIIV_CORE.strings.select_taxonomy}</option>`;

      for (let i = 0; i < taxonomies.length; i++) {
        html +=
          '<option value="' +
          taxonomies[i].name +
          '">' +
          taxonomies[i].label +
          "</option>";
      }

      $("#integration-toolkit-for-beehiiv-taxonomy").html(html);
      $("#integration-toolkit-for-beehiiv-post_tags-taxonomy").html(html)
    });

    $("#integration-toolkit-for-beehiiv-taxonomy").on("change", function () {
      let taxonomy = $(this).val();

      if (
        taxonomy == null ||
        taxonomy == undefined ||
        taxonomy == "" ||
        taxonomy == "0"
      ) {
        $("#integration-toolkit-for-beehiiv-taxonomy_term").html(
          `<option value="0">${INTEGRATION_TOOLKIT_FOR_BEEHIIV_CORE.strings.select_term}</option>`
        );
        $("#integration-toolkit-for-beehiiv-taxonomy_term").addClass("hidden");
        return false;
      }
      let taxonomies = AllTaxonomies[$("#integration-toolkit-for-beehiiv-post_type").val()];

      if (taxonomies == null || taxonomies == "") {
        // hide the taxonomy and taxonomy term select
        $("#integration-toolkit-for-beehiiv-taxonomy").addClass("hidden");
        $("#integration-toolkit-for-beehiiv-taxonomy_term").addClass("hidden");
        return false;
      }

      // show the taxonomy select
      $("#integration-toolkit-for-beehiiv-taxonomy_term").removeClass("hidden");

      // populate the taxonomy select
      let html = `<option value="0">${INTEGRATION_TOOLKIT_FOR_BEEHIIV_CORE.strings.select_term}</option>`;

      UpdateTaxonomyTerms();
    });

    $("#integration-toolkit-for-beehiiv-import-form").on("submit", function (e) {
      e.preventDefault();

      document.querySelectorAll('.integration-toolkit-for-beehiiv-import-fields--step--title' ).forEach(function(i) {
        i.setAttribute('data-error-count', 0);
      });

      if (!check_required_fields()) {
        return false;
      }

      // If all required fields are filled, form will be submitted
      this.submit();
    });

    $(".integration-toolkit-for-beehiiv-import-fields--step--title").on("click", function () {
      // hide all accordion content
      $(".integration-toolkit-for-beehiiv-import-fields--step-content").hide();
      // remove active class from all accordion
      $(".integration-toolkit-for-beehiiv-import-fields--step").removeClass("active");
      // add active class to current accordion
      $(this).parent().addClass("active");
      // show current accordion content
      $(this).next().show();
    });

    $("#integration-toolkit-for-beehiiv-import--cancel").on("click", function () {
      is_cancelled = true;
    });

    const $cancel_button = $("#integration-toolkit-for-beehiiv-import--cancel");
    if ($cancel_button) {
      $cancel_button.hide();

      // $cancel_button.on("click", function () {
      //   is_cancelled = true;

      //   // send ajax request to cancel import
      //   $.ajax({
      //     url: ajaxurl,
      //     type: "POST",
      //     data: {
      //       action: "integration_toolkit_for_beehiiv_cancel_import",
      //       nonce: INTEGRATION_TOOLKIT_FOR_BEEHIIV_CORE.progress_bar_nonce,
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
      'integration-toolkit-for-beehiiv-post_status--confirmed',
      'integration-toolkit-for-beehiiv-post_status--draft',
      'integration-toolkit-for-beehiiv-post_status--archived',
    ]
    const $beehiiv_status = document.querySelectorAll('input[name="integration-toolkit-for-beehiiv-beehiiv-status[]"]');
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
        
                const parentStep = jQuery(parentFieldset.closest(".integration-toolkit-for-beehiiv-import-fields--step"));
                const parentStepTitle = parentStep.find(".integration-toolkit-for-beehiiv-import-fields--step--title");
        
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


    if (document.getElementsByClassName('integration-toolkit-for-beehiiv-import-fields').length > 0) {
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
                const parentStep = jQuery(parentFieldset.closest(".integration-toolkit-for-beehiiv-import-fields--step"));
                const parentStepTitle = parentStep.find(".integration-toolkit-for-beehiiv-import-fields--step--title");
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

              const parentStep = jQuery(parentFieldset.closest(".integration-toolkit-for-beehiiv-import-fields--step"));
              const parentStepTitle = parentStep.find(".integration-toolkit-for-beehiiv-import-fields--step--title");

              
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
  jQuery("#integration-toolkit-for-beehiiv-taxonomy_term").html(
    `<option value="0">${INTEGRATION_TOOLKIT_FOR_BEEHIIV_CORE.strings.select_term}</option>`
  );

  let post_type = jQuery("#integration-toolkit-for-beehiiv-post_type").val();
  let taxonomy = jQuery("#integration-toolkit-for-beehiiv-taxonomy").val();

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

  let html = `<option value="0">${INTEGRATION_TOOLKIT_FOR_BEEHIIV_CORE.strings.select_term}</option>`;

  for (let i = 0; i < Terms.length; i++) {
    html +=
      '<option value="' + Terms[i].term_id + '">' + Terms[i].name + "</option>";
  }

  jQuery("#integration-toolkit-for-beehiiv-taxonomy_term").html(html);

  return true;
}

const list_of_required_fields = [
  {
    id: "integration-toolkit-for-beehiiv-content_type[]",
    name: INTEGRATION_TOOLKIT_FOR_BEEHIIV_CORE.strings.labels.content_type,
    type: "checkbox",
  },
  {
    id: "integration-toolkit-for-beehiiv-beehiiv-status[]",
    name: INTEGRATION_TOOLKIT_FOR_BEEHIIV_CORE.strings.labels.beehiiv_status,
    type: "checkbox",
  },
  {
    id: "integration-toolkit-for-beehiiv-post_type",
    name: INTEGRATION_TOOLKIT_FOR_BEEHIIV_CORE.strings.labels.post_type,
    type: "select",
  },
  {
    id: "integration-toolkit-for-beehiiv-post_author",
    name: INTEGRATION_TOOLKIT_FOR_BEEHIIV_CORE.strings.labels.post_author,
    type: "select",
  },
  {
    id: "integration-toolkit-for-beehiiv-import_method",
    name: INTEGRATION_TOOLKIT_FOR_BEEHIIV_CORE.strings.labels.import_method,
    type: "radio",
  },
  {
    id: "integration-toolkit-for-beehiiv-taxonomy",
    name: INTEGRATION_TOOLKIT_FOR_BEEHIIV_CORE.strings.labels.taxonomy,
    type: "select",
  },
  {
    id: "integration-toolkit-for-beehiiv-taxonomy_term",
    name: INTEGRATION_TOOLKIT_FOR_BEEHIIV_CORE.strings.labels.taxonomy_term,
    type: "select",
  },
];

function check_required_fields() {
  $notice_list = jQuery("#integration-toolkit-for-beehiiv-import--notices");
  $has_error = false;
  $notice_list.find("ul").html("");

  let $input, $input_value;
  let $tax_flag = false;
  list_of_required_fields.forEach(function (field) {
    if ( field.id == "integration-toolkit-for-beehiiv-taxonomy_term" ) {
      $input = jQuery(`#integration-toolkit-for-beehiiv-taxonomy`);
      $input_value = $input.val();
      if ( $input_value == null ||
        $input_value == undefined ||
        $input_value == "" ||
        $input_value == "0") {
        $tax_flag = false;
      } else {
        $input = jQuery(`#integration-toolkit-for-beehiiv-taxonomy_term`);
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

      let error_message = INTEGRATION_TOOLKIT_FOR_BEEHIIV_CORE.strings.required_fields.replace("{{field_name}}", field.name);

      $notice_list
        .find("ul")
        .append("<li>" + error_message + "</li>");

      const input = ( field.type == "select" ) ? jQuery(`#${field.id}`) : jQuery(`input[name='${field.id}']`);
      const parentFieldset = input.closest("fieldset");
      const parentStep = parentFieldset.closest(".integration-toolkit-for-beehiiv-import-fields--step");
      const parentStepTitle = parentStep.find(".integration-toolkit-for-beehiiv-import-fields--step--title");

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
      .append("<li>" + 'Select Post Status for each beehiiv status' + "</li>");
  }

  if ($has_error) {
    $notice_list
      .children(".integration-toolkit-for-beehiiv-import--notice-error")
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
      .children(".integration-toolkit-for-beehiiv-import--notice-error")
      .addClass("hidden");
  }

  return true;
}

function validateInput($input_name, $input_type) {
  let $input, $input_value;

  if ($input_name == 'integration-toolkit-for-beehiiv-taxonomy' || $input_name == 'integration-toolkit-for-beehiiv-taxonomy_term') {
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
    url: INTEGRATION_TOOLKIT_FOR_BEEHIIV_CORE.ajax_url,
    type: "POST",
    timeout: 30000,
    data: {
      action: "update_progress_bar",
      nonce: INTEGRATION_TOOLKIT_FOR_BEEHIIV_CORE.progress_bar_nonce,
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
          jQuery("#integration-toolkit-for-beehiiv-import--cancel").show();
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

      update_logs_box(response.logs,percentage);

      updateBarLength(percentage);

      if (total !== 0 && total === solved) {
        jQuery("#integration-toolkit-for-beehiiv-import--cancel").hide();
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

function update_logs_box( logs,percentage) {
  const resultBox = jQuery(".result-log");
  const logBox = jQuery(".result-log #log");
  logBox.html("");
  let process_compellation_time;
  logs.forEach((log) => {
    let status = log.status.charAt(0).toUpperCase() + log.status.slice(1);
    let time = log.time.split(" ")[1];
    logBox.append(`<div class="log-item"><span class="log-item__time">[${time}] </span><span class="log-item__status log-item__status--${log.status}">${status}</span> <span class="log-item__message">${log.message}</span></div>`);
    if(percentage === 100){
      process_compellation_time=time;
    }
  });

  if(percentage === 100){
    logBox.append(`<div class="log-item"><span class="log-item__time">[${process_compellation_time}] </span><span class="log-item__status log-item__status--success">All posts imported successfully.</span></div>`);
  }
  

  resultBox.scrollTop(logBox.height());

  resultBox.removeClass("hidden");
  jQuery(".result-log--title").removeClass("hidden");
}

function trigger_update_post_statuses() {
  const $wrapper = jQuery(".integration-toolkit-for-beehiiv-post_status--fields");
  const beehiiv_status = [];

  jQuery(`input[name='integration-toolkit-for-beehiiv-beehiiv-status[]']:checked`).each(function () {
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
      <div class="integration-toolkit-for-beehiiv-post_status--field mb-2">
        <select name="integration-toolkit-for-beehiiv-post_status--${status}" id="integration-toolkit-for-beehiiv-post_status--${status}">
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
  const $wrapper = jQuery(".integration-toolkit-for-beehiiv-post_status--fields");
  const beehiiv_status = [];

  jQuery(`input[name='integration-toolkit-for-beehiiv-beehiiv-status[]']:checked`).each(function () {
    beehiiv_status.push(jQuery(this).val());
  });

  if (!beehiiv_status.length > 0) {
    return false;
  }

  let isValid = true;

  beehiiv_status.forEach((status) => {
    const $select = jQuery(`#integration-toolkit-for-beehiiv-post_status--${status}`);

    if ($select.val() == "0") {
      isValid = false;
      const input = $select[0];
      const parentFieldset = jQuery(input.closest("fieldset"));
      const parentStep = jQuery(parentFieldset.closest(".integration-toolkit-for-beehiiv-import-fields--step"));
      const parentStepTitle = parentStep.find(".integration-toolkit-for-beehiiv-import-fields--step--title");

      parentStep.addClass("has-error");

      let countOfErrorsInStep = parentStepTitle.attr("data-error-count");
      countOfErrorsInStep = parseInt(countOfErrorsInStep) + 1;
      parentStepTitle.attr("data-error-count", countOfErrorsInStep);

      parentFieldset.addClass("has-error");
    }
  });

  return isValid;
}