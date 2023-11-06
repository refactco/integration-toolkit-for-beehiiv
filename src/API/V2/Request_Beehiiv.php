<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing
namespace Re_Beehiiv\API\V2;

defined( 'ABSPATH' ) || exit;

/**
 * Send a request to the Beehiiv API
 * This class is used to send a request to the Beehiiv API
 *
 * @since      1.0.0
 * @package    Re_Beehiiv
 * @subpackage Re_Beehiiv/API/V2
 */
class Request_Beehiiv {

	/**
	 * Send a GET request to the Beehiiv API
	 *
	 * @param string|null $api_key
	 * @param string $endpoint
	 * @return array|/WP_Error
	 */
	public static function get( string $api_key = null, string $endpoint ) {

		$headers  = self::get_headers( $api_key );
		$response = wp_remote_get(
			Routes::get_base_url() . $endpoint,
			array(
				'headers' => $headers,
				'timeout' => 60,
			)
		);

		return $response;

	}

	/**
	 * Get Headers
	 * If the api key is null, return the headers for a public request
	 * Otherwise, return the headers for an authenticated request
	 *
	 * @param string|null $api_key
	 * @return array
	 */
	private static function get_headers( ?string $api_key ): array {

		if ( $api_key === null ) {
			return array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json',
			);
		}

		$headers = array(
			'Authorization' => 'Bearer ' . $api_key,
			'Content-Type'  => 'application/json',
		);

		return $headers;

	}

}
