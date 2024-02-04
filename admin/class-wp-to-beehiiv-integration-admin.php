<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://refact.co
 * @since      1.0.0
 *
 * @package    WP_to_Beehiiv_Integration
 * @subpackage WP_to_Beehiiv_Integration/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    WP_to_Beehiiv_Integration
 * @subpackage WP_to_Beehiiv_Integration/admin
 * @author     Refact <info@refact.co>
 */
class WP_to_Beehiiv_Integration_Admin {


	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $wp_to_beehiiv_integration    The ID of this plugin.
	 */
	private $wp_to_beehiiv_integration;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $wp_to_beehiiv_integration       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $wp_to_beehiiv_integration, $version ) {

		$this->wp_to_beehiiv_integration = $wp_to_beehiiv_integration;

		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in WP_to_Beehiiv_Integration_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The WP_to_Beehiiv_Integration_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->wp_to_beehiiv_integration, plugin_dir_url( __FILE__ ) . 'css/wp-to-beehiiv-integration-admin.css', array(), $this->version, 'all' );
		wp_enqueue_script('tippy-tooltip1', 'https://unpkg.com/@popperjs/core@2/dist/umd/popper.min.js', array(), $this->version, false);
		wp_enqueue_script('tippy-tooltip2', 'https://unpkg.com/tippy.js@6/dist/tippy-bundle.umd.js', array(), $this->version, false);
		wp_enqueue_script('font-awesome', 'https://kit.fontawesome.com/57fc5c4e26.js', array(), $this->version, false);
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in WP_to_Beehiiv_Integration_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The WP_to_Beehiiv_Integration_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->wp_to_beehiiv_integration, plugin_dir_url( __FILE__ ) . 'js/wp-to-beehiiv-integration-admin.js', array( 'jquery' ), $this->version, false );

		wp_localize_script(
			$this->wp_to_beehiiv_integration,
			'WP_TO_BEEHIIV_INTEGRATIONCORE',
			array(
				'ajax_url'           => admin_url( 'admin-ajax.php' ),
				'progress_bar_nonce' => wp_create_nonce( 'update_progress_bar_nonce' ),
				'strings'            => array(
					'select_taxonomy' => __( 'Select taxonomy', 'wp-to-beehiiv-integration' ),
					'select_term'     => __( 'Select taxonomy term', 'wp-to-beehiiv-integration' ),
					'labels'          => array(
						'content_type'   => __( 'Content Type', 'wp-to-beehiiv-integration' ),
						'beehiiv_status' => __( 'Post status on Beehiiv', 'wp-to-beehiiv-integration' ),
						'post_type'      => __( 'Post Type', 'wp-to-beehiiv-integration' ),
						'taxonomy'       => __( 'Taxonomy', 'wp-to-beehiiv-integration' ),
						'taxonomy_term'  => __( 'Taxonomy Term', 'wp-to-beehiiv-integration' ),
						'post_author'    => __( 'Content Author', 'wp-to-beehiiv-integration' ),
						'import_method'  => __( 'Import Method', 'wp-to-beehiiv-integration' ),
						'post_status'    => __( 'Post Status', 'wp-to-beehiiv-integration' ),
					),
					// Translators: {{field_name}} is a required field name and should not be translated.
					'required_fields' => __( '{{field_name}}  is a Required Field', 'wp-to-beehiiv-integration' ),
				),
			)
		);
	}

	/**
	 * Setup ACF JSON save point
	 *
	 * @param string $paths
	 */
	public function acf_json_load_point( $paths ) {
		$paths[] = plugin_dir_path( __FILE__ ) . 'acf-json';

		return $paths;
	}

	/**
	 * Setup Admin Menu
	 */
	public function add_admin_menu() {  }
}
