<?php

namespace Re_Beehiiv\ShortCode;

use MarkItDone\ESP\Src\ESP_Bridge;


class ShortCode
{

    public static function init()
    {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';

        if (get_option('re_beehiiv_api_key') != '' && get_option('re_beehiiv_publication_id') != '' && class_exists('MarkItDone\ESP\Src\ESP_Bridge') && is_plugin_active('action-scheduler/action-scheduler.php')) {
            add_shortcode('re-beehiiv', [__CLASS__, 'render']);
            add_action('rest_api_init', [__CLASS__, 'register_signup_endpoint']);
        }
    }

    public static function render($args)
    {
        if (is_user_logged_in()) return;
        ob_start();
        $args = shortcode_atts(
            [
                'placeholder' => '',
                'button-text' => '',
                'classes' => '',
                'source' => '',
                'medium' => '',
                'honeypot-threshold' => 5,
                'redirect-url' => '',
            ],
            $args,
            're-beehiiv'
        );

        $placeholder     = isset($args['placeholder']) && !empty($args['placeholder']) ? $args['placeholder'] : 'Enter your email';
        $button_text     = isset($args['button-text']) && !empty($args['button-text']) ? $args['button-text'] : 'Subscribe';
        $redirect_url     = isset($args['redirect-url']) ? $args['redirect-url'] : home_url('/thank-you');


        // Main form classes
        $classes = isset($args['classes']) ? ' ' . $args['classes'] : '';
        $honeypot_threshold = isset($args['honeypot-threshold']) ? $args['honeypot-threshold'] : 5;


        // Check URL parameters
        $source = isset($_GET['utm_source']) ? $_GET['utm_source'] : '';
        $medium = isset($_GET['utm_medium']) ? $_GET['utm_medium'] : '';
        // Clean spaces and +
        $source = str_replace([" ", "+"], ["", ""], $source);
        $medium = str_replace([" ", "+"], ["", ""], $medium);

        // Override URL parameters over shortcode attributes
        $source = empty($source) ? $args['source'] : $source;
        $medium = empty($medium) ? $args['medium'] : $medium;

        // Sanitize
        $source = sanitize_text_field($source);
        $medium = sanitize_text_field($medium);

        // Escape HTML to prevent XSS attacks
        $source = esc_html($source);
        $medium = esc_html($medium);

        // escape attributes to prevent HTML, JS errors
        $source = esc_attr($source);
        $medium = esc_attr($medium);


        /**
         * Enqueue script to handle AJAX
         *
         */
        if (!wp_script_is('subscribe_form_handler', 'enqueued')) {

            wp_enqueue_script('subscribe_form_handler', plugin_dir_url(__FILE__) . 'subscribe-form-handler.js', [], '1.5.1', true);

            $options = [
                'wpnonce'       => wp_create_nonce('wp_rest'),
                'ajax_url'       => home_url('/wp-json/ajax/v1/beehiiv_signup'),
                'hp_threshold' => $honeypot_threshold
            ];

            wp_localize_script('subscribe_form_handler', 'subscribe_form_handler', $options);
        }

        $out = '
        <form action="" class="subscribe-form' . $classes . '">
          <input type="text" name="subscribe_form_email" placeholder="' . $placeholder . '">
          <input type="text" name="subscribe_form_name" placeholder="Enter your name" value="" class="c-subscribe-from_field">
          <button class="c-btn" id="subscribe-form-submit" type="submit">' . $button_text . '</button>
          <div class="subscribe-form-message"></div>
            <div class="subscribe-form-vars">
                <input type="hidden" class="subscribe-form-param" name="params[source]" value="' . $source . '">
                <input type="hidden" class="subscribe-form-param" name="params[medium]" value="' . $medium . '">
                <input type="hidden" class="subscribe-form-param" name="params[redirect_url]" value="' . $redirect_url . '">
            </div>
        </form>
        ';

        return $out;
    }

    public static function register_signup_endpoint()
    {
        register_rest_route('ajax/v1', '/beehiiv_signup', [
            'methods'                         => 'POST',
            'callback'                        => [__CLASS__, 'subscribe_form_submit']
        ]);
    }

    public static function subscribe_form_submit($request)
    {


        /**
         * Add honeypot trap with name
         */
        $honeypot_name                   = !empty($request->get_param('subscribe_form_name')) ? sanitize_text_field($request->get_param('subscribe_form_name')) : '';
        $honeypot_time                   = !empty($request->get_param('hp_ts')) ? sanitize_text_field($request->get_param('hp_ts')) : 0;
        $honeypot_try                   = !empty($request->get_param('hp_try')) ? sanitize_text_field($request->get_param('hp_try')) : 0;
        $honeypot_treshold             = !empty($request->get_param('hp_threshold')) ? sanitize_text_field($request->get_param('hp_threshold')) : 5;

        if (!empty($honeypot_name) || ($honeypot_time < $honeypot_treshold && $honeypot_try < 1)) {
            return new \WP_Error(
                "Invalid request",
                "Invalid request.",
                ['status' => 425]
            );
        }

        $email  = sanitize_text_field($request->get_param('subscribe_form_email'));
        $params = $request->get_param('params');
        $source = !empty($params['source']) ? sanitize_text_field($params['source']) : '';
        $medium = !empty($params['medium']) ? sanitize_text_field($params['medium']) : '';

        $redirect_url = isset($params['redirect_url']) && !empty($params['redirect_url']) ? sanitize_url($params['redirect_url']) : '';

        $validation_result = apply_filters('shortcode_email_verification', true, $email);


        if (empty($email)) {

            return new \WP_Error(
                "Required email",
                "You need to enter an email",
                ['status' => 400]
            );
        } else  if (!is_email($email) || (is_array($validation_result) && isset($validation_result['is_valid']) && !$validation_result['is_valid'])) {

            return new \WP_Error(
                "Invalid email",
                "Please enter a valid email",
                [
                    'status' => 400,
                    'suggestion' => isset($validation_result['suggestion']) ? $validation_result['suggestion'] : ''
                ]
            );
        }

        $final_data = [
            [
                'type'      => 'custom',
                'esp_field' => 'send_welcome_email',
                'value'     => true
            ],
            [
                'type'      => 'custom',
                'esp_field' => 'reactivate_existing',
                'value'     => true
            ]
        ];

        if (!empty($source)) {
            $final_data[] = [
                'type'      => 'custom',
                'esp_field' => 'utm_source',
                'value'     => $source
            ];
        }

        if (!empty($medium)) {
            $final_data[] = [
                'type'      => 'custom',
                'esp_field' => 'utm_medium',
                'value'     => $medium
            ];
        }

        $fingerprint_ip = self::get_the_user_ip();
        if ($fingerprint_ip) {
            $final_data[] = [
                'type'       =>  'custom',
                'esp_field'  =>  'CustomFields',
                'esp_key'    =>  'fingerprint_ip',
                'value'      =>  $fingerprint_ip,
                'isEmpty'    => 'clear'
            ];
        }



        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) && !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : false;
        if ($user_agent) {
            $final_data[] = [
                'type'       =>  'custom',
                'esp_field'  =>  'CustomFields',
                'esp_key'    =>  'fingerprint_user_agent',
                'value'      =>  $user_agent,
                'isEmpty'    => 'clear'
            ];
        }


        $final_data = apply_filters('shortcode_beehiiv_subscribe_final_data', $final_data, $final_data);

        $bridge = new ESP_Bridge('MarkItDone\ESP\Src\Services\Beehiiv', [
            "apiKey"        =>   get_option('re_beehiiv_api_key'),
            "publicationId" =>   get_option('re_beehiiv_publication_id')
        ]);

        $data = $bridge->getMappedData('1', $final_data);

        $result = $bridge->createOrUpdateUser(
            array(
                'data' => $data,
                'Email' => $email,
            )
        );

        $response_code = $result['code'];

        if (is_wp_error($result)) {

            return new \WP_Error(
                "Invalid request",
                $result->get_error_message(),
                [
                    'status' => $response_code,
                    'suggestion' => isset($validation_result['suggestion']) ? $validation_result['suggestion'] : ''
                ]
            );
        } else if ($response_code != 200 && $response_code != 201) { // 201 Created

            $response_message = $result['response']->errors[0]->message;

            return new \WP_Error(
                "Invalid request",
                $result['response']->errors[0]->message,
                [
                    'status' => $response_code,
                    'suggestion' => isset($validation_result['suggestion']) ? $validation_result['suggestion'] : ''
                ]
            );
        } else {

            do_action('signup_completed', $email);

            // If everything is successful
            $response = [
                'message' => '<span class="ga-thank-you">Thank you for subscribing.</span>',
                'suggestion' => isset($validation_result['suggestion']) ? $validation_result['suggestion'] : ''
            ];

            if (!empty($redirect_url)) {
                $response['url'] = $redirect_url;
            }

            return $response;
        }
    }


    public static function get_the_user_ip()
    {

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            //check ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            //to check ip is pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }
}
