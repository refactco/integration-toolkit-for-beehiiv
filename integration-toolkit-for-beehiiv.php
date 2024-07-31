<?php
/**
 * Plugin Name:       beehiiv to WordPress - Publish beehiiv newsletters as posts v2.0.0
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
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Load Plugin File autoload.
require_once __DIR__ . '/vendor/autoload.php';

if ( ! defined( 'ITFB_VERSION' ) ) {
	define( 'ITFB_VERSION', '2.0.0' );
}

if ( ! defined( 'ITFB_URL' ) ) {
	define( 'ITFB_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'ITFB_PATH' ) ) {
	define( 'ITFB_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'ITFB_FILE' ) ) {
	define( 'ITFB_FILE', __FILE__ );
}

/**
 * Run the loader to initialize the plugin.
 */
function itfb_run_integration_toolkit_for_beehiiv() {
	return ITFB\Init::get_instance();
}

itfb_run_integration_toolkit_for_beehiiv();
