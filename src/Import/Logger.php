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
     * @var string
     */
    private $group_name;

    public function __construct( string $group_name ) {
        $this->group_name = $group_name;
    }

    public function get_logs() : array {
        $log = get_transient( 're_beehiiv_import_log_' . $this->group_name );
        if ( false === $log ) {
            $log = array();
        }
        return $log;
    }

    public function log( array $log_item ) {
        $log = $this->get_logs();
        $log_item['time'] = current_time( 'timestamp' );
        $log[] = $log_item;
        set_transient( 're_beehiiv_import_log_' . $this->group_name, $log );
    }

    public function clear_log() {
        delete_transient( 're_beehiiv_import_log_' . $this->group_name );
    }
}