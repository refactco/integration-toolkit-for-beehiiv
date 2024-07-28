<?php
/**
 * This file contains the ImportTable class.
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
	public static function create_table(): void
	{
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
		dbDelta($sql);
	}
    
    /**
	 * Delete the custom table
	 *
	 * @return void
	 */
	public static function delete_table(): void
	{
		global $wpdb;
		$table_name = sanitize_text_field($wpdb->prefix . self::TABLE_NAME);
		// Delete the table using dbDelta
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta("DROP TABLE $table_name");
	}

	/**
	 * Delete all rows from the custom table
	 *
	 * @return void
	 */
	public static function delete_custom_table_rows(): void
	{
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %s',
				$table_name
			)
		);
	}

	/**
	 * Insert a row in the custom table
	 *
	 * @param string $key_name
	 * @param array $key_value
	 * @param string $group_name
	 * @return void
	 */
	public static function insert_custom_table_row(string $key_name, array $key_value, string $group_name ): void
	{
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$res = $wpdb->insert(
			$table_name,
			array(
				'key_name'   => sanitize_text_field($key_name),
				'key_value'  => wp_json_encode($key_value),
				'group_name' => sanitize_text_field($group_name),
			)
		);

		if (!$res) {
			throw new \Exception(esc_html__('Error inserting row in custom table', 'integration-toolkit-for-beehiiv'));
		}
	}

	/**
	 * Get a campaign data from the import table
	 *
	 * @param string $key_name
	 * @param string $group_name
	 */
	public static function get_and_decode_campaign_data(string $key_name, string $group_name)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result     = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$table_name}` WHERE key_name = %s AND group_name = %s",
				sanitize_text_field($key_name),
				sanitize_text_field($group_name)
			),
			ARRAY_A
		);
	
		if (!$result) {
			return false;
		}
	
		$campaign_data = $result['key_value'];
	
		$decoded_campaign_data = json_decode($campaign_data, true);
	
		return $decoded_campaign_data;
	}

	/**
	 * Get the remaining campaigns count
	 *
	 * @param string $group_name
	 * @return int
	 */
	public static function get_remaining_campaigns_count( string $group_name ) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$group_name = sanitize_text_field($group_name);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `{$table_name}` WHERE group_name = %s",
				$group_name
			)
		);
		return $result;
	}

	/**
	 * Remove a row from the custom table
	 *
	 * @param string $key_name
	 * @param string $group_name
	 * @return void
	 */
	public static function delete_custom_table_row(string $key_name, string $group_name): void
	{
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$key_name   = sanitize_text_field($key_name);
		$group_name = sanitize_text_field($group_name);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query($wpdb->prepare(
			"DELETE FROM `{$table_name}` WHERE key_name = %s AND group_name = %s",
			$key_name,
			$group_name
		));
	}	

	/**
	 * Remove All rows from the custom table by group name
	 *
	 * @param string $group_name
	 */
	public static function delete_row_by_group(string $group_name): void
	{
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$group_name = sanitize_text_field($group_name);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query($wpdb->prepare(
			"DELETE FROM `{$table_name}` WHERE group_name = %s",
			$group_name
		));
	}


	/**
	 * Get all rows from the import table by group name
	 *
	 * @param string $group_name
	 * @return array
	 */
	public static function get_rows_by_group_name(string $group_name = ''): array
	{
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$group_name = sanitize_text_field($group_name);

		if (!empty($group_name)) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
			$result = $wpdb->get_results($wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM `{$table_name}` WHERE group_name = %s",
				$group_name,
			));
		}

		if (!$result) {
			return array();
		}

		return $result;
	}
}