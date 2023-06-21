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
	const FIELDS       = array(
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
			'required' => false,
		),
		array(
			'name'     => 'taxonomy_term',
			'required' => false,
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
			'name'     => 'post_status--confirmed',
			'required' => false,
		),
		array(
			'name'     => 'post_status--draft',
			'required' => false,
		),
		array(
			'name'     => 'post_status--archived',
			'required' => false,
		),
		array(
			'name'     => 'cron_time',
			'required' => false,
		),
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

	/**
	 * Maybe start manual import
	 * This method checks if the user has started a manual import and if so, it starts it
	 * It also checks and validates the data from the form
	 * If the data is not valid, it will show an error message
	 *
	 * @return void
	 */
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
			add_action(
				're_beehiiv_admin_notices',
				function () use ( $form_data ) {
					?>
				<div class="notice notice-error">
					<p><?php echo esc_html( $form_data['error'] ); ?></p>
				</div>
					<?php
				}
			);
			return;
		}

		$this->run_manual_import( $form_data );
	}

	/**
	 * Run manual import
	 * This method starts the manual import
	 * It sets bunch of transients to show the user the import is running
	 * It also redirects the user to the import page
	 *
	 * @param array $form_data The data from the form
	 * @return void
	 */
	private function run_manual_import( $form_data ) {

		$group_name = 'manual_import_' . time();
		// set an transient to show the user the import is running
		set_transient( 'RE_BEEHIIV_manual_import_running', true, 60 * 60 * 24 );
		set_transient( 'RE_BEEHIIV_manual_import_group', $group_name, 60 * 60 * 24 );
		set_transient( 'RE_BEEHIIV_manual_import_group_data', $form_data, 60 * 60 * 24 );

		// redirect to import page
		wp_safe_redirect( admin_url( 'admin.php?page=re-beehiiv-import' ) );

		exit;
	}

	/**
	 * Run auto import
	 * This method used to start the import action for the auto import
	 *
	 * @param array $form_data The data from the form
	 * @return void
	 */
	public function run_auto_import( $form_data ) {
		$this->start_import( $form_data, 'auto_recurring_import' );
	}

	/**
	 * Start import
	 * This method will Fetch the data from the API and push it to the queue
	 *
	 * @param array $form_data The data from the form
	 * @param string $group_name The group name of the import
	 * @return bool
	 */
	private function start_import( $form_data, $group_name ) {

		$logger = new Logger( $group_name );
		$logger->log(
			array(
				'message' => 'Import started',
				'status'  => 'running',
			)
		);

		// get the data from the API
		$data = $this->get_all_data( $form_data['content_type'] );
		if ( ! $data ) {
			// show the error message
			add_action(
				're_beehiiv_admin_notices',
				function () {
					?>
				<div class="notice notice-error">
					<p><?php esc_html_e( 'No data found', 're-beehiiv' ); ?></p>
				</div>
					<?php
				}
			);

			$logger->log(
				array(
					'message' => 'No data found',
					'status'  => 'error',
				)
			);

			return false;
		}

		$logger->log(
			array(
				'message' => 'Data fetched',
				'status'  => 'success',
			)
		);

		$is_anything_added = $this->maybe_push_to_queue(
			$data,
			array(
				'auto'      => 'manual',
				'form_data' => $form_data,
				'group'     => $group_name,
			)
		);

		return $is_anything_added;
	}

	/**
	 * Maybe register auto import
	 * This method checks the data of the auto import form and if it's valid, it will call the register_auto_import method
	 *
	 * @return void
	 */
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
			add_action(
				're_beehiiv_admin_notices',
				function () use ( $form_data ) {
					?>
				<div class="notice notice-error">
					<p><?php echo esc_html( $form_data['error'] ); ?></p>
				</div>
					<?php
				}
			);
			return;
		}

		$this->register_auto_import( $form_data );
	}

	/**
	 * Register auto import
	 * This method used schedule the auto import
	 *
	 * @param array $form_data The data from the form
	 * @return void
	 */
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
	 * Verifying nonce should be done before calling this method
	 *
	 * @return array
	 */
	private function get_form_validated_data() {

		$form_data = array();

		foreach ( self::FIELDS as $field ) {
			$field_name = self::FIELD_PREFIX . $field['name'];

			if ( ! isset( $_POST[ $field_name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				continue;
			}

			if ( $field['required'] && ( ! isset( $_POST[ $field_name ] ) || empty( $_POST[ $field_name ] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing

				return array(
					'error'   => true,
					'message' => sprintf(
						// Translators: %s is a placeholder for the field label. This text is displayed when a required field is left blank.
						__( '%s is required', 're-beehiiv' ),
						$field['label']
					),
				);
			}

			$n = explode( '--', $field['name'] );
			if ( $n[0] === 'post_status' ) {
				$form_data['post_status'][ $n[1] ] = sanitize_text_field( $_POST[ $field_name ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				continue;
			}

			if ( is_array( $_POST[ $field_name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$form_data[ $field['name'] ] = array_map( 'sanitize_text_field', $_POST[ $field_name ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			} else {
				$form_data[ $field['name'] ] = sanitize_text_field( $_POST[ $field_name ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
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

		$import_interval_s = apply_filters( 're_beehiiv_import_interval', 4 );
		$import_interval   = $import_interval_s;
		$import_method     = $args['form_data']['import_method'];
		$args['group']     = 'auto_recurring_import' === $args['group'] ? 'auto_recurring_import_task' : $args['group'];
		$logger            = new Logger( $args['group'] );

		$is_added_to_queue = false;

		foreach ( $data as $value ) {
			if ( ! $value ) {
				continue;
			}

			// Maybe skip the post based on import method
			if ( $import_method === 'new' ) {
				// check if the post already exists
				if ( $this->is_unique_post( $value['id'] ) ) {
					$logger->log(
						array(
							// Translators: %1$s is a placeholder for the post ID, %2$s is a placeholder for the post title.
							'message' => sprintf( __( '%1$s - %2$s is already exists', 're-beehiiv' ), $value['id'], $value['title'] ),
							'status'  => 'skipped',
						)
					);
					continue;
				}
			} elseif ( $import_method === 'update' ) {
				// check if the post already exists
				if ( ! $this->is_unique_post( $value['id'] ) ) {
					$logger->log(
						array(
							// Translators: %1$s is a placeholder for the post ID, %2$s is a placeholder for the post title.
							'message' => sprintf( __( '%1$s - %2$s is not exists', 're-beehiiv' ), $value['id'], $value['title'] ),
							'status'  => 'skipped',
						)
					);
					continue;
				}
			}

			// Maybe skip the post based on beehiiv status
			if ( ! in_array( $value['status'], $args['form_data']['beehiiv-status'], true ) ) {
				$logger->log(
					array(
						// Translators: %1$s is a placeholder for the post ID, %2$s is a placeholder for the post title.
						'message' => sprintf( __( '%1$s - %2$s is not in selected status', 're-beehiiv' ), $value['id'], $value['title'] ),
						'status'  => 'skipped',
					)
				);
				continue;
			}

			$data = apply_filters(
				're_beehiiv_import_before_create_post',
				$this->prepare_beehiiv_data_for_wp(
					$value,
					$args
				),
				$value,
				$args
			);

			Import_Table::insert_custom_table_row( $value['id'], $data, $args['group'], 'pending' );
			$req['group'] = $args['group'];
			$req['args']  = array(
				'id' => $value['id'],
			);

			$is_added_to_queue = true;

			$this->get_queue()->push_to_queue( $req, $args['group'], $import_interval );
			$import_interval += $import_interval_s;
		}

		if ( ! $is_added_to_queue ) {
			$logger->log(
				array(
					'message' => __( 'No posts are pushed to queue', 're-beehiiv' ),
					'status'  => 'success',
				)
			);
			return false;
		}

		if ( $import_interval >= 60 ) {
			// Translators: %d is a placeholder for the import interval.
			$log = sprintf( __( 'All posts are pushed to queue. The queue will end in about %d minutes', 're-beehiiv' ), $import_interval / 60 );
		} else {
			// Translators: %d is a placeholder for the import interval.
			$log = sprintf( __( 'All posts are pushed to queue. The queue will end in about %d seconds', 're-beehiiv' ), $import_interval );
		}

		$logger->log(
			array(
				'message' => $log,
				'status'  => 'success',
			)
		);

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
			add_action(
				're_beehiiv_admin_notices',
				function () use ( $data ) {
					?>
				<div class="notice notice-error">
					<p><?php echo esc_html( $data['error'] ); ?></p>
				</div>
					<?php
				}
			);
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
		if ( ! isset( $args['form_data']['post_status'][ $value['status'] ] ) ) {
			$data['post']['post_status'] = 'draft';
		} else {
			$data['post']['post_status'] = $args['form_data']['post_status'][ $value['status'] ];
		}

		// set content
		if ( isset( $value['content'] ) ) {
			$content                      = $this->get_post_content( $value['content'], $args['form_data']['content_type'] );
			$data['post']['post_content'] = $this->filter_unnecessary_content( $content );
		}

		// set post author
		if ( isset( $args['form_data']['post_author'] ) ) {
			$data['post']['post_author'] = (int) $args['form_data']['post_author'];
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

		if ( isset( $_REQUEST['page'] ) && sanitize_text_field( $_REQUEST['page'] ) === 're-beehiiv-import' && isset( $_REQUEST['tab'] ) && sanitize_text_field( $_REQUEST['tab'] ) === 'auto-import' ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$group_name = get_transient( 'RE_BEEHIIV_manual_import_group' );
		$is_running = get_transient( 'RE_BEEHIIV_manual_import_running' );

		if ( empty( $group_name ) ) {

			if ( $is_running ) {
				add_action(
					're_beehiiv_admin_notices',
					function() {
						?>
					<div class="re-beehiiv-import--notice">
						<h4><?php esc_html_e( 'Importing posts from Beehiiv', 're-beehiiv' ); ?></h4>
						<span class="description"><?php esc_html_e( 'Importing posts from Beehiiv is in progress. Be patient, this may take a while.', 're-beehiiv' ); ?></span>
					</div>
						<?php
					}
				);
				return;
			} elseif ( isset( $_GET['notice'] ) && sanitize_text_field( $_GET['notice'] === 'nothing_to_import' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				add_action(
					're_beehiiv_admin_notices',
					function() {
						?>
					<div class="re-beehiiv-import--notice re-beehiiv-import--notice--success">
						<h4><?php esc_html_e( 'Import Complete', 're-beehiiv' ); ?></h4>
						<span class="description"><?php esc_html_e( 'Data Fetched from Beehiiv successfully but there is nothing new to import based on your settings.', 're-beehiiv' ); ?></span>
					</div>
						<?php
					}
				);
			} elseif ( isset( $_GET['notice'] ) && sanitize_text_field( $_GET['notice'] === 'import_canceled' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				add_action(
					're_beehiiv_admin_notices',
					function() {
						?>
					<div class="re-beehiiv-import--notice">
						<h4><?php esc_html_e( 'Import Canceled', 're-beehiiv' ); ?></h4>
						<span class="description"><?php esc_html_e( 'Importing posts from Beehiiv has been canceled.', 're-beehiiv' ); ?></span>
					</div>
						<?php
					}
				);
			}

			return;
		}

		$this->maybe_cancel_import();

		$complete_actions = Manage_Actions::get_actions( $group_name, 'complete' );
		$all_actions      = Manage_Actions::get_actions( $group_name );

		if ( empty( $all_actions ) ) {

			if ( ! empty( get_transient( 'RE_BEEHIIV_manual_import_group_data' ) ) ) {
				add_action(
					're_beehiiv_admin_notices',
					function() use ( $complete_actions, $all_actions, $group_name ) {
						$cancel_nonce = wp_create_nonce( 're_beehiiv_cancel_import' );
						$cancel_url   = add_query_arg(
							array(
								'page'   => 're-beehiiv-import',
								'cancel' => $group_name,
								'nonce'  => $cancel_nonce,
							),
							admin_url( 'admin.php' )
						);
						?>
					<div class="re-beehiiv-import--notice">
						<h4><?php esc_html_e( 'Importing posts from Beehiiv is in progress.', 're-beehiiv' ); ?></h4>
						<p class="description"><?php esc_html_e( 'The import process is currently running in the background. You may proceed with your work and close this page, but please be patient and wait until it is complete.', 're-beehiiv' ); ?>
						<br><strong><?php esc_html_e( 'Progress: ', 're-beehiiv' ); ?><span class="number" id="imported_count"><?php echo count( $complete_actions ) . '</span> / <span class="number" id="total_count">' . count( $all_actions ); ?></span></strong></p>
						<a class="re-beehiiv-button-secondary re-beehiiv-button-cancel" id="re-beehiiv-import--cancel" href="<?php echo esc_url( $cancel_url ); ?>"><?php esc_html_e( 'Cancel', 're-beehiiv' ); ?></a>
						<?php require_once RE_BEEHIIV_PATH . 'admin/partials/components/progressbar.php'; ?>
					</div>
						<?php
					}
				);
				return;
			}

			$this->remove_import_transient();
			return;
		}
		if ( count( $complete_actions ) === count( $all_actions ) ) {
			add_action(
				're_beehiiv_admin_notices',
				function() {
					?>
				<div class="re-beehiiv-import--notice re-beehiiv-import--notice-success">
					<h4 class="mb-0"><?php esc_html_e( 'Importing posts from Re Beehiiv is complete.', 're-beehiiv' ); ?></h4>
					<p class="description"></p>
				</div>
					<?php
				}
			);
			$this->remove_import_transient();
			return;
		}

		$failed_actions = Manage_Actions::get_actions( $group_name, 'failed' );

		if ( count( $failed_actions ) === count( $all_actions ) ) {
			add_action(
				're_beehiiv_admin_notices',
				function() {
					?>
				<div class="re-beehiiv-import--notice">
					<h4><?php esc_html_e( 'Importing posts from Re Beehiiv has failed.', 're-beehiiv' ); ?></h4>
					<p class="description">
						<?php esc_html_e( 'Check beehiiv credentials or contact plugin author.', 're-beehiiv' ); ?>
					</p>
				</div>
					<?php
				}
			);
			$this->remove_import_transient();
			return;
		}

		if ( count( $failed_actions ) + count( $complete_actions ) === count( $all_actions ) ) {
			add_action(
				're_beehiiv_admin_notices',
				function() use ( $failed_actions, $all_actions ) {
					?>
				<div class="re-beehiiv-import--notice">
					<h4><?php esc_html_e( 'Importing posts from Re Beehiiv is complete, but some posts failed to import.', 're-beehiiv' ); ?></h4>
					<p class="description">
						<?php
						// translators: %1$d is number of failed posts, %2$d is number of all posts
						sprintf( esc_html__( 'Failed posts: %1$d/%2$d', 're-beehiiv' ), count( $failed_actions ), count( $all_actions ) );
						?>
				</div>
					<?php
				}
			);
			$this->remove_import_transient();
			return;
		}

		// notice with progress bar
		add_action(
			're_beehiiv_admin_notices',
			function() use ( $complete_actions, $all_actions, $group_name ) {
				$cancel_nonce = wp_create_nonce( 're_beehiiv_cancel_import' );
				$cancel_url   = add_query_arg(
					array(
						'page'   => 're-beehiiv-import',
						'cancel' => $group_name,
						'nonce'  => $cancel_nonce,
					),
					admin_url( 'admin.php' )
				);
				?>
			<div class="re-beehiiv-import--notice">
				<h4><?php esc_html_e( 'Importing posts from Beehiiv is in progress.', 're-beehiiv' ); ?></h4>
				<p class="description"><?php esc_html_e( 'The import process is currently running in the background. You may proceed with your work and close this page, but please be patient and wait until it is complete.', 're-beehiiv' ); ?>
				<br><strong><?php esc_html_e( 'Progress: ', 're-beehiiv' ); ?><span class="number" id="imported_count"><?php echo count( $complete_actions ) . '</span> / <span class="number" id="total_count">' . count( $all_actions ); ?></span></strong></p>
				<a class="re-beehiiv-button-secondary re-beehiiv-button-cancel" id="re-beehiiv-import--cancel" href="<?php echo esc_url( $cancel_url ); ?>"><?php esc_html_e( 'Cancel', 're-beehiiv' ); ?></a>
				<?php require_once RE_BEEHIIV_PATH . 'admin/partials/components/progressbar.php'; ?>
			</div>
				<?php
			}
		);
	}

	/**
	 * Check if post is unique
	 *
	 * @param int $post_id post id on beehiiv
	 */
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

	/**
	 * Get progress bar data
	 * In the first call, it will start the fetching process
	 * then it will return the progress bar data
	 * Ajax: re_beehiiv_progress_bar_data
	 *
	 * @return void
	 */
	public function get_progress_bar_data() {

		// verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'progress_bar_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce' );
			exit;
		}

		$group_name = get_transient( 'RE_BEEHIIV_manual_import_group' );
		$is_running = get_transient( 'RE_BEEHIIV_manual_import_running' );

		if ( ! $group_name || ! $is_running ) {
			wp_send_json(
				array(
					'complete' => -1,
					'all'      => -1,
					'failed'   => -1,
					'status'   => 'nothing_to_import',
				)
			);
			wp_die();
		}

		$all_actions = Manage_Actions::get_actions( $group_name );

		if ( ! $all_actions ) {

			$form_data = get_transient( 'RE_BEEHIIV_manual_import_group_data' );

			if ( ! $form_data ) {

				$logger = new Logger( $group_name );
				$logger->log(
					array(
						'status'  => 'running',
						'message' => __( 'Waiting for Beehiiv API response', 're-beehiiv' ),
					)
				);

				$logs = $logger->get_logs();

				wp_send_json(
					array(
						'complete' => 0,
						'all'      => 0,
						'failed'   => 0,
						'status'   => 'waiting_for_api_response',
						'logs'     => $logs,
					)
				);
				wp_die();

			}

			delete_transient( 'RE_BEEHIIV_manual_import_group_data' );
			$is_anything_added = $this->start_import( $form_data, $group_name );

			if ( ! $is_anything_added ) {
				$this->remove_import_transient();
				wp_die();
			}

			$all_actions = Manage_Actions::get_actions( $group_name );
		}

		$complete_actions = Manage_Actions::get_actions( $group_name, 'complete' );
		$failed_actions   = Manage_Actions::get_actions( $group_name, 'failed' );

		$logger = ( new Logger( $group_name ) );
		if ( count( $complete_actions ) + count( $failed_actions ) === count( $all_actions ) ) {
			$logger->log(
				array(
					'status'  => 'success',
					'message' => sprintf(
						// translators: %1$d: number of imported posts, %2$d: number of failed posts
						__( 'Import completed successfully. %1$d posts imported and %2$d posts failed. Now you can close this window.', 're-beehiiv' ),
						count( $complete_actions ),
						count( $failed_actions )
					),
				)
			);
		}
		$logs = $logger->get_logs();

		$progress = array(
			'complete' => count( $complete_actions ),
			'all'      => count( $all_actions ),
			'failed'   => count( $failed_actions ),
			'logs'     => $logs,
		);

		wp_send_json( $progress );
		wp_die();
	}

	/**
	 * Maybe cancel import
	 * If cancel button is clicked, it will remove all related actions and redirect to import page
	 *
	 * @return void
	 */
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

		$this->remove_import_transient();

		wp_safe_redirect( admin_url( 'admin.php?page=re-beehiiv-import&notice=import_canceled' ) );

		exit;
	}

	/**
	 * Remove all related transients and actions
	 * This function will be called when import is finished or canceled
	 * It will remove all related transients and actions and clear the log
	 *
	 * @return void
	 */
	private function remove_import_transient() {
		$group_name = get_transient( 'RE_BEEHIIV_manual_import_group' );
		delete_transient( 'RE_BEEHIIV_manual_import_group' );
		delete_transient( 'RE_BEEHIIV_manual_import_running' );
		delete_transient( 'RE_BEEHIIV_manual_import_group_data' );
		( new Logger( $group_name ) )->clear_log();
		Import_Table::delete_row_by_group( $group_name );
	}

	/**
	 * Remove unnecessary content from html
	 * This function will remove unnecessary content from html
	 * It will remove html, head, body, style, class attributes and doctype
	 * It will also remove all inline styles
	 *
	 * @param string $content
	 * @return string
	 */
	private function filter_unnecessary_content( $content ) {
		$pattern = '/<!DOCTYPE.*?>|<head>.*?<\/head>|<body.*?>|<\/body>|style=[\'"].*?[\'"]|<style.*?<\/style>|class=[\'"][^\'"]*[\'"]|<html.*?>|<\/html>/s';

		return preg_replace( $pattern, '', $content );
	}

}
