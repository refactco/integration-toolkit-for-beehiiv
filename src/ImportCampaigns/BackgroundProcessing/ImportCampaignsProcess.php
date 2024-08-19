<?php
/**
 * Background process for importing fetched campaigns into WordPress.
 *
 * @package ITFB_Beehiiv
 * @subpackage Importcampaigns\BackgroundProcessing
 * @since 1.0.0
 */

namespace ITFB\ImportCampaigns\BackgroundProcessing;

use WP_Background_Process;
use ITFB\ImportCampaigns\Helper;
use ITFB\ImportCampaigns\ImportTable;

/**
 * Processes for importing fetched campaigns into WordPress.
 */
class ImportCampaignsProcess extends WP_Background_Process {

	/**
	 * The prefix for the background process.
	 *
	 * @var string
	 */
	protected $prefix = 'ITFB_Beehiiv';

	/**
	 * The batch size for the background process.
	 *
	 * @var int Batch size
	 */
	protected $batch_size = 5;

	/**
	 * The action name for importing campaigns.
	 *
	 * @var string
	 */
	protected $action = 'import_campaigns';

	/**
	 * Perform task with queued item.
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over.
	 *
	 * @return mixed
	 */
	protected function task( $item ) {
		$this->import_campaign( unserialize( $item ) );
		return false;
	}

	/**
	 * Complete processing.
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		parent::complete();
	}

	/**
	 * Import the campaign.
	 *
	 * @param object $item The campaign object.
	 */
	protected function import_campaign( $item ) {

		// get the campaign from import table.
		$item['campaign'] = ImportTable::get_and_decode_campaign_data( trim( $item['campaign_id'] ), trim( $item['group_name'] ) );
		// delete the campaign from import table.
		ImportTable::delete_custom_table_row( trim( $item['campaign_id'] ), trim( $item['group_name'] ) );

		$content_type = ( 'free' === $item['params']['audience'] || 'all' === $item['params']['audience'] ) ? 'free' : 'premium';
		$wp_post_args = array(
			'post_title'   => sanitize_text_field( $item['campaign']['title'] ),
			'post_slug'    => sanitize_title( $item['campaign']['slug'] ),
			'post_content' => Helper::filter_campaign_content( $item['campaign']['content'][ $content_type ]['web'] ),
			'post_status'  => sanitize_text_field( $item['params']['post_status'][ $item['campaign']['status'] ] ),
			'post_type'    => sanitize_text_field( $item['params']['post_type'] ),
		);

		// Set the post date.
		if ( ! empty( $item['campaign']['publish_date'] ) ) {
			// Convert Unix timestamp to GMT date and time.
			$post_date_gmt = gmdate( 'Y-m-d H:i:s', $item['campaign']['publish_date'] );

			$wp_post_args['post_date']     = get_date_from_gmt( $post_date_gmt );
			$wp_post_args['post_date_gmt'] = $post_date_gmt;
		}

		// Set the post taxonomy and term.
		if ( ! empty( $item['params']['taxonomy'] ) && ! empty( $item['params']['taxonomy_term'] ) ) {
			$term = term_exists( intval( $item['params']['taxonomy_term'] ), $item['params']['taxonomy'] );

			if ( $term ) {
				$wp_post_args['tax_input'] = array(
					$item['params']['taxonomy'] => array( $term['term_id'] ),
				);
			}
		}

		// Add campaign tags as post categories or tags.
		if ( ! empty( $item['campaign']['content_tags'] ) ) {
			$tags = $item['campaign']['content_tags'];

			if ( 'category' === $item['params']['import_cm_tags_as'] ) {
				$tag_ids = array();
				foreach ( $tags as $tag ) {
					$term = term_exists( $tag, 'category' );
					if ( ! $term ) {
						$term = wp_insert_term( $tag, 'category' );
					}
					$tag_ids[] = $term['term_id'];
				}
				$wp_post_args['post_category'] = $tag_ids;
			} elseif ( 'post_tag' === $item['params']['import_cm_tags_as'] ) {
				$wp_post_args['tags_input'] = $tags;
			}
		}

		// Set post author.
		if ( ! empty( $item['params']['author'] ) ) {
			$wp_post_args['post_author'] = intval( $item['params']['author'] );
		}

		// Set post meta.
		$wp_post_args['meta_input'] = array(
			'beehiiv_campaign_id'     => $item['campaign']['id'],
			'beehiiv_web_version_url' => $item['campaign']['web_url'],
			'beehiiv_authors'         => serialize( $item['campaign']['authors'] ),
			'beehiiv_audience'        => serialize( $item['campaign']['audience'] ),
			'write_description'       => $item['campaign']['subtitle'],
		);

		// Base on the campaign wp_status we will decide to update or insert the post.
		if ( 'existing' === $item['campaign']['wp_status'] ) {
			$wp_post_args['ID'] = $item['campaign']['wp_post_id'];
			// Update the post.
			$post_id = wp_update_post( $wp_post_args );
		} else {
			// Insert the post.
			$post_id = wp_insert_post( $wp_post_args );
		}

		// Set the post thumbnail using the featured image.
		if ( ! empty( $item['campaign']['thumbnail_url'] ) ) {
			$thumbnail_id = Helper::itfb_set_post_thumbnail( $post_id, $item['campaign']['thumbnail_url'] );
			if ( $thumbnail_id ) {
				set_post_thumbnail( $post_id, $thumbnail_id );
			}
		}
	}
}
