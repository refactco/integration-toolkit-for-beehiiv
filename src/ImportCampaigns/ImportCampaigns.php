<?php
/**
 * This file is used to fetch campaigns from Beehiiv API
 * 
 * @package ITFB\ImportCampaigns;
 * @since 2.0.0
 */

namespace ITFB\ImportCampaigns;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ImportCampaigns class
 * Handles the  functionality of fetching campaigns from Beehiiv API
 * 
 * @since 2.0.0
 */
class ImportCampaigns {

    /**
     * The total fetched campaigns and pushed to the import queue.
	 * 
	 * @var int $total_queued_campaigns
	 * @since 2.0.0
	 */
	public $total_queued_campaigns = 0;


    /**
	 * The Beehiiv posts endpoint.
	 * 
	 * @var string BEEHIIV_POSTS_ENDPOINT
	 */
    const BEEHIIV_POSTS_ENDPOINT   = '/publications/publicationId/posts';

    /**
     * Past imported campaigns.
     * 
     * @var array $past_imported_campaigns
     */
    protected $past_imported_campaigns;

    /**
     * Import options.
     * 
     * @var array $params
     */
    protected $params;

	/**
	 * The import campaigns process.
	 * 
	 * @var ImportCampaignsProcess $import_campaigns_process
	 * @since 2.0.0
	 */
	public $import_campaigns_process;

    /**
     * ImportCampaigns constructor.
     * 
     * @param array $params The parameters array.
     */
    public function __construct ( $params, $import_campaigns_process ) {
        $this->params = $params;
		$this->import_campaigns_process = $import_campaigns_process;
        $this->past_imported_campaigns = $this->get_previous_imported_campaigns();
    }
    

	/**
	 * Get campaigns from Beehiiv API and push them to the import queue.
	 *
	 * @return array
	 */
	public function fetch_and_push_campaigns_to_import_queue() {
		
		foreach (array_keys($this->params['post_status']) as $status) {
			$result = self::fetch_campaigns_by_status($status);
	
			if (is_wp_error($result)) {
				return $result;
			}
		}
		
		$this->import_campaigns_process->save()->dispatch();

		set_transient('itfb_total_queued_campaigns', $this->total_queued_campaigns, 0);

		return $this->total_queued_campaigns;
	}

	/**
	 * Fetch campaigns by a specific status.
	 *
	 * @param string $status The campaign status.
	 * @return array|WP_Error
	 */
	public function fetch_campaigns_by_status( $status ) {
		if ( ! in_array( $status, array( 'draft', 'confirmed', 'archived' ), true ) ) {
			return new \WP_Error( 'unknown_status', __( 'Unknown status.', 'integration-toolkit-for-beehiiv' ), array( 'status' => 400 ) );
		}
				

		$expand = $this->params['audience'] === 'free' ? 'free_web_content' : 'premium_web_content';

		$page = 1;

		while ( true ) {
			$data = $this->get_campaigns_in_page( $status, $expand, $page );

			if ( isset( $data['error'] ) ) {
				return $data;
			}

			$filtered_campaigns=$this->filter_campaigns($data['data'], $this->past_imported_campaigns, $this->params['import_option']);

			$this->push_campaigns_to_import_queue($filtered_campaigns);

            $this->total_queued_campaigns += count($filtered_campaigns);

			if ( $data['page'] >= $data['total_pages'] ) {
				break;
			}

			$page++;
		}

		return true;
	}

	/**
	 * Get Campaigns in page.
	 * This method returns the Campaigns of a publication in a specific page.
	 *
	 * @param string $status The campaign status.
	 * @param string $expand The expand parameter.
	 * @param int $page The page number.
	 * @return array
	 */
	public function get_campaigns_in_page($status, $expand = '', $page = 1) {
		$api_key = trim( $this->params['credentials']['api_key']);
		$publication_id = trim( $this->params['credentials']['publication_id']);
	
		$route = BeehiivClient::build_route(
			self::BEEHIIV_POSTS_ENDPOINT,
			['publicationId' => $publication_id],
			[
				'page' => $page,
				'limit' => 50,
				'expand' => $expand,
				'status' => $status,
				'audience' =>  $this->params['audience']
			]
		);
	
		$response = BeehiivClient::get($api_key, $route);
	
		if (is_wp_error($response)) {
			return ['error' => $response->get_error_message()];
		}
	
		// Check for rate limiting and handle it
		$headers = wp_remote_retrieve_headers($response);
		if (isset($headers['x-ratelimit-remaining']) && $headers['x-ratelimit-remaining'] == 0) {
			$reset_time = isset($headers['x-ratelimit-reset']) ? (int)$headers['x-ratelimit-reset'] : 60;
			sleep($reset_time + 1); // Adding a buffer of 1 second
			return $this->get_campaigns_in_page($status, $expand, $page);
		}
	
		return json_decode(wp_remote_retrieve_body($response), true);
	}

    /**
     * Get past imported campaigns.
     *
     * @return array|\WP_Error
     */
    public function get_previous_imported_campaigns() {
        // Validate the post_type parameter
        if (empty($this->params['post_type'])) {
            return new \WP_Error(
                'missing_post_type', 
                __('Missing post type.', 'integration-toolkit-for-beehiiv'), 
                ['status' => 400]
            );
        }

        // Sanitize the post_type parameter
        $post_type = sanitize_text_field($this->params['post_type']);

        // Query for past imported campaigns
        $query_args = [
            'post_type'   => $post_type,
            'post_status' => 'any',
            'meta_query'  => [
                [
                    'key'     => 'beehiiv_campaign_id',
                    'compare' => 'EXISTS',
                ],
            ],
            'fields'      => 'ids',
            'numberposts' => -1,
        ];

        $past_imported_posts_ids = get_posts($query_args);

        // Prepare the output array
        $output = [];
        foreach ($past_imported_posts_ids as $post_id) {
            $beehiiv_campaign_id = get_post_meta($post_id, 'beehiiv_campaign_id', true);
            if (!empty($beehiiv_campaign_id)) {
                $output[$beehiiv_campaign_id] = $post_id;
            }
        }

        return $output;
    }


	/**
	 * Filter the campaigns based on the import options.
	 * 
	 * @param array $campaigns The campaigns array.
	 * @param array $past_imported_campaigns The past imported campaigns array.
	 * @param array $import_option The parameters array.
	 * @return array
	 */
	public function filter_campaigns($campaigns, $past_imported_campaigns, $import_option) {
		$output = [];
		foreach ($campaigns as $cm) {
			$is_imported = !empty($past_imported_campaigns) && array_key_exists($cm['id'], $past_imported_campaigns);
	
			switch ($import_option) {
				case 'both':
					if ($is_imported) {
						$cm['wp_status'] = 'existing';
						$cm['wp_post_id'] = $past_imported_campaigns[$cm['id']];
					} else {
						$cm['wp_status'] = 'new';
					}
					$output[] = $cm;
					break;
	
				case 'new':
					if (!$is_imported) {
						$cm['wp_status'] = 'new';
						$output[] = $cm;
					}
					break;
	
				case 'update':
					if ($is_imported) {
						$cm['wp_status'] = 'existing';
						$cm['wp_post_id'] = $past_imported_campaigns[$cm['id']];
						$output[] = $cm;
					}
					break;
			}
		}
	
		return $output;
	}

	/**
	 * Push campaigns to the import queue.
	 * 
	 * @param array $campaigns The campaigns array.
	 * @return void
	 */
	public function push_campaigns_to_import_queue($campaigns) {
		foreach ($campaigns as $campaign) {
			$item= [
				'campaign' => $campaign,
				'params' => $this->params,
			];
			$this->import_campaigns_process->push_to_queue($item);
		}
	}
}
