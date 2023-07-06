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
			add_filter( 'gform_form_settings', array( self::class, 'add_beehiiv_to_gf_setting' ), 10, 2 );
			add_filter( 'gform_pre_form_settings_save', array( self::class, 'save_beehiiv_form_setting' ), 10, 1 );

			add_action( 'gform_field_standard_settings', array( self::class, 'add_mapping_setting_to_gf_fields' ), 10, 2 );
			add_action( 'gform_editor_js', array( self::class, 'gf_editor_script' ) );
			add_filter( 'gform_tooltips', array( self::class, 'gf_add_encryption_tooltips' ) );

			add_action( 'gform_after_submission', array( self::class, 'sync_to_beehiiv' ), 10, 2 );
        }
    }

   
	/**
	 * Adds Beehiiv Integration settings to the Gravity Forms settings array
	 *
	 * @param array $settings The Gravity Forms settings array.
	 * @param array $form The current form being edited.
	 * @return array The updated Gravity Forms settings array.
	 */
	public static function add_beehiiv_to_gf_setting( $settings, $form ) {
		$is_checked = isset( $form['_gform_setting_enable_beehiiv_sync'] ) && 'enable' === $form['_gform_setting_enable_beehiiv_sync'] ? 'checked' : '';

		$settings[ __( 'Beehiiv Integration', 're-beehiiv' ) ]['custom_setting'] = '
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
            </td>
        </tr>
    ';
		return $settings;
	}

	/**
	 * Filter the form object before the form is saved.
	 *
	 * @param array $form The form object about to be saved.
	 * @return array $form The form object to be saved.
	 */
	public static function save_beehiiv_form_setting( $form ) {

		$form['_gform_setting_enable_beehiiv_sync'] = 'on' === rgpost( '_gform_setting_enable_beehiiv_sync' ) ? 'enable' : 'disable';

		return $form;
	}

	/**
	 * Add mapping setting to Gravity Forms form field.
	 *
	 * @param int $position The position of the form field in the admin.
	 */
	public static function add_mapping_setting_to_gf_fields( $position ) {

		if ( 25 === $position ) {
			?>
			<li class="map_setting field_setting">
				<label for="field_map_value" class="section_label">
					<?php esc_html_e( 'Beehiiv Mapping ', 're-beehiiv' ); ?>
					<?php gform_tooltip( 'form_field_map_value' ); ?>
				</label>
				<input type="text" id="field_map_value" name="field_map_value" oninput="SetFieldProperty('mapField', this.value);" />
			</li>
			<?php
		}
	}

	/**
	 * This code adds a map field setting to the field settings for the address field
	 */
	public static function gf_editor_script() {
		?>
		<script type='text/javascript'>
			fieldSettings.text += ', .map_setting';
			jQuery(document).on('gform_load_field_settings', function(event, field, form) {
				jQuery('#field_map_value').prop('value', rgar(field, 'mapField'));
			});
		</script>
		<?php
	}

	/**
	 * Add new tooltips for Beehiiv Integration.
	 *
	 * @param array $tooltips The current tooltips.
	 * @return array
	 */
	public static function gf_add_encryption_tooltips( $tooltips ) {
		$tooltips['form_field_map_value'] = '<strong>' . __( 'Beehiiv Mapping Fields', 're-beehiiv' ) . '</strong><br />' . __( "Determine this field's value map on witch Beehiiv custom field.", 're-beehiiv' );
		return $tooltips;
	}

	/**
	 * Add a notice to admin dashboard
     * 
     * @return void
     */
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

    /**
	 * Sync the entry to Beehiiv.
	 *
	 * @param array $entry The current entry.
	 * @param array $form The current form.
	 */
	public static function sync_to_beehiiv( $entry, $form ) {

        $email_field = self::get_email_field( $entry, $form );


		if ( false === $email_field['has_email_field'] ) {
			return;
		}
        
		$bridge = new ESP_Bridge(
			'MarkItDone\ESP\Src\Services\\Beehiiv',
			array(
			'apiKey'        => get_option( 'beehiiv_api_key' ),
			'publicationId' => get_option( 'beehiiv_publication_id' ),
			)
		);

		$final_data = array(
			array(
				'type'      => 'custom',
				'esp_field' => 'send_welcome_email',
				'value'     => true,
			),
			array(
				'type'      => 'custom',
				'esp_field' => 'reactivate_existing',
				'value'     => true,
			),
		);

		$mapped_fields = self::map_gravity_form_fields_to_beehiiv_custom_fields( $entry, $form );

		if ( ! empty( $mapped_fields ) ) {

			$esp_special_fields = array(
				'utm_source',
				'utm_medium',
				'utm_campaign',
			);

			foreach ( $mapped_fields as $key => $value ) {
				if ( in_array( $key, $esp_special_fields ) ) {
					$final_data[] = array(
						'type'      => 'custom',
						'esp_field' => $key,
						'value'     => $value,
					);
				} else {
					$final_data[] = array(
						'type'       => 'custom',
						'esp_field'  => 'CustomFields',
						'esp_key'    => $key,
						'value'      => $value,
						'isEmpty'    => 'clear',
					);
				}
			}
		}

		$data = $bridge->getMappedData( '1', $final_data );

		$response = $bridge->createOrUpdateUser(
			array(
				'data'  => $data,
				'Email' => $email_field['email_field'],
			)
		);
	}

	/**
	 * Get the email field.
	 *
	 * @param array $entry The current entry.
	 * @param array $form The current form.
	 * @return array
	 */
	public static function get_email_field( $entry, $form ) {
		$result = array(
			'has_email_field' => false,
		);
		foreach ( $form['fields'] as $field ) {
			if ( 'email' === $field['type'] ) {
				$email_value = $entry[ $field['id'] ];
				if ( is_email( $email_value ) ) {
					$result['has_email_field'] = true;
					$result['email_field']     = sanitize_email( $email_value );
				}
				break;
			}
		}
		return $result;
	}

	/**
	 * Map Gravity Form fields to Beehiiv custom fields.
	 *
	 * @param array $entry The current entry.
	 * @param array $form The current form.
	 * @return array
	 */
	public static function map_gravity_form_fields_to_beehiiv_custom_fields( $entry, $form ) {
		$result = array();
		foreach ( $form['fields'] as $field ) {
			if ( isset( $field['mapField'] ) && '' !== $field['mapField'] ) {
				$result[ $field['mapField'] ] = $entry[ $field['id'] ];
			}
		}
		return $result;
	}
}
