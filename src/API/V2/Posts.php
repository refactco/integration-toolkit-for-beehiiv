<?php
namespace Re_Beehiiv\API\V2;

defined( 'ABSPATH' ) || exit;

class Posts {

    public static function get_all_posts($content_types) {
            
        $page = 1;
        $posts = array();

        while (true) {

            $data = self::get_posts_in_page($page, $content_types);
            $posts = array_merge($posts, $data['data']);

            if ($data['page'] == $data['total_pages']) {
                break;
            }

            $page++;
        }

        return $posts;

    }

    public static function get_posts_in_page( $page = 1, $expand = array()) {

        $route = Routes::build_route(
            Routes::POSTS_INDEX,
            array( 'publicationId' => get_option('re_beehiiv_publication_id') ),
            array(
                'page' => $page,
                'expand[]' => $expand
            )
        );

        $api_key = get_option('re_beehiiv_api_key');

        $response = Request_Beehiiv::get($api_key, $route);

        if (is_wp_error($response)) {
            return array(
                'error' => $response->get_error_message()
            );
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        return $data;
    }
}