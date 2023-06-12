<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing

namespace Re_Beehiiv\Blocks;

/**
 * Register "Conditional Display" block
 *
 * @package MIDNewsletter
 * @subpackage MIDNewsletterCore
 * @since 1.0.0
 */
class Settings {


	/**
	 * Block ID
	 *
	 * @var string
	 */
	private static $id = 'settings';

	/**
	 * Register block
	 *
	 * @return void
	 */
	public static function register() {

		$editor_js  = RE_BEEHIIV_URL . 'blocks/' . self::$id . '/build/index.js';
		$editor_css = RE_BEEHIIV_URL . 'blocks/' . self::$id . '/build/index.css';
		$css        = RE_BEEHIIV_URL . 'blocks/' . self::$id . '/build/style-index.css';

		$dependencies = include RE_BEEHIIV_PATH . 'blocks/' . self::$id . '/build/index.asset.php';

		wp_register_script( 'refact-newsletter-block-' . self::$id . '-editor-script', $editor_js, $dependencies['dependencies'], time(), true );
		wp_register_style( 'refact-newsletter-block-' . self::$id . '-editor-style', $editor_css, array( 'wp-components' ), time() );
		wp_register_style( 'refact-newsletter-block-' . self::$id . '-style', $css, array(), time() );

		wp_enqueue_style( 'refact-newsletter-block-' . self::$id . '-editor-editor-style' );
		wp_enqueue_style( 'refact-newsletter-block-' . self::$id . '-editor-style' );
		wp_enqueue_script( 'refact-newsletter-block-' . self::$id . '-editor-script' );

		$re_beehiiv_api_key        = get_option( 're_beehiiv_api_key', '' );
		$re_beehiiv_publication_id = get_option( 're_beehiiv_publication_id', '' );
		$re_beehiiv_api_status     = get_option( 're_beehiiv_api_status', false );

		$options = array(
			'api_key'        => $re_beehiiv_api_key,
			'publication_id' => $re_beehiiv_publication_id,
			'api_status'     => $re_beehiiv_api_status,
		);

		wp_localize_script( 'refact-newsletter-block-' . self::$id . '-editor-script', 're_beehiiv_settings', $options );

	}

	/**
	 * Register rest routes
	 *
	 * @return void
	 */
	public static function register_rest_routes() {
		register_rest_route(
			'rebeehiiv/v1',
			'/disconnect_api',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'disconnect_api' ),
				'permission_callback' => array( __CLASS__, 'permissions_check' ),
			)
		);

		register_rest_route(
			'rebeehiiv/v1',
			'/save_settings',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'save_settings' ),
				'permission_callback' => array( __CLASS__, 'permissions_check' ),
			)
		);
	}

	/**
	 * Disconnect API
	 *
	 * @param object $req Request object.
	 * @return array
	 */
	public static function disconnect_api( $req ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		delete_option( 're_beehiiv_api_key' );
		delete_option( 're_beehiiv_publication_id' );
		delete_option( 're_beehiiv_api_status' );

		return array(
			'success' => true,
			'message' => 'Disconnected',
		);
	}

	/**
	 * Save settings
	 *
	 * @param object $req Request object.
	 * @return array
	 */
	public static function save_settings( $req ) {
		$api_key        = $req->get_param( 'apiKey' );
		$api_status     = $req->get_param( 'status' );
		$publication_id = $req->get_param( 'publicationId' );

		if ( empty( $api_key ) || empty( $publication_id ) ) {
			return array(
				'success' => false,
				'message' => 'Please fill all fields',
			);
		}

		update_option( 're_beehiiv_api_key', $api_key );
		update_option( 're_beehiiv_api_status', $api_status );
		update_option( 're_beehiiv_publication_id', $publication_id );

		return array(
			'success' => true,
			'message' => 'Settings saved',
		);
	}

	/**
	 * Permissions check
	 *
	 * @return bool
	 */
	public static function permissions_check() {
		return current_user_can( 'manage_options' );
	}
}
