<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing
namespace Re_Beehiiv\Import;

use Re_Beehiiv\API\V2\Posts;

defined( 'ABSPATH' ) || exit;

/**
 * Class Import
 *
 * This class is used to handle the AJAX request for manual import
 *
 * @package Re_Beehiiv\Import
 */
class Import {

	const FIELD_PREFIX = 're-beehiiv-';
	const FIELDS = array(
		array(
			'name'     => 'content_type',
			'required' => true,
		),
		array(
			'name'     => 'beehiiv-status',
			'required' => true,
		),
		array(
			'name'     => 'post_type',
			'required' => true,
		),
		array(
			'name'     => 'taxonomy',
			'required' => true,
		),
		array(
			'name'     => 'taxonomy_term',
			'required' => true,
		),
		array(
			'name'     => 'post_author',
			'required' => true,
		),
		array(
			'name'     => 'post_tags',
			'required' => true,
		),
		array(
			'name'     => 'import_method',
			'required' => true,
		),
		array(
			'name'     => 'post_status',
			'required' => true,
		),
		array(
			'name'     => 'cron_time',
			'required' => false,
		)
	);

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

	public function maybe_start_manual_import() {
		
		if ( ! isset( $_POST['action'] ) || 're_beehiiv_manual_import' !== $_POST['action'] ) {
			return;
		}

		if ( ! isset( $_POST['re_beehiiv_import_nonce'] ) || ! wp_verify_nonce( $_POST['re_beehiiv_import_nonce'], 're_beehiiv_import_nonce' ) ) {
			return;
		}

		// get the data from the form
		$form_data = $this->get_form_validated_data();

		if ( isset( $form_data['error'] ) ) {
			// show the error message
			add_action( 're_beehiiv_admin_notices', function () use ( $form_data ) {
				?>
				<div class="notice notice-error">
					<p><?php echo esc_html( $form_data['error'] ); ?></p>
				</div>
				<?php
			} );
			return;
		}
		
		$this->run_manual_import( $form_data );
	}

	private function run_manual_import( $form_data ) {
	
		// set an transient to show the user the import is running
		set_transient( 'RE_BEEHIIV_manual_import_running', true, 60 * 60 * 24 );

		$group_name = 'manual_import_' . time();
		$this->start_import( $form_data, $group_name);

		// redirect to import page
		wp_safe_redirect( admin_url( 'admin.php?page=re-beehiiv-import' ) );

	}

	public function run_auto_import( $form_data ) {
		$this->start_import( $form_data, 'auto_recurring_import');
	}

	private function start_import( $form_data, $group_name ) {

		$logger = new Logger( $group_name );
		$logger->log( array(
			'message' => 'Import started',
			'status' => 'running',
		) );

		// get the data from the API
		$data = $this->get_all_data( $form_data['content_type'] );
		if ( ! $data ) {
			// show the error message
			add_action( 're_beehiiv_admin_notices', function () {
				?>
				<div class="notice notice-error">
					<p><?php esc_html_e( 'No data found', 're-beehiiv' ); ?></p>
				</div>
				<?php
			} );

			$logger->log( array(
				'message' => 'No data found',
				'status' => 'error',
			) );

			return false;
		}

		$logger->log( array(
			'message' => 'Data fetched',
			'status' => 'success',
		) );

		if ( 'auto_recurring_import' !== $group_name ) {
			set_transient( 'RE_BEEHIIV_manual_import_group', $group_name, 60 * 60 * 24 );
		}

		$this->maybe_push_to_queue(
			$data,
			array(
				'auto'            => 'manual',
				'form_data'       => $form_data,
				'group'           => $group_name,
			)
		);

		return true;
	}

	public function maybe_register_auto_import() {
		if ( ! isset( $_POST['action'] ) || 're_beehiiv_auto_import' !== $_POST['action'] ) {
			return;
		}

		if ( ! isset( $_POST['re_beehiiv_import_nonce'] ) || ! wp_verify_nonce( $_POST['re_beehiiv_import_nonce'], 're_beehiiv_import_nonce' ) ) {
			return;
		}

		// get the data from the form
		$form_data = $this->get_form_validated_data();

		if ( isset( $form_data['error'] ) ) {
			// show the error message
			add_action( 're_beehiiv_admin_notices', function () use ( $form_data ) {
				?>
				<div class="notice notice-error">
					<p><?php echo esc_html( $form_data['error'] ); ?></p>
				</div>
				<?php
			} );
			return;
		}

		$this->register_auto_import( $form_data );
	}

	private function register_auto_import( array $form_data ) {

		Manage_Actions::remove_auto_actions();

		$group_name = 'auto_recurring_import';

		$req['group'] = $group_name;
		$req['args']  = array(
			'auto' => 'auto',
		);
		$req['args']  = array_merge( $req['args'], $form_data );

		$this->get_queue()->add_recurrence_task( $req );

		// redirect to import page
		wp_safe_redirect( admin_url( 'admin.php?page=re-beehiiv-import&tab=auto-import' ) );
	}

	/**
	 * Validate the form data
	 *
	 * @return array
	 */
	private function get_form_validated_data() {
		
		$form_data = array();

		foreach ( self::FIELDS as $field ) {
			$field_name = self::FIELD_PREFIX . $field['name'];

			if ( !isset( $_POST[ $field_name ] ) ) {
				continue;
			}

			if ( $field['required'] && (!isset($_POST[$field_name]) ||  empty($_POST[$field_name])) ) {

				return array(
					'error'   => true,
					'message' => $field['name'] . ' is required',
				);
			}

			if ( is_array( $_POST[ $field_name ] ) ) {
				$form_data[ $field['name'] ] = array_map( 'sanitize_text_field', $_POST[ $field_name ] );
			} else {
				$form_data[ $field['name'] ] = sanitize_text_field( $_POST[ $field_name ] );
			}
		}

		return $form_data;
	}

	/**
	 * Push data to queue
	 *
	 * @param array $data
	 * @param array $args
	 * @return bool
	 */
	public function maybe_push_to_queue( $data, $args ) {

		$import_interval_s = apply_filters( 're_beehiiv_import_interval', 5 );
		$import_interval = $import_interval_s;
		$import_method   = $args['form_data']['import_method'];
		$args['group']	 = 'auto_recurring_import' === $args['group'] ? 'auto_recurring_import_task' : $args['group'];
		$logger = new Logger( $args['group'] );

		foreach ( $data as $value ) {
			if ( ! $value ) {
				continue;
			}

			// Maybe skip the post based on import method
			if ( $import_method === 'new' ) {
				// check if the post already exists
				if ( $this->is_unique_post( $value['id'] ) ) {
					$logger->log( array(
						'message' => $value['id'] . ' is already exists',
						'status' => 'skipped',
					) );
					continue;
				}
			} elseif ( $import_method === 'update' ) {
				// check if the post already exists
				if ( ! $this->is_unique_post( $value['id'] ) ) {
					$logger->log( array(
						'message' => $value['id'] . ' is not exists',
						'status' => 'skipped',
					) );
					continue;
				}
			}

			// Maybe skip the post based on beehiiv status
			if ( ! in_array( $value['status'], $args['form_data']['beehiiv-status'], true ) ) {
				$logger->log( array(
					'message' => $value['id'] . ' is not in selected status',
					'status' => 'skipped',
				) );
				continue;
			}

			$data = apply_filters( 're_beehiiv_import_before_create_post', $this->prepare_beehiiv_data_for_wp(
				$value,
				$args
			), $value, $args );

			Import_Table::insert_custom_table_row( $value['id'], $data, $args['group'], 'pending' );
			$req['group'] = $args['group'];
			$req['args']  = array(
				'id' => $value['id'],
			);

			$this->get_queue()->push_to_queue( $req, $args['group'], $import_interval );
			$import_interval += $import_interval_s;
		}

		$logger->log( array(
			'message' => 'All posts are pushed to queue. The queue will end in about ' . $import_interval . ' seconds',
			'status' => 'success',
		) );

		return true;
	}

	/**
	 * Get all data from beehiiv
	 *
	 * @param array $content_type
	 * @return array
	 */
	public function get_all_data( array $content_type ) {
		if ( in_array( 'premium_web_content', $content_type, true ) ) {

			$data                = Posts::get_all_posts( 'free_web_content' );
			$premium_web_content = Posts::get_all_posts( 'premium_web_content' );

			foreach ( $data as $key => $value ) {
				if ( isset( $premium_web_content[ $key ]['content']['premium']['web'] ) ) {
					$data[ $key ]['content']['premium']['web'] = $premium_web_content[ $key ]['content']['premium']['web'];
				}
			}
		} else {
			$data = Posts::get_all_posts( 'free_web_content' );
		}

		if ( isset( $data['error'] ) ) {
			// show the error message
			add_action( 're_beehiiv_admin_notices', function () use ( $data ) {
				?>
				<div class="notice notice-error">
					<p><?php echo esc_html( $data['error'] ); ?></p>
				</div>
				<?php
			} );
			return array();
		}

		return $data;
	}

	/**
	 * Get post content
	 *
	 * @param array $content
	 * @param array $content_type
	 * @return string
	 */
	private function get_post_content( $content, array $content_type ) {
		if ( isset( $content['premium']['web'] ) && in_array( 'premium_web_content', $content_type, true ) ) {
			return $content['premium']['web'];
		} elseif ( isset( $content['free']['web'] ) && in_array( 'free_web_content', $content_type, true ) ) {
			return $content['free']['web'];
		} else {
			return '';
		}
	}

	/**
	 * Prepare beehiiv data for creating post in WordPress
	 *
	 * @param array $value
	 * @param array $args
	 * @return array
	 */
	private function prepare_beehiiv_data_for_wp( $value, $args ) {

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
				'content_type' => $args['form_data']['content_type'],
				'status'       => $value['status'],
				'post_id'      => $value['id'],
				'post_url'     => $value['web_url'],
			),
			'auto' => $args['auto'] ?? 'manual',
			'args' => $args,
		);

		// set post status
		if ( $value['status'] === 'confirmed' ) {
			$data['post']['post_status'] = $args['form_data']['post_status'];
		} else {
			$data['post']['post_status'] = 'draft';
		}

		// set content
		if ( isset( $value['content'] ) ) {
			$data['post']['post_content'] = $this->get_post_content( $value['content'], $args['form_data']['content_type'] );
		}

		// set post author
		if ( isset( $args['form_data']['post_author'] ) ) {
			$data['post']['post_author'] = (int)$args['form_data']['post_author'];
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
		
		if ( isset( $_REQUEST['page'] ) && sanitize_text_field( $_REQUEST['page'] ) === 're-beehiiv-import' && isset( $_REQUEST['tab'] ) && sanitize_text_field( $_REQUEST['tab'] ) === 'auto-import' ) {
			return;
		}

		$group_name = get_transient( 'RE_BEEHIIV_manual_import_group' );
		$is_running = get_transient( 'RE_BEEHIIV_manual_import_running' );

		if ( empty( $group_name ) ) {

			if ( $is_running ) {
				add_action( 're_beehiiv_admin_notices', function() use ( $group_name ) {
					$cancel_nonce = wp_create_nonce( 're_beehiiv_cancel_import' );
					$cancel_url = add_query_arg(
						 array(
							'page' => 're-beehiiv-import',
							'cancel' => $group_name,
							'nonce' => $cancel_nonce,
						),
						admin_url( 'admin.php' )
					);
					?>
					<div class="re-beehiiv-import--notice">
						<h4>Importing posts from Beehiiv</h4>
						<span class="description">Importing posts from Beehiiv is in progress. Be patient, this may take a while.</span>
						<a class="re-beehiiv-button-secondary re-beehiiv-button-cancel" id="re-beehiiv-import--cancel" href="<?php echo esc_url( $cancel_url ); ?>">Cancel</a>
					</div>
					<?php
				});
				return;
			}

			return;
		}

		$this->maybe_cancel_import();

		$complete_actions = Manage_Actions::get_actions( $group_name, 'complete' );
		$all_actions      = Manage_Actions::get_actions( $group_name );

		if ( empty( $all_actions ) ) {
			delete_transient( 'RE_BEEHIIV_manual_import_group' );
			delete_transient( 'RE_BEEHIIV_manual_import_running' );
			( new Logger( $group_name ) )->clear_log();
			return;
		}
		if ( count( $complete_actions ) === count( $all_actions ) ) {
			add_action( 're_beehiiv_admin_notices', function() {
				?>
				<div class="re-beehiiv-import--notice">
					<h4 class="mb-0">Importing posts from Re Beehiiv is complete.</h4>
					<p class="description"></p>
				</div>
				<?php
			});
			delete_transient( 'RE_BEEHIIV_manual_import_group' );
			delete_transient( 'RE_BEEHIIV_manual_import_running' );
			( new Logger( $group_name ) )->clear_log();
			return;
		}

		$failed_actions = Manage_Actions::get_actions( $group_name, 'failed' );

		if ( count( $failed_actions ) === count( $all_actions ) ) {
			add_action( 're_beehiiv_admin_notices', function() {
				?>
				<div class="re-beehiiv-import--notice">
					<h4>Importing posts from Re Beehiiv has failed.</h4>
					<p class="description">Check beehiiv credentials or contact plugin author.</p>
				</div>
				<?php
			});
			delete_transient( 'RE_BEEHIIV_manual_import_group' );
			delete_transient( 'RE_BEEHIIV_manual_import_running' );
			( new Logger( $group_name ) )->clear_log();
			return;
		}

		if ( count( $failed_actions ) + count( $complete_actions ) === count( $all_actions ) ) {
			add_action( 're_beehiiv_admin_notices', function() use ( $failed_actions, $all_actions ) {
				?>
				<div class="re-beehiiv-import--notice">
					<h4>Importing posts from Re Beehiiv is complete, but some posts failed to import.</h4>
					<p class="description"><?php echo 'Failed posts: ' . count( $failed_actions ) . '/' . count( $all_actions ); ?></p>
				</div>
				<?php
			});
			delete_transient( 'RE_BEEHIIV_manual_import_group' );
			delete_transient( 'RE_BEEHIIV_manual_import_running' );
			( new Logger( $group_name ) )->clear_log();
			return;
		}

		// notice with progress bar
		add_action( 're_beehiiv_admin_notices', function() use ( $complete_actions, $all_actions, $group_name ) {
			$cancel_nonce = wp_create_nonce( 're_beehiiv_cancel_import' );
			$cancel_url = add_query_arg(
					array(
					'page' => 're-beehiiv-import',
					'cancel' => $group_name,
					'nonce' => $cancel_nonce,
				),
				admin_url( 'admin.php' )
			);
			?>
			<div class="re-beehiiv-import--notice">
				<h4>Importing posts from Beehiiv is in progress.</h4>
				<p class="description">The import process is currently running in the background. You may proceed with your work and close this page, but please be patient and wait until it is complete. It is not possible to initiate another manual import while the current one is still in progress.<br><strong> Progress: <span class="number" id="imported_count"><?php echo count( $complete_actions ) . '</span> / <span class="number" id="total_count">' . count( $all_actions ) ?></span></strong></p>
				<a class="re-beehiiv-button-secondary re-beehiiv-button-cancel" id="re-beehiiv-import--cancel" href="<?php echo esc_url( $cancel_url ); ?>">Cancel</a>
				<?php require_once RE_BEEHIIV_PATH . 'admin/partials/components/progressbar.php'; ?>
			</div>
			<?php
		});
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

	public function is_unique_post( $post_id ) {
		$args  = array(
			'meta_key'       => 're_beehiiv_post_id',
			'meta_value'     => $post_id,
			'post_type'      => 'post',
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

	public function get_progress_bar_data() {

		$group_name = get_transient( 'RE_BEEHIIV_manual_import_group' );
		$is_running = get_transient( 'RE_BEEHIIV_manual_import_running' );

		if ( ! $group_name || ! $is_running ) {
			return;
		}

		$complete_actions = Manage_Actions::get_actions( $group_name, 'complete' );
		$all_actions      = Manage_Actions::get_actions( $group_name );
		$logs			  = ( new Logger( $group_name ) )->get_logs();

		$failed_actions = Manage_Actions::get_actions( $group_name, 'failed' );

		$progress = array(
			'complete' => count( $complete_actions ),
			'all'      => count( $all_actions ),
			'failed'   => count( $failed_actions ),
			'logs'     => $logs,
		);

		wp_send_json( $progress );
		exit;
	}

	public function maybe_cancel_import() {
		if ( ! isset( $_GET['cancel'] ) || ! isset( $_GET['nonce'] ) ) {
			return;
		}

		$group_name = sanitize_text_field( $_GET['cancel'] );
		$nonce      = sanitize_text_field( $_GET['nonce'] );

		if ( ! wp_verify_nonce( $nonce, 're_beehiiv_cancel_import' ) ) {
			return;
		}

		Manage_Actions::remove_actions( $group_name );

		delete_transient( 'RE_BEEHIIV_manual_import_group' );
		delete_transient( 'RE_BEEHIIV_manual_import_running' );
		( new Logger( $group_name ) )->clear_log();


		wp_redirect( admin_url( 'admin.php?page=re-beehiiv-import' ) );
	}

}
