<?php

require_once(__DIR__ . '/api.php');
require_once(__DIR__ . '/consts.php');

// Register and load the widget
function mfn_load_widget()
{
    register_widget('mfn_archive_widget');
    register_widget('mfn_subscription_widget');
    register_widget('mfn_news_feed_widget');
}

add_action('widgets_init', 'mfn_load_widget');

function create_mfn_wid_translate()
{
    $l10n = array(
        'Financial reports' => ['sv' => "Finansiella rapporter", 'fi' => "Taloudelliset raportit"],
        'All' => ['sv' => "Alla", 'fi' => 'Kaikki'],
        'Interim reports' => ['sv' => "Kvartalsrapport", 'fi' => "Osavuosikatsaukset"],
        'Annual Reports' => ['sv' => "Årsredovisning", 'fi' => "Vuosiraportit"],
        'Filter' => ['sv' => "Filter", 'fi' => "Suodattaa"],
        'Next' => ['sv' => "Nästa", 'fi' => "Seuraava"],
        'Previous' => ['sv' => "Föregående", 'fi' => "Edellinen"],
        'Press releases' => ['sv' => "Pressmeddelanden", 'fi' => "Lehdistötiedotteet"],
        'Reports' => ['sv' => "Rapporter", 'fi' => "Raportit"],
        'Annual reports' => ['sv' => "Årsredovisning", 'fi' => "Vuosiraportit"],
        'Other news' => ['sv' => "Övriga nyheter", 'fi' => "Muut uutiset"],
        'Subscribe' => ['sv' => "Prenumerera", 'fi' => "Tilaa"],
        'Approve' => ['sv' => "Godkänn", 'fi' => "Hyväksy"],
        'A real email address must be provided.' => ['sv' => "Välj en korrekt emailadress.", 'fi' => "Oikea sähköpostiosoite on annettava."],
        'The GDPR policy must be accepted.' => ['sv' => "GDPR policyn måste godkännas", 'fi' => "GDPR-politiikka on hyväksyttävä"],
        'An email has been sent to confirm your subscription.' => ['sv' => "Ett email har skickats till adressen, bekräfta det för att slutföra prenumerationen.", 'fi' => "Sähköposti on lähetetty osoitteeseen, vahvista se tilauksen loppuun saattamiseksi."],
        'Check the languages you would like to subscribe to.' => ['sv' => "Välj vilka språk du vill prenumerera på.", 'fi' => "Valitse kielet, jotka haluat tilata."],
        'Receive company data continuously to your inbox.' => ['sv' => "Få kontinuerlig information från bolaget via email.", 'fi' => "Saa säännöllisesti tietoja yritykseltä sähköpostitse."],
        'Check the category of messages you would like to subscribe to below.' => ['sv' => "Välj vilka typer av meddelanden du vill prenumerera på genom att fylla i checkboxen för respektive typ.", 'fi' => "Valitse tilaamasi viestityypit täyttämällä kunkin tyypin valintaruutu."],
        'sv-name' => ['sv' => "Svenska", 'en' => "Swedish", 'fi' => 'Ruotsi'],
        'en-name' => ['sv' => "Engelska", 'en' => "English", 'fi' => 'Englanti'],
        'fi-name' => ['sv' => "Finska", 'en' => "Finnish", 'fi' => 'Suomi'],
        'Interim Report' => ['sv' => "Kvartalsrapport", 'fi' => "Osavuosikatsaukset"],
        'Year-end Report' => ['sv' => "Bokslutskommuniké", 'fi' => "Tilinpäätöstiedote"],
        'Annual Report' => ['sv' => "Årsredovisning", 'fi' => "Vuosiraportit"],
    );

    return static function ($word, $lang) use ($l10n) {
        if (empty($l10n[$word])) {
            return $word;
        }
        if (empty($l10n[$word][$lang])) {
            return $word;
        }
        return $l10n[$word][$lang];
    };
}
$mfn_wid_translate = create_mfn_wid_translate();

// Determine locale
function determineLocale() {
    if (function_exists('determine_locale')) {
        return determine_locale();
    }
    return get_locale();
}

// Creating the widget
class mfn_archive_widget extends WP_Widget
{

    public function __construct()
    {
        parent::__construct(
            'mfn_archive_widget',
            __('MFN Report Archive', 'mfn_archive_widget_domain'),
            array('description' => __('A widget that creates an archive for reports', 'mfn_archive_widget_domain'),)
        );
    }

    public function widget($args, $instance)
    {

        $w = array(
            'showfilter' => $instance['showfilter'] ?? false,
            'showthumbnail' => $instance['showthumbnail'] ?? false,
            'showgenerictitle' => $instance['showgenerictitle'] ?? false,
            'usefiscalyearoffset' => $instance['usefiscalyearoffset'] ?? true,
            'fiscalyearoffset' => $instance['fiscalyearoffset'] ?? 0,
            'limit' => $instance['limit'] ?? 500,
            'exclude_latest' => $instance['exclude_latest'] ?? false,
            'instance_id' => random_int(1, time())
        );

        $l = static function ($word, $lang) {
            global $mfn_wid_translate;
            return $mfn_wid_translate($word, $lang);
        };

        echo $args['before_widget'];

        $lang = 'en';
        $locale = determineLocale();
        if (is_string($locale)) {
            $parts = explode("_", $locale);
            if (strlen($parts[0]) === 2) {
                $lang = $parts[0];
            }
        }

        $pmlang = empty($instance['lang']) ? 'all' : $instance['lang'];
        if ($pmlang === 'auto') {
            $pmlang = $lang;
        }

        echo "<div class=\"mfn-report-container all\" id=\"mfn-report-archive-id-" . $w['instance_id'] . "\">";

        if (!empty($instance['showheading'])) {
            echo "<h2>" . $l("Financial reports", $lang) . "</h2>";
        }

        $fiscal_year_offset = null;
        if ($w['usefiscalyearoffset']) {
            $fiscal_year_offset = $w['fiscalyearoffset'];
        }

        $reports = MFN_get_reports($pmlang, 0, $w['limit'], 'DESC', $fiscal_year_offset);

        if (count($reports) < 1) {
            return;
        }

        if (empty($instance['showyear'])) {
            echo "<style>#mfn-year-header-id-" . $w['instance_id'] . "{ display: none; }</style>";
        }

        if (empty($instance['showdate'])) {
            echo "<style>#mfn-report-date-id-" . $w['instance_id'] . "{ display: none; }</style>";
        }

        echo '
        <style>
            ul.mfn-report-items {
                list-style: none;
                padding-left: 0;
            }
            .mfn-report-thumbnail {
                width: 150px;
            }
            .mfn-report-year::before {
                content: "\00a0";
            }
            .mfn-report-quarter::before {
                content: "\00a0";
            }
        </style>
        ';
        if ($w['showfilter']) {
            echo "
            <style>
                .mfn-filter ul {
                    list-style: none;
                    display: inline-block;
                }
                .mfn-filter li {
                    cursor: pointer;
                    display: inline-block;
                    padding-right: 1em;
                }
                .mfn-report-container.annual .mfn-report-interim {
                    display: none;
                }
                .mfn-report-container.interim .mfn-report-annual {
                    display: none;
                }
                .mfn-report-container.all .mfn-filter .all,
                .mfn-report-container.annual .mfn-filter .annual,
                .mfn-report-container.interim .mfn-filter .interim {
                    text-decoration: underline;
                }
            </style>
            <script>
                function MFN_SET_FILTER(type, instance_id) {
                    var list = document.querySelector('#mfn-report-archive-id-' + instance_id);
                    list.classList.remove('all');
                    list.classList.remove('annual');
                    list.classList.remove('interim');
                    list.classList.add(type);
                }
            </script>
            <div class=\"mfn-filter\">
            " . $l('Filter', $lang) . ":
                <ul>
                    <li class=\"all\" onclick=\"MFN_SET_FILTER('all', '" . $w['instance_id'] . "')\">" . $l('All', $lang) . "</li>
                    <li class=\"interim\" onclick=\"MFN_SET_FILTER('interim', '" . $w['instance_id'] . "')\">" . $l('Interim reports', $lang) . "</li>
                    <li class=\"annual\" onclick=\"MFN_SET_FILTER('annual', '" . $w['instance_id'] . "')\">" . $l('Annual Reports', $lang) . "</li>
                </ul>
            </div>
            ";
        }

        $year = "";
        foreach ($reports as $k => $r) {

            $y = $r->year;
            if ($y !== $year) {
                if ($year !== "") {
                    echo "</ul>";
                }

                $year = $y;
                echo "<h3 class='mfn-year-header' id='mfn-year-header-id-" . $w['instance_id'] . "'>$year</h3>";
                echo "<ul class='mfn-report-items'>";
            }

            $date = substr($r->timestamp, 0, 10);

            $parts = explode('-', $r->type);
            $base_type = implode("-", array_slice($parts, 0, count($parts)-1));

            $li  = "<li class='mfn-report-item mfn-report-year-$year $base_type $r->type'>";
            $li .=   "<span class='mfn-report-date' id='mfn-report-date-id-" . $w['instance_id'] . "'>$date</span>";

            if ($w['showthumbnail']) {
                $li .=   "<div class='mfn-report-thumbnail'>";
                $li .=     "<a href=\"$r->url\" target=\"_blank\" rel='noopener'>";
                $li .=       "<img src=\"https://storage.mfn.se/proxy?url=" . $r->url . "&type=jpg\" />";
                $li .=     "</a>";
                $li .=   "</div>";
            }

            $li .=   "<span class='mfn-report-title'>";
            $li .=     "<a href=\"$r->url\" target=\"_blank\" rel='noopener'>";

            if ($w['showgenerictitle']) {

                global $mfn_wid_translate;

                $report_names = [
                    'mfn-report-interim-q4' => "Year-end Report",
                    'mfn-report-interim' => "Interim Report",
                    'mfn-report-annual' => "Annual Report"
                ];

                $base_title = $report_names[$r->type] ?? $report_names[$base_type];
                $base_title = $mfn_wid_translate($base_title, $r->lang);

                $li .=     "<span class='mfn-report-base-title mfn-report-base-title-$r->lang'>";
                $li .=       $base_title;
                $li .=     "</span>";

                if ($r->type !== 'mfn-report-annual') {
                    $li .=     "<span class='mfn-report-quarter mfn-report-quarter-" . end($parts) . "'>";
                    $li .=       strtoupper(end($parts));
                    $li .=     "</span>";
                }

                $li .=     "<span class='mfn-report-year'>";
                $li .=       $r->year;
                $li .=     "</span>";

            } else {
                $li .=       $r->title;
            }
            $li .=     "</a>";
            $li .=   "</span>";

            $li .= "</li>";

            if ($k === 0 && $w['exclude_latest']) {
                continue;
            }

            echo $li;

        }
        echo "</ul>";
        echo "</div>";

        echo $args['after_widget'];
    }

    public function form($instance)
    {
        if (isset($instance['lang'])) {
            $lang = $instance['lang'];
        } else {
            $lang = 'auto';
        }

        if (isset($instance['showheading'])) {
            $showheading = $instance['showheading'];
        } else {
            $showheading = '0';
        }

        if (isset($instance['showfilter'])) {
            $showfilter = $instance['showfilter'];
        } else {
            $showfilter = '1';
        }

        if (isset($instance['showyear'])) {
            $showyear = $instance['showyear'];
        } else {
            $showyear = '1';
        }

        if (isset($instance['showdate'])) {
            $showdate = $instance['showdate'];
        } else {
            $showdate = '1';
        }

        if (isset($instance['showthumbnail'])) {
            $showthumbnail = $instance['showthumbnail'];
        } else {
            $showthumbnail = '0';
        }

        if (isset($instance['showgenerictitle'])) {
            $showgenerictitle = $instance['showgenerictitle'];
        } else {
            $showgenerictitle = '0';
        }

        if (isset($instance['usefiscalyearoffset'])) {
            $usefiscalyearoffset = $instance['usefiscalyearoffset'];
        } else {
            $usefiscalyearoffset = '1';
        }

        if (isset($instance['fiscalyearoffset'])) {
            $fiscalyearoffset = $instance['fiscalyearoffset'];
        } else {
            $fiscalyearoffset = '0';
        }

        if (isset($instance['limit'])) {
            $limit = $instance['limit'];
        } else {
            $limit = '500';
        }

        if (isset($instance['exclude_latest'])) {
            $exclude_latest = $instance['exclude_latest'];
        } else {
            $exclude_latest = '0';
        }

        ?>

        <p>
            <label for="<?php echo $this->get_field_id('lang'); ?>"><?php _e('Archive Language', 'text_domain'); ?></label>
            <select name="<?php echo $this->get_field_name('lang'); ?>" id="<?php echo $this->get_field_id('lang'); ?>"
                    class="widefat">
                <?php
                // Your options array
                $options = array(
                    'all' => __('All', 'text_domain'),
                    'auto' => __('Auto (best effort to figure out what lang is used)', 'text_domain'),
                    'sv' => __('Swedish', 'text_domain'),
                    'en' => __('English', 'text_domain'),
                    'fi' => __('Finnish', 'text_domain'),
                );

                // Loop through options and add each one to the select dropdown
                foreach ($options as $key => $name) {
                    echo '<option value="' . esc_attr($key) . '" id="' . esc_attr($key) . '" ' . selected($lang, $key, false) . '>' . $name . '</option>';

                } ?>
            </select>
        </p>

        <p>
            <input id="<?php echo esc_attr($this->get_field_id('showheading')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('showheading')); ?>" type="checkbox"
                   value="1" <?php checked('1', $showheading); ?> />
            <label for="<?php echo esc_attr($this->get_field_id('showheading')); ?>"><?php _e('Show heading', 'text_domain'); ?></label>
        </p>
        <p>
            <input id="<?php echo esc_attr($this->get_field_id('showfilter')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('showfilter')); ?>" type="checkbox"
                   value="1" <?php checked('1', $showfilter); ?> />
            <label for="<?php echo esc_attr($this->get_field_id('showfilter')); ?>"><?php _e('Show filter', 'text_domain'); ?></label>
        </p>

        <p>
            <input id="<?php echo esc_attr($this->get_field_id('showyear')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('showyear')); ?>" type="checkbox"
                   value="1" <?php checked('1', $showyear); ?> />
            <label for="<?php echo esc_attr($this->get_field_id('showyear')); ?>"><?php _e('Show year', 'text_domain'); ?></label>
        </p>

        <p>
            <input id="<?php echo esc_attr($this->get_field_id('showdate')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('showdate')); ?>" type="checkbox"
                   value="1" <?php checked('1', $showdate); ?> />
            <label for="<?php echo esc_attr($this->get_field_id('showdate')); ?>"><?php _e('Show date of report', 'text_domain'); ?></label>
        </p>

        <p>
            <input id="<?php echo esc_attr($this->get_field_id('showthumbnail')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('showthumbnail')); ?>" type="checkbox"
                   value="1" <?php checked('1', $showthumbnail); ?> />
            <label for="<?php echo esc_attr($this->get_field_id('showthumbnail')); ?>"><?php _e('Show thumbnail', 'text_domain'); ?></label>
        </p>

        <p>
            <input id="<?php echo esc_attr($this->get_field_id('showgenerictitle')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('showgenerictitle')); ?>" type="checkbox"
                   value="1" <?php checked('1', $showgenerictitle); ?> />
            <label for="<?php echo esc_attr($this->get_field_id('showgenerictitle')); ?>"><?php _e('Show generic title', 'text_domain'); ?></label>
        </p>

        <p>
            <input id="<?php echo esc_attr($this->get_field_id('usefiscalyearoffset')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('usefiscalyearoffset')); ?>" type="checkbox"
                   value="1" <?php checked('1', $usefiscalyearoffset); ?> />
            <label for="<?php echo esc_attr($this->get_field_id('usefiscalyearoffset')); ?>"><?php _e('Use fiscal year for grouping', 'text_domain'); ?></label>
        </p>

        <p>
            <input id="<?php echo esc_attr($this->get_field_id('exclude_latest')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('exclude_latest')); ?>" type="checkbox"
                   value="1" <?php checked($exclude_latest, '1'); ?> />
            <label for="<?php echo esc_attr($this->get_field_id('exclude_latest')); ?>"><?php _e('Exclude the latest report', 'text_domain'); ?></label>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('fiscalyearoffset')); ?>"><?php _e('Fiscal year offset', 'text_domain'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('fiscalyearoffset')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('fiscalyearoffset')); ?>" type="text"
                   value="<?php echo esc_attr($fiscalyearoffset); ?>"/>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('limit')); ?>"><?php _e('Limit amount of reports to show', 'text_domain'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('limit')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('limit')); ?>" type="text"
                   value="<?php echo esc_attr($limit); ?>"/>
        </p>

        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['lang'] = (!empty($new_instance['lang'])) ? strip_tags($new_instance['lang']) : '';
        $instance['showheading'] = (!empty($new_instance['showheading'])) ? strip_tags($new_instance['showheading']) : '';
        $instance['showfilter'] = (!empty($new_instance['showfilter'])) ? strip_tags($new_instance['showfilter']) : '';
        $instance['showyear'] = (!empty($new_instance['showyear'])) ? strip_tags($new_instance['showyear']) : '';
        $instance['showdate'] = (!empty($new_instance['showdate'])) ? strip_tags($new_instance['showdate']) : '';
        $instance['showgenerictitle'] = (!empty($new_instance['showgenerictitle'])) ? strip_tags($new_instance['showgenerictitle']) : '';
        $instance['showthumbnail'] = (!empty($new_instance['showthumbnail'])) ? strip_tags($new_instance['showthumbnail']) : '';
        $instance['usefiscalyearoffset'] = (!empty($new_instance['usefiscalyearoffset'])) ? strip_tags($new_instance['usefiscalyearoffset']) : '';
        $instance['fiscalyearoffset'] = (!empty($new_instance['fiscalyearoffset'])) ? strip_tags($new_instance['fiscalyearoffset']) : '';
        $instance['limit'] = (!empty($new_instance['limit'])) ? strip_tags($new_instance['limit']) : '';
        $instance['exclude_latest'] = (!empty($new_instance['exclude_latest'])) ? strip_tags($new_instance['exclude_latest']) : '';
        return $instance;
    }
} //

// Creating the widget
class mfn_subscription_widget extends WP_Widget
{

    public function __construct()
    {
        parent::__construct(
            'mfn_subscription_widget',
            __('MFN Subscription Widget', 'mfn_subscription_widget_domain'),
            array('description' => __('A widget that adds MFN subscription possibilities', 'mfn_subscription_widget_domain'),)
        );
    }

    public function widget($args, $instance)
    {
        $l = static function ($word, $lang) {
            global $mfn_wid_translate;
            return $mfn_wid_translate($word, $lang);
        };

        echo $args['before_widget'];

        $lang = empty($instance['lang']) ? 'auto' : $instance['lang'];
        $locale = determineLocale();

        if ($lang === "auto") {
            if (is_string($locale)) {
                $parts = explode("_", $locale);
                if (strlen($parts[0]) === 2) {
                    $lang = $parts[0];
                }
            }
        }

        if ($lang === "auto") {
            $lang = "en";
        }

        $langs = strtolower(trim(empty($instance['langs']) ? 'sv,en' : $instance['langs']));
        $langs = explode(",", $langs);

        $privacy_policy = empty($instance['privacy_policy']) ? "https://mfn.se/privacy-policy" : $instance['privacy_policy'];

        $ops = get_option('mfn-wp-plugin');
        $entity_id = $ops['entity_id'] ?? "bad-entity-id";
        $hub_url = $ops['hub_url'] ?? "bad-hub-url";

        if (empty($instance['showlangs'])) {
            echo "<style>.mfn-subscribe .mfn-languages { display: none; }</style>";
        }

        if (empty($instance['showtypes'])) {
            echo "<style>.mfn-subscribe .mfn-categories { display: none; }</style>";
        }

        ?>

        <style>
            .mfn-subscribe .hidden {
                display: none;
            }

            #policy-text {
                border-bottom: 5px solid transparent;
            }

            #policy-text.alert {
                border-bottom-color: red;
            }
        </style>
        <div id="mfn-subscribe-div" class="mfn-subscribe">
            <input type="hidden" id="sub-hub-entity-id" name="hub.entityid" value="<?php echo $entity_id ?>">
            <input type="hidden" id="sub-hub-topic" name="hub.topic" value="/s">
            <input type="hidden" id="sub-hub-url" name="hub.url" value="<?php echo $hub_url ?>">
            <input type="hidden" id="sub-hub-lang" name="hub.lang" value="<?php echo $lang ?>">
            <input type="hidden" id="sub-hub-subscribe-to-widget-language" name="hub.subscribe-to-widget-language"
                   value="{{.Settings.SubscribeToWidgetLanguage}}">

            <div class="mfn-info">
               <p><?php echo $l("Receive company data continuously to your inbox.", $lang) ?></p>
            </div>

            <div class="mfn-info mfn-categories">
                <p>
                    <?php echo $l("Check the category of messages you would like to subscribe to below.", $lang) ?>
                </p>
                <ul>
                    <li>
                        <input checked id="sub-ir" type="checkbox">
                        <label for="sub-ir"><?php echo $l("Press releases", $lang) ?></label>
                    </li>
                    <li>
                        <input checked id="sub-report" type="checkbox">
                        <label for="sub-report"><?php echo $l("Reports", $lang) ?></label>
                    </li>
                    <li>
                        <input checked id="sub-annual" type="checkbox">
                        <label for="sub-annual"><?php echo $l("Annual reports", $lang) ?></label>
                    </li>
                    <li>
                        <input checked id="sub-pr" type="checkbox">
                        <label for="sub-pr"><?php echo $l("Other news", $lang) ?></label>
                    </li>
                </ul>
            </div>

            <div class="mfn-info mfn-languages">
                <p><?php echo $l("Check the languages you would like to subscribe to.", $lang) ?></p>
                <ul>
                    <?php
                    foreach ($langs as $la) {
                        $name = $l($la . "-name", $lang);
                        echo "<li>
                                 <input class=\"mfn-sub-lang\" id=\"mfn-sub-lang-$la\" type=\"checkbox\" checked>
                                 <label for=\"mfn-sub-lang-$la\">$name</label>
                             </li>";
                    }
                    ?>
                </ul>
            </div>

            <div>
                <p id="policy-text">
                    <?php
                        if ($lang === "sv") {
                            echo "För att prenumerera på detta behöver du godkänna våra <a href=\"$privacy_policy\" target=\"_blank\">generella villkor</a> i syfte för GDPR.";
                        }
                        if ($lang === "en") {
                            echo "To subscribe, please read and approve our <a href=\"$privacy_policy\" target=\"_blank\">data storage policy</a> to comply with GDPR.";
                        }
                        if ($lang === "fi") {
                            echo "Tilataksesi, lue ja hyväksy <a href=\"$privacy_policy\" target=\"_blank\">tietojen tallennuskäytäntömme</a> noudattamaan GDPR: ää.";
                        }
                    ?>
                    <div class="mfn-approve-container">
                        <label for="approve">
                            <?php echo $l("Approve", $lang) ?>
                        </label>
                        <input id="approve" onclick="document.getElementById('gdpr-policy-fail').classList.add('hidden');" type="checkbox">
                    </div>
                </p>
            </div>

            <div class="subscription-wrapper">
                <form onsubmit="event.preventDefault(); return datablocks_SubscribeMail()">
                    <label for="sub-email"></label><input id="sub-email" type="text" placeholder="Email" name="hub.callback">
                    <button type="submit">
                        <?php echo $l("Subscribe", $lang); ?>
                    </button>
                </form>
            </div>
            <div id="email-bad-input" class="hidden warning mfn-info alert">
                <?php echo $l("A real email address must be provided.", $lang); ?>
            </div>
            <div id="gdpr-policy-fail" class="hidden warning mfn-info alert">
                <?php echo $l("The GDPR policy must be accepted.", $lang); ?>
            </div>
            <div id="email-success" class="hidden success mfn-info">
                <?php echo $l("An email has been sent to confirm your subscription.", $lang); ?>
            </div>
        </div>
        <?php
        echo "<script>" . JS_SUB_LIB . "</script>";

        echo $args['after_widget'];
    }

    public function form($instance)
    {
        $lang = $instance['lang'] ?? 'auto';
        $privacy_policy = $instance['privacy_policy'] ?? "https://mfn.se/privacy-policy";

        if (isset($instance['langs'])) {
            $langs = strtolower(trim($instance['langs']));
        } else {
            $langs = 'sv,en';
        }

        if (isset($instance['showlangs'])) {
            $showlangs = strtolower(trim($instance['showlangs']));
        } else {
            $showlangs = '1';
        }

        if (isset($instance['showtypes'])) {
            $showtypes = strtolower(trim($instance['showtypes']));
        } else {
            $showtypes = '1';
        }

        ?>

        <p>
            <label for="<?php echo $this->get_field_id('lang'); ?>"><?php _e('Language', 'text_domain'); ?></label>
            <select name="<?php echo $this->get_field_name('lang'); ?>" id="<?php echo $this->get_field_id('lang'); ?>"
                    class="widefat">
                <?php
                // Your options array
                $options = array(
                    'auto' => __('Auto (best effort to figure out what lang is used)', 'text_domain'),
                    'sv' => __('Swedish', 'text_domain'),
                    'en' => __('English', 'text_domain'),
                    'fi' => __('Finnish', 'text_domain'),
                );

                // Loop through options and add each one to the select dropdown
                foreach ($options as $key => $name) {
                    echo '<option value="' . esc_attr($key) . '" id="' . esc_attr($key) . '" ' . selected($lang, $key, false) . '>' . $name . '</option>';

                } ?>
            </select>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('langs')); ?>"><?php _e('Languages to select (eg sv,en)', 'text_domain'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('langs')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('langs')); ?>" type="text"
                   value="<?php echo esc_attr($langs); ?>"/>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('privacy_policy')); ?>"><?php _e('GDPR Policy link', 'text_domain'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('privacy_policy')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('privacy_policy')); ?>" type="text"
                   value="<?php echo esc_attr($privacy_policy); ?>"/>
        </p>

        <p>
            <input id="<?php echo esc_attr($this->get_field_id('showtypes')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('showtypes')); ?>" type="checkbox"
                   value="1" <?php checked('1', $showtypes); ?> />
            <label for="<?php echo esc_attr($this->get_field_id('showtypes')); ?>"><?php _e('Show Categories', 'text_domain'); ?></label>
        </p>

        <p>
            <input id="<?php echo esc_attr($this->get_field_id('showlangs')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('showlangs')); ?>" type="checkbox"
                   value="1" <?php checked('1', $showlangs); ?> />
            <label for="<?php echo esc_attr($this->get_field_id('showlangs')); ?>"><?php _e('Show Languages', 'text_domain'); ?></label>
        </p>

        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['lang'] = (!empty($new_instance['lang'])) ? strip_tags($new_instance['lang']) : '';
        $instance['langs'] = (!empty($new_instance['langs'])) ? strip_tags($new_instance['langs']) : '';
        $instance['showlangs'] = (!empty($new_instance['showlangs'])) ? strip_tags($new_instance['showlangs']) : '';
        $instance['showtypes'] = (!empty($new_instance['showtypes'])) ? strip_tags($new_instance['showtypes']) : '';
        $instance['privacy_policy'] = strtolower(trim((!empty($new_instance['privacy_policy'])) ? strip_tags($new_instance['privacy_policy']) : ''));

        return $instance;
    }
} //

// Creating the widget
class mfn_news_feed_widget extends WP_Widget
{

    public function __construct()
    {
        parent::__construct(
            'mfn_news_feed_widget',
            __('MFN News Feed', 'mfn_news_feed_domain'),
            array('description' => __('A widget that creates a news feed', 'mfn_news_feed_domain'),)
        );

    }

    private function list_news_items($res, $tzLocation, $timestampFormat, $onlytagsallowed, $tagtemplate, $template, $groupbyyear) {
        $years = [];
        $group_by_year = $groupbyyear && !empty($res);

        foreach ($res as $k => $item) {
            $year = explode("-", $item->post_date_gmt)[0];
            if ($k === 0) {
                $years[] = $year;

                if ($group_by_year) {
                    $this->parse_year_header($year);
                }
            } else if (!in_array($year, $years, true)) {
                if ($group_by_year) {
                    $this->parse_year_header($year);
                }
                $years[] = $year;
            }

            $date = new DateTime($item->post_date_gmt . "Z");
            $date->setTimezone(new DateTimeZone($tzLocation));
            $datestr = date_i18n($timestampFormat,$date->getTimestamp() + $date->getOffset());

            $tags = "";
            foreach ($item->tags as $tag) {
                $parts = explode(":", $tag);
                if (count($onlytagsallowed) > 0) {
                    $key = array_search($parts[1], $onlytagsallowed, true);
                    if (!is_numeric($key)) {
                        continue;
                    }
                }
                $html = $tagtemplate;
                $html = str_replace(array("[tag]", "[slug]"), array($parts[0], $parts[1]), $html);
                $tags .= $html;
            }

            $templateData = array(
                'date' => $datestr,
                'title' => $item->post_title,
                'url' => get_home_url() . "/mfn_news/" . $item->post_name,
                'tags' => $tags,
            );

            $html = $template;
            foreach ($templateData as $key => $value) {
                $html = str_replace("[$key]", $value, $html);
            }

            echo $html;
        }
    }

    public function parse_year_header($year) {
        echo "<h4 class='mfn-feed-year-header' id='mfn-feed-year-header-" . $year . "'>$year</h4>";
    }

    public function widget($args, $instance)
    {
        $query_param = static function ($name, $default) {
            return $_GET[$name] ?? $default;
        };

        $l = static function ($word, $lang) {
            global $mfn_wid_translate;
            return $mfn_wid_translate($word, $lang);
        };

        echo $args['before_widget'];

        global $wp;
        $baseurl = explode('?', home_url(add_query_arg(array(), $wp->request)))[0];

        $tzLocation = empty($instance['tzLocation']) ? 'Europe/Stockholm' : $instance['tzLocation'];
        $timestampFormat = empty($instance['timestampFormat']) ? 'Y-m-d H:i' : $instance['timestampFormat'];

        if (isset($instance['tzlocation'])) {
            $tzLocation = normalize_whitespace($instance['tzlocation']);
        }

        if (isset($instance['timestampformat'])) {
            $timestampFormat = normalize_whitespace($instance['timestampformat']);
        }

        $pagelen = empty($instance['pagelen']) ? 20 : $instance['pagelen'];
        $showyears = empty($instance['showyears']) ? false : $instance['showyears'];
        $groupbyyear = empty($instance['groupbyyear']) ? false : $instance['groupbyyear'];
        $showpagination = empty($instance['showpagination']) ? false : $instance['showpagination'];

        $lang = 'en';
        $locale = determineLocale();
        if (is_string($locale)) {
            $parts = explode("_", $locale);
            if (strlen($parts[0]) === 2) {
                $lang = $parts[0];
            }
        }

        $pmlang = empty($instance['lang']) ? 'all' : $instance['lang'];
        if ($pmlang === 'auto') {
            $pmlang = $lang;
        }

        $page = $query_param('m-page', 0);
        if ($page < 0) {
            return;
        }

        $tagsstr = $query_param('m-tags', "");
        if (isset($instance['tags'])) {
            $tagsstr = normalize_whitespace($instance['tags']);
        }
        $hasTags = array();
        $hasNotTags = array();
        foreach (explode(",", $tagsstr) as $tag) {
            if ($tag === "") {
                continue;
            }
            if (strpos($tag, '-') === 0 || strpos($tag, '!') === 0) {
                $tag = substr($tag, 1);
                if (strpos($tag, 'mfn-') !== 0) {
                    $tag = 'mfn-' . $tag;
                }

                $hasNotTags[] = $tag;
                continue;
            }

            if (strpos($tag, 'mfn-') !== 0) {
                $tag = 'mfn-' . $tag;
            }
            $hasTags[] = $tag;
        }

        $year = $query_param('m-year', "");
        $y = $year;

        if (isset($instance['year'])) {
            $year = normalize_whitespace($instance['year']);
        }
        else if (empty($year)) {
            $y = "";
        }

        $min_max_years = MFN_get_feed_min_max_years($lang);
        $res = MFN_get_feed($pmlang, $y, $hasTags, $hasNotTags, $page * $pagelen, $pagelen);

        $template = empty($instance['template']) ? "
        <div class='mfn-item'>
            <div class='mfn-date'>[date]</div>
            <div class='mfn-tags'>[tags]</div>
            <div class='mfn-title'><a href='[url]'>[title]</a></div>
        </div>
        " : $instance['template'];

        $tagtemplate = empty($instance['tagtemplate']) ? "
        <div class='mfn-tag mfn-tag-[slug]'>[tag]</div>
        " : $instance['tagtemplate'];

        $yeartemplate = empty($instance['yeartemplate']) ? "
        <a href='[url]' class='[mfn-year-selected]'>[year]</a>
        " : $instance['yeartemplate'];

        $onlytagsallowed = array();
        $onlytagsallowedstr = empty($instance['onlytagsallowed']) ? "" : $instance['onlytagsallowed'];
        if ($onlytagsallowedstr !== "") {
            $onlytagsallowed = explode(",", $onlytagsallowedstr);
        }

        echo "
        <style>
            .mfn-tags { float: right; }
            .mfn-tag { display: inline-block; }
            .mfn-date { display: inline-block; }
        </style>";

        if (!$showyears) {
            echo "<style>.mfn-newsfeed-year-selector { display: none; }</style>";
        }
        if (!$showpagination) {
            echo "<style>.mfn-newsfeed-pagination { display: none; }</style>";
        }

        echo "<div class=\"mfn-newsfeed\">";

//        echo "<div class='mfn-newsfeed-tag-selector'>";
//            $params = http_build_query(array_merge($_GET, array('m-tags' => 'regulatory')));
//            $url1 = $baseurl . "?" . $params;
//            $params = http_build_query(array_merge($_GET, array('m-tags' => '-regulatory')));
//            $url2 = $baseurl . "?" . $params;
//            $params = http_build_query(array_merge($_GET, array('m-tags' => 'report')));
//            $url3 = $baseurl . "?" . $params;
//            echo "<a href='$url1'>Regulatory</a>";
//            echo "<a href='$url2'>Non-Regulatory</a>";
//            echo "<a href='$url3'>Reports</a>";
//        echo "</div>";

        if (is_object($min_max_years) &&
            isset($min_max_years->max_year) &&
            isset($min_max_years->min_year) &&
            is_numeric($min_max_years->max_year) &&
            is_numeric($min_max_years->min_year)) {

            echo "<div class='mfn-newsfeed-year-selector'>";
            for ($i = $min_max_years->max_year; $i >= $min_max_years->min_year; $i--) {
                $params = http_build_query(array_merge($_GET, array('m-year' => $i)));
                $url = $baseurl . "?" . $params;
                $html = $yeartemplate;
                $html = str_replace(array("[url]", "[year]", "[mfn-year-selected]"),
                    array($url, $i, $i === $year ? 'mfn-year-selected' : ''), $html);

                echo $html;
            }
            echo "</div>";
        }

        echo "<div class=\"mfn-list\">";

        $this->list_news_items($res, $tzLocation, $timestampFormat, $onlytagsallowed, $tagtemplate, $template, $groupbyyear);

        echo "</div></div><div class='mfn-newsfeed-pagination'>";

        if ($page > 0) {
            $params = http_build_query(array_merge($_GET, array('m-page' => $page - 1)));
            $url1 = $baseurl . "?" . $params;
            $word = $l("Previous", $lang);
            echo "<a href='$url1' class='mfn-next-link'>$word</a>";
        }

        if (count($res) == $pagelen) {
            $params = http_build_query(array_merge($_GET, array('m-page' => $page + 1)));
            $url2 = $baseurl . "?" . $params;
            $word = $l("Next", $lang);
            echo "<a href='$url2' class='mfn-next-link'>$word</a>";
        }

        echo "</div>";

        echo $args['after_widget'];
    }

    public function form($instance)
    {
        $lang = $instance['lang'] ?? 'auto';
        $pagelen = $instance['pagelen'] ?? '20';
        $showpagination = $instance['showpagination'] ?? '1';
        $showyears = $instance['showyears'] ?? '1';
        $groupbyyear = $instance['groupbyyear'] ?? '0';
        $tzLocation = $instance['tzLocation'] ?? 'Europe/Stockholm';
        // Format at https://www.php.net/manual/en/function.date.php#refsect1-function.date-parameters
        $timestampFormat = $instance['timestampFormat'] ?? 'Y-m-d H:i';

        if (isset($instance['template']) && $instance['template'] !== "") {
            $template = $instance['template'];
        } else {
            $template = "
                <div class='mfn-item'>
                    <div class='mfn-date'>[date]</div>
                    <div class='mfn-tags'>[tags]</div>
                    <div class='mfn-title'><a href='[url]'>[title]</a></div>
                </div>
            ";
        }

        if (isset($instance['tagtemplate']) && $instance['tagtemplate'] !== "") {
            $tagtemplate = $instance['tagtemplate'];
        } else {
            $tagtemplate = "<div class='mfn-tag mfn-tag-[slug]'>[tag]</div>";
        }

        if (isset($instance['yeartemplate']) && $instance['yeartemplate'] !== "") {
            $yeartemplate = $instance['yeartemplate'];
        } else {
            $yeartemplate = "<a href='[url]' class='[mfn-year-selected]'>[year]</a>";
        }

        $onlytagsallowed = $instance['onlytagsallowed'] ?? "";

        ?>

        <p>
            <input id="<?php echo esc_attr($this->get_field_id('showyears')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('showyears')); ?>" type="checkbox"
                   value="1" <?php checked('1', $showyears); ?> />
            <label for="<?php echo esc_attr($this->get_field_id('showyears')); ?>"><?php _e('Show Years', 'text_domain'); ?></label>
        </p>

        <p>
            <input id="<?php echo esc_attr($this->get_field_id('groupbyyear')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('groupbyyear')); ?>" type="checkbox"
                   value="1" <?php checked($groupbyyear, '1'); ?> />
            <label for="<?php echo esc_attr($this->get_field_id('groupbyyear')); ?>"><?php _e('Group By Year', 'text_domain'); ?></label>
        </p>

        <p>
            <input id="<?php echo esc_attr($this->get_field_id('showpagination')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('showpagination')); ?>" type="checkbox"
                   value="1" <?php checked('1', $showpagination); ?> />
            <label for="<?php echo esc_attr($this->get_field_id('showpagination')); ?>"><?php _e('Show Pagination', 'text_domain'); ?></label>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('lang'); ?>"><?php _e('Archive Language', 'text_domain'); ?></label>
            <select name="<?php echo $this->get_field_name('lang'); ?>" id="<?php echo $this->get_field_id('lang'); ?>"
                    class="widefat">
                <?php
                // Your options array
                $options = array(
                    'all' => __('All', 'text_domain'),
                    'auto' => __('Auto (best effort to figure out what lang is used)', 'text_domain'),
                    'sv' => __('Swedish', 'text_domain'),
                    'en' => __('English', 'text_domain'),
                    'fi' => __('Finnish', 'text_domain'),
                );

                // Loop through options and add each one to the select dropdown
                foreach ($options as $key => $name) {
                    echo '<option value="' . esc_attr($key) . '" id="' . esc_attr($key) . '" ' . selected($lang, $key, false) . '>' . $name . '</option>';
                } ?>
            </select>

        </p>
        <p>
            <label for="<?php echo $this->get_field_id('tzLocation'); ?>"><?php _e('Timestamp Location', 'text_domain'); ?></label>
            <select name="<?php echo $this->get_field_name('tzLocation'); ?>"
                    id="<?php echo $this->get_field_id('tzLocation'); ?>"
                    class="widefat">
                <?php
                // Your options array
                $options = array(
                    'Europe/Stockholm' => __('Stockholm', 'text_domain'),
                    'Europe/Copenhagen' => __('Copenhagen', 'text_domain'),
                    'Europe/Helsinki' => __('Helsinki', 'text_domain'),
                    'Europe/Oslo' => __('Oslo', 'text_domain'),
                    'Europe/London' => __('London', 'text_domain'),
                    'Europe/Paris' => __('Paris', 'text_domain'),
                    'Europe/Berlin' => __('Berlin', 'text_domain'),
                );

                // Loop through options and add each one to the select dropdown
                foreach ($options as $key => $name) {
                    echo '<option value="' . esc_attr($key) . '" id="' . esc_attr($key) . '" ' . selected($tzLocation, $key, false) . '>' . $name . '</option>';
                } ?>
            </select>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('pagelen')); ?>"><?php _e('# stories / page:', 'text_domain'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('pagelen')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('pagelen')); ?>" type="number"
                   value="<?php echo esc_attr($pagelen); ?>"/>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('timestampFormat')); ?>"><?php _e('Timestamp Format:', 'text_domain'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('timestampFormat')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('timestampFormat')); ?>" type="text"
                   value="<?php echo esc_attr($timestampFormat); ?>"/>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('template')); ?>">Template</label>
            <textarea rows="8" class="widefat" id="<?php echo esc_attr($this->get_field_id('template')); ?>"
                      name="<?php echo esc_attr($this->get_field_name('template')); ?>"><?php echo wp_kses_post($template); ?></textarea>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('tagtemplate')); ?>">Tag Template</label>
            <textarea rows="2" class="widefat" id="<?php echo esc_attr($this->get_field_id('tagtemplate')); ?>"
                      name="<?php echo esc_attr($this->get_field_name('tagtemplate')); ?>"><?php echo wp_kses_post($tagtemplate); ?></textarea>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('yeartemplate')); ?>">Year Template</label>
            <textarea rows="2" class="widefat" id="<?php echo esc_attr($this->get_field_id('yeartemplate')); ?>"
                      name="<?php echo esc_attr($this->get_field_name('yeartemplate')); ?>"><?php echo wp_kses_post($yeartemplate); ?></textarea>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('onlytagsallowed')); ?>">Show Only tags</label>
            <textarea rows="2" class="widefat" id="<?php echo esc_attr($this->get_field_id('onlytagsallowed')); ?>"
                      name="<?php echo esc_attr($this->get_field_name('onlytagsallowed')); ?>"><?php echo wp_kses_post($onlytagsallowed); ?></textarea>
        </p>

        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['lang'] = (!empty($new_instance['lang'])) ? wp_strip_all_tags($new_instance['lang']) : '';
        $instance['showpagination'] = (!empty($new_instance['showpagination'])) ? strip_tags($new_instance['showpagination']) : '';
        $instance['showyears'] = (!empty($new_instance['showyears'])) ? strip_tags($new_instance['showyears']) : '';
        $instance['groupbyyear'] = (!empty($new_instance['groupbyyear'])) ? strip_tags($new_instance['groupbyyear']) : '';
        $instance['pagelen'] = (!empty($new_instance['pagelen'])) ? wp_strip_all_tags($new_instance['pagelen']) : 20;
        $instance['tzLocation'] = (!empty($new_instance['tzLocation'])) ? wp_strip_all_tags($new_instance['tzLocation']) : '';
        $instance['timestampFormat'] = (!empty($new_instance['timestampFormat'])) ? wp_strip_all_tags($new_instance['timestampFormat']) : '';
        $instance['template'] = (!empty($new_instance['template'])) ? wp_kses_post($new_instance['template']) : '';
        $instance['tagtemplate'] = (!empty($new_instance['tagtemplate'])) ? wp_kses_post($new_instance['tagtemplate']) : '';
        $instance['yeartemplate'] = (!empty($new_instance['yeartemplate'])) ? wp_kses_post($new_instance['yeartemplate']) : '';
        $instance['onlytagsallowed'] = (!empty($new_instance['onlytagsallowed'])) ? wp_kses_post($new_instance['onlytagsallowed']) : '';
        return $instance;
    }
} //

function load_shortcode_mfn_archive_widget($atts)
{
    ob_start();
    the_widget('mfn_archive_widget', $atts);
    return ob_get_clean();
}

add_shortcode('mfn_archive_widget', 'load_shortcode_mfn_archive_widget');

function load_shortcode_mfn_news_feed_widget($atts)
{
    ob_start();
    the_widget('mfn_news_feed_widget', $atts);
    return ob_get_clean();
}

add_shortcode('mfn_news_feed_widget', 'load_shortcode_mfn_news_feed_widget');

function load_shortcode_mfn_subscription_widget($atts)
{
    ob_start();
    the_widget('mfn_subscription_widget', $atts);
    return ob_get_clean();
}

add_shortcode('mfn_subscription_widget', 'load_shortcode_mfn_subscription_widget');