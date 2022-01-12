<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://ecardsystems.com
 * @since             1.0.0
 * @package           Ecard
 *
 * @wordpress-plugin
 * Plugin Name:       eCard Toolkit
 * Plugin URI:        https://ecardsystems.com
 * Description:       Custom function store.
 * Version:           1.0.0
 * Author:            eCardSystems
 * Author URI:        https://ecardsystems.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ecard
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
  die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'ECARD_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-ecard-activator.php
 */
function activate_ecard() {
  require_once plugin_dir_path( __FILE__ ) . 'includes/class-ecard-activator.php';
  Ecard_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-ecard-deactivator.php
 */
function deactivate_ecard() {
  require_once plugin_dir_path( __FILE__ ) . 'includes/class-ecard-deactivator.php';
  Ecard_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_ecard' );
register_deactivation_hook( __FILE__, 'deactivate_ecard' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-ecard.php';

/**
 * The core plugin initialization class for shared modules.,
 */
require plugin_dir_path( __FILE__ ) . 'includes/init.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_ecard() {

  $plugin = new Ecard();
  $plugin->run();

}
run_ecard();
