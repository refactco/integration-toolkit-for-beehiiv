<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing

namespace Re_Beehiiv\Import;
use Re_Beehiiv\Import\Interfaces\Importer;
use Re_Beehiiv\Import\Queue;
use Re_Beehiiv\Lib\Logger;

defined( 'ABSPATH' ) || exit;

class Manual_Importer extends Importer {

    /**
     * Importer constructor.
     *
     * @param array $form_data
     * @param string $group_name
     * @param string $method
     */
    public function __construct($form_data, $group_name, $method, $mode = 'Queue') {
        $this->form_data = $form_data;
        $this->group_name = $group_name;
        $this->method = $method;
        $this->mode = $mode;

        $this->logger = new Logger( $this->group_name );

        $data = self::get_import_progress();


        if ( ! empty ( $data ) && $data['status'] !== 'finished' ) {

            $this->form_data = $data['form_data'];
            $this->group_name = $data['group_name'];
            $this->method = $data['method'];
            $this->mode = $data['mode'];
            $this->status = $data['status'];
            $this->get_data_method = $data['get_data_method'];
            $this->import_method = $data['import_method'];
            $this->posts_progress = $data['posts_progress'];
            $this->progress_data = $data;
        } else {
            if ( $this->mode === 'Queue' ) {
                $this->queue = new Queue();
                $this->get_data_method = 'full';
                $this->import_method = 'at_once';
            } elseif ( $this->mode === 'AJAX' ) {
                $this->get_data_method = 'page_by_page';
                $this->import_method = 'page_by_page';
            }
        }


    }

    public function import() {

        if ( $this->status === 'start' ) {
            $this->update_import_progress( 'start' );
        }

        if ( $this->status === 'start' || $this->status === 'getting_data' ) {
            $this->get_data_from_beehiiv();
        } elseif ( $this->status === 'data_ready' || $this->status === 'importing_post' ) {
            $this->import_data_to_wordpress();
            $this->status = 'importing_post';
        } elseif ( $this->status === 'finished' ) {
            delete_option( 're_beehiiv_manual_import_progress' );
        }
    }

    protected function get_data_from_beehiiv() {
        if ( $this->get_data_method === 'page_by_page' ) {
            $args = array(
                'group_name'    => $this->group_name,
                'content_types' => $this->form_data['content_type'],
                'page'          => $this->progress_data['page'] ?? 1,
                'total_pages'   => $this->progress_data['total_pages'] ?? 1,
                'method'        => 'page_by_page',
            );
        } elseif ( $this->get_data_method === 'at_once' ) {
            $args = array(
                'group_name'    => $this->group_name,
                'content_types' => $this->form_data['content_type'],
                'page'          => $this->progress_data['page'],
                'method'        => 'at_once',
            );
        }

        $process = new Process_Pages( 
            $args,
            $this->logger
        );

        // prepare items and save to custom table
        $items = $process->get_items();
        $prepared_items = $this->prepare_data_for_saving_to_custom_table( $items );

        $this->save_items_to_custom_table( $prepared_items );

        $progress_data = $process->get_progress_data();

        $this->update_import_progress( $progress_data['status'], $progress_data );

        return $progress_data;
    }


    protected function import_data_to_wordpress() {
        if ( $this->mode === 'Queue' ) {
            $is_imported = $this->import_data_using_queue();
        } elseif ( $this->mode === 'AJAX' ) {
            $is_imported = $this->import_data_using_ajax();
        }

        if ( ! $is_imported ) {
            $this->update_import_progress( 'finished' );
        } else {
            $this->update_import_progress( 'importing_post' );
        }

        return $is_imported;
    }

    protected function import_data_using_queue() {
        // TODO: Add items to queue and track progress queue progress
    }

    protected function import_data_using_ajax() {
        $process_item = new Process_Item( 
            array(
                'group_name' => $this->group_name,
                'form_data'  => $this->form_data,
                'status'     => $this->status,
            ),
            $this->logger
        );

        $posts_progress = $process_item->get_progress_data();
        $this->posts_progress = array_merge( $this->posts_progress, $posts_progress );

        if ( ! $process_item->is_ready ) {
            return false; // that means we have no items to import
        }
        
        $process_item->run_process();
        return true;
    }

    public function update_import_progress( $status = 'running', $args = array() ) {

        $data = array(
            'status' => $status,
            'group_name' => $this->group_name,
            'form_data' => $this->form_data,
            'method' => $this->method,
            'mode' => $this->mode,
            'get_data_method' => $this->get_data_method,
            'import_method' => $this->import_method,
            'page' => 1,
            'posts_progress' => $this->posts_progress ?? array(),
        );

        $args = array_merge( $data, $args );

        return update_option( 're_beehiiv_manual_import_progress', $args );
    }
}
