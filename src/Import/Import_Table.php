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
		$wpdb->query(
			$wpdb->prepare(
				'DROP TABLE IF EXISTS %i', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedPlaceholder,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				$table_name
			)
		);
	}

	/**
	 * Delete all rows from the custom table
	 *
	 * @return void
	 */
	public static function delete_custom_table_rows() : void {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedPlaceholder,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
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
	public static function insert_custom_table_row( string $key_name, array $key_value, string $group_name, string $status ) : void {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$res = $wpdb->insert(
			$table_name,
			array(
				'key_name'   => sanitize_text_field( $key_name ),
				'key_value'  => wp_json_encode( $key_value ),
				'group_name' => sanitize_text_field( $group_name ),
				'status'     => sanitize_text_field( $status ),
			)
		);

		if ( ! $res ) {
			throw new \Exception( __('Error inserting row in custom table' , 're-beehiiv'));
		}
	}

	/**
	 * Get a row from the custom table
	 *
	 * @param string $key_name
	 * @param string $group_name
	 */
	public static function get_custom_table_row( string $key_name, string $group_name ) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$result     = $wpdb->get_results(
			$wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
				'SELECT * FROM %i WHERE key_name = %s AND group_name = %s', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedPlaceholder
				$table_name,
				sanitize_text_field( $key_name ),
				$group_name
			),
		);

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
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE key_name = %s', $table_name, $key_name ) ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedPlaceholder,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
	}

	/**
	 * Remove All rows from the custom table by group name
	 *
	 * @param string $group_name
	 */
	public static function delete_row_by_group( string $group_name ) : void {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$group_name = sanitize_text_field( $group_name );
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE group_name = %s', $table_name, $group_name ) ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedPlaceholder,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
	}

	public static function get_rows_by_status( string $status, string $group_name = '' ) : array {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$status     = sanitize_text_field( $status );
		$group_name = sanitize_text_field( $group_name );
		$query      = "SELECT * FROM $table_name WHERE status = %s";
	
		$params = array( $status );
	
		if ( ! empty( $group_name ) ) {
			$query .= " AND group_name = %s";
			$params[] = $group_name;
		}
	
		$prepared_query = $wpdb->prepare( $query, $params ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
		$result = $wpdb->get_results( $prepared_query );//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
		if ( ! $result ) {
			return [];
		}
	
		return $result;
	}
	
}
