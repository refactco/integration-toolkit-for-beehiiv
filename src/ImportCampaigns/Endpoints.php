<?php
/**
 * This File Contains the Endpoints Class of the Plugin.
 *
 * @package ITFB\ImportCampaigns;
 * @since 2.0.0
 */

namespace ITFB\ImportCampaigns;

use ITFB\ImportCampaigns\Helper;
use ITFB\ImportCampaigns\BackgroundProcessing\ImportCampaignsProcess;

defined( 'ABSPATH' ) || exit;

/**
 * The Endpoints class.
 *
 * Handles the Endpoints functionality of the plugin.
 *
 * @since      2.0.0
 * @package    ITFB\ImportCampaigns
 */
class Endpoints {

	/**
	 * Total queued campaigns result.
	 *
	 * @var int $total_queued_campaigns_result
	 * @since 2.0.0
	 */
	public $total_queued_campaigns_result = 0;

	/**
	 * The import campaigns process.
	 *
	 * @var ImportCampaignsProcess $import_campaigns_process
	 */
	public $import_campaigns_process;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 *
	 * @since    2.0.0
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'handle_background_processes' ) );
		add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );
		add_action( 'itfb_import_campaigns', array( $this, 'handle_scheduled_import' ) );
		add_action( 'init', Helper::class . '::include_action_scheduler' );
	}

	/**
	 * Register the endpoints.
	 *
	 * @since 2.0.0
	 */
	public function register_endpoints() {
		register_rest_route(
			'itfb/v1',
			'/import-defaults-options',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'import_defaults_options' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'itfb/v1',
			'/import-campaigns',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'import_campaigns' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'itfb/v1',
			'/import-status',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'import_status' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'itfb/v1',
			'/manage-import-job',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'manage_import_job' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'itfb/v1',
			'/get-scheduled-imports',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_scheduled_imports' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'itfb/v1',
			'/delete-scheduled-import/',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_scheduled_import' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Get import defaults options.
	 *
	 * @param    \WP_REST_Request $request   The request object.
	 * @since    1.0.0
	 */
	public function import_defaults_options( \WP_REST_Request $request ) {
		$data = array();

		// All post types and taxonomies and terms.
		$data = array_merge( $data, Helper::get_all_post_types_tax_term() );

		// Current server time.
		$data['current_server_time'] = gmdate( '(D) H:i' );

		// All post statuses.
		$data = array_merge( $data, Helper::get_all_post_statuses() );

		// All authors users.
		$data = array_merge( $data, Helper::get_all_authors() );

		return rest_ensure_response( $data );
	}

	/**
	 * Import campaigns.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function import_campaigns( $request ) {
		// Get all parameters.
		$params = array(
			'credentials'       => json_decode( sanitize_text_field( $request->get_param( 'credentials' ) ), true ),
			'audience'          => sanitize_text_field( $request->get_param( 'audience' ) ),
			'post_status'       => json_decode( sanitize_text_field( $request->get_param( 'post_status' ) ), true ),
			'schedule_settings' => json_decode( sanitize_text_field( $request->get_param( 'schedule_settings' ) ), true ),
			'post_type'         => sanitize_text_field( $request->get_param( 'post_type' ) ),
			'taxonomy'          => sanitize_text_field( $request->get_param( 'taxonomy' ) ),
			'taxonomy_term'     => sanitize_text_field( $request->get_param( 'taxonomy_term' ) ),
			'author'            => sanitize_text_field( $request->get_param( 'author' ) ),
			'import_cm_tags_as' => sanitize_text_field( $request->get_param( 'import_cm_tags_as' ) ),
			'import_option'     => sanitize_text_field( $request->get_param( 'import_option' ) ),
		);

		// Validate all parameters.

		$validation = Validator::validate_all_parameters( $params );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$this->total_queued_campaigns_result = ( new ImportCampaigns( $params, $this->import_campaigns_process, 'manual' ) )->fetch_and_push_campaigns_to_import_queue();

		if ( is_wp_error( $this->total_queued_campaigns_result ) ) {
			return $this->total_queued_campaigns_result;
		}

		$output = array(
			'message'                => $this->total_queued_campaigns_result['total_queued_campaigns'] . ' campaigns are being fetched and pushed to the import queue.',
			'total_queued_campaigns' => $this->total_queued_campaigns_result['total_queued_campaigns'],
			'group_name'             => $this->total_queued_campaigns_result['group_name'],
		);

		if ( 'on' === $params['schedule_settings']['enabled'] ) {
			$schedule_import_result = Helper::schedule_import_campaigns( $params );
			if ( is_wp_error( $schedule_import_result ) ) {
				$output['schedule_id'] = $schedule_import_result->get_error_message();
			} else {
				$output['schedule_id'] = $schedule_import_result;
			}
		}

		return rest_ensure_response( $output );
	}



	/**
	 * Handle scheduled import.
	 *
	 * @param array $params The parameters array.
	 */
	public function handle_scheduled_import( $params ) {
		$this->total_queued_campaigns = ( new ImportCampaigns( $params, $this->import_campaigns_process, 'auto' ) )->fetch_and_push_campaigns_to_import_queue();
	}

	/**
	 * Get import status.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function import_status( \WP_REST_Request $request ) {
		$group_name = $request->get_param( 'group_name' );
		// check if the group name is set.
		if ( ! $group_name ) {
			return new \WP_Error( 'no_group_name', 'Group name is required.', array( 'status' => 400 ) );
		}

		if ( $this->import_campaigns_process->is_active() ) {

			if ( $this->import_campaigns_process->is_paused() ) {
				$output['status'] = 'paused';
			} else {
				$output['status'] = 'active';
			}

			$remaining_campaigns = ImportTable::get_remaining_campaigns_count( $group_name );

			$output['remaining_campaigns'] = $remaining_campaigns;

		} else {
			$output['status'] = 'not_active';
		}

		return rest_ensure_response( $output );
	}


	/**
	 * Manage the import job (cancel, pause, resume).
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function manage_import_job( \WP_REST_Request $request ) {
		// Sanitize and retrieve parameters from the request.
		$job_action  = sanitize_text_field( $request->get_param( 'job_action' ) );
		$schedule_id = $request->get_param( 'schedule_id' );
		$group_name  = $request->get_param( 'group_name' );

		// Validate the job_action parameter.
		if ( empty( $job_action ) ) {
			return new \WP_Error(
				'no_job_action',
				__( 'Job action is required.', 'integration-toolkit-for-beehiiv' ),
				array( 'status' => 400 )
			);
		}

		// Check if there is an active import process, required for all actions.
		if ( ! $this->import_campaigns_process->is_active() ) {
			return new \WP_Error(
				'no_active_process',
				__( 'There is no active import process to manage.', 'integration-toolkit-for-beehiiv' ),
				array( 'status' => 400 )
			);
		}

		// Handle different job actions.
		switch ( $job_action ) {
			case 'cancel':
				// Validate that group_name is provided for the cancel action.
				if ( empty( $group_name ) ) {
					return new \WP_Error(
						'no_group_name',
						__( 'Group name is required for canceling the job.', 'integration-toolkit-for-beehiiv' ),
						array( 'status' => 400 )
					);
				}
				$response = $this->handle_cancel_action( $schedule_id, $group_name );
				break;

			case 'pause':
				$this->import_campaigns_process->pause();
				$response = array(
					'status'  => 'paused',
					'message' => __( 'Import process has been paused.', 'integration-toolkit-for-beehiiv' ),
				);
				break;

			case 'resume':
				$this->import_campaigns_process->resume();
				$response = array(
					'status'  => 'resumed',
					'message' => __( 'Import process has been resumed.', 'integration-toolkit-for-beehiiv' ),
				);
				break;

			default:
				return new \WP_Error(
					'invalid_job_action',
					__( 'Invalid job action provided', 'integration-toolkit-for-beehiiv' ),
					array( 'status' => 400 )
				);
		}

		// Return the response.
		return rest_ensure_response( $response );
	}


	/**
	 * Handle the cancellation of an import job.
	 *
	 * @param int    $schedule_id The ID of the schedule.
	 * @param string $group_name The name of the group.
	 * @return array|\WP_Error The response array or WP_Error on failure.
	 */
	private function handle_cancel_action( $schedule_id, $group_name ) {

		// If schedule_id is provided, attempt to delete the action.
		if ( ! empty( $schedule_id ) ) {
			$schedule_id = intval( $schedule_id );

			// Attempt to fetch the action by ID.
			$action = \ActionScheduler::store()->fetch_action( $schedule_id );
			if ( is_null( $action ) || $action instanceof \ActionScheduler_NullAction ) {
				return new \WP_Error(
					'invalid_schedule_id',
					__( 'Schedule ID does not exist.', 'integration-toolkit-for-beehiiv' ),
					array( 'status' => 404 )
				);
			}

			// Attempt to delete the action and handle any exceptions.
			try {
				\ActionScheduler::store()->delete_action( $schedule_id );
			} catch ( \Exception $e ) {
				return new \WP_Error(
					'failed_delete',
					__( 'Failed to delete the scheduled import.', 'integration-toolkit-for-beehiiv' ),
					array(
						'status'  => 500,
						'details' => $e->getMessage(),
					)
				);
			}
		}

		// Attempt to cancel the import process.
		$this->import_campaigns_process->cancel();
		if ( ! $this->import_campaigns_process->is_cancelled() ) {
			return new \WP_Error(
				'failed_cancel',
				__( 'Failed to cancel the import process.', 'integration-toolkit-for-beehiiv' ),
				array( 'status' => 500 )
			);
		}

		// Delete remaining campaigns from the import table.
		try {
			ImportTable::delete_remaining_campaigns( $group_name );
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'failed_delete_campaigns',
				__( 'Failed to delete remaining campaigns from the database.', 'integration-toolkit-for-beehiiv' ),
				array(
					'status'  => 500,
					'details' => $e->getMessage(),
				)
			);
		}

		// Return a success response.
		return array(
			'status'  => 'canceled',
			'message' => __( 'Import process has been canceled and remaining campaigns deleted.', 'integration-toolkit-for-beehiiv' ),
		);
	}



	/**
	 * Get all scheduled imports.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_scheduled_imports( \WP_REST_Request $request ) {
		$group_name = 'itfb_import_campaigns_group';

		// Fetch scheduled actions with the specified group name and status.
		$actions = as_get_scheduled_actions(
			array(
				'group'  => $group_name,
				'status' => \ActionScheduler_Store::STATUS_PENDING,
			),
			'ids'
		);

		// Initialize an array to store formatted actions.
		$formatted_actions = array();

		// Iterate over each action ID to fetch and format the action details.
		foreach ( $actions as $action_id ) {
			$action = \ActionScheduler::store()->fetch_action( $action_id );
			if ( $action ) {
				$formatted_actions[] = array(
					'id'     => $action_id,
					'params' => $action->get_args(),
				);
			}
		}

		// Ensure the response is properly formatted as a REST response.
		return rest_ensure_response( $formatted_actions );
	}

	/**
	 * Delete a scheduled import.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function delete_scheduled_import( \WP_REST_Request $request ) {
		// Retrieve the schedule ID from the request parameters.
		$schedule_id = $request->get_param( 'id' );

		// Check if the schedule ID is valid.
		if ( ! $schedule_id ) {
			return new \WP_Error(
				'invalid_schedule_id',
				'Schedule ID is required.',
				array( 'status' => 400 )
			);
		}

		// Fetch the action with the specified ID.
		$action = \ActionScheduler::store()->fetch_action( intval( $schedule_id ) );
		if ( is_null( $action ) || $action instanceof \ActionScheduler_NullAction ) {
			return new \WP_Error(
				'invalid_schedule_id',
				'Schedule ID does not exist.',
				array( 'status' => 400 )
			);
		}

		try {
			// Attempt to delete the action.
			\ActionScheduler::store()->delete_action( intval( $schedule_id ) );
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'failed_delete',
				'Failed to delete the scheduled import.',
				array( 'status' => 500 )
			);
		}

		// Return a success response.
		return rest_ensure_response(
			array(
				'message' => 'Scheduled import has been deleted.',
				'id'      => $schedule_id,
			)
		);
	}

	/**
	 * Handle the background processes.
	 */
	public function handle_background_processes() {
		$this->import_campaigns_process = new ImportCampaignsProcess();
	}
}
