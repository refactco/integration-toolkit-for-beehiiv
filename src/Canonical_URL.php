<?php
namespace Re_Beehiiv;
/* This class is used to create a canonical URL for the post.
* It's also compatible with the Yoast SEO plugin.
*/
class Canonical_URL {

    public function register_hook() {
        if (class_exists('WPSEO_Frontend')) {
            add_filter('wpseo_canonical', array($this, 'wpseo_canonical'));
        } else {
            add_action('wp_head', array($this, 'add_canonical_url'));
        }
    }

    public function wpseo_canonical($canonical) {
        global $post;
        $canonical_url = get_post_meta($post->ID, 're_beehiiv_post_url', true);
        return $canonical_url ? $canonical_url : $canonical;
    }

    public function add_canonical_url() {
        global $post;
        $canonical_url = get_post_meta($post->ID, 're_beehiiv_post_url', true);

        if ($canonical_url) {
            echo '<link rel="canonical" href="' . $canonical_url . '" />';
        }
        return;
    }
}