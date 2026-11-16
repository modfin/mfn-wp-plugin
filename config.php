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
    $post = get_post();
    if (!is_null($post) && $post->post_type === MFN_POST_TYPE) {
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

    $taxonomy_rewrite_slug = '';
    if (isset(get_option(MFN_PLUGIN_NAME)['taxonomy_rewrite_slug'])) {
        $taxonomy_rewrite_slug = get_option(MFN_PLUGIN_NAME)['taxonomy_rewrite_slug'];
    }

    register_taxonomy(MFN_TAXONOMY_NAME, array(MFN_POST_TYPE), array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'query_var' => true,
        'rewrite' => array('slug' => empty($taxonomy_rewrite_slug) ? MFN_TAXONOMY_NAME : $taxonomy_rewrite_slug),
    ));

}

function mfn_sync_taxonomy()
{
    $tax = [
        "slug" => MFN_TAG_PREFIX,
        "name" => "News",
        "i10n" => ["sv" => "Nyheter", "fi" => "Uutiset"],
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
                "slug" => "lang-no",
                "name" => "Norwegian",
                "i10n" => ["sv" => "Norska"]
            ],
            [
                "slug" => "lang-zh",
                "name" => "Chinese",
                "i10n" => ["sv" => "Kinesiska"]
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
                        "slug" => "nsta",
                        "name" => "NSTA",
                        "i10n" => ["sv" => "NSTA"]
                    ],
                    [
                        "slug" => "dcma",
                        "name" => "DCMA",
                        "i10n" => ["sv" => "DCMA"]
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
                        "slug" => "aoa",
                        "name" => "Articles of association",
                        "i10n" => ["sv" => "Bolagsordning", 'fi' => "Yhtiöjärjestys"]
                    ],
                    [
                        "slug" => "bid",
                        "name" => "Tender offer",
                        "i10n" => ["sv" => "Offentligt uppköpserbjudande", 'fi' => "Julkinen ostotarjous"]
                    ],
                    [
                        "slug" => "member-state",
                        "name" => "Member state",
                        "i10n" => ["sv" => "Hemstat", 'fi' => "Jäsenvaltio"]
                    ],
                    [
                        "slug" => "nav",
                        "name" => "Net Asset Value",
                        "i10n" => ["sv" => "NAV kurs", 'fi' => "Substanssiarvo"]
                    ],
                    [
                        "slug" => "description",
                        "name" => "Company Description",
                        "i10n" => ["sv" => "Företagsbeskrivning", 'fi' => "Yrityksen kuvaus"]
                    ],
                    [
                        "slug" => "exdate",
                        "name" => "Ex date",
                        "i10n" => ["sv" => "X-datum"]
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
                        "slug" => "insider",
                        "name" => "Insider Transaction",
                        "i10n" => ["sv" => "Insynstransaktion", "fi" => "Johdon liiketoimet"]
                    ],
                    [
                        "slug" => "shareholder-announcement",
                        "name" => "Shareholder announcement",
                        "i10n" => ["sv" => "Flaggning", "fi" => "Liputusilmoitukset"]
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
                        "i10n" => ["sv" => "Personalförändring", "fi" => "Henkilöstön muutokset"]
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

    // Terms changed with raw SQL in $upsert_wpml, their caches are cleaned after the sync
    $wpmlTouchedTermIds = array();
    // Set when $upsert_wpml changes icl_translations
    $wpmlChanged = false;

    // Slugs of the terms the sync owns, English and translated, with their language. Other terms in
    // their translation groups were added by hand in WPML and are left alone.
    $managedSlugs = array();
    $collectSlugs = function ($item, $prefix = '') use (&$collectSlugs, &$managedSlugs) {
        $slug = $prefix . $item['slug'];
        $managedSlugs[$slug] = 'en';
        foreach ($item['i10n'] as $lang => $val) {
            $managedSlugs[$slug . '_' . $lang] = $lang;
        }
        if (isset($item['children'])) {
            foreach ($item['children'] as $val) {
                $collectSlugs($val, $slug . '-');
            }
        }
    };
    $collectSlugs($tax);

    $upsert_wpml = function ($enItem, $enTerm, $prefix = '') use (&$wpmlTouchedTermIds, &$wpmlChanged, $managedSlugs) {
        global $wpdb;
        $tbmlTranslations = $wpdb->prefix . 'icl_translations';
        $tbmlTerms = $wpdb->prefix . 'terms';
        $tbmlTermsTax = $wpdb->prefix . 'term_taxonomy';
        $elementType = 'tax_' . MFN_TAXONOMY_NAME;

        // WPML keys term translations on term_taxonomy_id, not term_id
        $getRow = function ($ttId) use ($wpdb, $tbmlTranslations, $elementType) {
            $q = $wpdb->prepare("
            SELECT trid, language_code, source_language_code
            FROM $tbmlTranslations
            WHERE element_id = %d AND element_type = %s
        ", $ttId, $elementType);
            return $wpdb->get_row($q);
        };

        // Inserts the row when missing, e.g. for terms created before the taxonomy was made translatable in WPML
        $setRow = function ($ttId, $trid, $lang, $sourceLang) use ($wpdb, $tbmlTranslations, $elementType, $getRow, &$wpmlChanged) {
            $data = array('trid' => $trid, 'language_code' => $lang, 'source_language_code' => $sourceLang);
            $where = array('element_id' => $ttId, 'element_type' => $elementType);
            if ($getRow($ttId) == null) {
                $wpdb->insert($tbmlTranslations, array_merge($where, $data));
            } else {
                $wpdb->update($tbmlTranslations, $data, $where);
            }
            $wpmlChanged = true;
        };

        $newTrid = function () use ($wpdb, $tbmlTranslations) {
            return (int)$wpdb->get_var("SELECT MAX(trid) FROM $tbmlTranslations") + 1;
        };

        // The element holding a language in a translation group, with its term when it is a term of this taxonomy
        $getSlot = function ($trid, $lang) use ($wpdb, $tbmlTranslations, $tbmlTerms, $tbmlTermsTax, $elementType, &$wpmlChanged) {
            $q = $wpdb->prepare("
            SELECT tr.element_id, tr.element_type, t.term_id, t.slug
            FROM $tbmlTranslations tr
            LEFT JOIN $tbmlTermsTax tt ON tt.term_taxonomy_id = tr.element_id AND tr.element_type = %s
            LEFT JOIN $tbmlTerms t ON t.term_id = tt.term_id
            WHERE tr.trid = %d AND tr.language_code = %s
        ", $elementType, $trid, $lang);
            foreach ($wpdb->get_results($q) as $slot) {
                // Left behind by a deleted term, WPML doesn't always remove the row
                if ($slot->element_type == $elementType && $slot->slug === null) {
                    $wpdb->delete($tbmlTranslations, array('element_id' => $slot->element_id, 'element_type' => $elementType));
                    $wpmlChanged = true;
                    continue;
                }
                return $slot;
            }
            return null;
        };

        $isManaged = function ($slot) use ($managedSlugs, $elementType) {
            return $slot->element_type == $elementType && isset($managedSlugs[$slot->slug]);
        };

        // A group with a term the sync doesn't own was put together by hand in WPML
        $isHandMade = function ($trid) use ($wpdb, $tbmlTranslations, $tbmlTerms, $tbmlTermsTax, $elementType, $managedSlugs) {
            $q = $wpdb->prepare("
            SELECT t.slug
            FROM $tbmlTranslations tr
            INNER JOIN $tbmlTermsTax tt ON tt.term_taxonomy_id = tr.element_id
            INNER JOIN $tbmlTerms t ON t.term_id = tt.term_id
            WHERE tr.trid = %d AND tr.element_type = %s
        ", $trid, $elementType);
            foreach ($wpdb->get_col($q) as $slug) {
                if (!isset($managedSlugs[$slug])) {
                    return true;
                }
            }
            return false;
        };

        // Earlier versions of the sync looked up groups by term_id without element_type, which could
        // put terms in a post's group
        $hasOtherTypes = function ($trid) use ($wpdb, $tbmlTranslations, $elementType) {
            $q = $wpdb->prepare("
            SELECT COUNT(*)
            FROM $tbmlTranslations
            WHERE trid = %d AND element_type != %s
        ", $trid, $elementType);
            return $wpdb->get_var($q) > 0;
        };

        $enTtId = $enTerm->term_taxonomy_id;
        $enRow = $getRow($enTtId);
        $trid = $enRow != null ? $enRow->trid : null;
        if ($trid != null && $hasOtherTypes($trid)) {
            $trid = null;
        }

        if ($trid == null) {
            $trid = $newTrid();
        } else {
            $slot = $getSlot($trid, 'en');
            if ($slot != null && $slot->element_id != $enTtId) {
                // The English term was added to a group made by hand
                if (!$isManaged($slot)) {
                    return;
                }
                // Left by an earlier version of the sync, which mixed up term_id and term_taxonomy_id.
                // A group held by another English term stays with it.
                if ($managedSlugs[$slot->slug] == 'en') {
                    $trid = $newTrid();
                } else {
                    $setRow($slot->element_id, $newTrid(), $managedSlugs[$slot->slug], null);
                }
            }
        }

        if ($enRow == null || $enRow->trid != $trid || $enRow->language_code != 'en' || $enRow->source_language_code != null) {
            $setRow($enTtId, $trid, 'en', null);
        }

        // Parents are synced before their children, so the parent's group is already repaired
        $enParent = null;
        if ($enTerm->parent > 0) {
            $q = $wpdb->prepare("
            SELECT t.slug, tr.trid
            FROM $tbmlTerms t
            INNER JOIN $tbmlTermsTax tt ON tt.term_id = t.term_id AND tt.taxonomy = %s
            LEFT JOIN $tbmlTranslations tr ON tr.element_id = tt.term_taxonomy_id AND tr.element_type = %s
            WHERE t.term_id = %d
        ", MFN_TAXONOMY_NAME, $elementType, $enTerm->parent);
            $enParent = $wpdb->get_row($q);
        }

        foreach ($enItem['i10n'] as $lang => $val) {

            $l_parent_term_id = 0;
            if ($enParent != null) {
                // The parent's translation can be a term made by hand in WPML, with a slug the sync doesn't know
                $parentSlot = $enParent->trid != null ? $getSlot($enParent->trid, $lang) : null;
                if ($parentSlot != null && $parentSlot->term_id != null) {
                    $l_parent_term_id = $parentSlot->term_id;
                } else {
                    $l_parent_terms = MFN_get_terms_wpml($enParent->slug . '_' . $lang);
                    if ($l_parent_terms) {
                        $l_parent_term_id = $l_parent_terms[0]->term_id;
                    }
                }
            }

            $l_slug = $enTerm->slug . '_' . $lang;
            $l_terms = MFN_get_terms_wpml($l_slug);
            $l_term = $l_terms ? $l_terms[0] : null;

            $slot = $getSlot($trid, $lang);
            if ($slot != null && ($l_term == null || $slot->element_id != $l_term->term_taxonomy_id)) {
                // A translation made by hand in WPML, keep it and add no term for the language
                if (!$isManaged($slot)) {
                    continue;
                }
                // Left by an earlier version of the sync, which mixed up term_id and term_taxonomy_id
                $setRow($slot->element_id, $newTrid(), $managedSlugs[$slot->slug], null);
            }

            if ($l_term != null) {
                // Sanitized like wp_insert_term does it, which stores e.g. "M&A" as "M&amp;A"
                $wpdb->update(
                    $tbmlTerms,
                    array('name' => wp_unslash(sanitize_term_field('name', $val, $l_term->term_id, MFN_TAXONOMY_NAME, 'db'))),
                    array('term_id' => $l_term->term_id)
                );

                if ($l_parent_term_id != 0) {
                    $wpdb->update(
                        $tbmlTermsTax,
                        array('parent' => $l_parent_term_id),
                        array('term_taxonomy_id' => $l_term->term_taxonomy_id)
                    );
                }

                $wpmlTouchedTermIds[] = $l_term->term_id;
                $l_term_tt_id = $l_term->term_taxonomy_id;
            } else {
                $ids = wp_insert_term($val, MFN_TAXONOMY_NAME, array(
                    'slug' => $l_slug,
                    'parent' => $l_parent_term_id
                ));
                if (is_wp_error($ids)) {
                    error_log("[MFN Sync taxonomy]: could not create term " . $l_slug . ": " . $ids->get_error_message());
                    continue;
                }
                $l_term_tt_id = $ids['term_taxonomy_id'];
            }

            $l_row = $getRow($l_term_tt_id);
            if ($l_row != null && $l_row->trid == $trid) {
                // Already in the group. A language that isn't synced was set by hand in WPML, e.g. a custom language code
                $inPlace = $l_row->language_code == $lang && $l_row->source_language_code == 'en';
                if ($inPlace || !in_array($l_row->language_code, $managedSlugs, true)) {
                    continue;
                }
            } else if ($l_row != null && $isHandMade($l_row->trid)) {
                // Linked by hand to another group in WPML
                continue;
            }

            $setRow($l_term_tt_id, $trid, $lang, 'en');
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

        foreach ($enItem['i10n'] as $i10nLang => $name) {

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

    $getTerm = function ($slug, $lang)  use ($use_wpml, $has_wpml) {
	    if ($has_wpml && $use_wpml) {
		    $terms = MFN_get_terms_wpml($slug);
			if (!$terms) {
				return false;
			}
			return $terms[0];
	    }
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

        if (!$term) {
            wp_insert_term($item['name'], MFN_TAXONOMY_NAME, array(
                'slug' => $slug,
                'parent' => $parent_id,
            ));
            $term = $getTerm($slug, 'en'); // to get term_id
        }
        if (is_object($term)) {
            $fields = array(
                'name' => $item['name'],
                'slug' => $slug,
            );
            if ($has_wpml && $use_wpml) {
                // wp_update_term reads the term with get_term(), which WPML swaps for its translation in the
                // current language (e.g. Swedish in admin), and then saves the translation's parent. The parent
                // is set explicitly to repair terms earlier syncs saved that way.
                $fields['parent'] = (int)$parent_id;
                $term->parent = $fields['parent'];
                MFN_without_wpml_term_filters(function () use ($term, $fields) {
                    wp_update_term($term->term_id, MFN_TAXONOMY_NAME, $fields);
                });
            } else {
                wp_update_term($term->term_id, MFN_TAXONOMY_NAME, $fields);
            }
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

    if ($has_wpml && $use_wpml) {
        // Also drops the cached hierarchy (mfn-news-tag_children)
        clean_term_cache($wpmlTouchedTermIds, MFN_TAXONOMY_NAME);
        // WPML caches term translations in the object cache, which the raw updates bypass
        if ($wpmlChanged && wp_using_ext_object_cache()) {
            wp_cache_flush();
        }
    }
}
