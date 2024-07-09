<?php

/**
 * Plugin Name:       beehiiv to WordPress - Publish beehiiv newsletters as posts
 * Plugin URI:        https://refact.co/
 * Description:       Effortlessly connect content and subscription capabilities with your WordPress site.
 * Version:           2.0.0
 * Author:            Refact.co
 * Author URI:        https://refact.co
 * License:           GPL2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       integration-toolkit-for-beehiiv
 * Domain Path:       /languages
 * Requires at least: 6.5.3
 * Requires PHP:      7.4
 *
 * @package Integration_Toolkit_For_Beehiiv
 **/

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

// Load Plugin File autoload
require_once dirname(__FILE__) . '/vendor/autoload.php';

if (!defined('INTEGRATION_TOOLKIT_FOR_BEEHIIV_CORE_VERSION')) {
	define('INTEGRATION_TOOLKIT_FOR_BEEHIIV_CORE_VERSION', '2.0.0');
}

if (!defined('INTEGRATION_TOOLKIT_FOR_BEEHIIV_URL')) {
	define('INTEGRATION_TOOLKIT_FOR_BEEHIIV_URL', plugin_dir_url(__FILE__));
}

if (!defined('INTEGRATION_TOOLKIT_FOR_BEEHIIV_PATH')) {
	define('INTEGRATION_TOOLKIT_FOR_BEEHIIV_PATH', plugin_dir_path(__FILE__));
}


/**
 * Run the loader to initialize the plugin.
 */
function run_integration_toolkit_for_beehiiv() {
	return ITFB\Init::get_instance();
}

run_integration_toolkit_for_beehiiv();

