<?php

require_once( WP_PLUGIN_DIR . '/mfn-wp-plugin/lib.php');

/**
 * Fired during plugin deactivation
 *
 * @link       https://github.com/crholm
 * @since      1.0.0
 *
 * @package    Mfn_Wp_Plugin
 * @subpackage Mfn_Wp_Plugin/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Mfn_Wp_Plugin
 * @subpackage Mfn_Wp_Plugin/includes
 * @author     Rasmus Holm <rasmus.holm@modularfinance.se>
 */
class Mfn_Wp_Plugin_Deactivator
{

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function deactivate()
    {
        MFN_unsubscribe();
        flush_rewrite_rules(false);
    }

}
