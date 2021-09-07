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
 * Version:           0.0.54
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
    register_mfn_types();
}

add_action('init', 'mfn_news_post_type');
add_action("save_post_" . MFN_POST_TYPE, 'mfn_news_post_saved');

function mfn_get_post_language($post_id)
{
    $lang_slug = "";

    if (defined('POLYLANG_BASENAME')) {
        $lang_slug = pll_get_post_language($post_id);
    } else if (defined('WPML_PLUGIN_BASENAME')) {
        $lang_slug = apply_filters('wpml_post_language_details', NULL, $post_id)["language_code"];
    }

    return $lang_slug;
}

function mfn_create_tags($lang, $lang_suffix) {
    $lang_suffix = empty($lang_suffix) ? '' : '_' . $lang_suffix;
    return [
        // mfn_[lang_suffix]
        MFN_TAG_PREFIX . $lang_suffix,
        // mfn-tag-pr_[lang_suffix]
        MFN_TAG_PREFIX . "-type-pr" . $lang_suffix,
        // mfn-lang-[lang]_[lang_suffix]
        MFN_TAG_PREFIX . "-lang-" . $lang . $lang_suffix
    ];
}

function mfn_news_post_saved($post_id)
{
    // do not interfere with upsert item
    if (did_action('mfn_before_upsertitem')) return;

    $lang_slug = mfn_get_post_language($post_id);


    $terms = wp_get_object_terms($post_id, MFN_TAXONOMY_NAME);
    $needle = MFN_TAG_PREFIX . '-lang-';

    $needles = mfn_create_tags('', '');

    $matching_terms = array();
    $lang_by_terms = '';
    foreach ($terms as $term) {
        foreach ($needles as $needle) {
            if (strpos($term->slug, $needle) === 0) {
                array_push($matching_terms, $term->slug);
            }
        }
        if (strpos($term->slug, MFN_TAG_PREFIX . "-lang-") === 0) {
            $lang_by_terms = explode($needle, $term->slug)[1];
        }
    }

    if (empty($lang_slug) && empty($lang_by_terms)) {
        return;
    }

    $primary_lang = 'en'; // todo

    $mode_normal = !empty($lang_by_terms);

    if ($mode_normal) {
        $lang_slug = $lang_by_terms;
    }

    update_post_meta(
        $post_id,
        MFN_POST_TYPE . "_lang",
        $lang_slug
    );


    if (!$mode_normal) {
        wp_remove_object_terms($post_id, $matching_terms, MFN_TAXONOMY_NAME);
    }

    $lang_suffix = ($mode_normal || $primary_lang === $lang_slug) ? '' : $lang_slug;
    $tags_to_insert = mfn_create_tags($lang_slug, $lang_suffix);

    wp_set_object_terms($post_id, $tags_to_insert, MFN_TAXONOMY_NAME, true);

}

function run_mfn_wp_plugin()
{
    $plugin = new Mfn_Wp_Plugin();
    $plugin->run();
}

run_mfn_wp_plugin();
