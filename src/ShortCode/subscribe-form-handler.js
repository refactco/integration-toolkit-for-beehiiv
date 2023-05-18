const validate_email = (email) => {
    return String(email)
    .toLowerCase()
    .match(
        /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
    );
};

var hp_interval;
var hp_ts = 0;
var hp_try = 0;

var activeForm;

function verifyCallback(response) {
    sendRequest(response, 2);
}

function sendRequest(token, version = 3) {

    var form = activeForm;
    var inputs = form.find('input');
    var data = {};

    // Check email before sending
    var email = form.find('input[name="subscribe_form_email"]').val();

    if(email == '' || !validate_email(email)) {
        form.find('.subscribe-form-message').html('Please enter a valid email!');
        return;
    }

    form.addClass('sending');

    form.find('.subscribe-form-message').html('Subscribing, Please wait ...');

    // Add WP action
    data._wpnonce = subscribe_form_handler.wpnonce;
    data.hp_threshold   = subscribe_form_handler.hp_threshold;
    data.hp_ts   = hp_ts;
    data.hp_try  = hp_try;
    data.g_response  = token;
    data.recaptcha_version  = version;

    inputs.each(function () {
        data[this.name] = jQuery(this).val();
    });

    jQuery.ajax({
        url: subscribe_form_handler.ajax_url,
        type: 'post',
        data: data,
        success: function (data, textStatus, request) {
            form.removeClass('sending');

            if (request.status == 429) {
                form.find('.subscribe-form-message').html("Too many requests! Please try again later.");
                return false;
            }
            
            if (data.success) {
                form.addClass('done');
            }

            form.find('.subscribe-form-message').html(data.message);

            if (typeof data.url != 'undefined' && data.url != '') {
                document.location.href = data.url;
            }
        },
        error: function (request, status, error) {

            jQuery('#g-recaptcha-v2').remove();

            var data = jQuery.parseJSON(request.responseText);
            form.find('.subscribe-form-message').html(data.message);

            form.removeClass('sending');
            if (request.status == 429) {
                form.find('.subscribe-form-message').html("Too many requests! Please try again later.");

                return false;
            }


            if (request.status == 406) {
                form.find('.subscribe-form-message').html(data.message);
                form.find('.subscribe-form-message').after('<div id="g-recaptcha-v2" style="margin-top:15px"></div>');
                grecaptcha.render('g-recaptcha-v2', {
                    'sitekey' : subscribe_form_handler.recaptcha_v2_site_key,
                    'callback' : verifyCallback,
                });

                return false;
            }

            if (data.data.suggestion != '' && typeof data.data.suggestion != 'undefined') {
                var email_parts = email.split("@");
                form.find('.subscribe-form-message').append('<br/> <span class="tdu-dym"> Did you mean? <a href="'+email_parts[0]+'@'+data.data.suggestion+'">'+email_parts[0]+'@'+data.data.suggestion+'</a></span>');
            }
            if (typeof data.url != 'undefined' && data.url != '') {
                document.location.href = data.url;
            }
        }
    });
}

jQuery(document).ready(function ($) {

    hp_interval = setInterval(function(){
        hp_ts += 1;
    },1000);

    $(document).delegate('.subscribe-form', 'submit', function(e) {
        e.preventDefault();

        var form = $(this);
        activeForm = form;

        if (hp_ts < subscribe_form_handler.hp_threshold && hp_try < 1) {
            form.find('.subscribe-form-message').html('Too fast! Are you a human? Try again please.');
            hp_ts = 0;
            hp_try += 1;

            return;
        } else {
            clearInterval(hp_interval);
        }

        // protect the submit by g-recaptcha
        grecaptcha.ready(function() {
            grecaptcha.execute(subscribe_form_handler.recaptcha_site_key,
            {action: 'submit'})
            .then(function(token) {
                sendRequest(token, 3);
            });
        });
    });

    $(document).delegate('.tdu-dym a', 'click', function(e) {
        e.preventDefault();
        var t = $(this);
        var p = t.parent().parent();
        var email = t.attr('href');
        $('input[name="subscribe_form_email"]').val(email);
        p.html('');
    });
    
});
