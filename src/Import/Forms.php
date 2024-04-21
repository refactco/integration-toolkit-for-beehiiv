<?php
/**
 * This file  is responsible for handling the import form data.
 *
 * @package Integration_Toolkit_For_Beehiiv
 */

namespace Integration_Toolkit_For_Beehiiv\Import;
/**
 * Class Forms
 *
 * @package Integration_Toolkit_For_Beehiiv
 */
class Forms {

	const FIELD_PREFIX = 'integration-toolkit-for-beehiiv-';
	const FIELDS       = array(
		array(
			'name'     => 'content_type',
			'required' => true,
		),
		array(
			'name'     => 'beehiiv-status',
			'required' => true,
		),
		array(
			'name'     => 'post_type',
			'required' => true,
		),
		array(
			'name'     => 'taxonomy',
			'required' => false,
		),
		array(
			'name'     => 'taxonomy_term',
			'required' => false,
		),
		array(
			'name'     => 'post_author',
			'required' => true,
		),
		array(
			'name'     => 'post_tags',
			'required' => true,
		),
		array(
			'name'     => 'import_method',
			'required' => true,
		),
		array(
			'name'     => 'post_status--confirmed',
			'required' => false,
		),
		array(
			'name'     => 'post_status--draft',
			'required' => false,
		),
		array(
			'name'     => 'post_status--archived',
			'required' => false,
		),
		array(
			'name'     => 'cron_time',
			'required' => false,
		),
		array(
			'name'     => 'post_tags-taxonomy',
			'required' => false,
		),
	);


	/**
	 * Register the auto import form.
	 *
	 * @return void
	 */
	public function maybe_register_auto_import() {
		$form_data = $this->get_form_validated_data();

		if ( isset( $form_data['error'] ) ) {
			// show the error message
			add_action(
				'integration_toolkit_for_beehiiv_admin_notices',
				function () use ( $form_data ) {
					?>
				<div class="notice notice-error">
					<p>
						<?php
						printf(
							/* Translators: %s is a placeholder for the error message. This text is displayed when there is an error in the form data. */
							esc_html__( 'Message : %s', 'integration-toolkit-for-beehiiv' ),
							esc_html( $form_data['message'] )
						);
						?>
					</p>
				</div>
					<?php
				}
			);
			return;
		}

		$form_data['api_key']        = get_option( 'integration_toolkit_for_beehiiv_api_key' );
		$form_data['publication_id'] = get_option( 'integration_toolkit_for_beehiiv_publication_id' );
		$form_data                   = array(
			'primary_account' => $form_data,
		);

		/**
		 * Filter the form data before starting the import
		 *
		 * @param array $form_data The form data
		 */
		$form_data = apply_filters( 'integration_toolkit_for_beehiiv_auto_import_form_data', $form_data );
		$import    = new Import( $form_data, 'auto_recurring_import', 'auto' );
		// redirect to import page
		wp_safe_redirect( admin_url( 'admin.php?page=integration-toolkit-for-beehiiv-import&tab=auto-import' ) );
	}

	/**
	 * Maybe start manual import
	 * This method checks if the user has started a manual import and if so, it starts it
	 * It also checks and validates the data from the form
	 * If the data is not valid, it will show an error message
	 *
	 * @return void
	 */
	public function maybe_start_manual_import() {
		// get the data from the form
		$form_data = $this->get_form_validated_data();

		if ( isset( $form_data['error'] ) ) {
			// show the error message
			add_action(
				'integration_toolkit_for_beehiiv_admin_notices',
				function () use ( $form_data ) {
					?>
				<div class="notice notice-error">
					<p>
						<?php
							printf(
								/* Translators: %s is a placeholder for the error message. This text is displayed when there is an error in the form data. */
								esc_html__( 'Message : %s', 'integration-toolkit-for-beehiiv' ),
								esc_html( $form_data['message'] )
							);
						?>
					</p>
				</div>
					<?php
				}
			);
			return;
		}

		$import = new Import( $form_data, 'manual_import_' . time(), 'manual' );

		// redirect to import page
		wp_safe_redirect( admin_url( 'admin.php?page=integration-toolkit-for-beehiiv-import' ) );
	}

	/**
	 * Validate the form data
	 * Verifying nonce should be done before calling this method
	 *
	 * @return array
	 */
	private function get_form_validated_data() {
		if ( isset( $_POST['integration_toolkit_for_beehiiv_import_nonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['integration_toolkit_for_beehiiv_import_nonce'] ) );

			if ( ! wp_verify_nonce( $nonce, 'integration_toolkit_for_beehiiv_action' ) ) {
				return array(
					'error'   => true,
					'message' => esc_html__( 'Invalid nonce', 'integration-toolkit-for-beehiiv' ),
				);
			}

			$form_data = array();

			foreach ( self::FIELDS as $field ) {
				$field_name = self::FIELD_PREFIX . $field['name'];

				if ( ! isset( $_POST[ $field_name ] ) ) {
					continue;
				}

				if ( $field['required'] && ( ! isset( $_POST[ $field_name ] ) || empty( $_POST[ $field_name ] ) ) ) {

					return array(
						'error'   => true,
						'message' => sprintf(
							// Translators: %s is a placeholder for the field label. This text is displayed when a required field is left blank.
							__( '%s is required', 'integration-toolkit-for-beehiiv' ),
							$field['label']
						),
					);
				}

				$n = explode( '--', $field['name'] );
				if ( $n[0] === 'post_status' ) {
					$form_data['post_status'][ $n[1] ] = sanitize_text_field( $_POST[ $field_name ] );
					continue;
				}

				if ( is_array( $_POST[ $field_name ] ) ) {
					$form_data[ $field['name'] ] = array_map( 'sanitize_text_field', $_POST[ $field_name ] );
				} else {
					$form_data[ $field['name'] ] = sanitize_text_field( $_POST[ $field_name ] );
				}
			}

			return $form_data;
		} else {
			return array(
				'error'   => true,
				'message' => esc_html__( 'Invalid nonce', 'integration-toolkit-for-beehiiv' ),
			);
		}
	}
}