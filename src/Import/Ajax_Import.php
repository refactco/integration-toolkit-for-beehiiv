<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing
namespace Re_Beehiiv\Import;

use Re_Beehiiv\API\V2\Posts;

defined( 'ABSPATH' ) || exit;

/**
 * Class Ajax_Import
 *
 * This class is used to handle the AJAX request for manual import
 *
 * @package Re_Beehiiv\Import
 */
class Ajax_Import {

	/**
	 * The Queue instance
	 *
	 * @var Queue
	 */
	protected $queue;

	/**
	 * Get instance of Queue
	 */
	public function get_queue() {
		if ( ! $this->queue ) {
			$this->queue = new Queue();
		}
		return $this->queue;
	}

	/**
	 * This method is used to get data from the API and push it to the queue
	 *
	 * @param array $args This parameter is only used when the method is called for auto import
	 * @return void
	 */
	public function callback( $args = array() ) {

		if ( isset( $args['auto'] ) && 'auto' === $args['auto'] ) {
			$form_data = $args;
		} else {
			$form_data = $this->get_form_validated_data();

			// set an transient to show the user the import is running
			set_transient( 'RE_BEEHIIV_manual_import_running', true, 60 * 60 );
		}

		if ( isset( $form_data['error'] ) ) {
			if ( isset( $form_data['auto'] ) && $form_data['auto'] === 'auto' ) {
				return;
			}
			wp_send_json( $form_data );
			exit;
		}

		$data = $this->get_all_data( $form_data['content_type'] );

		if ( isset( $data['error'] ) ) {
			if ( isset( $form_data['auto'] ) && $form_data['auto'] === 'auto' ) {
				return;
			}
			wp_send_json( $data );
			exit;
		}

		if ( isset( $form_data['auto'] ) && $form_data['auto'] === 'auto' ) {
			$group_name = 'auto_import' . time();
		} else {
			$group_name = 'manual_import_' . time();
			set_transient( 'RE_BEEHIIV_manual_import_group', $group_name, 60 * 60 * 24 );
		}

		$this->push_to_queue(
			$data,
			array(
				'auto'            => 'manual',
				'post_status'     => $form_data['post_status'],
				'update_existing' => $form_data['update_existing'],
				'exclude_draft'   => $form_data['exclude_draft'],
				'taxonomy'        => $form_data['taxonomy'],
				'term'            => $form_data['term'],
				'group'           => $group_name,
				'content_type'    => $form_data['content_type'],
			)
		);

		if ( isset( $form_data['auto'] ) && $form_data['auto'] === 'auto' ) {
			return;
		}

		wp_send_json(
			array(
				'success' => true,
				'message' => 'Import started',
			)
		);
		exit;
	}


	/**
	 * Ajax callback for auto import
	 *
	 * @return void
	 */
	public function auto_import_callback() {

		$form_data = $this->get_form_validated_data();

		if ( isset( $form_data['error'] ) ) {
			wp_send_json( $form_data );
			exit;
		}

		$form_data['cron_time'] = isset( $_POST['cron_time'] ) ? sanitize_text_field( $_POST['cron_time'] ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$auto = 'auto';

		$group_name = 'auto_recurring_import' . time();

		$req['group'] = $group_name;
		$req['args']  = array(
			'auto' => $auto,
		);
		$req['args']  = array_merge( $req['args'], $form_data );

		$this->get_queue()->add_recurrence_task( $req );

		wp_send_json(
			array(
				'success' => true,
				'message' => 'Cron job scheduled',
			)
		);
		exit;
	}

	/**
	 * Validate the form data for Ajax request
	 *
	 * @return array
	 */
	public function get_form_validated_data() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : false;

		if ( ! wp_verify_nonce( $nonce, 'RE_BEEHIIV_ajax_import' ) ) {
			return array(
				'error'   => true,
				'message' => 'Invalid nonce',
			);
		}

		$content_type    = isset( $_POST['content_type'] ) ? sanitize_text_field( $_POST['content_type'] ) : false;
		$post_status     = isset( $_POST['post_status'] ) ? sanitize_text_field( $_POST['post_status'] ) : false;
		$update_existing = isset( $_POST['update_existing'] ) ? sanitize_text_field( $_POST['update_existing'] ) : false;
		$exclude_draft   = isset( $_POST['exclude_draft'] ) ? sanitize_text_field( $_POST['exclude_draft'] ) : false;
		$taxonomy        = isset( $_POST['taxonomy'] ) ? sanitize_text_field( $_POST['taxonomy'] ) : false;
		$term            = isset( $_POST['term'] ) ? sanitize_text_field( $_POST['term'] ) : false;

		if ( ! $nonce || ! $content_type || ! $post_status || ! $update_existing || ! $exclude_draft || ! $taxonomy || ! $term ) {
			return array(
				'error'   => true,
				'message' => 'Invalid data',
			);
		}

		return array(
			'nonce'           => $nonce,
			'content_type'    => $content_type,
			'post_status'     => $post_status,
			'update_existing' => $update_existing,
			'exclude_draft'   => $exclude_draft,
			'taxonomy'        => $taxonomy,
			'term'            => $term,
		);
	}

	/**
	 * Push data to queue
	 *
	 * @param array $data
	 * @param array $args
	 * @return bool
	 */
	public function push_to_queue( $data, $args ) {

		$time_stamp = Queue::TIMESTAMP_4_SEC;

		foreach ( $data as $value ) {

			if ( $value['status'] !== 'confirmed' && $args['exclude_draft'] === 'yes' ) {
				continue;
			}

			$data = $this->prepare_beehiiv_data_for_wp(
				$value,
				$args['content_type'],
				array(
					'auto'            => $args['auto'],
					'post_status'     => $args['post_status'],
					'update_existing' => $args['update_existing'],
					'exclude_draft'   => $args['exclude_draft'],
					'taxonomy'        => $args['taxonomy'],
					'term'            => $args['term'],
				)
			);

			if ( ! $data ) {
				continue;
			}

			$data = apply_filters( 're_beehiiv_ajax_import_before_create_post', $data );

			Import_Table::insert_custom_table_row( $data['meta']['post_id'], $data, $args['group'], 'pending' );
			$req['group'] = $args['group'];
			$req['args']  = array(
				'id' => $data['meta']['post_id'],
			);

			$this->get_queue()->push_to_queue( $req, $args['group'], $time_stamp );
			$time_stamp += Queue::TIMESTAMP_4_SEC;
		}

		return true;
	}

	/**
	 * Get all data from beehiiv
	 *
	 * @param string $content_type
	 * @return array
	 */
	public function get_all_data( string $content_type ) {
		if ( $content_type === 'both' ) {

			$data                = Posts::get_all_posts( 'free_web_content' );
			$premium_web_content = Posts::get_all_posts( 'premium_web_content' );

			foreach ( $data as $key => $value ) {
				if ( isset( $premium_web_content[ $key ]['content']['premium']['web'] ) ) {
					$data[ $key ]['content']['premium']['web'] = $premium_web_content[ $key ]['content']['premium']['web'];
				}
			}
		} else {
			$data = Posts::get_all_posts( $content_type );
		}

		return $data;
	}

	/**
	 * Get post content
	 *
	 * @param array $content
	 * @param string $content_type
	 * @return string
	 */
	private function get_post_content( $content, $content_type ) {
		if ( $content_type === 'premium_web_content' ) {
			if ( ! isset( $content['premium']['web'] ) ) {
				return false;
			}
			return $content['premium']['web'];
		} elseif ( $content_type === 'free_web_content' ) {
			if ( ! isset( $content['free']['web'] ) ) {
				return false;
			}
			return $content['free']['web'];
		} else {
			if ( isset( $content['premium']['web'] ) ) {
				return $content['premium']['web'];
			} elseif ( isset( $content['free']['web'] ) ) {
				return $content['free']['web'];
			} else {
				return '';
			}
		}
	}

	/**
	 * Prepare beehiiv data for creating post in WordPress
	 *
	 * @param array $value
	 * @param string $content_type
	 * @param array $args
	 * @return array
	 */
	private function prepare_beehiiv_data_for_wp( $value, $content_type, $args = array() ) {

		// create a post
		$data = array(
			'post' => array(
				'post_title'   => $value['title'],
				'post_excerpt' => $value['subtitle'],
				'post_author'  => 1,
				'post_type'    => 'post',
				'post_name'    => $value['slug'],
			),
			'tags' => $value['content_tags'],
			'meta' => array(
				'content_type' => $content_type,
				'status'       => $value['status'],
				'post_id'      => $value['id'],
				'post_url'     => $value['web_url'],
			),
			'auto' => $args['auto'] ?? 'manual',
			'args' => $args,
		);

		// set post status
		if ( $value['status'] === 'confirmed' ) {
			$data['post']['post_status'] = $args['post_status'] ?? 'publish';
		} elseif ( $args['exclude_draft'] === 'yes' ) {
			return array();
		} else {
			$data['post']['post_status'] = 'draft';
		}

		// set content
		if ( isset( $value['content'] ) ) {
			$data['post']['post_content'] = $this->get_post_content( $value['content'], $content_type );
		}

		// set post date
		$data['post']['post_date'] = isset( $value['publish_date'] ) ? date( 'Y-m-d H:i:s', $value['publish_date'] ) : date( 'Y-m-d H:i:s', time() ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

		// maybe set premium content
		if ( isset( $value['content']['premium']['web'] ) ) {
			$data['meta']['premium_content'] = $value['content']['premium']['web'];
		}

		return $data;
	}

	/**
	 * Add notice for manual import
	 */
	public function register_progress_notice() {

		$group_name = get_transient( 'RE_BEEHIIV_manual_import_group' );
		$is_running = get_transient( 'RE_BEEHIIV_manual_import_running' );

		if ( empty( $group_name ) ) {

			if ( $is_running ) {
				echo "<div class='notice notice-info' id='re_beehiiv_progress_notice'>";
				echo '<p>Importing posts from Beehiiv is in progress. Be patient, this may take a while.';
				echo '</div>';
				return;
			}

			return;
		}

		$complete_actions = $this->get_queue()->get_manual_actions( $group_name, 'complete' );
		$all_actions      = $this->get_queue()->get_manual_actions( $group_name );

		if ( empty( $all_actions ) ) {
			delete_transient( 'RE_BEEHIIV_manual_import_group' );
			delete_transient( 'RE_BEEHIIV_manual_import_running' );
			return;
		}
		if ( count( $complete_actions ) === count( $all_actions ) ) {
			echo "<div class='notice notice-success is-dismissible' id='re_beehiiv_progress_notice'>";
			echo '<p>Importing posts from Re Beehiiv is complete.</p>';
			echo "<button type='button' class='notice-dismiss'><span class='screen-reader-text'>Dismiss this notice.</span></button>";
			echo '</div>';
			delete_transient( 'RE_BEEHIIV_manual_import_group' );
			delete_transient( 'RE_BEEHIIV_manual_import_running' );
			return;
		}

		$failed_actions = $this->get_queue()->get_manual_actions( $group_name, 'failed' );

		if ( count( $failed_actions ) === count( $all_actions ) ) {
			echo "<div class='notice notice-error is-dismissible' id='re_beehiiv_progress_notice'>";
			echo '<p>Importing posts from Re Beehiiv has failed.</p>';
			echo "<button type='button' class='notice-dismiss'><span class='screen-reader-text'>Dismiss this notice.</span></button>";
			echo '</div>';
			delete_transient( 'RE_BEEHIIV_manual_import_group' );
			delete_transient( 'RE_BEEHIIV_manual_import_running' );
			return;
		}

		if ( count( $failed_actions ) + count( $complete_actions ) === count( $all_actions ) ) {
			echo "<div class='notice notice-warning is-dismissible' id='re_beehiiv_progress_notice'>";
			echo '<p>Importing posts from Re Beehiiv is complete, but some posts failed to import.</p>';
			echo '<p>Failed posts: ' . count( $failed_actions ) . '/' . count( $all_actions ) . '</p>';
			echo '</div>';
			delete_transient( 'RE_BEEHIIV_manual_import_group' );
			delete_transient( 'RE_BEEHIIV_manual_import_running' );
			return;
		}

		// notice with progress bar
		echo "<div class='notice notice-info' id='re_beehiiv_progress_notice'>";
		echo '<p>Importing posts from Beehiiv is in progress. Be patient, this may take a while.';
		echo '<strong> Progress: ' . count( $complete_actions ) . ' / ' . count( $all_actions ) . '</strong></p>';
		echo '</div>';
	}

	/**
	 * Change heartbeat interval while manual import is running
	 * Filter: heartbeat_settings
	 *
	 * @param array $settings
	 * @return array
	 */
	public function change_heartbeat_while_process_is_running( $settings ) {
		$is_running = get_transient( 'RE_BEEHIIV_manual_import_running' );

		if ( $is_running ) {
			$settings['interval'] = 10;
		}

		return $settings;
	}

}
