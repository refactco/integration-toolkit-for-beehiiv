<?php
namespace Re_Beehiiv\Import;
use Re_Beehiiv\API\V2\Posts;

defined( 'ABSPATH' ) || exit;

class Ajax_Import {

    protected $queue;

    public function callback() {

        $this->queue = new Queue();
        $this->queue->setTimestamp(Queue::TIMESTAMP_2_MIN);

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
        $exclude_draft = isset($_POST['exclude_draft']) ? sanitize_text_field($_POST['exclude_draft']) : false;

        // GET ALL DATA (CACHED)
        $data = $this->get_all_data($content_type);

        if (isset($data['error'])) {
            wp_send_json($data);
            exit;
        }

        $group_name = 'manual_import_' . time();
        set_transient('RE_BEEHIIV_manual_import_group', $group_name, 60 * 60 * 24);
        foreach ($data as $value) {

            if ($value['status'] != 'confirmed' && $exclude_draft == 'yes') {
                continue;
            }

            $data = $this->prepare_beehiiv_data_for_wp($value, $content_type, [
                'auto' => $auto,
                'post_status' => $post_status,
                'update_existing' => $update_existing,
                'exclude_draft' => $exclude_draft,
                'taxonomy' => $taxonomy,
                'term' => $term
            ]);

            if (!$data) {
                continue;
            }

            $data = apply_filters('RE_BEEHIIV_ajax_import_before_create_post', $data);

            Import_Table::insert_custom_table_row($data['meta']['post_id'], $data, 'pending');

            $req['group'] = $group_name;
            $req['args'] = array(
                'id' => $data['meta']['post_id']
            );

            $this->queue->addToQueue($req);

        }
        wp_send_json([
            'success' => true,
            'message' => 'Import started'
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

        // set post status
        if ($value['status'] == 'confirmed') {
            $data['post']['post_status'] = $args['post_status'] ?? 'publish';
        } else if ($args['exclude_draft'] == 'yes') {
            return false;
        } else {
            $data['post']['post_status'] = 'draft';
        }


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

    public static function register_progress_notice() {

        $group_name = get_transient('RE_BEEHIIV_manual_import_group');

        if (empty($group_name)) {
            return;
        }

        $Queue = new Queue();
        $complete_actions = $Queue->get_manual_actions($group_name, 'complete');
        $all_actions = $Queue->get_manual_actions($group_name);

        if (empty($all_actions)) {
            delete_transient('RE_BEEHIIV_manual_import_group');
            return;
        }
        if (count($complete_actions) == count($all_actions)) {
            echo "<div class='notice notice-success is-dismissible' id='re_beehiiv_progress_notice'>";
            echo "<p>Importing posts from Re Beehiiv is complete.</p>";
            echo "<button type='button' class='notice-dismiss'><span class='screen-reader-text'>Dismiss this notice.</span></button>";
            echo "</div>";
            delete_transient('RE_BEEHIIV_manual_import_group');
            return;
        }

        // notice with progress bar
        echo "<div class='notice notice-info' id='re_beehiiv_progress_notice'>";
        echo "<p>Importing posts from Beehiiv is in progress. Be patient, this may take a while.";
        echo "<strong> Progress: " . count($complete_actions) . " / " . count($all_actions) . "</strong></p>";
        echo "</div>";
        return;
    }

}