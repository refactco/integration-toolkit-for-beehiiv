<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing
namespace Re_Beehiiv\Lib;

defined( 'ABSPATH' ) || exit;

/**
 * Class Logger
 *
 * This class is used to log the import process.
 *
 * @package Re_Beehiiv\Lib
 */
class Logger {

	/**
	 * Group name
	 *
	 * @var string
	 */
	protected $group_name;

	/**
	 * Logger constructor.
	 *
	 * @param string $group_name
	 */
	public function __construct( string $group_name = '' ) {
		$this->group_name = $this->group_name . $group_name;
	}

	/**
	 * Get logs
	 *
	 * @return array
	 */
	public function get_logs() : array {
		$log = get_transient( $this->group_name );
		if ( false === $log ) {
			$log = array();
		}
		return $log;
	}

	/**
	 * Log
	 *
	 * @param array $log_item
	 */
	public function log( array $log_item, bool $print_in_debug_file = false ) {
		$log              = $this->get_logs();
		$log_item['time'] = current_time( 'mysql' );
		$log[]            = $log_item;

		if ( $print_in_debug_file && ( ( defined('WP_DEBUG') && WP_DEBUG ) || ( defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ) ) ) {
			\error_log( print_r( $log_item, true ) );
		}

		set_transient( $this->group_name, $log, 60 * 60 * 24 );
	}

	/**
	 * Clear log
	 */
	public function clear_log() {
		delete_transient( $this->group_name );
	}
}
