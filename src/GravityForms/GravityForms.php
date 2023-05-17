<?php

namespace Re_Beehiiv\GravityForms;

use MarkItDone\ESP\Src\ESP_Bridge;


class GravityForms
{

    public static function init()
    {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';

        if (get_option('re_beehiiv_api_key') == '' || get_option('re_beehiiv_publication_id') == '' || !class_exists('MarkItDone\ESP\Src\ESP_Bridge') || !is_plugin_active('action-scheduler/action-scheduler.php')) {
            add_action('admin_notices', [self::class, 'admin_notice']);
        } else {
            add_filter('gform_form_settings', [self::class, 'add_re_beehiiv_form_setting'], 10, 2);
            add_filter('gform_pre_form_settings_save', [self::class, 'save_re_beehiiv_form_setting'], 10, 1);
            add_action('gform_after_submission', [self::class, 'sync_to_beehiiv'], 10, 2);
        }
    }

    public static function admin_notice()
    {
        if (get_option('re_beehiiv_api_key') == '') {
            $message = 'Re/Beehiiv Plugin : API key is missing.';
            $class = 'error';
        } elseif (get_option('re_beehiiv_publication_id') == '') {
            $message = 'Re/Beehiiv Plugin : Publication ID is missing.';
            $class = 'error';
        } elseif (!class_exists('MarkItDone\ESP\Src\ESP_Bridge')) {
            $message = 'ESP Bridge plugin is required to run Re/Beehiiv Plugin.';
            $class = 'error';
        } else if (!is_plugin_active('action-scheduler/action-scheduler.php')) {
            $message = 'Action Scheduler plugin is required to run Re/Beehiiv Plugin.';
            $class = 'error';
        } else {
            return;
        }

        printf('<div class="%1$s"><p>%2$s</p></div>', $class . ' is-dismissible', $message);
    }

    public static function add_re_beehiiv_form_setting($settings, $form)
    {
        $is_checked = isset($form['_gform_setting_enable_beehiiv_sync']) && $form['_gform_setting_enable_beehiiv_sync'] == 'enable' ? 'checked' : '';

        $beehiiv_field_map = isset($form['_gform_setting_beehiiv_field_map']) ? esc_textarea($form['_gform_setting_beehiiv_field_map']) : '';

        $settings[__('Beehiiv Integration', 're-beehiiv')]['custom_setting'] = '
        <tr>
            <td>
                <div class="gform-settings-field gform-settings-field__toggle">
                    <div class="gform-settings-field__header">
                        <label class="gform-settings-label">Enable Beehiiv Integration</label>
                    </div>
                    <span class="gform-settings-input__container">
                        <input type="checkbox" name="_gform_setting_enable_beehiiv_sync" id="_gform_setting_enable_beehiiv_sync" ' . $is_checked . '>
                        <label class="gform-field__toggle-container" for="_gform_setting_enable_beehiiv_sync">
                            <span class="gform-field__toggle-switch"></span>
                        </label>
                    </span>
                </div>
                <div class="gform-settings-field gform-settings-field__text">
                    <div class="gform-settings-field__header">
                        <label class="gform-settings-label" for="beehiiv_field_map">Map Form Fields With Beehive Custom Fields</label>
                        <button onclick="return false;" onkeypress="return false;" class="gf_tooltip tooltip tooltip_form_legacy_markup" aria-label="Separate each mapping into a separate line">
                            <i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i>
                        </button>
                    </div>
                    <span class="gform-settings-input__container">
                        <textarea type="text" name="_gform_setting_beehiiv_field_map"  id="beehiiv_field_map" placeholder="beehive_custom_field : gravity_form_field_id" >' . $beehiiv_field_map . '</textarea>
                    </span>
                </div>
            </td>
        </tr>
    ';
        return $settings;
    }

    public static function save_re_beehiiv_form_setting($form)
    {

        $form['_gform_setting_enable_beehiiv_sync'] = rgpost('_gform_setting_enable_beehiiv_sync') == 'on' ? 'enable' : 'disable';
        $form['_gform_setting_beehiiv_field_map'] = rgpost('_gform_setting_beehiiv_field_map') ?: null;

        return $form;
    }

    public static function sync_to_beehiiv($entry, $form)
    {
        $email_field = self::get_email_field($entry, $form);
        if ($email_field['has_email_field'] == false) {
            return;
        }

        if (isset($form['_gform_setting_enable_beehiiv_sync']) && $form['_gform_setting_enable_beehiiv_sync'] == 'enable') {

            $bridge = new ESP_Bridge('MarkItDone\ESP\Src\Services\Beehiiv', [
                "apiKey"        =>   get_option('re_beehiiv_api_key'),
                "publicationId" =>   get_option('re_beehiiv_publication_id')
            ]);


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

            $mapped_fields = self::map_gravity_form_fields_to_beehiiv_custom_fields($entry, $form);

            if (!empty($mapped_fields)) {

                $beehiiv_special_fields = [
                    'utm_source',
                    'utm_medium',
                    'utm_campaign',
                ];

                foreach ($mapped_fields as $key => $value) {
                    if (in_array($key, $beehiiv_special_fields)) {
                        $final_data[] = [
                            'type'      => 'custom',
                            'esp_field' => $key,
                            'value'     => $value
                        ];
                    } else {
                        $final_data[] = [
                            'type'       =>  'custom',
                            'esp_field'  =>  'CustomFields',
                            'esp_key'    =>  $key,
                            'value'      =>  $value,
                            'isEmpty'    => 'clear'
                        ];
                    }
                }
            }

            $data = $bridge->getMappedData('1', $final_data);

            $response = $bridge->createOrUpdateUser(
                array(
                    'data' => $data,
                    'Email' => $email_field['email_field'],
                )
            );
            $subscriber_id = $response['response']->data->id ?? null;
            // add subscriber id to entry meta
            gform_update_meta($entry['id'], 'beehiiv_subscriber_id', $subscriber_id);
        }
    }

    public static function  get_email_field($entry, $form)
    {
        $result = [
            'has_email_field' => false,
        ];
        foreach ($form['fields'] as $field) {
            if ($field['type'] == 'email') {
                $email_value = $entry[$field['id']];
                if (is_email($email_value)) {
                    $result['has_email_field'] = true;
                    $result['email_field'] = sanitize_email($email_value);
                }
                break;
            }
        }

        return $result;
    }

    public static function map_gravity_form_fields_to_beehiiv_custom_fields($entry, $form)
    {
        $result = [];
        // check if there is a field map
        if (isset($form['_gform_setting_beehiiv_field_map']) && $form['_gform_setting_beehiiv_field_map'] != '') {

            // get the field map
            $field_map = explode("\n", $form['_gform_setting_beehiiv_field_map']);

            // loop through the field map
            foreach ($field_map as $map) {

                // get the field map
                $map = explode(':', $map);

                // check if the field map is valid
                if (count($map) == 2) {

                    // get the beehiiv custom field
                    $beehiiv_custom_field = trim($map[0]);

                    // get the gravity form field id
                    $gravity_form_field_id = trim($map[1]);

                    // check if the beehiiv custom field is valid
                    if ($beehiiv_custom_field != '') {

                        // check if the gravity form field id is valid
                        if ($gravity_form_field_id != '') {

                            // get the gravity form field value
                            $gravity_form_field_value = rgar($entry, $gravity_form_field_id);

                            // check if the gravity form field value is valid
                            if ($gravity_form_field_value != '') {

                                $result[$beehiiv_custom_field] = $gravity_form_field_value;
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }
}
