<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing

namespace Re_Beehiiv\Import;
use Re_Beehiiv\Lib\Logger;

/**
 * Class Create_Post
 * This class is responsible for creating a post from the data
 *
 * @package Re_Beehiiv\Import
 */
class Create_Post {

	/**
	 * Post ID
	 *
	 * @var int
	 */
	protected $post_id;

	/**
	 * Prepared data for import
	 *
	 * @var array|bool
	 */
	protected $data;

	/**
	 * Logger instance
	 *
	 * @var Logger
	 */
	protected $logger;

	/**
	 * Create_Post constructor.
	 *
	 * @param object $data
	 * @param string $group_name
	 */
	public function __construct( $data, string $group_name ) {
		$this->logger = new Logger( $group_name );

		if ( ! isset( $data->key_value ) && isset( $data['id'] ) ) {
			$data = Import_Table::get_custom_table_row( $data['id'], $group_name );
			if ( $data ) {
				$data = $data[0];
			}
		}

		if ( ! $data || ! isset( $data->key_value ) ) {
			$this->data = false;
			$this->logger->log(
				array(
					'message' => __( 'No data found', 're-beehiiv' ),
					'status'  => 'error',
				)
			);
			return;
		}

		$data->key_value = json_decode( $data->key_value, true );
		$this->data         = $data->key_value;
	}

	/**
	 * Process the import
	 *
	 * @return array
	 */
	public function process() {

		if ( ! $this->data || ! isset( $this->data['meta']['post_id'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'No data found', 're-beehiiv' ),
			);
		}

		$existing_id = $this->is_unique_post();
		if ( $existing_id ) {

			$import_method = $this->data['args']['form_data']['import_method'] ?? 'new_and_update';
			if ( 'update' === $import_method || 'new_and_update' === $import_method ) {
				$this->data['post']['ID'] = $existing_id;
				$this->post_id            = $existing_id;
				$this->update_existing_post();

				$this->logger->log(
					array(
						'message' => $this->data['meta']['post_id'] . ' - <a href="' . $this->get_edit_post_link() . '" target="_blank">#' . $existing_id . ' - ' . $this->data['post']['post_title'] . '</a> ' . esc_attr__( 'updated', 're-beehiiv' ),
						'status'  => 'success',
					)
				);

				return array(
					'success' => true,
					'message' => __('Post updated', 're-beehiiv' ),
				);
			} else {

				$this->logger->log(
					array(
						'message' => $this->data['meta']['post_id'] . ' - <a href="' . $this->get_edit_post_link() . '" target="_blank">#' . $existing_id . ' - ' . $this->data['post']['post_title'] . '</a> ' . esc_attr__( 'skipped', 're-beehiiv' ),
						'status'  => 'skipped',
					)
				);

				$this->complete();
				return array(
					'success' => true,
					'message' => __( 'Post already exists', 're-beehiiv' ),
				);
			}
		}

		$this->create_post();
		$this->add_meta();
		$this->add_taxonomies();
		$this->add_tags();

		$this->logger->log(
			array(
				'message' => $this->data['meta']['post_id'] . ' - <a href="' . $this->get_edit_post_link() . '" target="_blank">#' . $this->post_id . ' - ' . $this->data['post']['post_title'] . '</a> ' . esc_attr__( 'created', 're-beehiiv' ),
				'status'  => 'success',
			)
		);

		return $this->complete();
	}

	/**
	 * Complete the import
	 *
	 * @return array
	 */
	protected function complete() {
		Import_Table::delete_custom_table_row( $this->data['meta']['post_id'] );

		return array(
			'success' => true,
			'message' => __( 'Post created', 're-beehiiv' ),
		);
	}

	/**
	 * Insert new post into database
	 *
	 * @return void
	 */
	private function create_post() {
		$this->post_id = wp_insert_post( $this->data['post'] );
	}

	/**
	 * Add meta data to post
	 *
	 * @return void
	 */
	private function add_meta() {
		foreach ( $this->data['meta'] as $key => $value ) {
			update_post_meta( $this->post_id, 're_beehiiv_' . $key, $value );
		}
	}


	/**
	 * Add tags to post
	 *
	 * @return bool
	 */
	private function add_tags() {
		if ( ! isset( $this->data['args']['form_data']['post_tags-taxonomy'] ) || $this->data['args']['form_data']['post_tags-taxonomy'] === '0' ) {
			return false;
		}

		$taxonomy = $this->data['args']['form_data']['post_tags-taxonomy'];

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return false;
		}

		foreach ( $this->data['tags'] as $tag ) {
			$term = term_exists( $tag, $taxonomy );
			if ( ! $term ) {
				$term = wp_insert_term(
					$tag,
					$taxonomy,
					array(
						'slug' => strtolower( str_ireplace( ' ', '-', $tag ) ),
					)
				);
			}
			$term = get_term_by( 'id', $term['term_id'], $taxonomy );
			wp_set_post_terms( $this->post_id, array( (int) $term->term_id ), $taxonomy, true );
		}

		return true;
	}

	/**
	 * Check if post already exists
	 */
	private function is_unique_post() {
		$args  = array(
			'meta_key'       => 're_beehiiv_post_id',
			'meta_value'     => $this->data['meta']['post_id'],
			'post_type'      =>  $this->data['args']['form_data']['post_type'],
			'post_status'    => 'any',
			'posts_per_page' => 1,
		);
		$posts = get_posts( $args );

		// return post id if exists
		if ( isset( $posts[0] ) ) {
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
		$taxonomy = $this->data['args']['form_data']['taxonomy'] ?? '';
		$term     = $this->data['args']['form_data']['taxonomy_term'] ?? '';

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return false;
		}

		$term = get_term_by( 'id', $term, $taxonomy );
		if ( $term ) {
			wp_set_post_terms( $this->post_id, array( (int) $term->term_id ), $taxonomy, false );
		}

		return true;

	}

	/**
	 * Update existing post
	 *
	 * @return void
	 */
	private function update_existing_post() {
		wp_update_post( $this->data['post'] );
		$this->add_meta();
		$this->add_taxonomies();
		$this->add_tags();

		Import_Table::delete_custom_table_row( $this->data['meta']['post_id'] );
	}

	/**
	 * Get edit post link
	 *
	 * @return string
	 */
	private function get_edit_post_link() {
		$edit_post_link = get_edit_post_link( $this->post_id );

		if ( ! $edit_post_link ) {
			$edit_post_link = admin_url( 'post.php?post=' . $this->post_id . '&action=edit' );
		}

		return $edit_post_link;
	}

}
