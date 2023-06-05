<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

namespace Re_Beehiiv;

/**
 * This class is responsible for registering and loading the admin menus
 */
class Admin_Menus
{

	/**
	 * Register the admin menus
	 *
	 * @return void
	 */
	public function register() {

		add_menu_page(
			'Re Beehiiv',
			'Re Beehiiv',
			'manage_options',
			're-beehiiv-import',
			array( $this, 'load_page_import' ),
			'dashicons-admin-generic',
			75
		);
    
    	add_submenu_page(
			're-beehiiv',
			'Re Beehiiv - Settings',
			'Settings',
			'manage_options',
			're-beehiiv-settings',
			[$this, 'add_settings_page']
		);
	}
  
  /**
	 * Load the import page
	 *
	 * @return void
	 */
	public function load_page_import() {
		$this->add_notice_when_not_activated();
		if ( \Re_Beehiiv::is_plugin_activated() ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/re-beehiiv-admin-import.php';
		}
	}

	/**
	 * Add a notice when the plugin is not activated
	 *
	 * @return void
	 */
	private function add_notice_when_not_activated() {
		if ( ! \Re_Beehiiv::is_plugin_activated() ) {
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php esc_html_e( 'Re Beehiiv is not activated. Please activate the plugin first.', 're-beehiiv' ); ?></p>
			</div>
			<?php
		}
	}


	public function add_settings_page()
	{
		require_once RE_BEEHIIV_PATH . 'admin/partials/re-beehiiv-admin-settings.php';
	}
        


}
