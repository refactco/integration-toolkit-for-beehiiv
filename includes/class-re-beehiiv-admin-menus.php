<?php

/**
 * This class is responsible for registering and loading the admin menus
 * 
 * @since 1.0.0
 * @package Re_Beehiiv
 * @subpackage Re_Beehiiv/includes
 * 
 * @author     Refact <info@refact.co>
 * 
 */
class Re_Beehiiv_Admin_Menus {

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
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/re-beehiiv-admin-display.php';
    }

    public function load_page_import() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/re-beehiiv-admin-import.php';
    }
    
}