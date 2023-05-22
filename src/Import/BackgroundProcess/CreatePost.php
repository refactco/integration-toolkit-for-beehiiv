<?php

namespace Re_Beehiiv\Import\BackgroundProcess;
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

        $existing_id = $this->is_unique_post($data);
        if ($existing_id) {
            if ($data['args']['update_existing'] == 'yes') {
                $data['post']['ID'] = $existing_id;
                $this->post_id = $existing_id;
                $this->update_existing_post($data);
            }
            return false;
        }
        
        $this->create_post($data);
        $this->add_meta($data);
        $this->add_tags($data);
        $this->add_taxonomies($data);

		return false;
	}

	protected function complete() {
		parent::complete();

        $count = (int) get_option('RE_BEEHIIV_manual_total_items', false);
        update_option('RE_BEEHIIV_last_check_id', $count);
        update_option('RE_BEEHIIV_manual_percent', '100');
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
        
        // return post id if exists
        if (isset($posts[0])) {
            return $posts[0]->ID;
        }
        return false;
    }

    private function add_taxonomies($data) {
        $taxonomy = $data['args']['taxonomy'] ?? '';
        $term = $data['args']['term'] ?? '';

        if (!taxonomy_exists($taxonomy)) {
            return false;
        }

        $term = get_term_by('id', $term, $taxonomy);
        if ($term) {
            wp_set_post_terms($this->post_id, $term->term_id, $taxonomy);
        }

    }

    private function update_existing_post($data) {
        wp_update_post($data['post']);
        $this->add_meta($data);
        $this->add_tags($data);
        $this->add_taxonomies($data);
    }

}