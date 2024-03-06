<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing
namespace Integration_Toolkit_For_Beehiiv\API\V2;

defined( 'ABSPATH' ) || exit;

/**
 * Posts
 * This class contains all the methods to interact with the posts endpoint of the API
 *
 * @since      1.0.0
 * @package    Integration_Toolkit_For_Beehiiv
 * @subpackage Integration_Toolkit_For_Beehiiv/API/V2
 */
class Posts {

	/**
	 * Get all posts
	 * This method returns all the posts of a publication
	 *
	 * @param string $content_types
	 * @return array
	 */
	public static function get_all_posts( string $content_types , array $args = array() ) {

		$page  = 1;
		$posts = array();

		while ( true ) {

			$data = self::get_posts_in_page( $page, $content_types, $args);
			if ( isset( $data['error'] ) ) {
				return $data;
			}
			$posts = array_merge( $posts, $data['data'] );

			if ( $data['page'] === $data['total_pages'] ) {
				break;
			}

			$page++;
		}

		return $posts;

	}

	/**
	 * Get posts in page
	 * This method returns the posts of a publication in a specific page
	 * If the page is not specified, it will return the posts of the first page
	 *
	 * @param int $page
	 * @param string $expand
	 */
	public static function get_posts_in_page( $page = 1, $expand = '', array $args = array() ) {
		
		if ( ! empty( $args ) && ! empty( $args['publication_id'] ) && ! empty( $args['api_key'] ) && !empty( $args['content_type']) ) {
			$publication_id = $args['publication_id'];
			$api_key        = $args['api_key'];
		} else {
			$publication_id = get_option( 'integration_toolkit_for_beehiiv_publication_id' );
			$api_key        = get_option( 'integration_toolkit_for_beehiiv_api_key' );
		}

		$route = Routes::build_route(
			Routes::POSTS_INDEX,
			array( 'publicationId' => $publication_id ),
			array(
				'page'   => $page,
				'limit'  => 20,
				'expand' => $expand,
			)
		);


		$response = Request_Beehiiv::get( $api_key, $route );

		if ( is_wp_error( $response ) ) {
			return array(
				'error' => $response->get_error_message(),
			);
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		return $data;
	}
}
