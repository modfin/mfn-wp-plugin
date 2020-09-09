<?php

require_once('consts.php');
require_once('api.php');


// If ABSPATH not defined, php app is initiated from plugin folder.
// Lets try to find and run wp-config.php
if (!defined('ABSPATH')) {
    $dir = __DIR__;
    for ($i = 0; $i < 3; $i++) {
        if (file_exists($dir . "/wp-config.php")) {
            break;
        }
        $dir = dirname($dir);
    }
    if (!file_exists($dir . "/wp-config.php")) {
        echo "could not find wp-config.php";
        die();
    }

    // Found wp-config, lets find wp-load
    if (!file_exists($dir . "/wp-load.php")) {
        // checking parent child dirs for load file
        foreach (scandir($dir) as $file) {
            if (is_dir($dir . "/" . $file) && file_exists($dir . "/" . $file . "/wp-load.php")) {
                $dir = $dir . "/" . $file;
                break;
            }
        }
    }

    if (!file_exists($dir . "/wp-load.php")) {
        echo "could not find wp-load.php";
        die();
    }
    require_once($dir . '/wp-load.php');
}

require_once(ABSPATH . 'wp-admin/includes/taxonomy.php');
require_once(ABSPATH . 'wp-includes/taxonomy.php');



function register_mfn_types()
{

    if(empty(MFN_POST_TYPE)) {
        die("MFN News Feed - The post type was empty. Please enter a post type name in consts.php.");
    }
    else {
        register_post_type(MFN_POST_TYPE,
            array(
                'labels' => array(
                    'name' => __('MFN News Items'),
                    'singular_name' => __('MFN News Item'),
                ),
                'public' => true,
                'has_archive' => true,
            )
        );

    }


    $labels = array(
        'name' => _x('News Tags', 'MFN News tags'),
        'singular_name' => _x('News Tag', 'MFN News tag'),
        'search_items' => __('Search News Tags'),
        'all_items' => __('All News Tags'),
        'parent_item' => __('Parent News Tag'),
        'parent_item_colon' => __('Parent News Tag:'),
        'edit_item' => __('Edit News Tag'),
        'update_item' => __('Update News Tag'),
        'add_new_item' => __('Add New News Tag'),
        'new_item_name' => __('New News Tag Name'),
        'menu_name' => __('News Tags'),
    );

    register_taxonomy(MFN_TAXONOMY_NAME, array(MFN_POST_TYPE), array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => array('slug' => MFN_TAXONOMY_NAME),
    ));

}


function sync_mfn_taxonomy()
{
    $tax = [
        "slug" => MFN_TAG_PREFIX,
        "name" => "News",
        "i10n" => ["sv" => "Nyheter"],
        "children" => [
            [
                "slug" => "type-ir",
                "name" => "IR",
                "i10n" => ["sv" => "IR", "fi" => "IR"]
            ],
            [
                "slug" => "type-pr",
                "name" => "PR",
                "i10n" => ["sv" => "PR", "fi" => "PR"]
            ],
            [
                "slug" => "lang-sv",
                "name" => "Swedish",
                "i10n" => ["sv" => "Svenska", "fi" => "Ruotsi"]
            ],
            [
                "slug" => "lang-en",
                "name" => "English",
                "i10n" => ["sv" => "Engelska", 'fi' => "Englanti"]
            ],
            [
                "slug" => "lang-fi",
                "name" => "Finnish",
                "i10n" => ["sv" => "Finska", 'fi' => "Suomi"]
            ],
            [
                "slug" => "correction",
                "name" => "Correction",
                "i10n" => ["sv" => "Korrektion", 'fi' => "Korjaaminen"],
            ],
            [
                "slug" => "regulatory",
                "name" => "Regulatory",
                "i10n" => ["sv" => "Regulatorisk", 'fi' => "Sääntelyn"],
                "children" => [
                    [
                        "slug" => "mar",
                        "name" => "MAR",
                        "i10n" => ["sv" => "MAR", "fi" => "MAR"]
                    ],
                    [
                        "slug" => "vpml",
                        "name" => "VPML",
                        "i10n" => ["sv" => "VPML"]
                    ],
                    [
                        "slug" => "lhfi",
                        "name" => "LHFI",
                        "i10n" => ["sv" => "LHFI"]
                    ],
                    [
                        "slug" => "listing",
                        "name" => "Listing Regulation",
                        "i10n" => ["sv" => "Noteringskrav", 'fi' => "Listausvaatimukset"]
                    ],

                ]
            ],
            [
                "slug" => "report",
                "name" => "Report",
                "i10n" => ["sv" => "Rapport", 'fi' => "Raportti"],
                "children" => [
                    [
                        "slug" => "annual",
                        "name" => "Annual",
                        "i10n" => ["sv" => "Årsredovisning", 'fi' => "Vuotuinen"]
                    ],
                    [
                        "slug" => "interim",
                        "name" => "Interim",
                        "i10n" => ["sv" => "Delårsrapport", 'fi' => "Osavuosikatsaus"],
                        "children" => [
                            [
                                "slug" => "q1",
                                "name" => "Q1",
                                "i10n" => ["sv" => "Q1", "fi" => "Q1"]
                            ],
                            [
                                "slug" => "q2",
                                "name" => "Q2",
                                "i10n" => ["sv" => "Q2", "fi" => "Q2"]
                            ],
                            [
                                "slug" => "q3",
                                "name" => "Q3",
                                "i10n" => ["sv" => "Q3", "fi" => "Q3"]
                            ],
                            [
                                "slug" => "q4",
                                "name" => "Yearend",
                                "i10n" => ["sv" => "Bokslutskommuniké", 'fi' => "Vuoden loppu"]
                            ],

                        ]
                    ],
                ]
            ],
            [
                "slug" => "ca",
                "name" => "Corporate Action",
                "i10n" => ["sv" => "Bolagshändelse", 'fi' => "Vuosittainen tapahtuma"],
                "children" => [
                    [
                        "slug" => "other",
                        "name" => "Other",
                        "i10n" => ["sv" => "Övrig", "fi" => "Muut"]
                    ],
                    [
                        "slug" => "ma",
                        "name" => "M&A",
                        "i10n" => ["sv" => "M&A", "fi" => "M&A"]
                    ],
                    [
                        "slug" => "ipo",
                        "name" => "IPO",
                        "i10n" => ["sv" => "IPO", "fi" => "IPO"]
                    ],
                    [
                        "slug" => "prospectus",
                        "name" => "Prospectus",
                        "i10n" => ["sv" => "Prospekt", 'fi' => "Esite"]
                    ],
                    [
                        "slug" => "shares",
                        "name" => "Shares",
                        "i10n" => ["sv" => "Aktie", 'fi' => "Osakkeet"],
                        "children" => [
                            [
                                "slug" => "issuance",
                                "name" => "Issuance",
                                "i10n" => ["sv" => "Emission", 'fi'=>"Osakeanti"]
                            ],
                            [
                                "slug" => "repurchase",
                                "name" => "Repurchase",
                                "i10n" => ["sv" => "Återköp", 'fi' => "Takaisinosto"]
                            ],
                            [
                                "slug" => "rights",
                                "name" => "Rights Change",
                                "i10n" => ["sv" => "Rättighetsförändring", 'fi' => "Oikeuksien muutos"]
                            ]

                        ]
                    ]
                ]
            ],
            [
                "slug" => "ci",
                "name" => "Corporate Information",
                "i10n" => ["sv" => "Bolagsinformation", 'fi' => "Yrityksen tiedot"],
                "children" => [
                    [
                        "slug" => "gm",
                        "name" => "General meeting",
                        "i10n" => ["sv" => "Bolagsstämman", 'fi' => "Yhtiökokous"],
                        "children" => [
                            [
                                "slug" => "notice",
                                "name" => "Notice",
                                "i10n" => ["sv" => "Kallelse", 'fi'=>"Kutsumus"]
                            ],
                            [
                                "slug" => "info",
                                "name" => "Report of the AGM",
                                "i10n" => ["sv" => "Rapport bolagsstämman", 'fi' => "Yhtiökokouksen raportti"]
                            ]
                        ]
                    ],
                    [
                        "slug" => "other",
                        "name" => "Other Corporate Information",
                        "i10n" => ["sv" => "Övrig bolagsinformation", 'fi' => "Muut yritystiedot"]
                    ],
                    [
                        "slug" => "calendar",
                        "name" => "Financial calendar",
                        "i10n" => ["sv" => "Finansiell kalender", 'fi' => "Taloudellinen kalenteri"]
                    ],
                    [
                        "slug" => "presentation",
                        "name" => "Presentation",
                        "i10n" => ["sv" => "Presentation", 'fi' => "Esittely"]
                    ],
                    [
                        "slug" => "nomination",
                        "name" => "Nomination Committee",
                        "i10n" => ["sv" => "Valberedning", "fi" => "Nimityskomitea"]
                    ],
                    [
                        "slug" => "sales",
                        "name" => "Sales",
                        "i10n" => ["sv" => "Försäljning", "fi" => "Myynti"],
                        "children" => [
                            [
                                "slug" => "order",
                                "name" => "Order",
                                "i10n" => ["sv" => "Order", "fi" => "Tilaus"],
                            ]
                        ]
                    ],
                    [
                        "slug" => "staff",
                        "name" => "Staff change",
                        "i10n" => ["sv" => "Personalförändring", "fi" => "Henkilöstön muutokset"],
                        "children" => [
                            [
                                "slug" => "xxo",
                                "name" => "Executive staff changes",
                                "i10n" => ["sv" => "Exekutiva personalförändringar", "fi" => "Johtava henkilöstö muuttuu"],
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    $options = get_option(MFN_PLUGIN_NAME);
    $use_wpml = isset($options['use_wpml']) ? $options['use_wpml'] : 'off';
    $has_wpml = defined('WPML_PLUGIN_BASENAME');


    $use_pll = isset($options['use_pll']) ? $options['use_pll'] : 'off';
    $has_pll = defined('POLYLANG_BASENAME');

    $upsert_wpml = function ($enItem, $enTerm, $prefix = '') {
        global $wpdb;
        $tbmlTranslations = $wpdb->prefix . 'icl_translations';
        $wpdb->update(
            $tbmlTranslations,
            array('language_code' => 'en', 'source_language_code' => null),
            array('element_id' => $enTerm->term_id, 'element_type' => 'tax_mfn-news-tag')
        );

        $q = $wpdb->prepare("
            SELECT trid
            FROM $tbmlTranslations
            WHERE element_id = %s
        ", $enTerm->term_id);
        $trid = $wpdb->get_var($q);

        $enParentTerm = null;
        if ($enTerm->parent > 0) {
            $enParentTerm = get_term($enTerm->parent, MFN_TAXONOMY_NAME);
        }

        $tbmlTerms = $wpdb->prefix . 'terms';
        $tbmlTermsTax = $wpdb->prefix . 'term_taxonomy';

        foreach ($enItem['i10n'] as $lang => $val) {

            $l_parent_term_id = 0;
            if ($enParentTerm != null) {
                $l_parent_slug = $enParentTerm->slug . '_' . $lang;
                $q = $wpdb->prepare("
            SELECT term_id
            FROM $tbmlTerms
            WHERE slug = %s
        ", $l_parent_slug);
                $l_parent_term_id = $wpdb->get_var($q);
            }


            $l_slug = $enTerm->slug . '_' . $lang;


            $q = $wpdb->prepare("
            SELECT term_id
            FROM $tbmlTerms
            WHERE slug = %s
        ", $l_slug);
            $l_term_id = $wpdb->get_var($q);


            if ($l_term_id != null) {
                $wpdb->update(
                    $tbmlTerms,
                    array('name' => $val),
                    array('term_id' => $l_term_id)
                );

                if ($l_parent_term_id != 0) {
                    $wpdb->update(
                        $tbmlTermsTax,
                        array('parent' => $l_parent_term_id),
                        array('term_id' => $l_term_id)
                    );
                }

                $wpdb->update(
                    $tbmlTranslations,
                    array('source_language_code' => 'en', 'language_code' => $lang, 'trid' => $trid),
                    array('element_id' => $tbmlTerms, 'element_type' => 'tax_mfn-news-tag')
                );
            }


            if ($l_term_id == null) {
                wp_insert_term($val, MFN_TAXONOMY_NAME, array(
                    'slug' => $l_slug,
                    'parent' => $l_parent_term_id
                ));
                $l_term = get_term_by('slug', $l_slug, MFN_TAXONOMY_NAME);
                $wpdb->update(
                    $tbmlTranslations,
                    array('source_language_code' => 'en', 'language_code' => $lang, 'trid' => $trid),
                    array('element_id' => $l_term->term_id, 'element_type' => 'tax_mfn-news-tag')
                );
            }
        }
    };


    $upsert_pll = function ($enItem, $enTerm, $prefix = '') {

        $enParentTerm = null;
        if ($enTerm->parent > 0) {
            $enParentTerm = get_term($enTerm->parent, MFN_TAXONOMY_NAME);
        }


        $translations = array();
        $translations['en'] = $enTerm->term_id;


        foreach ($enItem['i10n'] as $lang => $name) {
            $slug = $enTerm->slug . "_" . $lang;

            $l_parent_term_id = 0;
            if ($enParentTerm != null) {
                $p_slug = $enParentTerm->slug . '_' . $lang;
                $term = get_term_by('slug', $p_slug, MFN_TAXONOMY_NAME);
                if (is_object($term)) {
                    $l_parent_term_id = $term->term_id;
                }
            }


            $term = get_term_by('slug', $slug, MFN_TAXONOMY_NAME);
            if (is_object($term)) {
                wp_update_term($term->term_id, MFN_TAXONOMY_NAME, array(
                    'name' => $name,
                    'slug' => $slug,
                    'parent' => $l_parent_term_id,
                ));
            }
            if ($term == false) {
                wp_insert_term($name, MFN_TAXONOMY_NAME, array(
                    'slug' => $slug,
                    'parent' => $l_parent_term_id,
                ));

                $term = get_term_by('slug', $slug, MFN_TAXONOMY_NAME);
            }

            pll_set_term_language($term->term_id, $lang);
            $translations[$lang] = $term->term_id;
        }
        pll_save_term_translations($translations);
    };


    $upsert = function ($item, $prefix = '', $parent_id = null) use (&$upsert, $use_wpml, $has_wpml, $upsert_wpml, $has_pll, $use_pll, $upsert_pll) {

        $slug = $prefix . $item['slug'];

        $term = get_term_by('slug', $slug, MFN_TAXONOMY_NAME);
        if (is_object($term)) {
            wp_update_term($term->term_id, MFN_TAXONOMY_NAME, array(
                'name' => $item['name'],
                'slug' => $slug,
            ));
        }
        if ($term == false) {
            wp_insert_term($item['name'], MFN_TAXONOMY_NAME, array(
                'slug' => $slug,
                'parent' => $parent_id,
            ));

            $term = get_term_by('slug', $slug, MFN_TAXONOMY_NAME);
        }


        if ($has_wpml && $use_wpml == 'on') {
            $upsert_wpml($item, $term, $prefix);
        }

        if (isset($item['children'])) {
            foreach ($item['children'] as $val) {
                $upsert($val, $slug . '-', $term->term_id);
            }
        }
    };

    $upsert($tax);
}
