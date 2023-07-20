<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing
namespace Re_Beehiiv\Lib\BulkProcess;

use Re_Beehiiv\Lib\Logger;

/**
 * Class Bulk_Process
 * This class is responsible for handling the bulk process.
 */
abstract class Bulk_Process {
    /**
     * Action name
     * @var string
     */
    protected $action = '';

    /**
     * Items to process
     * @var array
     */
    protected $items = array();

    /**
     * Logger
     * @var Logger
     */
    protected $logger;

    /**
     * Args
     * @var array
     */
    protected array $args = array();

    public bool $is_ready = true;

    /**
	 * Initialize
     *
     * @return void
	 */
	public function __construct( $args = array(), Logger $logger = null ) {
		if ( ! empty( $logger ) ) {
            $this->logger = $logger;
        }

        $this->args = $args;

        $this->setup();

	}

    /**
     * Progress
     *
     * @var int
     */
    protected $progress = 0;

    /**
     * Get items
     *
     * @return array
     */
    public function get_items() : array {
        return $this->items;
    }

    /**
     * Set items
     *
     * @param array $item
     * @return void
     */
    public function add_item( array $item ) {
        $this->items[] = $item;
    }

    /**
     * Setup
     *
     * @return void
     */
    abstract public function setup();

    /**
     * Process
     * Runs the process.
     *
     * @return void
     */
    public function run_process() {
        $this->pre_process();

        $this->process();

        $this->after_process();
    }



    /**
     * After process
     * Runs after the process is complete.
     *
     * @return void
     */
    abstract protected function after_process();

    /**
     * Pre process
     * Runs before the process is complete.
     *
     * @return void
     */
    abstract protected function pre_process();

    /**
     * Process
     * Runs the process.
     *
     * @return void
     */
    abstract protected function process();

    /**
     * Mark item
     *
     * @param mixed $item
     * @return void
     */
    abstract protected function mark_item( $item, $status = 'success' );

    /**
     * Get progress
     *
     * @return int
     */
    public function get_progress() : int {
        return $this->progress;
    }

    public function get_items_count() : int {
        return count( $this->items );
    }

    public function get_processed_count() : int {
        return $this->progress;
    }

    public function get_percentage() : float {
        $total_items     = $this->get_items_count();
		$total_processed = $this->get_processed_count();
        return (float) number_format( (float) ( ! empty( $total_items ) ) ? 100 - ( ( ( $total_items - $total_processed ) / $total_items ) * 100 ) : 0, 2, '.', '' );
    }
    
}