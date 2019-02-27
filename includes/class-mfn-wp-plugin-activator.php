<?php

require_once( ABSPATH . '/wp-content/plugins/mfn-wp-plugin/config.php');


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


    public static function generateTags()
    {
        wp_insert_term('News', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX,
        ));
        $mfnNews = get_term_by('slug', 'mfn', MFN_TAXONOMY_NAME);


        wp_insert_term('PR', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-type-pr',
            'parent' => $mfnNews->term_id,
        ));
        wp_insert_term('IR', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-type-ir',
            'parent' => $mfnNews->term_id,
        ));

        wp_insert_term('Swedish', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-lang-sv',
            'parent' => $mfnNews->term_id,
        ));
        wp_insert_term('English', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-lang-en',
            'parent' => $mfnNews->term_id,
        ));


        wp_insert_term('Correction', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-correction',
            'parent' => $mfnNews->term_id,
        ));


        // Regulatory
        wp_insert_term('Regulatory', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-regulatory',
            'parent' => $mfnNews->term_id,
        ));
        $mfnReg = get_term_by('slug', 'mfn-regulatory', MFN_TAXONOMY_NAME);

        wp_insert_term('MAR', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-regulatory-mar',
            'parent' => $mfnReg->term_id,
        ));


        wp_insert_term('VPML', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-regulatory-vpml',
            'parent' => $mfnReg->term_id,
        ));

        wp_insert_term('LHFI', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-regulatory-lhfi',
            'parent' => $mfnReg->term_id,
        ));

        wp_insert_term('Exchange Regulatio', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-regulatory-sxr',
            'parent' => $mfnReg->term_id,
        ));


        // Report
        wp_insert_term('Report', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-report',
            'parent' => $mfnNews->term_id,
        ));
        $mfnRep = get_term_by('slug', 'mfn-report', MFN_TAXONOMY_NAME);


        wp_insert_term('Annual', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-report-annual',
            'parent' => $mfnRep->term_id,
        ));

        wp_insert_term('Interim', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-report-interim',
            'parent' => $mfnRep->term_id,
        ));
        $mfnRepInt = get_term_by('slug', 'mfn-report-interim', MFN_TAXONOMY_NAME);

        wp_insert_term('Q1', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-report-interim-q1',
            'parent' => $mfnRepInt->term_id,
        ));
        wp_insert_term('Q2', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-report-interim-q2',
            'parent' => $mfnRepInt->term_id,

        ));
        wp_insert_term('Q3', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-report-interim-q3',
            'parent' => $mfnRepInt->term_id,
        ));
        wp_insert_term('Yearend', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-report-interim-q4',
            'parent' => $mfnRepInt->term_id,
        ));

        //  Corporate Action
        wp_insert_term('Corporate Action', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-ca',
            'parent' => $mfnNews->term_id,
        ));
        $mfnCA = get_term_by('slug', 'mfn-ca', MFN_TAXONOMY_NAME);

        wp_insert_term('Shares', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-ca-shares',
            'parent' => $mfnCA->term_id,
        ));
        $mfnCASares = get_term_by('slug', 'mfn-ca-shares', MFN_TAXONOMY_NAME);

        wp_insert_term('Issuance', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-ca-shares-issuance',
            'parent' => $mfnCASares->term_id,
        ));

        wp_insert_term('Mergers & Acquisitions', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-ca-ma',
            'parent' => $mfnCA->term_id,
        ));

        wp_insert_term('Initial Public Offering', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-ca-ipo',
            'parent' => $mfnCA->term_id,
        ));

        wp_insert_term('Prospectus', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-ca-prospectus',
            'parent' => $mfnCA->term_id,
        ));
        wp_insert_term('Other', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-ca-other',
            'parent' => $mfnCA->term_id,
        ));



        //  Corporate Information

        wp_insert_term('Corporate Information', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-ci',
            'parent' => $mfnNews->term_id,
        ));
        $mfnCI = get_term_by('slug', 'mfn-ci', MFN_TAXONOMY_NAME);


        wp_insert_term('General Meeting', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-ci-gm',
            'parent' => $mfnCI->term_id,
        ));
        $mfnCIGM = get_term_by('slug', 'mfn-ci-gm', MFN_TAXONOMY_NAME);

        wp_insert_term('General Meeting Notice', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-ci-gm-notice',
            'parent' => $mfnCIGM->term_id,
        ));
        wp_insert_term('General Meeting Report', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-ci-gm-info',
            'parent' => $mfnCIGM->term_id,
        ));


        wp_insert_term('Financial Calendar', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-ci-calendar',
            'parent' => $mfnCI->term_id,
        ));

        wp_insert_term('Presentation', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-ci-presentation',
            'parent' => $mfnCI->term_id,
        ));

        wp_insert_term('Nomination Committee', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-ci-nomination',
            'parent' => $mfnCI->term_id,
        ));

        wp_insert_term('Sales', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-ci-sales',
            'parent' => $mfnCI->term_id,
        ));
        $mfnCISales = get_term_by('slug', 'mfn-ci-sales', MFN_TAXONOMY_NAME);

        wp_insert_term('Order', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-ci-sales-order',
            'parent' => $mfnCISales->term_id,
        ));

        wp_insert_term('Staff Changes', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-ci-staff',
            'parent' => $mfnCI->term_id,
        ));
        $mfnCIStaff = get_term_by('slug', 'mfn-ci-staff', MFN_TAXONOMY_NAME);

        wp_insert_term('Executive Staff Changes', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-ci-staff-xxo',
            'parent' => $mfnCIStaff->term_id,
        ));

        wp_insert_term('Other Corporate Information', MFN_TAXONOMY_NAME, array(
            'slug' => MFN_TAG_PREFIX . '-ci-other',
            'parent' => $mfnCI->term_id,
        ));

    }


    public static function activate()
    {
        register_mfn_types();
        self::generateTags();

    }

}
