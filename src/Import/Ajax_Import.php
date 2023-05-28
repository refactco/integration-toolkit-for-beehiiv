<?php
namespace Re_Beehiiv\Import;
use Re_Beehiiv\API\V2\Posts;

defined( 'ABSPATH' ) || exit;

class Ajax_Import {

    protected $queue;

    public function callback($args = []) {

        if (isset($args['auto']) && $args['auto'] === 'auto') {
            $form_data = $args;
        } else {
            $form_data = $this->get_form_validated_data();
            
            // set an transient to show the user the import is running
            set_transient('RE_BEEHIIV_manual_import_running', true, 60 * 60);
        }

        if (isset($form_data['error'])) {
            if (isset($form_data['auto']) && $form_data['auto'] === 'auto') {
                return;
            }
            wp_send_json($form_data);
            exit;
        }

        $this->queue = new Queue();

        $data = $this->get_all_data($form_data['content_type']);

        if (isset($data['error'])) {
            if (isset($form_data['auto']) && $form_data['auto'] === 'auto') {
                return;
            }
            wp_send_json($data);
            exit;
        }

        if (isset($form_data['auto']) && $form_data['auto'] === 'auto') {
            $group_name = 'auto_import' . time();
        } else {
            $group_name = 'manual_import_' . time();
            set_transient('RE_BEEHIIV_manual_import_group', $group_name, 60 * 60 * 24);
        }

        $this->push_to_queue($data, [
            'auto' => 'manual',
            'post_status' => $form_data['post_status'],
            'update_existing' => $form_data['update_existing'],
            'exclude_draft' => $form_data['exclude_draft'],
            'taxonomy' => $form_data['taxonomy'],
            'term' => $form_data['term'],
            'group' => $group_name,
            'content_type' => $form_data['content_type'],
        ]);

        if (isset($form_data['auto']) && $form_data['auto'] === 'auto') {
            return true;
        }
        
        wp_send_json([
            'success' => true,
            'message' => 'Import started'
        ]);
        exit;
    }

    public function auto_import_callback() {
            
            $form_data = $this->get_form_validated_data();

            
            if (isset($form_data['error'])) {
                wp_send_json($form_data);
                exit;
            }
            $form_data['cron_time'] = isset($_POST['cron_time']) ? sanitize_text_field($_POST['cron_time']) : false;
    
            $this->queue = new Queue();    
            $auto = 'auto';
    
            $group_name = 'auto_recurring_import' . time();

            $req['group'] = $group_name;
            $req['args'] = array(
                'auto' => $auto,
            );
            $req['args'] = array_merge($req['args'], $form_data);

            $this->queue->addRecurrenceTask($req);
            
            wp_send_json([
                'success' => true,
                'message' => 'Cron job scheduled'
            ]);
            exit;
    }

    public function get_form_validated_data() {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : false;

        if (!wp_verify_nonce($nonce, 'RE_BEEHIIV_ajax_import')) {
            return [
                'error' => true,
                'message' => 'Invalid nonce'
            ];
        }

        $content_type = isset($_POST['content_type']) ? sanitize_text_field($_POST['content_type']) : false;
        $post_status = isset($_POST['post_status']) ? sanitize_text_field($_POST['post_status']) : false;
        $update_existing = isset($_POST['update_existing']) ? sanitize_text_field($_POST['update_existing']) : false;
        $exclude_draft = isset($_POST['exclude_draft']) ? sanitize_text_field($_POST['exclude_draft']) : false;
        $taxonomy = isset($_POST['taxonomy']) ? sanitize_text_field($_POST['taxonomy']) : false;
        $term = isset($_POST['term']) ? sanitize_text_field($_POST['term']) : false;

        if (!$nonce || !$content_type || !$post_status || !$update_existing || !$exclude_draft || !$taxonomy || !$term) {
            return [
                'error' => true,
                'message' => 'Invalid data'
            ];
        }

        return [
            'nonce' => $nonce,
            'content_type' => $content_type,
            'post_status' => $post_status,
            'update_existing' => $update_existing,
            'exclude_draft' => $exclude_draft,
            'taxonomy' => $taxonomy,
            'term' => $term
        ];
    }

    public function push_to_queue($data, $args) {

        $time_stamp = Queue::TIMESTAMP_4_SEC;

        foreach ($data as $value) {

            if ($value['status'] != 'confirmed' && $args['exclude_draft'] == 'yes') {
                continue;
            }

            $data = $this->prepare_beehiiv_data_for_wp($value, $args['content_type'], [
                'auto' => $args['auto'],
                'post_status' => $args['post_status'],
                'update_existing' => $args['update_existing'],
                'exclude_draft' => $args['exclude_draft'],
                'taxonomy' => $args['taxonomy'],
                'term' => $args['term']
            ]);

            if (!$data) {
                continue;
            }

            $data = apply_filters('RE_BEEHIIV_ajax_import_before_create_post', $data);

            Import_Table::insert_custom_table_row($data['meta']['post_id'], $data, 'pending');

            $req['group'] = $args['group'];
            $req['args'] = array(
                'id' => $data['meta']['post_id']
            );

            $this->queue->addToQueue($req, $args['group'], $time_stamp);
            $time_stamp += Queue::TIMESTAMP_4_SEC;
        }

        return true;
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
        $is_running = get_transient('RE_BEEHIIV_manual_import_running');

        if (empty($group_name)) {

            if ($is_running) {
                echo "<div class='notice notice-info' id='re_beehiiv_progress_notice'>";
                echo "<p>Importing posts from Beehiiv is in progress. Be patient, this may take a while.";
                echo "</div>";
                return;
            }

            return;
        }

        $Queue = new Queue();
        $complete_actions = $Queue->get_manual_actions($group_name, 'complete');
        $all_actions = $Queue->get_manual_actions($group_name);

        if (empty($all_actions)) {
            delete_transient('RE_BEEHIIV_manual_import_group');
            delete_transient('RE_BEEHIIV_manual_import_running');
            return;
        }
        if (count($complete_actions) == count($all_actions)) {
            echo "<div class='notice notice-success is-dismissible' id='re_beehiiv_progress_notice'>";
            echo "<p>Importing posts from Re Beehiiv is complete.</p>";
            echo "<button type='button' class='notice-dismiss'><span class='screen-reader-text'>Dismiss this notice.</span></button>";
            echo "</div>";
            delete_transient('RE_BEEHIIV_manual_import_group');
            delete_transient('RE_BEEHIIV_manual_import_running');
            return;
        }

        $failed_actions = $Queue->get_manual_actions($group_name, 'failed');

        if ( $failed_actions == $all_actions ) {
            echo "<div class='notice notice-error is-dismissible' id='re_beehiiv_progress_notice'>";
            echo "<p>Importing posts from Re Beehiiv has failed.</p>";
            echo "<button type='button' class='notice-dismiss'><span class='screen-reader-text'>Dismiss this notice.</span></button>";
            echo "</div>";
            delete_transient('RE_BEEHIIV_manual_import_group');
            delete_transient('RE_BEEHIIV_manual_import_running');
            return;
        }

        if ( $failed_actions + $complete_actions == $all_actions ) {
            echo "<div class='notice notice-warning is-dismissible' id='re_beehiiv_progress_notice'>";
            echo "<p>Importing posts from Re Beehiiv is complete, but some posts failed to import.</p>";
            echo "<p>Failed posts: " . count($failed_actions) . "/" . count($all_actions) . "</p>";
            echo "</div>";
            delete_transient('RE_BEEHIIV_manual_import_group');
            delete_transient('RE_BEEHIIV_manual_import_running');
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