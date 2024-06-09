<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

namespace Integration_Toolkit_For_Beehiiv;

/**
 * This class is responsible for registering and loading the admin menus
 */
class Admin_Menus {


	/**
	 * Register the admin menus
	 *
	 * @return void
	 */
	public function register() {
		add_menu_page(
			__( 'Integration Toolkit for beehiiv', 'integration-toolkit-for-beehiiv' ),
			__( 'Integration Toolkit for beehiiv', 'integration-toolkit-for-beehiiv' ),
			'manage_options',
			'integration-toolkit-for-beehiiv-import',
			array( $this, 'load_page_import' ),
			'dashicons-welcome-write-blog',
			75
		);

		// add submenu page

		add_submenu_page(
			'integration-toolkit-for-beehiiv-import',
			__( 'Integration Toolkit for beehiiv - Import', 'integration-toolkit-for-beehiiv' ),
			__( 'Import Content', 'integration-toolkit-for-beehiiv' ),
			'manage_options',
			'integration-toolkit-for-beehiiv-import',
			array( $this, 'load_page_import' )
		);

		add_submenu_page(
			'integration-toolkit-for-beehiiv-import',
			__( 'Integration Toolkit for beehiiv - Import', 'integration-toolkit-for-beehiiv' ),
			'Settings',
			'manage_options',
			'integration-toolkit-for-beehiiv-settings',
			array( $this, 'add_settings_page' )
		);
	}

	/**
	 * Load the import page
	 *
	 * @return void
	 */
	public function load_page_import() {
		$this->add_notice_when_not_activated();
		if ( \Integration_Toolkit_For_Beehiiv::is_plugin_activated() ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/integration-toolkit-for-beehiiv-admin-import.php';
		}
	}

	/**
	 * Add a notice when the plugin is not activated
	 *
	 * @return void
	 */
	private function add_notice_when_not_activated() {
		if ( ! \Integration_Toolkit_For_Beehiiv::is_plugin_activated() ) {
			?>
			<div class="notice notice-error is-dismissible">
				<p>
				<?php
					echo '<p>' . esc_html__( ' API Key or publication ID is not set. Please set it on the ', 'integration-toolkit-for-beehiiv' ) . '<a href="' . esc_url( home_url( '/wp-admin/admin.php?page=integration-toolkit-for-beehiiv-settings' ) ) . '">settings page.</a></p>';
				?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Load the settings page
	 *
	 * @return void
	 */
	public function add_settings_page() {
		require_once INTEGRATION_TOOLKIT_FOR_BEEHIIV_PATH . 'admin/partials/integration-toolkit-for-beehiiv-admin-settings.php';
	}


	/**
	 * Register the importer
	 * 
	 * @return void
	 */
	public function register_beehiiv_importer() {
		register_importer(
			'integration_toolkit_for_beehiiv',
			__( 'Integration Toolkit for beehiiv', 'integration-toolkit-for-beehiiv' ),
			__( 'Import content to WordPress using "Integration Toolkit For" ', 'integration-toolkit-for-beehiiv' ),
			array( $this, 'beehiiv_importer_callback' )
		);
	}
	/**
	 * Importer Callback
	 * 
	 * @return void
	 */
	public function beehiiv_importer_callback() {}
	/**
	 * Register the importer.
	 * 
	 * @return void
	 */
	public function redirect_importer_to_plugin_settings_page(){
		wp_redirect( admin_url( 'admin.php?page=integration-toolkit-for-beehiiv-settings' ) );
	}
	
}
