<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing
namespace Integration_Toolkit_For_Beehiiv\API\V2;

defined( 'ABSPATH' ) || exit;

/**
 * Routes
 * This class contains all the routes for the API
 *
 * @since      1.0.0
 * @package    Integration_Toolkit_For_Beehiiv
 * @subpackage Integration_Toolkit_For_Beehiiv/API/V2
 */
class Routes {

	const BASE_URL      = 'https://api.beehiiv.com/v2';
	const POSTS_INDEX   = '/publications/publicationId/posts';
	const POSTS_SHOW    = '/publications/publicationId/posts/postId';
	const POSTS_DESTROY = '/publications/publicationId/posts/postId';


	/**
	 * Build Route
	 * Use this method to build a route with params
	 *
	 * @param string $route
	 * @param array $params
	 * @param array $query_params
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


	/**
	 * Get Base URL
	 * This method return the base url for the API based on the environment
	 *
	 * @return string
	 */
	public static function get_base_url() {
		return self::BASE_URL;
	}

}
