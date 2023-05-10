<?php

/**
 * Plugin Name:       Re-Beehiiv
 * Plugin URI:        https://refact.co/
 * Description:       integrates WP with Beehive, making it easy to sync subscriptions and streamline your email marketing campaigns.
 * Version:           1.0.0
 * Author:            Refact.co
 * Author URI:        https://refact.co
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 **/

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

//Load Plugin File autoload
include_once dirname(__FILE__) . '/vendor/autoload.php';

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('RE_BEEHIIV_VERSION', '1.0.0');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-re-beehiiv-activator.php
 */
function activate_re_beehiiv()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-re-beehiiv-activator.php';
	Re_Beehiiv_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-re-beehiiv-deactivator.php
 */
function deactivate_re_beehiiv()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-re-beehiiv-deactivator.php';
	Re_Beehiiv_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_re_beehiiv');
register_deactivation_hook(__FILE__, 'deactivate_re_beehiiv');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-re-beehiiv.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function re_beehiiv()
{

	$plugin = new Re_Beehiiv();
	$plugin->run();
}
re_beehiiv();