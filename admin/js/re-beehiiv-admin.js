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
      location.reload();
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
        return false;
      }

      let taxonomies = AllTaxonomies[post_type];

      // if taxonomies is empty
      if (taxonomies == null || taxonomies == "") {
        // hide the taxonomy and taxonomy term select
        $("#re-beehiiv-taxonomy").addClass("hidden");
        $("#re-beehiiv-taxonomy_term").addClass("hidden");
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

    // Progress bar
    const bar = document.querySelector(".bar");
    if (bar) {
      setInterval(function () {
        update_progress_bar();
      } , 5000);
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

function check_required_fields() {
  $list_of_required_fields = [
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
      id: "re-beehiiv-taxonomy",
      name: RE_BEEHIIV_CORE.strings.labels.taxonomy,
      type: "select",
    },
    {
      id: "re-beehiiv-taxonomy_term",
      name: RE_BEEHIIV_CORE.strings.labels.taxonomy_term,
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
      id: "re-beehiiv-post_status",
      name: RE_BEEHIIV_CORE.strings.labels.post_status,
      type: "radio",
    },
  ];

  $notice_list = jQuery("#re-beehiiv-import--notices");
  $has_error = false;
  $notice_list.find("ul").html("");

  $list_of_required_fields.forEach(function (field) {
    if (!validateInput(field.id, field.type)) {
      $has_error = true;
      $notice_list
        .find("ul")
        .append("<li>" + RE_BEEHIIV_CORE.strings.required_fields.replace("{{field_name}}", field.name) + "</li>");
    }
  });

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
  switch ($input_type) {
    case "select":
      let $input = jQuery(`#${$input_name}`);
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

// progress bar update
function update_progress_bar() {
  // ajax call to get the progress
  jQuery.ajax({
    url: RE_BEEHIIV_CORE.ajax_url,
    type: "POST",
    data: {
      action: "re_beehiiv_progress_bar_data",
      nonce: RE_BEEHIIV_CORE.progress_bar_nonce,
    },
    success: function (response) {
      const percentageTag = document.querySelector(".percentage");
      const totalTag = document.querySelector("#total_count");
      const solvedTag = document.querySelector("#imported_count");
      const bar = document.querySelector(".bar");

      let total = response.all;
      let solved = response.complete + response.failed;

      if (total === -1) {
        // no post to import
        // refresh the page
        // add a query string to the url then refresh to new url

        let url = new URL(window.location.href);
        url.searchParams.set("notice", "nothing_to_import");
        
        location.href = url.href;
      }

      const ruleOfThree = (num1, num2) => {
        const proportion = (num2 * 100) / num1;
        return Math.round(proportion * 10) / 10;
      };

      const updateBarLength = () => {
        const percentage = ruleOfThree(total, solved);
        bar.style.width = percentage + "%";
      };

      const updateText = () => {
        solvedTag.textContent = solved;
        percentageTag.textContent = ruleOfThree(total, solved) + "%";
      };

      totalTag.textContent = total;
      solvedTag.textContent = solved;
      percentageTag.textContent = ruleOfThree(total, solved) + "%";

      update_logs_box(response.logs);

      console.log(response);

      updateBarLength();
      updateText();

      if (total !== 0 && total === solved) {
        // refresh the page
        location.reload();
      }
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