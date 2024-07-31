<?php
/**
 * This File Contains the Init Class of the Plugin.
 *
 * @package ITFB;
 * @since 2.0.0
 */

namespace ITFB;

use Integration_Toolkit_For_Beehiiv\Import\Import;
use ITFB\ImportCampaigns\Endpoints;

defined( 'ABSPATH' ) || exit;


/**
 * The init class.
 *
 * Handles the settings front-end and back-end functionality of the plugin.
 *
 * @since      2.0.0
 * @package    ITFB
 */
class Init {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    2.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    2.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Initialize the plugin by defining the properties.
	 *
	 * @since     2.0.0
	 * @var mixed $instance The instance of the class.
	 */
	public static $instance = null;


	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    2.0.0
	 * @access   protected
	 */
	public function __construct() {

		$this->version = defined( 'ITFB_VERSION' ) ? ITFB_VERSION : '2.0.0';

		$this->plugin_name = 'integration-toolkit-for-beehiiv';

		$this->load_dependencies();

		$this->define_admin_hooks();

		register_activation_hook( ITFB_FILE, array( $this, 'activation_process' ) );
		add_action( 'init', array( $this, 'check_plugin_version' ) );
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @since    2.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		new AdminMenu( $this->get_plugin_name(), $this->get_version() );

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		new Endpoints();
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    2.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		/**
		 * Hook to load the text domain for translation.
		 *
		 * @since    2.0.0
		 * @return void
		 */
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
	}


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    2.0.0
	 * @return void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'integration-toolkit-for-beehiiv',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}

	/**
	 * Activation Process
	 *
	 * @since    2.0.0
	 * @return void
	 */
	public function activation_process() {
		// Check the installed version.
		$installed_version = get_option( 'itfb_version', '1.0.0' ); // Set default to '1.0.0' if not defined.

		// Compare versions and run the necessary updates.
		if ( version_compare( $installed_version, '2.0.0', '<' ) ) {
			ImportCampaigns\ImportTable::delete_table();
		}
		ImportCampaigns\ImportTable::create_table();
		update_option( 'itfb_version', ITFB_VERSION );
	}

	/**
	 * Check Plugin Version
	 *
	 * @since    2.0.0
	 * @return void
	 */
	public function check_plugin_version() {
		$installed_version = get_option( 'itfb_version', '1.0.0' ); // Set default to '1.0.0' if not defined.

		if ( ITFB_VERSION !== $installed_version ) {
			$this->activation_process();
		}
	}



	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     2.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     2.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Main Integration_Toolkit_For_Beehiiv Instance
	 *
	 * Ensures only one instance of Integration_Toolkit_For_Beehiiv is loaded or can be loaded.
	 *
	 * @since 2.0.0
	 * @return Init
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
