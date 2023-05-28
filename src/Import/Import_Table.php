<?php

namespace Re_Beehiiv\Import;

class Import_Table {

    const TABLE_NAME = 're_beehiiv_import';

    /**
     * Create the custom table
     *
     * @return void
     */
    public static function create_table() : void {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            key_name varchar(255) NOT NULL,
            key_value longtext NOT NULL,
            status varchar(255) NOT NULL,
            UNIQUE KEY id (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Delete the custom table
     *
     * @return void
     */
    public static function delete_table() : void {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $sql = "DROP TABLE IF EXISTS $table_name";
        $wpdb->query($sql);
    }

    /**
     * Delete all rows from the custom table
     *
     * @return void
     */
    public static function delete_custom_table_rows() : void {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $sql = "DELETE FROM $table_name";
        $wpdb->query($sql);
    }

    /**
     * Insert a row in the custom table
     *
     * @param string $key_name
     * @param array $key_value
     * @param string $status
     * @return void
     */
    public static function insert_custom_table_row(string $key_name, array $key_value, string $status) : void {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $key_value = json_encode($key_value);
        $wpdb->insert(
            $table_name,
            array(
                'key_name' => $key_name,
                'key_value' => $key_value,
                'status' => $status
            )
        );
    }

    /**
     * Get a row from the custom table
     * 
     * @param string $key_name
     * @return array|false
     */
    public static function get_custom_table_row(string $key_name) : array|false {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $sql = "SELECT * FROM $table_name WHERE key_name = '%s'";
        $sql = $wpdb->prepare($sql, $key_name);
        $result = $wpdb->get_results($sql);

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
    public static function delete_custom_table_row( string $key_name ) : void {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $sql = "DELETE FROM $table_name WHERE key_name = '%s'";
        $sql = $wpdb->prepare($sql, $key_name);
        $wpdb->query($sql);
    }
}