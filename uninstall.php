<?php
/**
 * Uninstall Integration Toolkit for Beehiiv2.0.1.
 *
 * @package Integration_Toolkit_For_Beehiiv.
 * @since 2.0.1
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete options.
delete_option( 'itfb_db_compatibility' );
delete_option( 'itfb_schedule_compatibility' );

// delete database tables.
global $wpdb;
$itfb_table_name = $wpdb->prefix . 'integration_toolkit_for_beehiiv_import';
//phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( $wpdb->prepare( "DROP TABLE IF EXISTS $itfb_table_name" ) );
