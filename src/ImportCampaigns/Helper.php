<?php
/**
 * This File Contains the Helper Class of the Plugin.
 *
 * @package ITFB\ImportCampaigns;
 * @since 2.0.0
 */

namespace ITFB\ImportCampaigns;

defined( 'ABSPATH' ) || exit;

/**
 * The Helper class.
 *
 * Handles the Helper functionality of the plugin.
 *
 * @since      2.0.0
 * @package    ITFB\ImportCampaigns
 */
class Helper {

	/**
	 * The Beehiiv posts endpoint.
	 *
	 * @var string BEEHIIV_POSTS_ENDPOINT
	 */
	const BEEHIIV_POSTS_ENDPOINT = '/publications/publicationId/posts';

	/**
	 * Get all post types.
	 *
	 * @return array
	 */
	public static function get_all_post_types_tax_term() {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$data       = array( 'post_types' => array() );

		foreach ( $post_types as $post_type ) {
			if ( in_array( $post_type->name, array( 'attachment', 'revision', 'nav_menu_item' ) ) ) {
				continue;
			}

			$post_type_data = array(
				'post_type'  => $post_type->name,
				'taxonomies' => array(),
			);

			$taxonomies = get_object_taxonomies( $post_type->name, 'objects' );

			foreach ( $taxonomies as $taxonomy ) {
				if ( in_array( $taxonomy->name, array( 'nav_menu', 'link_category', 'post_format', 'author' ) ) ) {
					continue;
				}

				$taxonomy_data = array(
					'taxonomy_slug' => $taxonomy->name,
					'taxonomy_name' => $taxonomy->labels->name,
					'terms'         => array(),
				);

				$terms = get_terms(
					array(
						'taxonomy'   => $taxonomy->name,
						'hide_empty' => false,
					)
				);

				foreach ( $terms as $term ) {
					$term_data = array(
						'term_id'   => $term->term_id,
						'term_name' => $term->name,
					);

					$taxonomy_data['terms'][] = $term_data;
				}

				$post_type_data['taxonomies'][] = $taxonomy_data;
			}

			$data['post_types'][] = $post_type_data;
		}

		return $data;
	}

	/**
	 * Get all post statuses.
	 *
	 * @return array
	 */
	public static function get_all_post_statuses() {
		$post_statuses         = get_post_stati( array( 'internal' => false ), 'objects' );
		$data['post_statuses'] = wp_list_pluck( $post_statuses, 'label' );
		return $data;
	}
	/**
	 * Get all authors.
	 *
	 * @return array
	 */
	public static function get_all_authors() {
		$data['authors'] = get_users(
			array(
				'role'   => 'author',
				'fields' => array( 'ID', 'display_name' ),
			)
		);
		return $data;
	}

	/**
	 * Set the post thumbnail.
	 *
	 * @param int    $post_id The post ID.
	 * @param string $thumbnail_url The thumbnail URL.
	 *
	 * @return int The thumbnail ID.
	 */
	public static function itfb_set_post_thumbnail( $post_id, $thumbnail_url ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$image_id = media_sideload_image( $thumbnail_url, $post_id, null, 'id' );

		return $image_id;
	}

	/**
	 * Set scheduled import.
	 *
	 * @param array $params The parameters array.
	 * @param bool  $update Whether to update the schedule.
	 * @return int|\WP_Error
	 */
	public static function schedule_import_campaigns( $params, $update = false ) {

		if ( $update ) {
			try {
				// Attempt to delete the action.
				\ActionScheduler::store()->delete_action( intval( $params['id'] ) );
			} catch ( \Exception $e ) {
				return new \WP_Error(
					'failed_delete',
					'Failed to delete the scheduled import.',
					array( 'status' => 500 )
				);
			}
		}

		$frequency = $params['schedule_settings']['frequency'];
		$timestamp = strtotime( 'now' );

		switch ( $frequency ) {
			case 'hourly':
				$hours_interval = $params['schedule_settings']['specific_hour'];
				$timestamp      = strtotime( 'now' ) + $hours_interval * 3600;
				$interval       = $hours_interval * 3600; // Convert hours to seconds.
				break;
			case 'daily':
				$specific_time = $params['schedule_settings']['time']; // Expected format: 'HH:MM'.
				$tomorrow      = gmdate( 'Y-m-d', strtotime( 'tomorrow' ) );
				$timestamp     = strtotime( "$tomorrow $specific_time UTC" );
				$interval      = DAY_IN_SECONDS; // Schedule daily.
				break;
			case 'weekly':
				$specific_day  = $params['schedule_settings']['specific_day']; // Expected format: 'Monday', 'Tuesday', etc.
				$specific_time = $params['schedule_settings']['time']; // Expected format: 'HH:MM'.
				$next_week     = strtotime( "next $specific_day $specific_time UTC" );
				// Ensure it's set to the next occurrence if today is the same as the specified day.
				if ( gmdate( 'l' ) === $specific_day && strtotime( "$specific_time UTC" ) > time() ) {
					$next_week = strtotime( "$specific_day $specific_time UTC" );
				}
				$timestamp = $next_week;
				$interval  = WEEK_IN_SECONDS; // Schedule weekly.
				break;
			default:
				return new \WP_Error( 'invalid_frequency', __( 'Invalid frequency.', 'integration-toolkit-for-beehiiv' ), array( 'status' => 400 ) );
		}

		$schedule_id = as_schedule_recurring_action(
			$timestamp,
			$interval,
			'itfb_import_campaigns',
			array( $params ),
			'itfb_import_campaigns_group'
		);

		if ( 0 === $schedule_id ) {
			return new \WP_Error( 'failed_to_schedule_import', __( 'Failed to schedule import.', 'integration-toolkit-for-beehiiv' ), array( 'status' => 400 ) );
		} else {
			return $schedule_id;
		}
	}
	/**
	 * Include the WooCommerce action scheduler.
	 *
	 * @return void
	 */
	public static function include_action_scheduler() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! is_plugin_active( 'action-scheduler/action-scheduler.php' ) ) {
			require_once ITFB_PATH . 'vendor/woocommerce/action-scheduler/action-scheduler.php';
		}
	}

	/**
	 * Filter the campaign content.
	 *
	 * @param string $content The campaign content.
	 * @return string
	 */
	public static function filter_campaign_content( $content ) {

		// Create a new DOMDocument instance.
		$doc = new \DOMDocument();

		// Load the content as HTML and handle encoding issues.
		libxml_use_internal_errors( true ); // Suppress HTML warnings/errors.
		$doc->loadHTML( mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' ), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();

		// Get the web-header div and remove it from the DOM if it exists.
		$web_header_tag = $doc->getElementById( 'web-header' );
		if ( $web_header_tag ) {
			//phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$web_header_tag->parentNode->removeChild( $web_header_tag );
		}

		// Get the content inside the head tag.
		$head_tag     = $doc->getElementsByTagName( 'head' )->item( 0 );
		$head_content = '';
		if ( $head_tag ) {
			// Loop through the head tag's children and append them to head_content.
			//phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			foreach ( $head_tag->childNodes as $child ) {
				//phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$head_content .= $doc->saveHTML( $child );
			}
		}

		// Get the content inside the body tag.
		$body_tag     = $doc->getElementsByTagName( 'body' )->item( 0 );
		$body_content = '';
		if ( $body_tag ) {
			// Loop through the body tag's children and append them to body_content.
			//phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			foreach ( $body_tag->childNodes as $child ) {
				//phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$body_content .= $doc->saveHTML( $child );
			}
		}

		// Combine head content with separator and body content.
		$final_content = $head_content . $body_content;

		$final_content = preg_replace( "/^\s*[\r\n]/m", '', $final_content ); // Remove empty lines.

		return $final_content;
	}
}
