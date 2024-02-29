<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing

namespace Integration_Toolkit_For_Beehiiv\Import;
use Integration_Toolkit_For_Beehiiv\API\V2\Posts;

defined( 'ABSPATH' ) || exit;


class Process_Pages extends \Integration_Toolkit_For_Beehiiv\Lib\Bulk_Process {

    protected int $total_pages = 1;

    protected int $current_page_index = 1;

    protected string $current_page_status = 'running';

    protected array $progress_data = array();
    
    protected string $method = 'page_by_page';

    public function setup() {


        $this->action = 'import_page_by_page';

        $this->total_pages        = $this->args['total_pages'] ?? 1;
        $this->current_page_index = $this->args['page'] ?? 1;
        $this->method             = $this->args['method'] ?? 'page_by_page';

        $this->progress_data = $this->args;

        $this->run_process();

    }

    public function process() {
       // get the data from the API
		if ( in_array( 'premium_web_content', $this->progress_data['content_types'], true ) ) {

			$data                = Posts::get_posts_in_page( $this->current_page_index, 'free_web_content' );
			$premium_web_content = Posts::get_posts_in_page( $this->current_page_index, 'premium_web_content' );

			foreach ( $data as $key => $value ) {
				if ( isset( $premium_web_content[ $key ]['content']['premium']['web'] ) ) {
					$data[ $key ]['content']['premium']['web'] = $premium_web_content[ $key ]['content']['premium']['web'];
				}
			}
		} else {
			$data = Posts::get_posts_in_page( $this->current_page_index, implode( '', $this->progress_data['content_types'] ) );
		}

        if ( ! $data || ! isset( $data['data'] ) ) {
            $error_msg = isset( $data['errors'] ) && isset( $data['errors'][0] ) ? $data['errors'][0] : array();
            $this->mark_item( $this, 'error' );
            return false;
        }

        $this->mark_item( $this, 'success' );
        $this->total_pages = $data['total_pages'];

        $this->items = $data['data'];

        // save.

        return $data['data'];
    }

    public function pre_process() {
        if ( $this->total_pages > 1 ) {
            $this->current_page_index++;
        }
    }

    public function after_process() {
        $data = array(
            'status'     => 'getting_data',
            'total_pages' => $this->total_pages,
            'page'       => $this->current_page_index,
        );

        if ( $this->current_page_index >= $this->total_pages ) {
            $data['status'] = 'data_ready';
        }
        
        $this->progress_data = $data;
    }

    public function mark_item( $data, $status = 'success' ) {
        $this->current_page_status = $status;

        if ( ! empty( $this->logger ) ) {


            if ( 'error' === $status ) {
                $this->logger->log(
                    array(
                        'message' => __('Unable to fetch content from page ', 'integration-toolkit-for-beehiiv') . $this->current_page_index,
                        'status'  => 'error',
                    )
                );
            } elseif ( 'success' === $status ) {
                $this->logger->log(
                    array(
                        'message' => __('Fetched content from page ', 'integration-toolkit-for-beehiiv') . $this->current_page_index,
                        'status'  => 'success',
                    )
                );
            }
        }
    }

    public function get_progress_data() {
        return $this->progress_data;
    }

}
