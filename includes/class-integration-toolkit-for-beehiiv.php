<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://refact.co
 * @since      1.0.0
 *
 * @package    Integration_Toolkit_For_Beehiiv
 * @subpackage Integration_Toolkit_For_Beehiiv/includes
 */

use Integration_Toolkit_For_Beehiiv\Import\Queue;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Integration_Toolkit_For_Beehiiv
 * @subpackage Integration_Toolkit_For_Beehiiv/includes
 * @author     Refact <info@refact.co>
 */
class Integration_Toolkit_For_Beehiiv {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Integration_Toolkit_For_Beehiiv_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $integration_toolkit_for_beehiiv    The string used to uniquely identify this plugin.
	 */
	protected $integration_toolkit_for_beehiiv;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'INTEGRATION_TOOLKIT_FOR_BEEHIIV_CORE_VERSION' ) ) {
			$this->version = INTEGRATION_TOOLKIT_FOR_BEEHIIV_CORE_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->integration_toolkit_for_beehiiv = 'integration-toolkit-for-beehiiv';

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->run_import();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Integration_Toolkit_For_Beehiiv_Loader. Orchestrates the hooks of the plugin.
	 * - Integration_Toolkit_For_Beehiiv_Admin. Defines all hooks for the admin area.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-integration-toolkit-for-beehiiv-loader.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-integration-toolkit-for-beehiiv-admin.php';

		if ( ! $this->is_action_scheduler_plugin_active() ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/woocommerce/action-scheduler/action-scheduler.php';
		}

		$this->loader = new Integration_Toolkit_For_Beehiiv_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	public function load_text_domain() {
		load_plugin_textdomain(
			'integration-toolkit-for-beehiiv',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/',
		);
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$this->loader->add_action( 'plugins_loaded', $this, 'load_text_domain' );

		$plugin_admin = new Integration_Toolkit_For_Beehiiv_Admin( $this->get_integration_toolkit_for_beehiiv(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$admin_menus = new \Integration_Toolkit_For_Beehiiv\Admin_Menus();
		$this->loader->add_action( 'admin_menu', $admin_menus, 'register', 10 );
		
		$this->loader->add_action( 'admin_init', $admin_menus, 'register_beehiiv_importer' );
		$this->loader->add_action( 'load-importer-integration_toolkit_for_beehiiv', $admin_menus, 'redirect_importer_to_plugin_settings_page' );

		new \Integration_Toolkit_For_Beehiiv\Import\AJAX\Update_Progress_Bar();
		$forms = new \Integration_Toolkit_For_Beehiiv\Import\Forms();
		$this->loader->add_action( 'admin_post_integration_toolkit_for_beehiiv_manual_import', $forms, 'maybe_start_manual_import' );
		$this->loader->add_action( 'admin_post_integration_toolkit_for_beehiiv_auto_import', $forms, 'maybe_register_auto_import' );

		$canonical_url = new \Integration_Toolkit_For_Beehiiv\Canonical_URL();
		$this->loader->add_action( 'plugins_loaded', $canonical_url, 'register_hook' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$settings = new \Integration_Toolkit_For_Beehiiv\Blocks\Settings();
		$blocks   = new \Integration_Toolkit_For_Beehiiv\Blocks\Blocks();

		$this->loader->add_action( 'rest_api_init', $settings, 'register_rest_routes' );
		$this->loader->add_action( 'init', $blocks, 'register_all_blocks', 10 );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_integration_toolkit_for_beehiiv() {
		return $this->integration_toolkit_for_beehiiv;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Integration_Toolkit_For_Beehiiv_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Check if Action Scheduler plugin is active.
	 *
	 * @return bool
	 */
	public function is_action_scheduler_plugin_active() {
		$active_plugins = get_option( 'active_plugins' );
		return in_array( 'action-scheduler/action-scheduler.php', $active_plugins, true );
	}

	/**
	 * Run the import.
	 */
	public function run_import() {
		$queue = new Queue();
		$queue->queue_handler();
	}

	/**
	 * Check if api key and publication id are set.
	 *
	 * @return bool
	 */
	public static function is_plugin_activated(): bool {
		return ! empty( get_option( 'integration_toolkit_for_beehiiv_publication_id' ) ) && ! empty( get_option( 'integration_toolkit_for_beehiiv_api_key' ) );
	}
}
