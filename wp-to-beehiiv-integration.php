<?php
/**
 * Plugin Name:       WP to Beehiiv Integration
 * Plugin URI:        https://refact.co/
 * Description:       integrates WP with Beehive, making it easy to sync subscriptions and streamline your email marketing campaigns.
 * Version:           1.0.0
 * Author:            Refact.co
 * Author URI:        https://refact.co
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       Re-Beehiiv
 * Domain Path:       /languages
 * Requires at least: 5.2
 * Requires PHP:      7.2
 *
 * @package WP_to_Beehiiv_Integration
 **/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Load Plugin File autoload
require_once dirname( __FILE__ ) . '/vendor/autoload.php';

if ( ! defined( 'WP_TO_BEEHIIV_INTEGRATIONCORE_VERSION' ) ) {
	define( 'WP_TO_BEEHIIV_INTEGRATIONCORE_VERSION', '1.0.0' );
}

if ( ! defined( 'WP_TO_BEEHIIV_INTEGRATIONURL' ) ) {
	define( 'WP_TO_BEEHIIV_INTEGRATIONURL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'WP_TO_BEEHIIV_INTEGRATIONPATH' ) ) {
	define( 'WP_TO_BEEHIIV_INTEGRATIONPATH', plugin_dir_path( __FILE__ ) );
}



/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wp-to-beehiiv-integration-activator.php
 */
function re_bee_activate_wp_to_beehiiv_integration() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-to-beehiiv-integration-activator.php';
	WP_to_Beehiiv_Integration_Activator::activate();
}

register_activation_hook( __FILE__, 're_bee_activate_wp_to_beehiiv_integration' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wp-to-beehiiv-integration.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function wp_to_beehiiv_integration() {
	$plugin = new WP_to_Beehiiv_Integration();
	$plugin->run();
}
wp_to_beehiiv_integration();
