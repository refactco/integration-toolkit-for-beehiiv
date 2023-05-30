<?php
/**
 * Fired during plugin activation
 *
 * @link       https://refact.co
 * @since      1.0.0
 *
 * @package    Re_Beehiiv
 * @subpackage Re_Beehiiv/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Re_Beehiiv
 * @subpackage Re_Beehiiv/includes
 * @author     Refact <info@refact.co>
 */
class Re_Beehiiv_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		\Re_Beehiiv\Import\Import_Table::create_table();
	}

}
