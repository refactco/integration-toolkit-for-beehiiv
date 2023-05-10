<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://refact.co
 * @since      1.0.0
 *
 * @package    Re_Beehiiv
 * @subpackage Re_Beehiiv/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Re_Beehiiv
 * @subpackage Re_Beehiiv/admin
 * @author     Refact <info@refact.co>
 */
class Re_Beehiiv_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $re_beehiiv    The ID of this plugin.
	 */
	private $re_beehiiv;

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
	 * @param      string    $re_beehiiv       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $re_beehiiv, $version ) {

		$this->re_beehiiv = $re_beehiiv;
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
		 * defined in Re_Beehiiv_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Re_Beehiiv_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->re_beehiiv, plugin_dir_url( __FILE__ ) . 'css/re-beehiiv-admin.css', array(), $this->version, 'all' );

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
		 * defined in Re_Beehiiv_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Re_Beehiiv_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->re_beehiiv, plugin_dir_url( __FILE__ ) . 'js/re-beehiiv-admin.js', array( 'jquery' ), $this->version, false );

		wp_localize_script( $this->re_beehiiv, 'SA_CORE', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

	}

	function acf_json_load_point($paths)
	{	
		$paths[] = plugin_dir_path( __FILE__ ) . 'acf-json';

		return $paths;
	}

}