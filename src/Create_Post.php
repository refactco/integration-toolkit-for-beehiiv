<?php
namespace Re_Beehiiv;

/**
 * Create a new post with the given title, content, category, tags, and more.
 * 
 */
class Create_Post {

    /**
     * The data to be used.
     * 
     * @var array
     */
    protected $data;
    protected $post_id;


    public function __construct($data) {
        $this->data = $data;

        if (!$this->is_unique_post()) {
            throw new \Exception( '"' . $this->data['post']['post_title'] . '" is not a unique post id.' );
        }

        $this->create_post();
        $this->add_meta();
        $this->add_tags();
        // $this->add_featured_image();
    }

    private function create_post() {
        $this->post_id = wp_insert_post($this->data['post']);
    }

    private function add_meta() {
        foreach ($this->data['meta'] as $key => $value) {
            update_post_meta($this->post_id, 're_beehiiv_' . $key, $value);
        }
    }

    private function add_tags() {
        wp_set_post_tags($this->post_id, $this->data['tags'], true);
    }

    private function is_unique_post() {
        $args = array(
            'meta_key' => 're_beehiiv_post_id',
            'meta_value' => $this->data['meta']['post_id'],
            'post_type' => 'post',
            'post_status' => 'any',
            'posts_per_page' => 1
        );
        $posts = get_posts($args);
        
        return empty($posts);
    }

}