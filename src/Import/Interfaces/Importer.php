<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing

namespace Integration_Toolkit_For_Beehiiv\Import\Interfaces;

use Integration_Toolkit_For_Beehiiv\Import\Import_Table;
use Integration_Toolkit_For_Beehiiv\Import\Prepare_Post;
use Integration_Toolkit_For_Beehiiv\Import\Queue;
use Integration_Toolkit_For_Beehiiv\Lib\Logger;

defined( 'ABSPATH' ) || exit;

abstract class Importer {

    /**
     * @var array
     */
    protected $form_data;

    /**
     * @var string
     */
    protected $group_name;

    /**
     * @var string
     */
    protected $method;

    protected string $mode = 'Queue';

    protected string $status = 'start';

    protected array $progress_data = array();

    protected array $posts_progress = array();

    protected string $get_data_method = 'full';

    protected string $import_method = 'at_once';

    protected Logger $logger;

    /**
     * @var Queue
     */
    protected Queue $queue;

    /**
     * Importer constructor.
     *
     * @param array $form_data
     * @param string $group_name
     * @param string $method
     */
    public function __construct($form_data, $group_name, $method) {
        $this->form_data = $form_data;
        $this->group_name = $group_name;
        $this->method = $method;

    }

    abstract protected function import();

    abstract protected function get_data_from_beehiiv();

    public static function get_import_progress() {
        return get_option( 'integration_toolkit_for_beehiiv_manual_import_progress', array() );
    }

    public static function remove_import_progress() {
        delete_option( 'integration_toolkit_for_beehiiv_manual_import_progress' );
    }

    public function maybe_push_to_queue( $prepared_items ) {
        if ( $this->mode === 'AJAX' ) {
            return;
        }
        
        $import_interval_s = apply_filters( 'integration_toolkit_for_beehiiv_import_interval', 5 );
		$import_interval   = $import_interval_s;
        foreach( $prepared_items as $item ) {
            $this->push_to_queue( $item, $import_interval );
            $import_interval += $import_interval_s;
        }
    }

    public function push_to_queue( $item, $interval = 5 ) {

        $req = array(
            'group' => $this->group_name,
            'args'  => array(
                'id' => $item['meta']['post_id'],
            ),
        );

        $this->queue->push_to_queue( $req, $this->group_name, $interval );
    }

    public function save_items_to_custom_table( $items ) {
        foreach( $items as $item ) {
            $this->save_item_to_custom_table( $item );
        }
    }


    public function prepare_data_for_saving_to_custom_table( $items ) {
        $prepared_items = array();
        foreach ( $items as $item ) {

            try {
                $post_data = ( new Prepare_Post( $item, $this->form_data ) )->prepare_post();
            } catch ( \Exception $e ) {
                $this->logger->log( array(
                    'message' => $e->getMessage(),
                    'status' => 'skipped',
                ) );
                continue;
            }
            
            if ( $post_data && is_array( $post_data ) ) {
                $prepared_items[] = $post_data;
            }
		}

        return $prepared_items;
    }

    public function save_item_to_custom_table( array $item ) {
        $item['args']['form_data'] = $this->form_data;
        try {
            Import_Table::insert_custom_table_row( $item['meta']['post_id'], $item, $this->group_name, 'pending' );
        } catch ( \Exception $e ) {
            $this->logger->log( array(
                'message' => __('Error saving item to custom table: ','Integration Toolkit for beehiiv') . $e->getMessage(),
                'status' => 'error',
            ) );
            return;
        }
    }
}