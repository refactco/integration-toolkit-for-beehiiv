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

        if ($response->status_code != 200) {
            return array(
                'error' => true,
                'message' => 'Error while fetching posts',
                'status_code' => $response->status_code,
                'body' => $response->body,
            );
        }

        $data = json_decode($response->body, true);

        return $data;
    }
}