<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/crholm
 * @since             1.0.0
 * @package           Mfn_Wp_Plugin
 *
 * @wordpress-plugin
 * Plugin Name:       MFN Feed
 * Plugin URI:        https://github.com/modfin/mfn-wp-plugin
 * Description:       The MFN Feed plugin enables syncing of a news items feed for a particular company from mfn.se into Wordpress.
 * Version:           0.0.92
 * Author:            Rasmus Holm
 * Author URI:        https://github.com/crholm
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       mfn-wp-plugin
 * Domain Path:       /languages
 */

require_once(dirname(__FILE__) . '/config.php');
require_once(dirname(__FILE__) . '/widgets.php');

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-mfn-wp-plugin-activator.php
 */
function activate_mfn_wp_plugin()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-mfn-wp-plugin-activator.php';
    Mfn_Wp_Plugin_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-mfn-wp-plugin-deactivator.php
 */
function deactivate_mfn_wp_plugin()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-mfn-wp-plugin-deactivator.php';
    Mfn_Wp_Plugin_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_mfn_wp_plugin');
register_deactivation_hook(__FILE__, 'deactivate_mfn_wp_plugin');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-mfn-wp-plugin.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */

function mfn_news_post_type()
{
    mfn_register_types();
}

// actions
add_action('init', 'mfn_news_post_type');
add_action("save_post_" . MFN_POST_TYPE, 'mfn_news_post_saved');
add_action( 'add_meta_boxes', 'mfn_add_custom_meta_box' );
add_action(
    'admin_head-edit.php',
    MFN_POST_TYPE . '_edit_post_change_title_in_list'
);

// filters
add_filter( 'get_user_option_meta-box-order_' . MFN_POST_TYPE, 'mfn_metabox_order' );

function run_mfn_wp_plugin()
{
    $plugin = new Mfn_Wp_Plugin();
    $plugin->run();
}

run_mfn_wp_plugin();
