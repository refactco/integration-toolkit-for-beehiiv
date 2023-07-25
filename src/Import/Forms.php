<?php
namespace Re_Beehiiv\Import;
class Forms {

	const FIELD_PREFIX = 're-beehiiv-';
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


	public function maybe_register_auto_import() {
		if ( ! isset( $_POST['re_beehiiv_import_nonce'] ) || ! wp_verify_nonce( $_POST['re_beehiiv_import_nonce'], 're_beehiiv_import_nonce' ) ) {
			return;
		}

 
		$form_data = $this->get_form_validated_data();

		if ( isset( $form_data['error'] ) ) {
			// show the error message
			add_action(
				're_beehiiv_admin_notices',
				function () use ( $form_data ) {
					?>
				<div class="notice notice-error">
					<p><?php echo esc_html( $form_data['error'] ); ?></p>
				</div>
					<?php
				}
			);
			return;
		}

		$import = new Import( $form_data, 'auto_recurring_import', 'auto' );

		// redirect to import page
		wp_safe_redirect( admin_url( 'admin.php?page=re-beehiiv-import&tab=auto-import' ) );
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
		if ( ! isset( $_POST['re_beehiiv_import_nonce'] ) || ! wp_verify_nonce( $_POST['re_beehiiv_import_nonce'], 're_beehiiv_import_nonce' ) ) {
			return;
		}

		// get the data from the form
		$form_data = $this->get_form_validated_data();

		if ( isset( $form_data['error'] ) ) {
			// show the error message
			add_action(
				're_beehiiv_admin_notices',
				function () use ( $form_data ) {
					?>
				<div class="notice notice-error">
					<p><?php echo esc_html( $form_data['error'] ); ?></p>
				</div>
					<?php
				}
			);
			return;
		}

		$import = new Import( $form_data, 'manual_import_' . time(), 'manual' );

		// redirect to import page
		wp_safe_redirect( admin_url( 'admin.php?page=re-beehiiv-import' ) );
	}

	/**
	 * Validate the form data
	 * Verifying nonce should be done before calling this method
	 *
	 * @return array
	 */
	private function get_form_validated_data() {

		$form_data = array();

		foreach ( self::FIELDS as $field ) {
			$field_name = self::FIELD_PREFIX . $field['name'];

			if ( ! isset( $_POST[ $field_name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				continue;
			}

			if ( $field['required'] && ( ! isset( $_POST[ $field_name ] ) || empty( $_POST[ $field_name ] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing

				return array(
					'error'   => true,
					'message' => sprintf(
						// Translators: %s is a placeholder for the field label. This text is displayed when a required field is left blank.
						__( '%s is required', 're-beehiiv' ),
						$field['label']
					),
				);
			}

			$n = explode( '--', $field['name'] );
			if ( $n[0] === 'post_status' ) {
				$form_data['post_status'][ $n[1] ] = sanitize_text_field( $_POST[ $field_name ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				continue;
			}

			if ( is_array( $_POST[ $field_name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$form_data[ $field['name'] ] = array_map( 'sanitize_text_field', $_POST[ $field_name ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			} else {
				$form_data[ $field['name'] ] = sanitize_text_field( $_POST[ $field_name ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			}
		}

		return $form_data;
	}

}