<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing

namespace Integration_Toolkit_For_Beehiiv\Import;

/**
 * Class Import_Table
 * This class is responsible for creating the custom table and its operations
 *
 * @package Integration_Toolkit_For_Beehiiv\Import
 */
class Import_Table
{

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
			status varchar(255) NOT NULL,
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
	 * @param string $status
	 * @return void
	 */
	public static function insert_custom_table_row(string $key_name, array $key_value, string $group_name, string $status): void
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
				'status'     => sanitize_text_field($status),
			)
		);

		if (!$res) {
			throw new \Exception(esc_html__('Error inserting row in custom table', 'integration-toolkit-for-beehiiv'));
		}
	}

	/**
	 * Get a row from the custom table
	 *
	 * @param string $key_name
	 * @param string $group_name
	 */
	public static function get_custom_table_row(string $key_name, string $group_name)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result     = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM %s WHERE key_name = %s AND group_name = %s",
				sanitize_text_field($table_name),
				sanitize_text_field($key_name),
				sanitize_text_field($group_name)
			),
		);

		if (!$result) {
			return false;
		}

		return $result;
	}


	/**
	 * Remove a row from the custom table
	 *
	 * @param string $key_name
	 * @return void
	 */
	public static function delete_custom_table_row(string $key_name): void
	{
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$key_name   = sanitize_text_field($key_name);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query($wpdb->prepare(
			"DELETE FROM `{$table_name}` WHERE key_name = %s",
			$key_name
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
	 * Get all rows from the custom table.
	 *
	 * @param string $status
	 * @param string $group_name
	 * @return array
	 */
	public static function get_rows_by_status(string $status, string $group_name = ''): array
	{
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$status     = sanitize_text_field($status);
		$group_name = sanitize_text_field($group_name);

		if (!empty($group_name)) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
			$result = $wpdb->get_results($wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM `{$table_name}` WHERE status = %s AND group_name = %s",
				$status,
				$group_name
			));
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery 
			$result = $wpdb->get_results($wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM `{$table_name}` WHERE status = %s",
				$status
			));
		}

		if (!$result) {
			return array();
		}

		return $result;
	}
}
