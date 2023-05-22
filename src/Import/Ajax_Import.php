<?php
namespace Re_Beehiiv\Import;
use Re_Beehiiv\API\V2\Posts;

defined( 'ABSPATH' ) || exit;

class Ajax_Import {

    protected $createPostProcess;

    public function callback() {

        $this->createPostProcess = new BackgroundProcess\CreatePost();
        $auto = 'manual';

        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'RE_BEEHIIV_ajax_import')) {
            wp_send_json([
                'success' => false,
                'message' => 'Invalid nonce'
            ]);
            exit;
        }

        // check content type
        $content_type = isset($_POST['content_type']) ? sanitize_text_field($_POST['content_type']) : false;
        if (!$content_type) {
            wp_send_json([
                'success' => false,
                'message' => 'Invalid content type'
            ]);
            exit;
        }

        // check taxonomy
        $taxonomy = isset($_POST['taxonomy']) ? sanitize_text_field($_POST['taxonomy']) : false;
        if (!$taxonomy) {
            wp_send_json([
                'success' => false,
                'message' => 'Invalid taxonomy'
            ]);
            exit;
        }

        // check term
        $term = isset($_POST['term']) ? sanitize_text_field($_POST['term']) : false;
        if (!$term) {
            wp_send_json([
                'success' => false,
                'message' => 'Invalid term'
            ]);
            exit;
        }

        $post_status = isset($_POST['post_status']) ? sanitize_text_field($_POST['post_status']) : 'draft';
        $update_existing = isset($_POST['update_existing']) ? sanitize_text_field($_POST['update_existing']) : false;


        // GET ALL DATA (CACHED)
        $data = $this->get_all_data($content_type);

        if (isset($data['error'])) {
            wp_send_json($data);
            exit;
        }


        update_option('RE_BEEHIIV_last_check_id', 0);
        update_option('RE_BEEHIIV_manual_percent', 0);
        update_option('RE_BEEHIIV_manual_total_items', count($data));
        foreach ($data as $value) {

            if ($value['status'] != 'confirmed') {
                continue;
            }

            $data = $this->prepare_beehiiv_data_for_wp($value, $content_type, [
                'auto' => $auto,
                'post_status' => $post_status,
                'update_existing' => $update_existing,
                'taxonomy' => $taxonomy,
                'term' => $term
            ]);
            $data = apply_filters('RE_BEEHIIV_ajax_import_before_create_post', $data);
            $this->createPostProcess->push_to_queue($data);

        }
        $this->createPostProcess->save()->dispatch();

        wp_send_json([
            'success' => true,
            'message' => 'Import started'
        ]);
        exit;
    }

    public function manual_import_progress() {
        $last_id = (int) get_option('RE_BEEHIIV_last_check_id', 0);
        $count = (int) get_option('RE_BEEHIIV_manual_total_items', 0);
        $percent = intval( ( $last_id / $count) * 100);
        
        if ($percent >= 100) {
            delete_option('RE_BEEHIIV_last_check_id');
            delete_option('RE_BEEHIIV_manual_total_items');
            delete_option('RE_BEEHIIV_manual_percent');
        }
        wp_send_json([
            'success' => true,
            'percent' => $percent,
            'last_id' => $last_id,
            'count' => $count
        ]);
        exit;
    }

    public function get_all_data($content_type){
        if ($content_type == 'both') {

            $data = Posts::get_all_posts('free_web_content');
            $premium_web_content = Posts::get_all_posts('premium_web_content');

            foreach ($data as $key => $value) {
                if (isset($premium_web_content[$key]['content']['premium']['web'])) {
                    $data[$key]['content']['premium']['web'] = $premium_web_content[$key]['content']['premium']['web'];
                }
            }

        } else {
            $data = Posts::get_all_posts($content_type);
        }

        return $data;
    }

    private function get_post_content($content, $content_type) {
        if ($content_type == 'premium_web_content') {
            if (!isset($content['premium']['web'])) {
                return false;
            }
            return $content['premium']['web'];
        } else if ($content_type == 'free_web_content') {
            if (!isset($content['free']['web'])) {
                return false;
            }
            return $content['free']['web'];
        } else {
            if (isset($content['premium']['web'])) {
                return $content['premium']['web'];
            } else if (isset($content['free']['web'])) {
                return $content['free']['web'];
            } else {
                return '';
            }
        }
    }

    private function prepare_beehiiv_data_for_wp( $value, $content_type, $args = array() ) {

        //create a post
        $data = [
            'post'          => [
                'post_title'    => $value['title'],
                'post_excerpt'  => $value['subtitle'],
                'post_author'   => 1,
                'post_type'     => 'post',
                'post_name'     => $value['slug'],
                'post_status'   => $args['post_status'] ?? 'draft',
            ],
            'tags'          => $value['content_tags'],
            'meta'          => [
                'content_type' => $content_type,
                'status'       => $value['status'],
                'post_id' => $value['id'],
                'post_url' => $value['web_url']
            ],
            'auto'          => $args['auto'] ?? 'manual',
            'args'          => $args
        ];


        // set content
        if ( isset($value['content']) ) {
            $data['post']['post_content'] = $this->get_post_content($value['content'], $content_type);
        }

        // set post date
        $data['post']['post_date'] = isset($value['publish_date']) ? date('Y-m-d H:i:s', $value['publish_date']) : date('Y-m-d H:i:s', time());

        // maybe set premium content
        if ( isset($value['content']['premium']['web']) ) {
            $data['meta']['premium_content'] = $value['content']['premium']['web'];
        }

        return $data;
    }

    public function manual_change_import_status() {
        $this->createPostProcess = new BackgroundProcess\CreatePost();
        
        $status = isset($_POST['new_status']) ? sanitize_text_field($_POST['new_status']) : false;

        if ($status == 'pause') {
            $this->createPostProcess->pause();
        } else if ($status == 'resume') {
            $this->createPostProcess->resume();
        } else if ($status == 'cancel') {
            $this->createPostProcess->cancel();
        } else {
            wp_send_json([
                'success' => false,
                'message' => 'Invalid status'
            ]);
            exit;
        }

        wp_send_json([
            'success' => true,
            'message' => 'Import status changed'
        ]);
        exit;
    }

    public function seconds_between_batches( $s ) {
        return 1;
    }

}