<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://refact.co
 * @since      1.0.0
 *
 * @package    Integration_Toolkit_For_Beehiiv
 * @subpackage Integration_Toolkit_For_Beehiiv/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Integration_Toolkit_For_Beehiiv
 * @subpackage Integration_Toolkit_For_Beehiiv/admin
 * @author     Refact <info@refact.co>
 */

class Integration_Toolkit_For_Beehiiv_Admin {


	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $integration_toolkit_for_beehiiv    The ID of this plugin.
	 */
	private $integration_toolkit_for_beehiiv;

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
	 * @param      string    $integration_toolkit_for_beehiiv       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $integration_toolkit_for_beehiiv, $version ) {

		$this->integration_toolkit_for_beehiiv = $integration_toolkit_for_beehiiv;

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
		 * defined in Integration_Toolkit_For_Beehiiv_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Integration_Toolkit_For_Beehiiv_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->integration_toolkit_for_beehiiv, plugin_dir_url( __FILE__ ) . 'css/integration-toolkit-for-beehiiv-admin.css', array(), $this->version, 'all' );
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
		 * defined in Integration_Toolkit_For_Beehiiv_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Integration_Toolkit_For_Beehiiv_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->integration_toolkit_for_beehiiv, plugin_dir_url( __FILE__ ) . 'js/integration-toolkit-for-beehiiv-admin.js', array( 'jquery' ), $this->version, false );
		// get all taxonomies based on post type
		$post_types = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);
		
		$taxonomies = array();
		foreach ($post_types as $re_post_type) {
			if ($re_post_type->name === 'attachment') {
				continue;
			}
			$post_type_taxonomies = get_object_taxonomies($re_post_type->name, 'objects');

			foreach ($post_type_taxonomies as $re_taxonomy) {
				if ($re_taxonomy->public != 1 || $re_taxonomy->name === 'post_format') { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
					continue;
				}
				$taxonomies[$re_post_type->name][] = array(
					'name'  => $re_taxonomy->name,
					'label' => $re_taxonomy->label,
				);
			}
		}

		$taxonomy_terms = array();
		foreach ($taxonomies as $re_post_type => $re_taxonomy) {
			foreach ($re_taxonomy as $re_tax) {
				$terms = get_terms(
					array(
						'taxonomy'   => $re_tax['name'],
						'hide_empty' => false,
					)
				);
				$taxonomy_terms[$re_post_type][$re_tax['name']] = $terms;
			}
		}

		$wp_post_status = get_post_stati(array('show_in_admin_all_list' => true), 'objects');
		$post_statuses  = array();
		// Filter post statuses.

		foreach ($wp_post_status as $post_status => $post_status_object) {
			if ('future' === $post_status) {
				continue;
			}
			$post_statuses[] = array(
				'name'  => $post_status,
				'label' => $post_status_object->label,
			);
		}

		wp_localize_script(
			$this->integration_toolkit_for_beehiiv,
			'INTEGRATION_TOOLKIT_FOR_BEEHIIV_CORE',
			array(
				'ajax_url'           => admin_url( 'admin-ajax.php' ),
				'progress_bar_nonce' => wp_create_nonce( 'update_progress_bar_nonce' ),
				'strings'            => array(
					'select_taxonomy' => __( 'Select taxonomy', 'integration-toolkit-for-beehiiv' ),
					'select_term'     => __( 'Select taxonomy term', 'integration-toolkit-for-beehiiv' ),
					'labels'          => array(
						'content_type'   => __( 'Content Type', 'integration-toolkit-for-beehiiv' ),
						'beehiiv_status' => __( 'Post status on', 'integration-toolkit-for-beehiiv' ),
						'post_type'      => __( 'Post Type', 'integration-toolkit-for-beehiiv' ),
						'taxonomy'       => __( 'Taxonomy', 'integration-toolkit-for-beehiiv' ),
						'taxonomy_term'  => __( 'Taxonomy Term', 'integration-toolkit-for-beehiiv' ),
						'post_author'    => __( 'Content Author', 'integration-toolkit-for-beehiiv' ),
						'import_method'  => __( 'Import Method', 'integration-toolkit-for-beehiiv' ),
						'post_status'    => __( 'Post Status', 'integration-toolkit-for-beehiiv' ),
					),
					// Translators: {{field_name}} is a required field name and should not be translated.
					'required_fields' => __( '{{field_name}}  is a Required Field', 'integration-toolkit-for-beehiiv' ),
				),
				'AllTaxonomies'          => $taxonomies,
				'AllTaxonomyTerms'       => $taxonomy_terms,
				'AllPostStatuses'        => $post_statuses
			)
		);

		wp_enqueue_script( 'tippy-tooltip1', INTEGRATION_TOOLKIT_FOR_BEEHIIV_URL . 'admin/js/popper.min.js', array(), $this->version, false );

		wp_enqueue_script( 'tippy-tooltip2', INTEGRATION_TOOLKIT_FOR_BEEHIIV_URL . 'admin/js/tippy-bundle.iife.js', array(), $this->version, false );
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
