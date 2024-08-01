<?php
/**
 * This File Contains the BeehiivClient Class
 *
 * @package ITFB\ImportCampaigns;
 * @since 2.0.0
 */

namespace ITFB\ImportCampaigns;

defined( 'ABSPATH' ) || exit;

/**
 * Send a request to the Beehiiv API
 * This class is used to send a request to the Beehiiv API
 *
 * @since      1.0.0
 * @package    ITFB
 */
class BeehiivClient {

	/**
	 * Base URL
	 * The base URL for the Beehiiv API
	 *
	 * @var string
	 */
	const BASE_URL = 'https://api.beehiiv.com/v2';

	/**
	 * Send a GET request to the Beehiiv API
	 *
	 * @param string|null $api_key The API key.
	 * @param string      $endpoint The endpoint.
	 * @return array|/WP_Error
	 */
	public static function get( string $api_key = null, string $endpoint ) {

		$headers  = self::get_headers( $api_key );
		$response = wp_remote_get(
			self::BASE_URL . $endpoint,
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
	 * @param string|null $api_key The API key.
	 * @return array
	 */
	private static function get_headers( ?string $api_key ): array {

		if ( null === $api_key ) {
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

	/**
	 * Build Route
	 * Use this method to build a route with params
	 *
	 * @param string $route The route.
	 * @param array  $params The params.
	 * @param array  $query_params The query params.
	 * @return string
	 *
	 * @example $route = Routes::build_route( Routes::POSTS_INDEX, array( 'publicationId' => 1 ) );
	 */
	public static function build_route( string $route, array $params = null, array $query_params = null ) {
		if ( ! $params ) {
			return $route;
		}

		$route = str_replace( array_keys( $params ), array_values( $params ), $route );

		if ( ! empty( $query_params ) ) {
			$route .= '?' . http_build_query( $query_params );
		}

		return $route;
	}
}
