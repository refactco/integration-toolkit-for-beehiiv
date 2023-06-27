<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing
namespace Re_Beehiiv\Import;

defined( 'ABSPATH' ) || exit;

/**
 * Class Logger
 *
 * This class is used to log the import process.
 *
 * @package Re_Beehiiv\Import
 */
class Logger {

	/**
	 * Group name
	 *
	 * @var string
	 */
	private $group_name;

	/**
	 * Logger constructor.
	 *
	 * @param string $group_name
	 */
	public function __construct( string $group_name ) {
		$this->group_name = $group_name;
	}

	/**
	 * Get logs
	 *
	 * @return array
	 */
	public function get_logs() : array {
		$log = get_transient( 're_beehiiv_import_log_' . $this->group_name );
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
	public function log( array $log_item ) {
		$log              = $this->get_logs();
		$log_item['time'] = current_time( 'mysql' );
		$log[]            = $log_item;
		set_transient( 're_beehiiv_import_log_' . $this->group_name, $log, 60 * 60 * 24 );
	}

	/**
	 * Clear log
	 */
	public function clear_log() {
		delete_transient( 're_beehiiv_import_log_' . $this->group_name );
	}
}
