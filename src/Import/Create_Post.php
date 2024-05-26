<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing

namespace Integration_Toolkit_For_Beehiiv\Import;
use Integration_Toolkit_For_Beehiiv\Lib\Logger;

/**
 * Class Create_Post
 * This class is responsible for creating a post from the data
 *
 * @package Integration_Toolkit_For_Beehiiv\Import
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
					'message' => __( 'No data found', 'integration-toolkit-for-beehiiv' ),
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
				'message' => __( 'No data found', 'integration-toolkit-for-beehiiv' ),
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
						'message' => esc_attr__( ' Updated post', 'integration-toolkit-for-beehiiv' ) . ' - <a href="' . $this->get_edit_post_link() . '" target="_blank">#' . $existing_id . ' - ' . $this->data['post']['post_title'] . '</a> ',
						'status'  => 'success',
					)
				);

				return array(
					'success' => true,
					'message' => __('Post updated', 'integration-toolkit-for-beehiiv' ),
				);
			} else {

				$this->logger->log(
					array(
						'message' => esc_attr__( 'Skipped post', 'integration-toolkit-for-beehiiv' ) . ' - <a href="' . $this->get_edit_post_link() . '" target="_blank">#' . $existing_id . ' - ' . $this->data['post']['post_title'] . '</a> ',
						'status'  => 'skipped',
					)
				);

				$this->complete();
				return array(
					'success' => true,
					'message' => __( 'Post already exists', 'integration-toolkit-for-beehiiv' ),
				);
			}
		}

		$this->create_post();
		$this->add_meta();
		$this->add_taxonomies();
		$this->add_tags();
		$this->add_thumbnail();
		$this->logger->log(
			array(
				'message' => esc_attr__( 'Created post', 'integration-toolkit-for-beehiiv' ) . ' - <a href="' . $this->get_edit_post_link() . '" target="_blank">#' . $this->post_id . ' - ' . $this->data['post']['post_title'] . '</a> ',
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
			'message' => __( 'Post created', 'integration-toolkit-for-beehiiv' ),
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
			update_post_meta( $this->post_id, 'integration_toolkit_for_beehiiv_' . $key, $value );
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
   		// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		$args  = array(
			'meta_key'       => 'integration_toolkit_for_beehiiv_post_id',
			'meta_value'     => $this->data['meta']['post_id'],
			'post_type'      =>  $this->data['args']['form_data']['post_type'],
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		);
		$posts = get_posts( $args );

        // phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
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
		$this->add_thumbnail($update = true);
		Import_Table::delete_custom_table_row( $this->data['meta']['post_id'] );
	}

		/**
	 * Add thumbnail to post
	 *
	 * @return bool
	 */
	private function add_thumbnail($update = false) {
		if ( !isset( $this->data['thumbnail_url'] ) || empty( $this->data['thumbnail_url'] ) ) {
			return false;
		}
		
		// if update post, remove old thumbnail
		if ($update) {
			delete_post_thumbnail($this->post_id);
		}
		
		$image_id = media_sideload_image($this->data['thumbnail_url'], $this->post_id, null, 'id');

		if (!is_wp_error($image_id)) {
			set_post_thumbnail($this->post_id, $image_id);
		}
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
