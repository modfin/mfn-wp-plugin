<?php

require_once( WP_PLUGIN_DIR . '/mfn-wp-plugin/config.php');

/**
 * Fired during plugin activation
 *
 * @link       https://github.com/crholm
 * @since      1.0.0
 *
 * @package    Mfn_Wp_Plugin
 * @subpackage Mfn_Wp_Plugin/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Mfn_Wp_Plugin
 * @subpackage Mfn_Wp_Plugin/includes
 * @author     Rasmus Holm <rasmus.holm@modularfinance.se>
 */
class Mfn_Wp_Plugin_Activator
{

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function activate()
    {
        mfn_register_types();
        mfn_sync_taxonomy();
        flush_rewrite_rules(false);
    }

}
