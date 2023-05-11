<?php
namespace Re_Beehiiv;
/**
 * This class is responsible for registering and loading the admin menus
 * 
 */
class Admin_Menus {

    public function register() {


        add_menu_page(
			'Re Beehiiv',
			'Re Beehiiv',
			'manage_options',
			're-beehiiv',
			[ $this, 'load_page_main' ],
			'dashicons-admin-generic',
			75
		);

		add_submenu_page(
			're-beehiiv',
			'Re Beehiiv - Import',
			'Import',
			'manage_options',
			're-beehiiv-import',
			[ $this, 'load_page_import' ]
		);

    }

    public function load_page_main() {

		if ( isset( $_GET['notice'] ) && $_GET['notice'] === 'not_activated' ) {
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php _e( 'Re Beehiiv is not activated. Please activate the plugin first.', 're-beehiiv' ); ?></p>
			</div>
			<?php
		}

        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/re-beehiiv-admin-display.php';
    }

    public function load_page_import() {
		$this->redirect_when_not_activated();
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/re-beehiiv-admin-import.php';
    }

	private function is_plugin_activated() : bool {
		return get_option( 're_beehiiv_api_status' ) === 'active';
	}

	private function redirect_when_not_activated() {
		if ( ! $this->is_plugin_activated() ) {
			wp_redirect( admin_url( 'admin.php?page=re-beehiiv&notice=not_activated' ) );
			exit;
		}
	}
    
}