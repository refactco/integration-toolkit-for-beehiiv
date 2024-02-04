<?php
/**
 * Fired during plugin activation
 *
 * @link       https://refact.co
 * @since      1.0.0
 *
 * @package    WP_to_Beehiiv_Integration
 * @subpackage WP_to_Beehiiv_Integration/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    WP_to_Beehiiv_Integration
 * @subpackage WP_to_Beehiiv_Integration/includes
 * @author     Refact <info@refact.co>
 */
class WP_to_Beehiiv_Integration_Activator {
	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		\WP_to_Beehiiv_Integration\Import\Import_Table::create_table();
	}

}
