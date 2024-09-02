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
use ITFB\ImportCampaigns\Helper;

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
	 * The table name.
	 *
	 * @var string $table_name
	 */
	const TABLE_NAME = 'integration_toolkit_for_beehiiv_import';


	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    2.0.0
	 * @access   protected
	 */
	public function __construct() {

		$this->version = defined( 'ITFB_VERSION' ) ? ITFB_VERSION : '1.0.0';

		$this->plugin_name = 'integration-toolkit-for-beehiiv';

		$this->load_dependencies();

		$this->define_admin_hooks();

		// Register activation and deactivation hooks.
		register_activation_hook( ITFB_FILE, array( $this, 'activation_process' ) );
		register_deactivation_hook( ITFB_FILE, array( $this, 'deactivation_process' ) );

		// Check the plugin version.
		add_action( 'init', array( $this, 'old_versions_compatibility' ) );
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
		ImportCampaigns\ImportTable::create_table();
	}

	/**
	 * Deactivation Process
	 *
	 * @since    2.0.0
	 * @return void
	 */
	public function deactivation_process() {
		delete_option( 'itfb_db_compatibility' );
		delete_option( 'itfb_schedule_compatibility' );
	}

	/**
	 * Check Plugin Version
	 *
	 * @since    2.0.0
	 * @return void
	 */
	public function old_versions_compatibility() {

		$db_compatibility       = get_option( 'itfb_db_compatibility' );
		$schedule_compatibility = get_option( 'itfb_schedule_compatibility' );

		if ( ! in_array( $db_compatibility, array( 'done', 'not_needed' ) ) ) {
			$this->db_compatibility();
		}

		if ( ! in_array( $schedule_compatibility, array( 'done', 'not_needed' ) ) ) {
			$this->schedule_compatibility();
		}
	}

	/**
	 * Check the database compatibility.
	 *
	 * @since    2.0.0
	 * @return void
	 */
	public function db_compatibility() {
		global $wpdb;

		// Define your table name.
		$table_name = $wpdb->prefix . $this::TABLE_NAME;

		$column_exists = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SHOW COLUMNS FROM $table_name LIKE %s",
				'status'
			)
		);

		if ( ! empty( $column_exists ) ) {
			ImportCampaigns\ImportTable::delete_table();
			ImportCampaigns\ImportTable::create_table();
			update_option( 'itfb_db_compatibility', 'done' );
		} else {
			update_option( 'itfb_db_compatibility', 'not_needed' );
		}
	}

	/**
	 * Check the schedule compatibility.
	 *
	 * @since    2.0.0
	 * @return void
	 */
	public function schedule_compatibility() {
		$params = array(
			'hook'    => 'integration_toolkit_for_beehiiv_bulk_import',
			'status'  => \ActionScheduler_Store::STATUS_PENDING,
			'orderby' => 'date',
			'order'   => 'ASC',
			'group'   => 'auto_recurring_import',
		);

		$old_action_id = \ActionScheduler::store()->query_action( $params );
		if ( $old_action_id ) {
			// get the action by id.
			$action = \ActionScheduler::store()->fetch_action( $old_action_id );

			// get the action args.
			$new_action_args = $this->map_old_schedule_args_to_new_schedule_args( $action->get_args() );
			$action_id       = Helper::schedule_import_campaigns( $new_action_args );
			try {
				\ActionScheduler::store()->cancel_action( $old_action_id );
			} catch ( Exception $exception ) {
				\ActionScheduler::logger()->log(
					$action_id,
					sprintf(
						/* translators: %1$s is the name of the hook to be cancelled, %2$s is the exception message. */
						__( 'Caught exception while cancelling action "%1$s": %2$s', 'integration-toolkit-for-beehiiv' ),
						$hook,
						$exception->getMessage()
					)
				);

				$action_id = null;
			}
			update_option( 'itfb_schedule_compatibility', 'done' );
		} else {
			update_option( 'itfb_schedule_compatibility', 'not_needed' );
		}
	}


	/**
	 * Convert the old schedule to the new schedule.
	 *
	 * @since    2.0.0
	 * @return void
	 */
	public function convert_old_schedule_to_new_schedule() {
		$params = array(
			'hook'    => 'integration_toolkit_for_beehiiv_bulk_import',
			'status'  => \ActionScheduler_Store::STATUS_PENDING,
			'orderby' => 'date',
			'order'   => 'ASC',
			'group'   => 'auto_recurring_import',
		);

		$old_action_id = \ActionScheduler::store()->query_action( $params );
		if ( $old_action_id ) {
			// get the action by id.
			$action = \ActionScheduler::store()->fetch_action( $old_action_id );

			// get the action args.
			$new_action_args = $this->map_old_schedule_args_to_new_schedule_args( $action->get_args() );
			$action_id       = Helper::schedule_import_campaigns( $new_action_args );
			try {
				\ActionScheduler::store()->cancel_action( $old_action_id );
			} catch ( Exception $exception ) {
				\ActionScheduler::logger()->log(
					$action_id,
					sprintf(
						/* translators: %1$s is the name of the hook to be cancelled, %2$s is the exception message. */
						__( 'Caught exception while cancelling action "%1$s": %2$s', 'integration-toolkit-for-beehiiv' ),
						$hook,
						$exception->getMessage()
					)
				);

				$action_id = null;
			}
		}
	}

	/**
	 * Map the old schedule arguments to the new schedule arguments.
	 *
	 * @param array $input_array The input array.
	 *
	 * @return array The mapped array.
	 */
	public function map_old_schedule_args_to_new_schedule_args( $input_array ) {
		// Extract the relevant data from the input array.
		$api_key           = isset( $input_array['args']['api_key'] ) ? $input_array['args']['api_key'] : '';
		$publication_id    = isset( $input_array['args']['publication_id'] ) ? $input_array['args']['publication_id'] : '';
		$post_status       = isset( $input_array['args']['post_status'] ) ? $input_array['args']['post_status'] : array();
		$cron_time         = isset( $input_array['args']['cron_time'] ) ? $input_array['args']['cron_time'] : 24;
		$post_type         = isset( $input_array['args']['post_type'] ) ? $input_array['args']['post_type'] : 'post';
		$taxonomy          = isset( $input_array['args']['taxonomy'] ) ? $input_array['args']['taxonomy'] : 'category';
		$taxonomy_term     = isset( $input_array['args']['taxonomy_term'] ) ? $input_array['args']['taxonomy_term'] : '1';
		$author            = isset( $input_array['args']['post_author'] ) ? $input_array['args']['post_author'] : '2';
		$import_option     = isset( $input_array['args']['import_method'] ) ? $input_array['args']['import_method'] : 'new';
		$import_cm_tags_as = isset( $input_array['args']['import_cm_tags_as'] ) ? $input_array['args']['import_cm_tags_as'] : 'post_tag';
		// Determine the audience based on content_type.
		$content_type = isset( $input_array['args']['content_type'][0] ) ? $input_array['args']['content_type'][0] : 'free_web_content';
		$audience     = ( 'free_web_content' === $content_type ) ? 'free' : 'paid';
		// Schedule settings (assuming cron_time in hours).
		$schedule_settings = array(
			'enabled'       => 'on',
			'frequency'     => 'hourly',
			'specific_hour' => $cron_time > 0 ? $cron_time : 1,
		);

		if ( 'new_and_update' === $import_option ) {
			$import_option = 'both';
		}

		// Map the data to the desired structure.
		$mapped_array = array(
			'credentials'       => array(
				'api_key'        => $api_key,
				'publication_id' => $publication_id,
			),
			'audience'          => $audience,
			'post_status'       => $post_status,
			'schedule_settings' => $schedule_settings,
			'post_type'         => $post_type,
			'taxonomy'          => $taxonomy,
			'taxonomy_term'     => $taxonomy_term,
			'author'            => $author,
			'import_cm_tags_as' => $import_cm_tags_as,
			'import_option'     => $import_option,
		);
		return $mapped_array;
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
