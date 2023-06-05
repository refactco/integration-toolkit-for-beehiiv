<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing

namespace Re_Beehiiv\Import;

/**
 * Class Import_Table
 * This class is responsible for creating the custom table and its operations
 *
 * @package Re_Beehiiv\Import
 */
class Import_Table {

	const TABLE_NAME = 're_beehiiv_import';

	/**
	 * Create the custom table
	 *
	 * @return void
	 */
	public static function create_table() : void {
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
		dbDelta( $sql );
	}

	/**
	 * Delete the custom table
	 *
	 * @return void
	 */
	public static function delete_table() : void {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$sql        = esc_sql( "DROP TABLE IF EXISTS {$table_name}" );
		$wpdb->query( $sql );
	}

	/**
	 * Delete all rows from the custom table
	 *
	 * @return void
	 */
	public static function delete_custom_table_rows() : void {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$sql        = esc_sql( "DELETE FROM {$table_name}" );
		$wpdb->query( $sql );
	}

	/**
	 * Insert a row in the custom table
	 *
	 * @param string $key_name
	 * @param array $key_value
	 * @param string $status
	 * @return void
	 */
	public static function insert_custom_table_row( string $key_name, array $key_value, string $group_name, string $status ) : void {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$key_value  = wp_json_encode( $key_value );
		$wpdb->insert(
			$table_name,
			array(
				'key_name'  => sanitize_text_field( $key_name ),
				'key_value' => sanitize_text_field( $key_value ),
				'group_name' => sanitize_text_field( $group_name ),
				'status'    => sanitize_text_field( $status ),
			)
		);

		$err = $wpdb->last_error;

		if ( $err ) {
			error_log( $err );
		}
	}

	/**
	 * Get a row from the custom table
	 *
	 * @param string $key_name
	 */
	public static function get_custom_table_row( string $key_name, string $group_name ) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$key_name   = sanitize_text_field( $key_name );
		$sql        = $wpdb->prepare( "SELECT * FROM {$table_name} WHERE key_name = '%s' AND group_name = '%s'", $key_name, $group_name );
		$result     = $wpdb->get_results( $sql );

		if ( ! $result ) {
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
	public static function delete_custom_table_row( string $key_name ) : void {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$key_name   = sanitize_text_field( $key_name );
		$sql        = $wpdb->prepare( "DELETE FROM {$table_name} WHERE key_name = '%s'", $key_name );
		$wpdb->query( $sql );
	}
}
