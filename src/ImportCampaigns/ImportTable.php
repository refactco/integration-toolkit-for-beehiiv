<?php
/**
 * This file contains the ImportTable class.
 *
 * @package ITFB\ImportCampaigns
 * @since 2.0.0
 */

namespace ITFB\ImportCampaigns;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ImportTable class
 * Handles the functionality of creating the import table.
 */
class ImportTable {
	/**
	 * The table name.
	 *
	 * @var string $table_name
	 */
	const TABLE_NAME = 'integration_toolkit_for_beehiiv_import';

	/**
	 * Create the custom table
	 *
	 * @return void
	 */
	public static function create_table(): void {
		global $wpdb;
		$table_name      = $wpdb->prefix . self::TABLE_NAME;
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE IF NOT EXISTS $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			key_name varchar(255) NOT NULL,
			key_value longtext NOT NULL,
			group_name varchar(255) NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Update Table Structure
	 *
	 * @since    2.0.0
	 * @return void
	 */
	public static function update_table_structure(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;

		// Check if the table exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name ) {

			// Check if the column 'status' exists in the table.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$column = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM {$table_name} LIKE %s", 'status' ) );

			if ( ! empty( $column ) ) {
				// Prepare and execute the query to drop the column 'status'.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query( "ALTER TABLE {$table_name} DROP COLUMN status" );
			}
		}
	}



	/**
	 * Insert a row in the custom table
	 *
	 * @param string $key_name The key name.
	 * @param array  $key_value The key value.
	 * @param string $group_name The group name.
	 * @return void
	 * @throws \Exception If there is an error inserting the row.
	 */
	public static function insert_custom_table_row( string $key_name, array $key_value, string $group_name ): void {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$res = $wpdb->insert(
			$table_name,
			array(
				'key_name'   => sanitize_text_field( $key_name ),
				'key_value'  => wp_json_encode( $key_value ),
				'group_name' => sanitize_text_field( $group_name ),
			)
		);

		if ( ! $res ) {
			throw new \Exception( esc_html__( 'Error inserting row in custom table', 'integration-toolkit-for-beehiiv' ) );
		}
	}

	/**
	 * Get a campaign data from the import table
	 *
	 * @param string $key_name The key name.
	 * @param string $group_name The group name.
	 */
	public static function get_and_decode_campaign_data( string $key_name, string $group_name ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_name = sanitize_text_field( $wpdb->prefix . self::TABLE_NAME );
		$key_name   = sanitize_text_field( $key_name );
		$group_name = sanitize_text_field( $group_name );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$query = $wpdb->prepare( "SELECT * FROM {$table_name} WHERE key_name = %s AND group_name = %s", $key_name, $group_name );

		//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->get_row( $query, ARRAY_A );

		if ( ! $result ) {
			return false;
		}

		$campaign_data = $result['key_value'];

		$decoded_campaign_data = json_decode( $campaign_data, true );

		return $decoded_campaign_data;
	}


	/**
	 * Get the remaining campaigns count
	 *
	 * @param string $group_name The group name.
	 * @return int
	 */
	public static function get_remaining_campaigns_count( string $group_name ) {
		global $wpdb;

		$table_name = sanitize_text_field( $wpdb->prefix . self::TABLE_NAME );
		$group_name = sanitize_text_field( $group_name );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} WHERE group_name = %s", $group_name ) );

		return $result;
	}


	/**
	 * Remove a row from the custom table
	 *
	 * @param string $key_name      The key name.
	 * @param string $group_name The group name.
	 * @return void
	 */
	public static function delete_custom_table_row( string $key_name, string $group_name ): void {
		global $wpdb;

		$table_name = sanitize_text_field( $wpdb->prefix . self::TABLE_NAME );
		$key_name   = sanitize_text_field( $key_name );
		$group_name = sanitize_text_field( $group_name );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table_name} WHERE key_name = %s AND group_name = %s", $key_name, $group_name ) );
	}
}
