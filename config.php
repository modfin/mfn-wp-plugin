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
            ],
            [
                "slug" => "gb",
                "name" => "United Kingdom",
                "i10n" => [], // todo för pll?
                "children" => [
                    [
                        "slug" => "hl",
                        "name" => "Headline",
                        "i10n" => [],
                        "children" => [
                            [
                                "slug" => "sus",
                                "name" => "Temporary Suspension",
                                "i10n" => []
                            ],
                            [
                                "slug" => "srs",
                                "name" => "Statement re. Suspension",
                                "i10n" => []
                            ],
                            [
                                "slug" => "ren",
                                "name" => "Restoration of Listing",
                                "i10n" => []
                            ],
                            [
                                "slug" => "lis",
                                "name" => "Initial admission to the Official List",
                                "i10n" => []
                            ],
                            [
                                "slug" => "msc",
                                "name" => "Miscellaneous",
                                "i10n" => []
                            ],
                            [
                                "slug" => "qrf",
                                "name" => "1st Quarter Results",
                                "i10n" => []
                            ],
                            [
                                "slug" => "qrt",
                                "name" => "3rd Quarter Results",
                                "i10n" => []
                            ],
                            [
                                "slug" => "acq",
                                "name" => "Acquisition",
                                "i10n" => []
                            ],
                            [
                                "slug" => "agm",
                                "name" => "AGM Statement",
                                "i10n" => []
                            ],
                            [
                                "slug" => "acs",
                                "name" => "Annual Financial Report",
                                "i10n" => []
                            ],
                            [
                                "slug" => "car",
                                "name" => "Capital Reorganisation",
                                "i10n" => []
                            ],
                            [
                                "slug" => "con",
                                "name" => "Conversion of Securities",
                                "i10n" => []
                            ],
                            [
                                "slug" => "tab",
                                "name" => "Disclosure Table (POTAM use only)",
                                "i10n" => []
                            ],
                            [
                                "slug" => "dis",
                                "name" => "Disposal",
                                "i10n" => []
                            ],
                            [
                                "slug" => "drl",
                                "name" => "Drilling/Production Report",
                                "i10n" => []
                            ],
                            [
                                "slug" => "gms",
                                "name" => "GM Statement",
                                "i10n" => []
                            ],
                            [
                                "slug" => "fr",
                                "name" => "Final Results",
                                "i10n" => []
                            ],
                            [
                                "slug" => "fee",
                                "name" => "Form 8 (OPD) [Insert name of offeree or offeror]",
                                "i10n" => []
                            ],
                            [
                                "slug" => "feo",
                                "name" => "Form 8.5 (EPT/NON-RI)",
                                "i10n" => []
                            ],
                            [
                                "slug" => "fer",
                                "name" => "Form 8.5 (EPT/RI)",
                                "i10n" => []
                            ],
                            [
                                "slug" => "fur",
                                "name" => "Further re (insert appropriate text)",
                                "i10n" => []
                            ],
                            [
                                "slug" => "ir",
                                "name" => "Half-year Report",
                                "i10n" => []
                            ],
                            [
                                "slug" => "iod",
                                "name" => "Issue of Debt",
                                "i10n" => []
                            ],
                            [
                                "slug" => "ioe",
                                "name" => "Issue of Equity",
                                "i10n" => []
                            ],
                            [
                                "slug" => "loi",
                                "name" => "Letter of Intent Signed",
                                "i10n" => []
                            ],
                            [
                                "slug" => "ofb",
                                "name" => "Offer by [add offeror’s name]",
                                "i10n" => []
                            ],
                            [
                                "slug" => "off",
                                "name" => "Offer for [add offeree’s name]",
                                "i10n" => []
                            ],
                            [
                                "slug" => "ola",
                                "name" => "Offer Lapsed",
                                "i10n" => []
                            ],
                            [
                                "slug" => "ore",
                                "name" => "Offer Rejection",
                                "i10n" => []
                            ],
                            [
                                "slug" => "ott",
                                "name" => "Offer Talks Terminated",
                                "i10n" => []
                            ],
                            [
                                "slug" => "oup",
                                "name" => "Offer Update",
                                "i10n" => []
                            ],
                            [
                                "slug" => "prl",
                                "name" => "Product Launch",
                                "i10n" => []
                            ],
                            [
                                "slug" => "agr",
                                "name" => "Agreement",
                                "i10n" => []
                            ],
                            [
                                "slug" => "all",
                                "name" => "Alliance",
                                "i10n" => []
                            ],
                            [
                                "slug" => "cnt",
                                "name" => "Contract",
                                "i10n" => []
                            ],
                            [
                                "slug" => "jve",
                                "name" => "Joint Venture",
                                "i10n" => []
                            ],
                            [
                                "slug" => "rap",
                                "name" => "Regulatory Application",
                                "i10n" => []
                            ],
                            [
                                "slug" => "rea",
                                "name" => "Regulatory Approval",
                                "i10n" => []
                            ],
                            [
                                "slug" => "res",
                                "name" => "Research Update",
                                "i10n" => []
                            ],
                            [
                                "slug" => "rsp",
                                "name" => "Response to (insert appropriate text)",
                                "i10n" => []
                            ],
                            [
                                "slug" => "rep",
                                "name" => "Restructure Proposals",
                                "i10n" => []
                            ],
                            [
                                "slug" => "rag",
                                "name" => "Result of AGM",
                                "i10n" => []
                            ],
                            [
                                "slug" => "roi",
                                "name" => "Result of Equity Issue",
                                "i10n" => []
                            ],
                            [
                                "slug" => "rom",
                                "name" => "Result of Meeting",
                                "i10n" => []
                            ],
                            [
                                "slug" => "rte",
                                "name" => "Result of Tender Offer",
                                "i10n" => []
                            ],
                            [
                                "slug" => "dcc",
                                "name" => "Form 8 (DD) - [Insert name of offeree or offeror]",
                                "i10n" => []
                            ],
                            [
                                "slug" => "ret",
                                "name" => "Form 8.3 - [Insert name of offeree or offeror]",
                                "i10n" => []
                            ],
                            [
                                "slug" => "soa",
                                "name" => "Scheme of Arrangement",
                                "i10n" => []
                            ],
                            [
                                "slug" => "str",
                                "name" => "Statement re (insert appropriate text)",
                                "i10n" => []
                            ],
                            [
                                "slug" => "ofd",
                                "name" => "Statement re Possible Offer",
                                "i10n" => []
                            ],
                            [
                                "slug" => "spc",
                                "name" => "Statement re Press Comment",
                                "i10n" => []
                            ],
                            [
                                "slug" => "spm",
                                "name" => "Statement re Share Price Movement",
                                "i10n" => []
                            ],
                            [
                                "slug" => "syr",
                                "name" => "Syndicate Results",
                                "i10n" => []
                            ],
                            [
                                "slug" => "ten",
                                "name" => "Tender Offer",
                                "i10n" => []
                            ],
                            [
                                "slug" => "tvr",
                                "name" => "Total Voting Rights",
                                "i10n" => []
                            ],
                            [
                                "slug" => "tst",
                                "name" => "Trading Statement",
                                "i10n" => []
                            ],
                            [
                                "slug" => "pos",
                                "name" => "Transaction in Own Shares",
                                "i10n" => []
                            ],
                            [
                                "slug" => "pgr",
                                "name" => "Report on Payments to Governments",
                                "i10n" => []
                            ],
                            [
                                "slug" => "upd",
                                "name" => "Strategy/Company/ Operations Update",
                                "i10n" => []
                            ],
                            [
                                "slug" => "irs",
                                "name" => "Industry Regulator Statement",
                                "i10n" => []
                            ],
                            [
                                "slug" => "ari",
                                "name" => "Announcement re: Rights Issue",
                                "i10n" => []
                            ],
                            [
                                "slug" => "als",
                                "name" => "Additional Listing",
                                "i10n" => []
                            ],
                            [
                                "slug" => "brc",
                                "name" => "Base Rate Change",
                                "i10n" => []
                            ],
                            [
                                "slug" => "blr",
                                "name" => "Block listing Interim Review",
                                "i10n" => []
                            ],
                            [
                                "slug" => "cmc",
                                "name" => "Compliance with Model Code",
                                "i10n" => []
                            ],
                            [
                                "slug" => "cas",
                                "name" => "Compulsory Acquisition of Shares",
                                "i10n" => []
                            ],
                            [
                                "slug" => "dsh",
                                "name" => "Director/PDMR Shareholding",
                                "i10n" => []
                            ],
                            [
                                "slug" => "boa",
                                "name" => "Directorate change",
                                "i10n" => []
                            ],
                            [
                                "slug" => "div",
                                "name" => "Dividend Declaration",
                                "i10n" => []
                            ],
                            [
                                "slug" => "rc",
                                "name" => "FRN Variable Rate Fix",
                                "i10n" => []
                            ],
                            [
                                "slug" => "geo",
                                "name" => "Geographical Distribution",
                                "i10n" => []
                            ],
                            [
                                "slug" => "hol",
                                "name" => "Holding(s) in Company",
                                "i10n" => []
                            ],
                            [
                                "slug" => "nav",
                                "name" => "Net Asset Value(s)",
                                "i10n" => []
                            ],
                            [
                                "slug" => "pfu",
                                "name" => "Portfolio Update",
                                "i10n" => []
                            ],
                            [
                                "slug" => "pdi",
                                "name" => "Publication of a Prospectus",
                                "i10n" => []
                            ],
                            [
                                "slug" => "psp",
                                "name" => "Publication of a Supplementary Prospectus",
                                "i10n" => []
                            ],
                            [
                                "slug" => "pft",
                                "name" => "Publication of Final Terms",
                                "i10n" => []
                            ],
                            [
                                "slug" => "rtt",
                                "name" => "Rule 2.10 Announcement",
                                "i10n" => []
                            ],
                            [
                                "slug" => "tav",
                                "name" => "Total Assets Value",
                                "i10n" => []
                            ],
                            [
                                "slug" => "trs",
                                "name" => "Treasury Stock",
                                "i10n" => []
                            ],
                            [
                                "slug" => "itf",
                                "name" => "Intention to Float",
                                "i10n" => []
                            ],
                            [
                                "slug" => "can",
                                "name" => "Change of Name",
                                "i10n" => []
                            ],
                            [
                                "slug" => "cir",
                                "name" => "Circ re. [insert appropriate document title]",
                                "i10n" => []
                            ],
                            [
                                "slug" => "cos",
                                "name" => "Company Secretary Change",
                                "i10n" => []
                            ],
                            [
                                "slug" => "rdn",
                                "name" => "Director Declaration",
                                "i10n" => []
                            ],
                            [
                                "slug" => "doc",
                                "name" => "Doc re. [insert appropriate document title]", // TODO research the impact of these
                                "i10n" => []
                            ],
                            [
                                "slug" => "nar",
                                "name" => "New Accounting Ref Date",
                                "i10n" => []
                            ],
                            [
                                "slug" => "noa",
                                "name" => "Notice of AGM",
                                "i10n" => []
                            ],
                            [
                                "slug" => "nog",
                                "name" => "Notice of GM",
                                "i10n" => []
                            ],
                            [
                                "slug" => "nor",
                                "name" => "Notice of Results",
                                "i10n" => []
                            ],
                            [
                                "slug" => "odp",
                                "name" => "Offer Document Posted",
                                "i10n" => []
                            ],
                        ]
                    ],
                    [
                        "slug" => "cl",
                        "name" => "Classification",
                        "i10n" => [],
                        "children" => [
                            [
                                "slug" => "1-1",
                                "name" => "Annual financial and audit reports",
                                "i10n" => []
                            ],
                            [
                                "slug" => "1-2",
                                "name" => "Half yearly financial reports and audit reports/limited reviews",
                                "i10n" => []
                            ],
                            [
                                "slug" => "1-3",
                                "name" => "Payment to government",
                                "i10n" => []
                            ],
                            [
                                "slug" => "2-2",
                                "name" => "Inside information",
                                "i10n" => []
                            ],
                            [
                                "slug" => "2-3",
                                "name" => "Major shareholding notifications",
                                "i10n" => []
                            ],
                            [
                                "slug" => "2-4",
                                "name" => "Acquisition or disposal of the issuer’s own shares",
                                "i10n" => []
                            ],
                            [
                                "slug" => "2-5",
                                "name" => "Total number of voting rights and capital",
                                "i10n" => []
                            ],
                            [
                                "slug" => "2-6",
                                "name" => "Changes in the rights attaching to the classes of shares or securities",
                                "i10n" => []
                            ],
                            [
                                "slug" => "3-1",
                                "name" => "Additional regulated information required to be disclosed under the laws of the United Kingdom",
                                "i10n" => []
                            ]
                        ]
                    ],
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
