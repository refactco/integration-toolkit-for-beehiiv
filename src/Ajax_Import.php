<?php
namespace Re_Beehiiv;
use Re_Beehiiv\API\V2\Posts;

defined( 'ABSPATH' ) || exit;

class Ajax_Import {

    public function callback() {

        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'RE_BEEHIIV_ajax_import')) {
            echo wp_send_json([
                'success' => false,
                'message' => 'Invalid nonce'
            ]);
            exit;
        }

        // check category
        $cat = (isset($_POST['cat']) && $_POST['cat']) ? get_term_by('id', $_POST['cat'], 'category') : false;
        if (!$cat) {
            echo wp_send_json([
                'success' => false,
                'message' => 'Invalid category'
            ]);
            exit;
        }

        // check content type
        $content_type = (isset($_POST['content_type']) && $_POST['content_type']) ? $_POST['content_type'] : false;
        if (!$content_type) {
            echo wp_send_json([
                'success' => false,
                'message' => 'Invalid content type'
            ]);
            exit;
        }

        $content_type = ($content_type == 'both') ? array('free_web_content', 'premium_web_content') : $content_type;


        // GET ALL DATA (CACHED)
        $data = $this->get_all_data(false, $content_type);

        if (isset($data['error'])) {
            echo wp_send_json($data);
            exit;
        }

        $last_id = (int) get_option('RE_BEEHIIV_ajax_last_check_id', false);
        $count = count($data);
        $percent = intval($last_id / $count * 100);
        if ($percent == 100) {
            $last_id = 0;
            update_option('RE_BEEHIIV_ajax_last_check_id', $last_id);
            // GET ALL DATA (NON-CACHED)
            $data = $this->get_all_data(true);
        }

        $index = 0;
        foreach ($data as $value) {
            $index++;
            if ($last_id && $last_id >= $index) continue;
            // ACTIONS
            //create a post
            $post = array(
                'post_title' => $value['title'],
                'post_excerpt' => $value['subtitle'],
                'post_author' => 1,
                'post_type' => 'post',
                'post_category' => array($cat->term_id),
                'post_date' => date('Y-m-d H:i:s', $value['publish_date']),
                'post_name' => $value['slug']
            );

            if ($content_type == 'free_web_content') {
                $post['post_content'] = $value['content']['free']['web'];
            } else {
                $post['post_content'] = $value['content']['premium']['web'];
            }


            if ($value['status'] == 'confirmed') {
                $post['post_status'] = 'publish';
            } else {
                $post['post_status'] = 'draft';
            }

            $post_id = wp_insert_post($post);

            wp_set_post_tags($post_id, $value['content_tags'], true);

            // add post meta
            add_post_meta($post_id, 'beehive_post_id', $value['id']);
            add_post_meta($post_id, 'beehive_post_url', $value['web_url']);

            // ACTIONS
            update_option('RE_BEEHIIV_ajax_last_check_id', $index);
            $last_id = $index;
            break;
        }
        $percent = intval($last_id / $count * 100);
        echo wp_send_json([
            'success' => true,
            'percent' => $percent,
            'count' => $count,
            'last_id' => $last_id
        ]);
        exit;
    }

    public function get_all_data($force = false, $content_type = array()){
        $cached = get_transient('RE_BEEHIIV_get_all_recurly_accounts');
        if ($cached && $force == false) return $cached;
        $data = Posts::get_all_posts($content_type);
        set_transient('RE_BEEHIIV_get_all_recurly_accounts', $data, DAY_IN_SECONDS);
        update_option('RE_BEEHIIV_ajax_all_recurly_accounts', count($data));
        return $data;
    }

}