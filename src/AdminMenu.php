<?php
/**
 * The admin menu class
 *
 * @since      2.0.0
 * @package    ITFB
 * @subpackage ITFB/src
 */

namespace ITFB;

defined( 'ABSPATH' ) || exit;


/**
 * The admin menu class
 *
 * @since      2.0.0
 * @package    ITFB
 * @subpackage ITFB/src
 */
class AdminMenu {

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
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    2.0.0
	 * @access   protected
	 */
	public function __construct( $plugin_name, $version ) {

		$this->version = $version;

		$this->plugin_name = $plugin_name;

		// Register the admin menu.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

		// Register the importer.
		add_action( 'admin_init', array( $this, 'register_beehiiv_importer' ) );
		add_action( 'load-importer-integration_toolkit_for_beehiiv', array( $this, 'redirect_importer_to_plugin_settings_page' ) );
	}

	/**
	 * Add admin menu
	 *
	 * @since    2.0.0
	 */
	public function add_admin_menu() {

		$page_hook_suffix = add_options_page(
			__( 'Integration Toolkit for Beehiiv', 'integration-toolkit-for-beehiiv' ),
			__( 'Integration Toolkit for Beehiiv', 'integration-toolkit-for-beehiiv' ),
			'manage_options',
			$this->plugin_name,
			array( $this, 'admin_page_callback' )
		);

		// Register admin assets for this page.
		add_action( "admin_print_scripts-{$page_hook_suffix}", array( $this, 'add_admin_assets' ) );
	}

	/**
	 * Admin menu page callback.
	 *
	 * @since    2.0.0
	 */
	public function admin_page_callback() {
		?>
			<div id="<?php echo esc_attr( $this->plugin_name ); ?>-app">        
			</div>
		<?php
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @since    2.0.0
	 */
	public function add_admin_assets() {
		wp_enqueue_script(
			$this->plugin_name . '-admin-script',
			ITFB_URL . 'build/index.js',
			array( 'wp-api', 'wp-i18n', 'wp-components', 'wp-element', 'wp-api-fetch' ),
			$this->version,
			true
		);

		// Enqueue the React Toastify CSS.
		wp_enqueue_style(
			're-esp-campaign-monitor-toastify',
			ITFB_URL . 'lib/assets/css/reactToastify.css',
			array(),
			'8.0.3'
		);
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
			__( 'Import posts from beehiiv to your site with ease.', 'integration-toolkit-for-beehiiv' ),
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
		wp_redirect( admin_url( 'options-general.php?page=integration-toolkit-for-beehiiv' ) );
	}
}
