<?php // phpcs:ignore WordPress.Files.FileName
namespace Re_Beehiiv;

/**
 * Class Canonical_URL
 * This class is used to create a canonical URL for the post.
 * It's also compatible with the Yoast SEO plugin.
 */
class Canonical_URL {

	/**
	 * Register the hook
	 * if Yoast SEO is active, use the wpseo_canonical filter
	 * else, use the wp_head action and add the canonical URL to the head
	 *
	 * @return void
	 */
	public function register_hook() {
		if ( class_exists( 'WPSEO_Frontend' ) ) {
			add_filter( 'wpseo_canonical', array( $this, 'wpseo_canonical' ) );
		} else {
			add_action( 'wp_head', array( $this, 'add_canonical_url' ) );
		}
	}

	/**
	 * Change the canonical URL if Yoast SEO is active
	 *
	 * @param string $canonical The canonical URL.
	 */
	public function wpseo_canonical( $canonical ) {
		global $post;
		$canonical_url = get_post_meta( $post->ID, 're_beehiiv_post_url', true );
		return $canonical_url ? $canonical_url : $canonical;
	}

	/**
	 * Add the canonical URL to the head
	 *
	 * @return void
	 */
	public function add_canonical_url() {
		global $post;
		$canonical_url = get_post_meta( $post->ID, 're_beehiiv_post_url', true );

		if ( $canonical_url ) {
			echo '<link rel="canonical" href="' . esc_url( $canonical_url ) . '" />';
		}
	}
}
