<?php

require_once('consts.php');
require_once('api.php');
require_once('text-domain.php');

function mfn_plugin_url(): string
{
    return plugins_url('', __FILE__);
}

// If ABSPATH not defined, php app is initiated from plugin folder.
// Lets try to find and run wp-config.php
if (!defined('ABSPATH')) {
    $dir = __DIR__;
    for ($i = 0; $i < 5; $i++) {
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
global $pagenow;

if (isset(get_option(MFN_PLUGIN_NAME)['enable_attachments'])
    && get_option(MFN_PLUGIN_NAME)['enable_attachments'] == true) {
    add_filter('the_content', 'mfn_enable_attachments');
}

function mfn_enable_attachments($content): string
{
    if (get_post()->post_type === MFN_POST_TYPE) {
        $content .= mfn_remove_regular_attachment_footer();
        $content .= mfn_list_post_attachments();
    }
    return $content;
}

$ops = get_option(MFN_PLUGIN_NAME);

if (isset($ops['rewrite_post_type'])) {

    // adding filter for rewriting the post_type from settings
    add_filter('register_post_type_args', 'rewrite_post_type', 10, 2);

    function rewrite_post_type($args, $post_type): array
    {
        $ops = get_option(MFN_PLUGIN_NAME);

        if ($post_type === 'mfn_news' && isset($ops['rewrite_post_type'])) {
            $rewrite_post_type = unserialize($ops['rewrite_post_type']);
            $disable_archive = isset($ops['disable_archive']) && $ops['disable_archive'] === 'on';

            $rewrite_slug = '';
            $rewrite_archive_name = '';
            $rewrite_singular_name = '';

            if (isset($rewrite_post_type['slug'])) {
                $rewrite_slug = $rewrite_post_type['slug'];
            }
            if (isset($rewrite_post_type['archive-name'])) {
                $rewrite_archive_name = $rewrite_post_type['archive-name'];
            }
            if (isset($rewrite_post_type['singular-name'])) {
                $rewrite_singular_name = $rewrite_post_type['singular-name'];
            }

            // rewrite
            if ($rewrite_slug !== '' && $rewrite_slug !== MFN_POST_TYPE) {
                $args['rewrite']['slug'] = $rewrite_slug;
            }
            if ($rewrite_archive_name !== '' && $rewrite_archive_name !== MFN_ARCHIVE_NAME) {
                $args['labels']['name'] = $rewrite_archive_name;
            }
            if ($rewrite_singular_name !== '' && $rewrite_singular_name !== MFN_SINGULAR_NAME) {
                $args['labels']['singular_name'] = $rewrite_singular_name;
            }
            $args['has_archive'] = !$disable_archive;
        }
        return $args;
    }
}

function mfn_register_types()
{
    $ops = get_option(MFN_PLUGIN_NAME);

    if (empty(MFN_POST_TYPE)) {
        die("MFN News Feed - The post type was empty. Please enter a post type name in consts.php.");
    } else {
        $supports = array('title', 'editor','excerpt');
        if (isset($ops['thumbnail_on'])) {
            $supports = array('title', 'editor', 'excerpt', 'thumbnail');
        }

        register_post_type(MFN_POST_TYPE,
            array(
                'labels' => array(
                    'name' => __(MFN_ARCHIVE_NAME),
                    'singular_name' => __(MFN_SINGULAR_NAME),
                ),
                'show_in_rest' => true,
                'public' => true,
                'has_archive' => true,
                'rewrite' => array(''),
                'supports' => $supports,
            ));
    }

    // do url rewrite option upon settings save
    add_action('update_option_mfn-wp-plugin', function () {
        mfn_register_types();
        flush_rewrite_rules(false);
    }, 11, 3);

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

function mfn_sync_taxonomy()
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
                                "i10n" => ["sv" => "Emission", 'fi' => "Osakeanti"]
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
                                "i10n" => ["sv" => "Kallelse", 'fi' => "Kutsumus"]
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
    $use_wpml = isset($options['language_plugin']) && $options['language_plugin'] == 'wpml';
    $use_pll = isset($options['language_plugin']) && $options['language_plugin'] == 'pll';
    $has_wpml = defined('WPML_PLUGIN_BASENAME');
    $has_pll = defined('POLYLANG_BASENAME');

    $upsert_wpml = function ($enItem, $enTerm, $prefix = '') {
        global $wpdb;
        $tbmlTranslations = $wpdb->prefix . 'icl_translations';
        $wpdb->update(
            $tbmlTranslations,
            array('language_code' => 'en', 'source_language_code' => null),
            array('element_id' => $enTerm->term_id, 'element_type' => 'tax_' . MFN_TAXONOMY_NAME)
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
                    array('element_id' => $tbmlTerms, 'element_type' => 'tax_' . MFN_TAXONOMY_NAME)
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
                    array('element_id' => $l_term->term_id, 'element_type' => 'tax_' . MFN_TAXONOMY_NAME)
                );
            }
        }
    };

    $upsert_pll = function ($enItem, $enTerm, $pllLangMapping, $prefix = '') {

        $enParentTerm = null;
        if ($enTerm->parent > 0) {
            $enParentTerm = get_term($enTerm->parent, MFN_TAXONOMY_NAME);
        }

        $translations = array();
        $translations['en'] = $enTerm->term_id;

        $allowed = pll_the_languages(array('raw' => true));

        foreach ($enItem['i10n'] as `>`$i10nLang => $name) {

            if (!isset($pllLangMapping[$i10nLang])) {
                continue;
            }

            $lang = $pllLangMapping[$i10nLang];

            if (!array_key_exists($lang, $allowed)) {
                continue;
            }
            $slug = $enTerm->slug . "_" . $lang;

            $l_parent_term_id = 0;
            if ($enParentTerm != null) {
                $p_slug = $enParentTerm->slug . '_' . $lang;
                $pterms = get_terms(array(
                    'taxonomy' => MFN_TAXONOMY_NAME,
                    'hide_empty' => false,
                    'slug' => $p_slug
                ));
                if (sizeof($pterms) == 0) {
                    $pterms = get_terms(array(
                        'taxonomy' => MFN_TAXONOMY_NAME,
                        'hide_empty' => false,
                        'slug' => $p_slug,
                        'lang' => $lang
                    ));
                }

                if (sizeof($pterms) > 0) {
                    $l_parent_term_id = $pterms[0]->term_id;
                }
            }

            $terms = get_terms(array(
                'taxonomy' => MFN_TAXONOMY_NAME,
                'hide_empty' => false,
                'slug' => $slug
            ));
            if (sizeof($terms) == 0) {
                $ids = wp_insert_term($name, MFN_TAXONOMY_NAME, array(
                    'slug' => $slug,
                    'parent' => $l_parent_term_id,
                ));
                if (is_array($ids)) {
                    pll_set_term_language($ids['term_id'], $lang);
                }
                $terms = get_terms(array(
                    'taxonomy' => MFN_TAXONOMY_NAME,
                    'hide_empty' => false,
                    'slug' => $slug,
                    'lang' => $lang
                ));
            }

            $term = $terms[0];
            if (is_object($term)) {
                wp_update_term($term->term_id, MFN_TAXONOMY_NAME, array(
                    'name' => $name,
                    'slug' => $slug,
                    'parent' => $l_parent_term_id,
                ));
            }

            pll_set_term_language($term->term_id, $lang);
            $translations[$lang] = $term->term_id;
        }
        pll_save_term_translations($translations);
    };

    $getTerm = function ($slug, $lang) {
        $terms = get_terms(array(
            'taxonomy' => MFN_TAXONOMY_NAME,
            'hide_empty' => false,
            'slug' => $slug,
        ));
        if (sizeof($terms) == 0) {
            $terms = get_terms(array(
                'taxonomy' => MFN_TAXONOMY_NAME,
                'hide_empty' => false,
                'slug' => $slug,
                'lang' => $lang
            ));
        }
        if (sizeof($terms) == 0) {
            $terms = get_terms(array(
                'taxonomy' => MFN_TAXONOMY_NAME,
                'hide_empty' => false,
                'slug' => $slug,
                'lang' => ''
            ));
        }
        if (sizeof($terms) == 0) {
            return false;
        }
        return $terms[0];
    };

    $upsert = function ($item, $prefix = '', $parent_id = null) use (&$upsert, &$getTerm, $use_wpml, $has_wpml, $upsert_wpml, $has_pll, $use_pll, $upsert_pll) {

        $slug = $prefix . $item['slug'];

        $term = $getTerm($slug, 'en');

        if ($term == false) {
            wp_insert_term($item['name'], MFN_TAXONOMY_NAME, array(
                'slug' => $slug,
                'parent' => $parent_id,
            ));
            $term = $getTerm($slug, 'en');
        }
        if (is_object($term)) {
            wp_update_term($term->term_id, MFN_TAXONOMY_NAME, array(
                'name' => $item['name'],
                'slug' => $slug,
            ));
        }

        if ($has_wpml && $use_wpml) {
            $upsert_wpml($item, $term, $prefix);
        }
        if ($has_pll && $use_pll) {
            $pllLangMapping = array();
            foreach (pll_languages_list(array('fields' => array())) as $pll_lang) {
                $l = explode('_', $pll_lang->locale)[0];
                $pllLangMapping[$l] = $pll_lang->slug;
            };
            pll_set_term_language($term->term_id, 'en');
            $upsert_pll($item, $term, $pllLangMapping, $prefix);
        }

        if (isset($item['children'])) {
            foreach ($item['children'] as $val) {
                $upsert($val, $slug . '-', $term->term_id);
            }
        }
    };

    $upsert($tax);
}
