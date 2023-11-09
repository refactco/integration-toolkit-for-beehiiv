<?php
/**
 * Plugin Name:       Re/Beehiiv
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
 * @package Re_Beehiiv
 **/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Load Plugin File autoload
require_once dirname( __FILE__ ) . '/vendor/autoload.php';

if ( ! defined( 'RE_BEEHIIV_CORE_VERSION' ) ) {
	define( 'RE_BEEHIIV_CORE_VERSION', '1.0.0' );
}

if ( ! defined( 'RE_BEEHIIV_URL' ) ) {
	define( 'RE_BEEHIIV_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'RE_BEEHIIV_PATH' ) ) {
	define( 'RE_BEEHIIV_PATH', plugin_dir_path( __FILE__ ) );
}



/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-re-beehiiv-activator.php
 */
function re_bee_activate_re_beehiiv() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-re-beehiiv-activator.php';
	Re_Beehiiv_Activator::activate();
}

register_activation_hook( __FILE__, 're_bee_activate_re_beehiiv' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-re-beehiiv.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function re_beehiiv() {
	$plugin = new Re_Beehiiv();
	$plugin->run();
}
re_beehiiv();
