<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

namespace WP_to_Beehiiv_Integration;

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
			__( 'WP to Beehiiv Integration', 'wp-to-beehiiv-integration' ),
			__( 'WP to Beehiiv Integration', 'wp-to-beehiiv-integration' ),
			'manage_options',
			'wp-to-beehiiv-integration-import',
			array( $this, 'load_page_import' ),
			'dashicons-welcome-write-blog',
			75
		);

		// add submenu page

		add_submenu_page(
			'wp-to-beehiiv-integration-import',
			__( 'WP to Beehiiv Integration - Import', 'wp-to-beehiiv-integration' ),
			__( 'Import Content', 'wp-to-beehiiv-integration' ),
			'manage_options',
			'wp-to-beehiiv-integration-import',
			array( $this, 'load_page_import' )
		);

		add_submenu_page(
			'wp-to-beehiiv-integration-import',
			__( 'WP to Beehiiv Integration - Import', 'wp-to-beehiiv-integration' ),
			'Settings',
			'manage_options',
			'wp-to-beehiiv-integration-settings',
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
		if ( \WP_to_Beehiiv_Integration::is_plugin_activated() ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/wp-to-beehiiv-integration-admin-import.php';
		}
	}

	/**
	 * Add a notice when the plugin is not activated
	 *
	 * @return void
	 */
	private function add_notice_when_not_activated() {
		if ( ! \WP_to_Beehiiv_Integration::is_plugin_activated() ) {
			?>
			<div class="notice notice-error is-dismissible">
				<p>
				<?php 
					$message = esc_html__( 'API Key or publication ID is not set. Please set it on the ', 'wp-to-beehiiv-integration' );
					$settings_url = esc_url( home_url( '/wp-admin/admin.php?page=wp-to-beehiiv-integration-settings' ) );

					echo "<p>{$message}<a href='{$settings_url}'>settings page.</a></p>";
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
		require_once WP_TO_BEEHIIV_INTEGRATIONPATH . 'admin/partials/wp-to-beehiiv-integration-admin-settings.php';
	}
}
