<?php

namespace Re_Beehiiv\BackgroundProcess;
use WP_Background_Process;

class CreatePost extends WP_Background_Process {

	protected $prefix = 're_beehiiv_';

	protected $action = 're_beehiiv_create_postt';

    protected $post_id;

	protected function task( $data ) {        
        
        if (!$data || !isset($data['meta']['post_id'])) {
            return false;
        }
        $last_id = (int) get_option('RE_BEEHIIV_last_check_id', false);
        update_option('RE_BEEHIIV_last_check_id', $last_id + 1);
        
        if (!$this->is_unique_post($data)) {
            return false;
        }
        
        $this->create_post($data);
        $this->add_meta($data);
        $this->add_tags($data);
        
        error_log('post created: ' . $this->post_id . ' - last_id: ' . $last_id + 1);

		return true;
	}

	protected function complete() {
		parent::complete();
        error_log('complete');
        delete_option('RE_BEEHIIV_last_check_id');
        delete_option('RE_BEEHIIV_manual_total_items');
        delete_option('RE_BEEHIIV_manual_percent');
	}


    private function create_post($data) {
        $this->post_id = wp_insert_post($data['post']);
    }

    private function add_meta($data) {
        foreach ($data['meta'] as $key => $value) {
            update_post_meta($this->post_id, 're_beehiiv_' . $key, $value);
        }
    }

    private function add_tags($data) {
        wp_set_post_tags($this->post_id, $data['tags'], true);
    }

    private function is_unique_post($data) {
        $args = array(
            'meta_key' => 're_beehiiv_post_id',
            'meta_value' => $data['meta']['post_id'],
            'post_type' => 'post',
            'post_status' => 'any',
            'posts_per_page' => 1
        );
        $posts = get_posts($args);
        
        return empty($posts);
    }

    private function update_progress_bar( $last_id ) {
        $total_items = (int) get_option('RE_BEEHIIV_manual_total_items', false);
        $percent = intval($last_id / $total_items * 100);


        update_option('RE_BEEHIIV_manual_percent', $percent);
    }

}