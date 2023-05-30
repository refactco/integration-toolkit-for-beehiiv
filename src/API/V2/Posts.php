<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing
namespace Re_Beehiiv\API\V2;

defined( 'ABSPATH' ) || exit;

/**
 * Posts
 * This class contains all the methods to interact with the posts endpoint of the Beehiiv API
 *
 * @since      1.0.0
 * @package    Re_Beehiiv
 * @subpackage Re_Beehiiv/API/V2
 */
class Posts {

	/**
	 * Get all posts
	 * This method returns all the posts of a publication
	 *
	 * @param string $content_types
	 * @return array
	 */
	public static function get_all_posts( string $content_types ) {

		$page  = 1;
		$posts = array();

		while ( true ) {

			$data = self::get_posts_in_page( $page, $content_types );
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
	 * @param array $expand
	 */
	public static function get_posts_in_page( $page = 1, $expand = array() ) {

		$route = Routes::build_route(
			Routes::POSTS_INDEX,
			array( 'publicationId' => get_option( 're_beehiiv_publication_id' ) ),
			array(
				'page'   => $page,
				'expand' => $expand,
			)
		);

		$api_key = get_option( 're_beehiiv_api_key' );

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
