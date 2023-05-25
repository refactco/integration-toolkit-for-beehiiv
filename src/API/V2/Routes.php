<?php
namespace Re_Beehiiv\API\V2;

defined( 'ABSPATH' ) || exit;

/**
 * Routes
 * This class contains all the routes for the API
 * 
 * @since      1.0.0
 * @package    Re_Beehiiv
 * @subpackage Re_Beehiiv/API/V2
 */
class Routes {

    const BASE_URL      = 'https://api.beehiiv.com/v2';
    const BASE_MOCK_URL = 'https://stoplight.io/mocks/beehiiv/v2/104190750';
    
    const POSTS_INDEX   = '/publications/publicationId/posts';
    const POSTS_SHOW    = '/publications/publicationId/posts/postId';
    const POSTS_DESTROY = '/publications/publicationId/posts/postId';


    /**
     * build_route
     * Use this method to build a route with params
     * @param string $route
     * @param array $params
     * @return string
     * 
     * @example $route = Routes::build_route( Routes::POSTS_INDEX, array( 'publicationId' => 1 ) );
     */
    public static function build_route( string $route, array $params = null, array $query_params = null )
    {
        if ( !$params ) {
            return $route;
        }

        $route = str_replace( array_keys( $params ), array_values( $params ), $route );

        if ( !empty( $query_params ) ) {
            $route .= '?' . http_build_query( $query_params );
        }

        return $route;
    }


    /**
     * get_base_url
     * This method return the base url for the API based on the environment
     * @return string
     */
    public static function get_base_url() {
        
        if ( defined( 'BEEHIIV_API_MOCK' ) && BEEHIIV_API_MOCK ) {
            return self::BASE_MOCK_URL;
        }

        return self::BASE_URL;
    }
}