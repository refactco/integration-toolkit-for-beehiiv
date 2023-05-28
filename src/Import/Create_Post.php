<?php

namespace Re_Beehiiv\Import;

class Create_Post {
    protected $post_id;

    protected $data;

    /**
     * Create_Post constructor.
     *
     * @param $req
     *  @param $req['id'] int
     */
    public function __construct( $req ) {
        $data = Import_Table::get_custom_table_row($req['id']);

        if (!$data) {
            $this->data = false;
            return;
        }

        $data[0]->key_value = json_decode($data[0]->key_value, true);
        $this->data = $data[0]->key_value;
    }

    /**
     * Process the import
     *
     * @return array
     */
	public function Process() {


        if (!$this->data || !isset($this->data['meta']['post_id'])) {
            return [
                'success' => false,
                'message' => 'No data found'
            ];
        }

        $existing_id = $this->is_unique_post();
        if ($existing_id) {
            if ($this->data['args']['update_existing'] == 'yes') {
                $this->data['post']['ID'] = $existing_id;
                $this->post_id = $existing_id;
                $this->update_existing_post();
            } else {
                $this->complete();
                return [
                    'success' => true,
                    'message' => 'Post already exists'
                ];
            }
        }
        
        $this->create_post();
        $this->add_meta();
        $this->add_tags();
        $this->add_taxonomies();
        return $this->complete();
	}

    /**
     * Complete the import
     *
     * @return array
     */
	protected function complete() {
        Import_Table::delete_custom_table_row($this->data['meta']['post_id']);

        return [
            'success' => true,
            'message' => 'Post created'
        ];
	}

    /**
     * Insert new post into database
     * 
     * @return void
     */
    private function create_post() {
        $this->post_id = wp_insert_post($this->data['post']);
    }

    /**
     * Add meta data to post
     * 
     * @return void
     */
    private function add_meta() {
        foreach ($this->data['meta'] as $key => $value) {
            update_post_meta($this->post_id, 're_beehiiv_' . $key, $value);
        }
    }


    /**
     * Add tags to post
     * 
     * @return void
     */
    private function add_tags() {
        wp_set_post_tags($this->post_id, $this->data['tags'], true);
    }

    /**
     * Check if post already exists
     * 
     * @return bool|int
     */
    private function is_unique_post() : bool|int {
        $args = array(
            'meta_key' => 're_beehiiv_post_id',
            'meta_value' => $this->data['meta']['post_id'],
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

    /**
     * Add taxonomies to post
     * 
     * @return bool
     */
    private function add_taxonomies() {
        $taxonomy = $this->data['args']['taxonomy'] ?? '';
        $term = $this->data['args']['term'] ?? '';

        if (!taxonomy_exists($taxonomy)) {
            return false;
        }

        $term = get_term_by('id', $term, $taxonomy);
        if ($term) {
            wp_set_post_terms($this->post_id, $term->term_id, $taxonomy);
        }

    }

    /**
     * Update existing post
     * 
     * @return void
     */
    private function update_existing_post() {
        wp_update_post($this->data['post']);
        $this->add_meta();
        $this->add_tags();
        $this->add_taxonomies();
    }

}