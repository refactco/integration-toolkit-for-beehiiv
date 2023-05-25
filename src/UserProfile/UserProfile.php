<?php

namespace Re_Beehiiv\UserProfile;


use Re_Beehiiv\API\V2\Routes;
use MarkItDone\ESP\Src\ESP_Bridge;

class UserProfile
{

    public static function init()
    {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';

        if (get_option('re_beehiiv_api_key') != '' && get_option('re_beehiiv_publication_id') != '' && class_exists('MarkItDone\ESP\Src\ESP_Bridge') && is_plugin_active('action-scheduler/action-scheduler.php')) {
            // an option on the user profile to unsubscribe/resubscribe from the newsletter
            add_action('show_user_profile', array(__CLASS__, 'unsubscribe_resubscribe_option'));
            add_action('edit_user_profile', array(__CLASS__, 'unsubscribe_resubscribe_option'));

            add_action('personal_options_update', array(__CLASS__, 'save_unsubscribe_checkbox'));
            add_action('edit_user_profile_update', array(__CLASS__, 'save_unsubscribe_checkbox'));
        }
    }

    public static function unsubscribe_resubscribe_option($user)
    {
        if (current_user_can('edit_user', $user->ID)) {

            $subscription_status = self::subscription_status($user);
            if (!$subscription_status) {
                return;
            }

            $checked = ($subscription_status['status'] == 'subscribe') ? true : false;

?>
            <h3><?php _e('Beehiiv Newsletter', 'beehiiv'); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label for="beehiiv_subscribe_status"><?php _e('Newsletter Status ', 're-beehiiv'); ?></label></th>
                    <td>
                        <input type="radio" name="beehiiv_subscribe_status" id="beehiiv_subscribe_status" value="1" <?php checked(true, $checked); ?> />
                        <span class="description"><?php _e('On', 're-beehiiv'); ?></span>
                        <br />
                        <input type="radio" name="beehiiv_subscribe_status" id="beehiiv_subscribe_status" value="0" <?php checked(false, $checked); ?> />
                        <span class="description"><?php _e('Off', 're-beehiiv'); ?></span>
                    </td>
                </tr>
            </table>
<?php
        }
    }

    public static function save_unsubscribe_checkbox($user_id)
    {
        $user_info = get_userdata($user_id);
        $new_subscribe_status = (isset($_POST['beehiiv_subscribe_status']) && $_POST['beehiiv_subscribe_status'] == 1) ? 'subscribe' : 'unsubscribe';
        $beehiiv_subscription_status = maybe_unserialize(get_user_meta($user_id, 'beehiiv_subscription_status', true));
        $beehive_subscription_id = $beehiiv_subscription_status['id'];
        $current_subscribe_status = $beehiiv_subscription_status['status'];
        if ($new_subscribe_status != $current_subscribe_status) {

            $bridge = new ESP_Bridge('MarkItDone\ESP\Src\Services\Beehiiv', [
                "apiKey"        =>   get_option('re_beehiiv_api_key'),
                "publicationId" =>   get_option('re_beehiiv_publication_id')
            ]);

            apply_filters('re_beehiiv_subscribe_status_change', $user_id, $new_subscribe_status, $beehive_subscription_id);

            if ($new_subscribe_status == 'subscribe') {
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
                    ],
                ];

                $data = $bridge->getMappedData($user_id, $final_data);

                $response = $bridge->createOrUpdateUser(
                    array(
                        'data' => $data,
                        'Email' => $user_info->user_email
                    )
                );

                if (!is_wp_error($response) && in_array($response['code'], array(200, 201))) {
                    update_user_meta($user_id, 'beehiiv_subscription_status', serialize(array('id' => $beehive_subscription_id, 'status' => 'subscribe')));
                }
            } else {
                $final_data = [
                    [
                        'type'      => 'custom',
                        'esp_field' => 'unsubscribe',
                        'value'     => true
                    ]
                ];

                $data = $bridge->getMappedData($user_id, $final_data);

                $response = $bridge->updateUser(
                    array(
                        'data' => $data,
                        'subscriptionId' => $beehive_subscription_id
                    )
                );

                if (!is_wp_error($response) && in_array($response['code'], array(200, 201))) {
                    update_user_meta($user_id, 'beehiiv_subscription_status', serialize(array('id' => $beehive_subscription_id, 'status' => 'unsubscribe')));
                }
            }
        }
    }


    public static function subscription_status($user)
    {
        $beehiiv_subscription_status = get_user_meta($user->ID, 'beehiiv_subscription_status', true);
        $result = false;
        if ($beehiiv_subscription_status) {
            $result =  maybe_unserialize($beehiiv_subscription_status);
        } else {

            $route = Routes::build_route(
                Routes::SUBSCRIPTIONS_INDEX,
                array('publicationId' => get_option('re_beehiiv_publication_id')),
                array(
                    'email' => $user->user_email
                )
            );

            $api_key = get_option('re_beehiiv_api_key');

            $response = \Re_Beehiiv\API\V2\Request_Beehiiv::get($api_key, $route);

            if (!is_wp_error($response)) {
                $data = json_decode(wp_remote_retrieve_body($response), true);
                if (isset($data['data'][0]['id'])) {
                    update_user_meta($user->ID, 'beehiiv_subscription_status', maybe_serialize(array(
                        'id' => $data['data'][0]['id'],
                        'status' => $data['data'][0]['status'] == 'inactive' ? 'unsubscribe' : 'subscribe'
                    )));
                    $beehiiv_subscription_status = get_user_meta($user->ID, 'beehiiv_subscription_status', true);
                    $result = maybe_unserialize($beehiiv_subscription_status);
                }
            }
        }
        return $result;
    }
}
