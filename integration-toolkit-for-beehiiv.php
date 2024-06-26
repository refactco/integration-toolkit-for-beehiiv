<?php

/**
 * Plugin Name:       beehiiv to WordPress - Publish beehiiv newsletters as posts
 * Plugin URI:        https://refact.co/
 * Description:       Effortlessly connect content and subscription capabilities with your WordPress site.
 * Version:           1.1.0
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
	define('INTEGRATION_TOOLKIT_FOR_BEEHIIV_CORE_VERSION', '1.0.0');
}

if (!defined('INTEGRATION_TOOLKIT_FOR_BEEHIIV_URL')) {
	define('INTEGRATION_TOOLKIT_FOR_BEEHIIV_URL', plugin_dir_url(__FILE__));
}

if (!defined('INTEGRATION_TOOLKIT_FOR_BEEHIIV_PATH')) {
	define('INTEGRATION_TOOLKIT_FOR_BEEHIIV_PATH', plugin_dir_path(__FILE__));
}



/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-integration-toolkit-for-beehiiv-activator.php
 */
function integration_toolkit_for_beehiiv_activation_hook()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-integration-toolkit-for-beehiiv-activator.php';
	Integration_Toolkit_For_Beehiiv_Activator::activate();
}

register_activation_hook(__FILE__, 'integration_toolkit_for_beehiiv_activation_hook');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-integration-toolkit-for-beehiiv.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function integration_toolkit_for_beehiiv()
{
	$plugin = new Integration_Toolkit_For_Beehiiv();
	$plugin->run();
}
integration_toolkit_for_beehiiv();
