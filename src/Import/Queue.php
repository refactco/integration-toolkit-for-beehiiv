<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing

namespace Re_Beehiiv\Import;

/**
 * Class Queue
 * This class is responsible for adding the request to the queue
 *
 * @package Re_Beehiiv\Import
 */
class Queue {
	// Constants
	const TIMESTAMP_4_SEC   = 4;
	const TIMESTAMP_2_MIN   = 2 * MINUTE_IN_SECONDS;
	const TIMESTAMP_30_MIN  = 1800;
	const TIMESTAMP_1_HOUR  = 3600;
	const TIMESTAMP_2_HOUR  = 7200;
	const TIMESTAMP_12_HOUR = 43200;
	const TIMESTAMP_1_DAY   = 86400;
	const TIMESTAMP_7_DAY   = 604800;
	const MAX_RETRY_COUNT   = 3;

	/**
	 * Action name
	 *
	 * @var string
	 */
	private $action = 're_beehiiv_bulk_import';

	/**
	 * Timestamp
	 *
	 * @var int
	 */
	private $timestamp = MINUTE_IN_SECONDS;

	/**
	 * Queue constructor.
	 *
	 * @param array $request
	 * @param string $group_name
	 * @param int $time_stamp
	 */
	public function push_to_queue( $request, $group_name, $time_stamp ) {
		$this->timestamp = $time_stamp;

		if ( as_has_scheduled_action( $this->action, $request, $group_name ) === false ) {
			as_schedule_single_action( time() + $this->timestamp, $this->action, $request, $group_name );
		}
	}

	/**
	 * Add a recurring task
	 * Used for auto import
	 *
	 * @param array $request
	 */
	public function add_recurrence_task( $request ) {
		$cron_time = $request['args']['cron_time'];
		$timestamp = $cron_time * self::TIMESTAMP_1_HOUR;
		if ( as_has_scheduled_action( $this->action, $request, $request['group'] ) === false ) {
			as_schedule_recurring_action( time() + $timestamp, $timestamp, $this->action, $request, $request['group'] );
		}
	}


	/**
	 * Queue task callback
	 *
	 * @param string $group_name
	 * @param array $args
	 */
	public function queue_callback( $group_name, $args ) {

		if ( isset( $args['auto'] ) && $args['auto'] === 'auto' ) {
			$this->auto_import_callback( $group_name, $args );
			return;
		}

		$request_key = $this->get_request_key( $args['id'] );
		$retry_count = get_transient( $request_key );
		if ( $retry_count === false || $retry_count < self::MAX_RETRY_COUNT ) {
			$res = ( new Create_Post( $args, $group_name ) )->process();
			if ( $res['success'] === false ) {
				$retry_count = $retry_count === false ? 1 : $retry_count + 1;
				set_transient( $request_key, $retry_count, self::TIMESTAMP_2_MIN );
			} else {
				delete_transient( $request_key );
			}
		}
	}

	/**
	 * Auto import callback
	 *
	 * @param string $group_name
	 * @param array $args
	 */
	public function auto_import_callback( $group_name, $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed
		( new Import() )->run_auto_import( $args );
	}

	/**
	 * Queue handler
	 */
	public function queue_handler() {
		add_action( $this->action, array( $this, 'queue_callback' ), 10, 2 );
	}

	/**
	 * Get the request key
	 *
	 * @param array $request
	 * @return string
	 */
	public function get_request_key( $request ) {
		return 're_beehiiv_' . md5( wp_json_encode( $request ) );
	}
}
