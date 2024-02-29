<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing

namespace Integration_Toolkit_For_Beehiiv\Import;
use Integration_Toolkit_For_Beehiiv\API\V2\Posts;

defined( 'ABSPATH' ) || exit;


class Process_Item extends \Integration_Toolkit_For_Beehiiv\Lib\Bulk_Process {

    public function setup() {

        $this->action = 'import_items';

        $pending_items = $this->get_pending_items( $this->args['group_name'] );

        if ( empty( $pending_items ) ) {
            $this->is_ready = false;
            return;
        }

        $this->items = $pending_items;
    }

    protected function process() {
        $post_creator = new Create_Post( $this->items[0], $this->args['group_name'] );
        $is_added = $post_creator->process();

        if ( $is_added['success'] ) {
            $this->mark_item( $this->items[0], 'success' );
        } else {
            $this->mark_item( $this->items[0], 'failed' );
        }

    }

    protected function pre_process() {
        // TODO: Implement retry logic
    }

    protected function after_process() {
        // TODO: Implement retry logic
    }

    protected function mark_item( $data, $status = 'success' ) {
        // TODO: Mark the item as success or failed in the database
    }

    protected function get_pending_items( $group_name ) {
        return Import_Table::get_rows_by_status( 'pending', $group_name );
    }

    public function get_progress_data() {
        $data = array(
            'pending_items' => count( $this->items ),
        );

        if ( $this->args['status'] === 'data_ready' ) {
            $data['total_items'] = count( $this->items );
        }

        return $data;
    }

}
