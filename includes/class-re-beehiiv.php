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
 * @package    Re_Beehiiv
 * @subpackage Re_Beehiiv/includes
 */

use Re_Beehiiv\Import\Queue;

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
 * @package    Re_Beehiiv
 * @subpackage Re_Beehiiv/includes
 * @author     Refact <info@refact.co>
 */
class Re_Beehiiv
{


	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Re_Beehiiv_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $re_beehiiv    The string used to uniquely identify this plugin.
	 */
	protected $re_beehiiv;

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
	public function __construct()
	{
		if (defined('RE_BEEHIIV_CORE_VERSION')) {
			$this->version = RE_BEEHIIV_CORE_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->re_beehiiv = 're-beehiiv';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->run_import();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Re_Beehiiv_Loader. Orchestrates the hooks of the plugin.
	 * - Re_Beehiiv_I18n. Defines internationalization functionality.
	 * - Re_Beehiiv_Admin. Defines all hooks for the admin area.
	 * - Re_Beehiiv_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{
		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-re-beehiiv-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-re-beehiiv-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-re-beehiiv-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-re-beehiiv-public.php';

		if (!$this->is_action_scheduler_plugin_active()) {
			require_once plugin_dir_path(dirname(__FILE__)) . 'vendor/woocommerce/action-scheduler/action-scheduler.php';
		}

		$this->loader = new Re_Beehiiv_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Re_Beehiiv_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale()
	{
		$plugin_i18n = new Re_Beehiiv_I18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{
		$plugin_admin = new Re_Beehiiv_Admin($this->get_re_beehiiv(), $this->get_version());

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

		$admin_menus = new \Re_Beehiiv\Admin_Menus();
		$this->loader->add_action('admin_menu', $admin_menus, 'register', 10);

		$import = new \Re_Beehiiv\Import\Import();
		$this->loader->add_action('admin_notices', $import, 'register_progress_notice');
		$this->loader->add_filter('heartbeat_settings', $import, 'change_heartbeat_while_process_is_running');
		$this->loader->add_action('admin_post_re_beehiiv_manual_import', $import, 'maybe_start_manual_import');
		$this->loader->add_action('admin_post_re_beehiiv_auto_import', $import, 'maybe_register_auto_import');

		$canonical_url = new \Re_Beehiiv\Canonical_URL();
		$this->loader->add_action('plugins_loaded', $canonical_url, 'register_hook');
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks()
	{
		$plugin_public = new Re_Beehiiv_Public($this->get_re_beehiiv(), $this->get_version());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

		$this->loader->add_action('rest_api_init', \Re_Beehiiv\Blocks\Settings::class, 'register_rest_routes');


		$this->loader->add_action('rest_api_init', \Re_Beehiiv\Blocks\Settings::class, 'register_rest_routes');

		/**
		 * Block Hooks
		 */
		$this->loader->add_action('init', \Re_Beehiiv\Blocks\Blocks::class, 'register_all_blocks', 10);
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_re_beehiiv()
	{
		return $this->re_beehiiv;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Re_Beehiiv_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}

	/**
	 * Check if Action Scheduler plugin is active.
	 *
	 * @return bool
	 */
	public function is_action_scheduler_plugin_active()
	{
		$active_plugins = get_option('active_plugins');
		return in_array('action-scheduler/action-scheduler.php', $active_plugins);
	}

	/**
	 * Run the import.
	 */
	public function run_import()
	{
		$queue = new Queue();
		$queue->queue_handler();
	}

	/**
	 * Check if api key and publication id are set.
	 *
	 * @return bool
	 */
	public static function is_plugin_activated(): bool
	{
		return !empty(get_option('re_beehiiv_publication_id')) && !empty(get_option('re_beehiiv_api_key'));
	}
}
