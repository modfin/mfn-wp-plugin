<?php

require_once(__DIR__ . '/api.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/consts.php');
require_once(__DIR__ . '/config.php');

// Register and load the widget
function mfn_load_widget()
{
    register_widget('mfn_archive_widget');
    register_widget('mfn_archive_v2_widget');
    register_widget('mfn_subscription_widget');
    register_widget('mfn_news_feed_widget');
}

add_action('widgets_init', 'mfn_load_widget');

function bool_check($value): bool
{
    return ($value === 'true') || $value === "1";
}

function create_mfn_wid_translate()
{
    $l10n = array(
        'Financial reports' => ['sv' => "Finansiella rapporter", 'fi' => "Taloudelliset raportit"],
        'All' => ['sv' => "Alla", 'fi' => 'Kaikki'],
        'Interim reports' => ['sv' => "Kvartalsrapport", 'fi' => "Osavuosikatsaukset"],
        'Annual Reports' => ['sv' => "Årsredovisning", 'fi' => "Vuosiraportit"],
        'Filter' => ['sv' => "Filter", 'fi' => "Suodattaa"],
        'Category' => ['sv' => "Kategori", 'fi' => "Kategoria"],
        'Year' => ['sv' => "År", 'fi' => "Vuosi"],
        'Next' => ['sv' => "Nästa", 'fi' => "Seuraava"],
        'Previous' => ['sv' => "Föregående", 'fi' => "Edellinen"],
        'Interim Report' => ['sv' => "Kvartalsrapport", 'fi' => "Osavuosikatsaukset"],
        'Year-end Report' => ['sv' => "Bokslutskommuniké", 'fi' => "Tilinpäätöstiedote"],
        'Annual Report' => ['sv' => "Årsredovisning", 'fi' => "Vuosiraportit"],
        'Regulatory' => ['sv' => "Regulatorisk", 'fi' => 'Sääntelyä'],
        'Non-Regulatory' => ['sv' => "Icke-Regulatorisk", 'fi' => 'Sääntelemätönä'],
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
function determineLocale(): string
{
    if (function_exists('determine_locale')) {
        return determine_locale();
    }
    return get_locale();
}

function yearClass($year): string
{
    return trim(str_replace('/', '-', str_replace('*', '', $year)));
}

function load_datablocks_widget($widget_id, $widget_type, $lang) {

    $loaderURL = DATABLOCKS_LOADER_URL;
    $instance_id = random_int(1, time());

    $widget = new stdClass();
    $q =  $widget_type . '-wrapper-' . $instance_id;
    $widget->query = '#' . $q;
    $widget->widget = $widget_type ?? '';
    $widget->token = $widget_id ?? '';
    $widget->locale = $lang;

    if($widget->token !== '' && $widget->query !== '') {
        // inject Datablocks widget
        echo '
            <script>        
                if(!window._MF) {
                    let b = document.createElement("script");
                    b.type = "text/javascript";
                    b.async = true;
                    b.src =  "' . DATABLOCKS_LOADER_URL . '/assets/js/loader-' . DATABLOOCKS_LOADER_VERSION . '.js' . '";
                    document.getElementsByTagName("body")[0].appendChild(b);
    
                    window._MF = window._MF || {
                        data: [],
                        url: "' . $loaderURL . '",
                        ready: !!0,
                        render: function() {
                            window._MF.ready = !0
                        },
                        push: function(conf) {
                            this.data.push(conf);
                        }
                    }
                }
                window._MF.push(' . json_encode($widget) . ')
            </script>
            ';

        echo '<div id="' . $q . '" class="mfn-' . $widget_type . '"></div>';
    }
}

// Creating the widget
class mfn_archive_widget extends WP_Widget
{

    public function __construct()
    {

        // load css
        wp_enqueue_style( MFN_PLUGIN_NAME . '-mfn-archive-css', plugin_dir_url( __FILE__ ) . 'widgets/mfn_archive/css/mfn-archive.css', array(), MFN_PLUGIN_NAME_VERSION );

        parent::__construct(
            'mfn_archive_widget',
            __('MFN Report Archive', 'mfn_archive_widget_domain'),
            array('description' => __('Creates a report archive from the MFN news feed.', 'mfn_archive_widget_domain'),)
        );
    }

    public function widget($args, $instance)
    {
        $query_param = static function ($name, $default) {
            return $_GET[$name] ?? $default;
        };

        $w = array(
            'showdate' => isset($instance['showdate']) && bool_check($instance['showdate']) ?? false,
            'showyear' => isset($instance['showyear']) && bool_check($instance['showyear']) ?? false,
            'showfilter' => isset($instance['showfilter']) && bool_check($instance['showfilter']) ?? false,
            'showthumbnail' =>  isset($instance['showthumbnail']) && bool_check($instance['showthumbnail']) ?? false,
            'showgenerictitle' => isset($instance['showgenerictitle']) && bool_check($instance['showgenerictitle']) ?? false,
            'usefiscalyearoffset' => isset($instance['usefiscalyearoffset']) && bool_check($instance['usefiscalyearoffset']) ?? true,
            'fiscalyearoffset' => (!empty($instance['fiscalyearoffset'])) ?? 0,
            'useproxiedattachments' => isset($instance['useproxiedattachments']) && bool_check($instance['useproxiedattachments']) ?? true,
            'fromyear' => $instance['fromyear'] ?? '',
            'toyear' => $instance['toyear'] ?? '',
            'limit' => (!empty($instance['limit'])) ? $instance['limit'] : 500,
            'offset' => (!empty($instance['offset'])) ? $instance['offset'] : 0,
            'instance_id' => random_int(1, time()),
            'v2api' => isset($instance['v2api']) && bool_check($instance['v2api']) ?? false,
        );

        // force to true, since 'showgenerictitle' depends on 'usefiscalyearoffset' to even show meaningful titles
        if ($w['showgenerictitle']) {
            $w['usefiscalyearoffset'] = true;
        }

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

        $from_year = $query_param('m-from-year', "");
        if (!empty($w['fromyear'])) {
            $from_year = normalize_whitespace($w['fromyear']);
        }
        if (empty($from_year)) {
            $from_year = "";
        }
        $to_year = $query_param('m-to-year', "");
        if (!empty($w['toyear'])) {
            $to_year = normalize_whitespace($w['toyear']);
        }
        if (empty($to_year)) {
            $to_year = "";
        }

        if ($w['v2api']) {
            echo "<div class=\"mfn-report-container all v2api\" id=\"mfn-report-archive-id-" . $w['instance_id'] . "\">";
        } else {
            echo "<div class=\"mfn-report-container all\" id=\"mfn-report-archive-id-" . $w['instance_id'] . "\">";
        }

        if (isset($instance['showheading']) && $instance['showheading']) {
            echo "<h2>" . $l("Financial reports", $lang) . "</h2>";
        }

        $fiscal_year_offset = null;
        if ($w['usefiscalyearoffset']) {
            $fiscal_year_offset = $w['fiscalyearoffset'];
        }

        if ($w['v2api']) {
            $reports = MFN_get_reports_v2($pmlang, $from_year, $to_year, $w['offset'], $w['limit'], $w['showgenerictitle']);
            $w['showgenerictitle'] = null;
        } else {
            $reports = MFN_get_reports($pmlang, $from_year, $to_year, $w['offset'], $w['limit'], 'DESC', $fiscal_year_offset);
        }

        if (count($reports) < 1) {
            echo "</div>";
            echo $args['after_widget'];
            return;
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

        if (!$w['showyear']) {
            echo "<ul class='mfn-report-items'>";
        }
        $year = "";
        foreach ($reports as $r) {

            $y = $r->year;
            if ($y !== $year) {
                if ($year !== "" && $w['showyear']) {
                    echo "</ul></div>";
                }

                $year = $y;

                $year_class = yearClass($year);

                if ($w['showyear']) {
                    echo "<div class='mfn-year-container'>";
                    echo "<h3 class='mfn-year-header mfn-year-$year_class'>$year</h3>";
                    echo "<ul class='mfn-report-items mfn-year-$year_class'>";
                }
            }

            $date = substr($r->timestamp, 0, 10);

            $parts = explode('-', $r->type);
            $base_type = implode("-", array_slice($parts, 0, count($parts)-1));

            $year_class = yearClass($year);

            $li  = "<li class='mfn-report-item mfn-report-year-$year_class mfn-report-group-id-$r->group_id mfn-report-lang-$pmlang $base_type $r->type'>";

            if ($w['showdate']) {
                $li .=   "<span class='mfn-report-date'>$date</span>";
            }

            $ops = get_option('mfn-wp-plugin');

            $storage_url = isset($ops['sync_url'])
                ? ((strpos($ops['sync_url'], 'https://feed.mfn.') === 0)
                    ? str_replace('//feed.mfn', '//storage.mfn', str_replace('/v1', '', $ops['sync_url']))
                    : str_replace('//mfn', '//storage.mfn', $ops['sync_url']))
                : null;

            $proxiedUrl = $storage_url !== null && $storage_url !== '' && (strpos($r->url, $storage_url) !== 0)
                ? "$storage_url/proxy?url=" . urlencode($r->url)
                : $r->url;


            if ($w['showthumbnail']) {
                $previewUrl = add_query_arg('type', 'jpg', $proxiedUrl);

                $li .=   "<div class='mfn-report-thumbnail'>";
                $li .=     "<a href=\"$proxiedUrl\" target=\"_blank\" rel='noopener'>";
                $li .=       "<img src=\"$previewUrl\" />";
                $li .=     "</a>";
                $li .=   "</div>";
            }

            $li .=   "<span class='mfn-report-title'>";

            if ($w['useproxiedattachments']) {
                $li .=     "<a href=\"$proxiedUrl\" target=\"_blank\" rel='noopener'>";
            } else {
                $li .=     "<a href=\"$r->url\" target=\"_blank\" rel='noopener'>";
            }

            if ($w['showgenerictitle']) {

                global $mfn_wid_translate;

                $slug_prefix = (MFN_TAG_PREFIX !== '' && MFN_TAG_PREFIX !== null ? MFN_TAG_PREFIX . '-' : '');

                $report_names = [
                    $slug_prefix . 'report-interim-q4' => "Year-end Report",
                    $slug_prefix . 'report-interim' => "Interim Report",
                    $slug_prefix . 'report-annual' => "Annual Report"
                ];

                $base_title = $report_names[$r->type] ?? $report_names[$base_type];
                $base_title = $mfn_wid_translate($base_title, $r->lang);

                $li .=     "<span class='mfn-report-base-title mfn-report-base-title-$r->lang'>";
                $li .=       $base_title;
                $li .=     "</span>";

                if ($r->type !== $slug_prefix . 'report-annual') {
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

            echo $li;

        }
        if ($w['showyear']) {
            echo "</ul></div>";
        } else {
            echo "</ul>";
        }
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

        if (isset($instance['offset'])) {
            $offset = $instance['offset'];
        } else {
            $offset = '0';
        }

        ?>

        <p>
            <label for="<?php echo $this->get_field_id('lang'); ?>"><?php _e('Archive Language', 'text_domain'); ?>:</label>
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
            <label for="<?php echo esc_attr($this->get_field_id('usefiscalyearoffset')); ?>"><?php _e('Use fiscal year for grouping', 'text_domain'); ?>:</label>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('fiscalyearoffset')); ?>"><?php _e('Fiscal year offset', 'text_domain'); ?>:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('fiscalyearoffset')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('fiscalyearoffset')); ?>" type="text"
                   value="<?php echo esc_attr($fiscalyearoffset); ?>"/>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('limit')); ?>"><?php _e('Limit: number of items to show', 'text_domain'); ?>:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('limit')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('limit')); ?>" type="text"
                   value="<?php echo esc_attr($limit); ?>"/>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('offset')); ?>"><?php _e('Offset: drop X number of items from start of the list', 'text_domain'); ?>:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('offset')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('offset')); ?>" type="text"
                   value="<?php echo esc_attr($offset); ?>"/>
        </p>

        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['lang'] = (!empty($new_instance['lang'])) ? strip_tags($new_instance['lang']) : '';
        $instance['showheading'] = (!empty($new_instance['showheading'])) ? strip_tags($new_instance['showheading']) : '';
        $instance['showfilter'] = (!empty($new_instance['showfilter'])) ? strip_tags($new_instance['showfilter']) : '';
        $instance['showfilterlabel'] = (!empty($new_instance['showfilterlabel'])) ? strip_tags($new_instance['showfilterlabel']) : '';
        $instance['filtertype'] = (!empty($new_instance['filtertype'])) ? strip_tags($new_instance['filtertype']) : 'dropdown';
        $instance['showyear'] = (!empty($new_instance['showyear'])) ? strip_tags($new_instance['showyear']) : '';
        $instance['showdate'] = (!empty($new_instance['showdate'])) ? strip_tags($new_instance['showdate']) : '';
        $instance['showgenerictitle'] = (!empty($new_instance['showgenerictitle'])) ? strip_tags($new_instance['showgenerictitle']) : '';
        $instance['showthumbnail'] = (!empty($new_instance['showthumbnail'])) ? strip_tags($new_instance['showthumbnail']) : '';
        $instance['usefiscalyearoffset'] = (!empty($new_instance['usefiscalyearoffset'])) ? strip_tags($new_instance['usefiscalyearoffset']) : '';
        $instance['fiscalyearoffset'] = (!empty($new_instance['fiscalyearoffset'])) ? strip_tags($new_instance['fiscalyearoffset']) : '';
        $instance['limit'] = (!empty($new_instance['limit'])) ? strip_tags($new_instance['limit']) : '';
        $instance['offset'] = (!empty($new_instance['offset'])) ? strip_tags($new_instance['offset']) : '';
        return $instance;
    }
}

// Creating the widget
class mfn_subscription_widget extends WP_Widget
{

    public function __construct()
    {
        parent::__construct(
            'mfn_subscription_widget',
            __('MFN Subscription', 'mfn_subscription_widget_domain'),
            array('description' => __('Adds a subscription form for subscribing to MFN news.', 'mfn_subscription_widget_domain'),)
        );
    }

    public function widget($args, $instance)
    {
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

        echo $args['before_widget'];

        // load subscription widget
        load_datablocks_widget(
            $instance['widget_id'] ?? '',
            "subscribe-v2",
            $lang
        );
        echo $args['after_widget'];
    }

    public function form($instance)
    {
        $lang = $instance['lang'] ?? 'auto';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('lang')); ?>">
                <?php _e('Language', 'text_domain'); ?>:
            </label>
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
            <label for="<?php echo esc_attr($this->get_field_id('widget_id')); ?>">
                <?php _e('Widget id', 'text_domain'); ?>:
            </label>
            <input
                    id="<?php echo esc_attr($this->get_field_id('widget_id')); ?>"
                    name="<?php echo esc_attr($this->get_field_name('widget_id')); ?>"
                    type="text"
                    value="<?php echo $instance['widget_id']; ?>"
                    class="widefat"
            />
        </p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['lang'] = (!empty($new_instance['lang'])) ? strip_tags($new_instance['lang']) : '';
        $instance['widget_id'] = (!empty($new_instance['widget_id'])) ? strip_tags($new_instance['widget_id']) : '';
        return $instance;
    }
}

// Creating the widget
class mfn_archive_v2_widget extends WP_Widget
{

    public function __construct()
    {
        parent::__construct(
            'mfn_archive_v2_widget',
            __('MFN Report Archive (V2)', 'mfn_archive_v2_widget_domain'),
            array('description' => __('Creates a report archive from the MFN news feed.', 'mfn_archive_v2_widget_domain'),)
        );
    }

    public function widget($args, $instance)
    {
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

        // load archive widget
        load_datablocks_widget(
            $instance['widget_id'] ?? '',
            "archive",
            $lang
        );
    }

    public function form($instance)
    {
        $lang = $instance['lang'] ?? 'auto';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('lang')); ?>">
                <?php _e('Language', 'text_domain'); ?>:
            </label>
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
            <label for="<?php echo esc_attr($this->get_field_id('widget_id')); ?>">
                <?php _e('Widget id', 'text_domain'); ?>:
            </label>
            <input
                    id="<?php echo esc_attr($this->get_field_id('widget_id')); ?>"
                    name="<?php echo esc_attr($this->get_field_name('widget_id')); ?>"
                    type="text"
                    value="<?php echo $instance['widget_id']; ?>"
                    class="widefat"
            />
        </p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['lang'] = (!empty($new_instance['lang'])) ? strip_tags($new_instance['lang']) : '';
        $instance['widget_id'] = (!empty($new_instance['widget_id'])) ? strip_tags($new_instance['widget_id']) : '';
        return $instance;
    }
}

// Creating the widget
class mfn_news_feed_widget extends WP_Widget
{

    public function __construct()
    {
        require_once(ABSPATH . 'wp-includes/functions.wp-styles.php');

        // load css
        wp_enqueue_style( MFN_PLUGIN_NAME . '-mfn-news-list-css', plugin_dir_url( __FILE__ ) . 'widgets/mfn_news_feed/css/mfn-news-feed.css', array(), MFN_PLUGIN_NAME_VERSION );
        // require news feed class
        require_once(__DIR__ . '/widgets/mfn_news_feed/class-mfn-news-feed.php');

        parent::__construct(
            'mfn_news_feed_widget',
            __('MFN News Feed', 'mfn_news_feed_domain'),
            array('description' => __('Creates a news feed for MFN press releases.', 'mfn_news_feed_domain'),)
        );

    }

    private function list_news_items($data): int
    {
        wp_enqueue_style( MFN_PLUGIN_NAME . '-mfn-news-list-css', plugin_dir_url( __FILE__ ) . 'widgets/mfn_news_feed/css/mfn-news-list.css', array(), MFN_PLUGIN_NAME_VERSION );

        $res = MFN_get_feed(
            $data['pmlang'],
            $data['year'],
            $data['hasTags'],
            $data['hasNotTags'],
            $data['offset'],
            $data['pagelen'],
            $data['showpreview']
        );

        $news_feed = new News_feed;

        echo $news_feed->list_news_items(
            $res,
            $data['tzLocation'],
            $data['timestampFormat'],
            $data['onlytagsallowed'],
            $data['tagtemplate'],
            $data['template'],
            $data['groupbyyear'],
            $data['skipcustomtags'],
            $data['showpreview'],
            $data['previewlen'],
            $data['disclaimerurl'],
            $data['disclaimertag']
        );

        return sizeof($res);
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

        global $wp;
        $baseurl = explode('?', home_url(add_query_arg(array(), $wp->request)))[0];

        // handle widget instance settings
        $tzLocation = empty($instance['tzLocation']) ? 'Europe/Stockholm' : $instance['tzLocation'];
        $timestampFormat = empty($instance['timestampFormat']) ? 'Y-m-d H:i' : $instance['timestampFormat'];

        if (isset($instance['tzlocation'])) {
            $tzLocation = normalize_whitespace($instance['tzlocation']);
        }

        if (isset($instance['timestampformat'])) {
            $timestampFormat = normalize_whitespace($instance['timestampformat']);
        }

        $instance_id = random_int(1, time());

        $pagelen = empty($instance['pagelen']) ? 20 : $instance['pagelen'];
        $previewlen = empty($instance['previewlen']) ? 250 : $instance['previewlen'];
        $showpreview = isset($instance['showpreview']) && bool_check($instance['showpreview']);

        $showyears = isset($instance['showyears']) && bool_check($instance['showyears']);
        $showyearslabel = !isset($instance['showyearslabel']) || bool_check($instance['showyearslabel']);
        $yearstype = empty($instance['yearstype']) ? 'dropdown' : $instance['yearstype'];

        $showfilter = isset($instance['showfilter']) && bool_check($instance['showfilter']);
        $showfilterlabel = !isset($instance['showfilterlabel']) || bool_check($instance['showfilterlabel']);
        $filtertype = empty($instance['filtertype']) ? 'dropdown' : $instance['filtertype'];

        $groupbyyear = isset($instance['groupbyyear']) && bool_check($instance['groupbyyear']);
        $showpagination = isset($instance['showpagination']) && bool_check($instance['showpagination']);
        $skipcustomtags = isset($instance['skipcustomtags']) && bool_check($instance['skipcustomtags']);

        $disclaimerurl = empty($instance['disclaimerurl']) ? null : $instance['disclaimerurl'];
        $disclaimertag = empty($instance['disclaimertag']) ? null : $instance['disclaimertag'];

        $lang = 'en';
        $locale = determineLocale();
        if (is_string($locale)) {
            $parts = explode("_", $locale);
            if (strlen($parts[0]) === 2) {
                $lang = $parts[0];
            }
        }

        $l10nlang = empty($instance['l10nlang']) ? null : $instance['l10nlang'];
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
                if (strpos($tag, MFN_TAG_PREFIX . '-') !== 0) {
                    $tag = MFN_TAG_PREFIX . '-' . $tag;
                }

                $hasNotTags[] = $tag;
                continue;
            }

            if (strpos($tag, MFN_TAG_PREFIX . '-') !== 0) {
                $tag = MFN_TAG_PREFIX . '-' . $tag;
            }

            $hasTags[] = $tag;
        }

        $year = $query_param('m-year', "");

        if (isset($instance['year'])) {
            $year = normalize_whitespace($instance['year']);
        }
        if (empty($year)) {
            $year = "";
        }

        $params = [];
        $query_arr = explode("&", http_build_query(array_merge($_GET)));
        $build_new_query = [];

        foreach($query_arr as $k => $v) {
            if($v !== '') {
                $arr = explode("=", $v);
                $build_new_query[$arr[0]] = $arr[1];
            }
        }

        if (sizeof($build_new_query) > 0) {
            if(isset($build_new_query['m-tags'])) {
                $params['m-tags'] = $build_new_query['m-tags'];
            }
            if (isset($build_new_query['m-year'])) {
                $params['m-year'] = $build_new_query['m-year'];
            }
            if (isset($build_new_query['m-page'])) {
                $params['m-page'] = $build_new_query['m-page'];
            }

        }

        $min_max_years = MFN_get_feed_min_max_years($pmlang);

        $template = empty($instance['template']) ? "
            <div class='mfn-item'>
                <div class='mfn-item-header'>
                    <span class='mfn-date'>[date]</span>
                    <span class='mfn-title'>
                        <a href='[url]'>[title]</a>
                    </span>
                    <span class='mfn-tags'>[tags]</span>
                </div>
                <div class='mfn-item-body'>
                    <div class='mfn-preview'>[preview]</div>
                </div>
            </div>
        " : $instance['template'];

        $tagtemplate = empty($instance['tagtemplate']) ? "
            <span class='mfn-tag mfn-tag-[slug]'>[tag]</span>
        " : $instance['tagtemplate'];

        $yeartemplate = empty($instance['yeartemplate']) ? "
            <span class='mfn-year-header mfn-year mfn-year-header-[year][active]'>
                <a href='[url]' class='mfn-year-header-link mfn-year-header-link-[year]'>[year]</a>
            </span>
        " : $instance['yeartemplate'];

        $onlytagsallowed = array();
        $onlytagsallowedstr = empty($instance['onlytagsallowed']) ? "" : $instance['onlytagsallowed'];
        if ($onlytagsallowedstr !== "") {
            $onlytagsallowed = explode(",", $onlytagsallowedstr);
        }

        echo "<div class=\"mfn-newsfeed\">";

        if ($showfilter || $showyears) {
            $filter_lang = $lang;
            if ($l10nlang) {
                $filter_lang = $l10nlang;
            }
            echo '<form method="GET" id="mfn-filter-form">';
            echo '<div class="mfn-filter-container">';
        }

        if ($showfilter) {
            $categoryTag = $_GET['m-tags'] ?? '';
            $q = $_GET;
            unset($q['m-tags']);
            echo '<div class="mfn-newsfeed-category-selector">';
            echo '<script>
                    function filterByCategory(e)
                    {
                        var type = e.getAttribute("filtertype");
                        var instance_id = document.getElementById("instance_id").value;
                        var q = document.getElementById("current_category_query").value;
                        if (type === "dropdown") {
                            var el = document.getElementById("mfn-category-filter-"  + instance_id);
                            if (e.value !== "") q += "&m-tags=" + e.value;
                            el.value = document.getElementById("category_tag").value;
                        } else if (type === "buttons") {
                            if (e.value !== "") q += "&m-tags=" + e.value;
                        }
                        window.location.href = q;
                        return false;
                    }
                 </script>
                 ';

            if ($showfilterlabel) {
                echo '<label for="mfn-category-filter-' . $instance_id  . '" class="mfn-category-label">' . $l('Category', $filter_lang) . '</label>';
            }
            if ($filtertype === 'dropdown') {
                $all_sel = $categoryTag === '' ? 'selected' : '';
                $regulatory_sel = $categoryTag === 'regulatory' || $categoryTag === 'mfn-regulatory' ? 'selected' : '';
                $nonRegulatory_sel = $categoryTag === '-regulatory' || $categoryTag === '-mfn-regulatory' ? 'selected' : '';
                echo '    <select name="mfn-category-filter" id="mfn-category-filter-' . $instance_id  . '" class="mfn-category-filter" filtertype="dropdown" onchange="filterByCategory(this);">
                            <option value="" ' . $all_sel . '>' . $l("All", $filter_lang) . '</option>
                            <option value="regulatory" ' . $regulatory_sel . '>' . $l("Regulatory", $filter_lang) . '</option>
                            <option value="-regulatory" ' . $nonRegulatory_sel . '>' . $l("Non-Regulatory", $filter_lang) . '</option>
                          </select>
                ';
            } else if ($filtertype === 'buttons') {
                $all_act = $categoryTag === '' ? ' mfn-filter-button-active' : '';
                $regulatory_act = $categoryTag === 'regulatory' || $categoryTag === 'mfn-regulatory' ? ' mfn-filter-button-active' : '';
                $nonRegulatory_act = $categoryTag === '-regulatory' || $categoryTag === '-mfn-regulatory' ? ' mfn-filter-button-active' : '';
                echo '    <div id="mfn-category-filter-' . $instance_id  . '" class="mfn-category-filter">';
                echo '    <button type="button" value="" class="mfn-category-button-all' . $all_act . '" filtertype="buttons" onclick="filterByCategory(this);">' . $l("All", $filter_lang) . '</button>';
                echo '    <button type="button" value="regulatory" class="mfn-category-button-regulatory' . $regulatory_act . '" filtertype="buttons" onclick="filterByCategory(this);">' . $l("Regulatory", $filter_lang) . '</button>';
                echo '    <button type="button" value="-regulatory" class="mfn-category-button-non-regulatory' . $nonRegulatory_act . '" filtertype="buttons" onclick="filterByCategory(this);">' . $l("Non-Regulatory", $filter_lang) . '</button>';
                echo '    </div>';
            }
            echo '  <input type="hidden" id="current_category_query" value="?' . http_build_query($q) . '" />';
            echo '  <input type="hidden" id="category_tag" value="' . $categoryTag . '" />';
            echo '</div>';
        }

        if ($showyears) {
            echo "<div class='mfn-newsfeed-year-selector'>";
            $current_year = $_GET['m-year'] ?? '';
            if (is_object($min_max_years) &&
                isset($min_max_years->max_year) &&
                isset($min_max_years->min_year) &&
                is_numeric($min_max_years->max_year) &&
                is_numeric($min_max_years->min_year)) {
                if ($yearstype === 'dropdown') {
                    $q = $_GET;
                    unset($q['m-year']);
                    echo '<script>
                        function filterByYear(e)
                        {                           
                        var q = document.getElementById("current_year_query").value;
                        var instance_id = document.getElementById("instance_id").value;
                        var el = document.getElementById("mfn-year-filter-"  + instance_id);
                        if (e.value !== "") q += "&m-year=" + e.value;
                            el.value = document.getElementById("current_year").value;
                            window.location.href = q;
                            return false;
                        }
                     </script>
                     ';
                    if ($showyearslabel) {
                        echo '<label for="mfn-year-filter-' . $instance_id  . '" class="mfn-year-label">' . $l('Year', $filter_lang) . '</label>';
                    }
                    echo '<select name="mfn-year-filter" id="mfn-year-filter-' . $instance_id  . '" class="mfn-year-filter" onchange="filterByYear(this);">';
                    echo '<option url="?' . http_build_query($q) . '" value="">' . $l("All", $filter_lang) . '</option>';
                    for ($i = $min_max_years->max_year; $i >= $min_max_years->min_year; $i--) {
                        $params = http_build_query(array_merge($_GET, array('m-year' => $i)));
                        $selected = $current_year == $i ? 'selected' : '';
                        echo '<option url="' . $baseurl . "?" . $params . '" value="' . $i . '" ' . $selected . '>' . $i . '</option>';
                    }
                    echo '</select>';
                    echo '  <input type="hidden" id="current_year_query" value="?' . http_build_query($q) . '" />';
                    echo '  <input type="hidden" id="current_year" value="' . $current_year . '" />';
                } else if ($yearstype === 'links') {
                    for ($i = $min_max_years->max_year; $i >= $min_max_years->min_year; $i--) {
                        $params = http_build_query(array_merge($_GET, array('m-year' => $i)));
                        $append_active = '';

                        if (isset($current_year) && $current_year == $i) {
                            $append_active = " mfn-filter-year-active";
                        }
                        $url = $baseurl . "?" . $params;

                        $html = str_replace("[active]", $append_active, $yeartemplate);
                        $html = str_replace(array("[url]", "[year]", "[mfn-year-selected]"), array($url, $i, $i === $year ? 'mfn-year-selected' : ''), $html);
                        $html = str_replace(array("{{url}}", "{{year}}", "{{mfn-year-selected}}"), array($url, $i, $i === $year ? 'mfn-year-selected' : ''), $html);

                        echo $html;
                    }
                }
            }
            echo "</div>";

            if ($showfilter || $showyears) {
                echo '</div>';
            }
        }
        if ($showfilter || $showyears) {
            echo '  <input type="hidden" id="instance_id" value="' . $instance_id . '" />';
            echo '</form>';
        }

        echo '<div class="mfn-list">';

        $news_items = $this->list_news_items(array(
            'pagelen' => $pagelen,
            'offset' => $page * $pagelen,
            'hasNotTags' => $hasNotTags,
            'hasTags' => $hasTags,
            'year' => $year,
            'pmlang' => $pmlang,
            'tzLocation' => $tzLocation,
            'timestampFormat' => $timestampFormat,
            'onlytagsallowed' => $onlytagsallowed,
            'tagtemplate' => $tagtemplate,
            'template' => $template,
            'groupbyyear' => $groupbyyear,
            'skipcustomtags' => $skipcustomtags,
            'showpreview' => $showpreview,
            'previewlen' => $previewlen,
            'showpagination' => $showpagination,
            'query' => $params,
            'disclaimerurl' => $disclaimerurl,
            'disclaimertag' => $disclaimertag
        ));

        if ($showpagination) {
            echo "</div><div class='mfn-newsfeed-pagination'>";

            if ($page > 0) {
                $params = http_build_query(array_merge($_GET, array('m-page' => $page - 1)));
                $url1 = $baseurl . "?" . $params;
                $llang = $lang;
                if ($l10nlang) {
                    $llang = $l10nlang;
                }
                $word = $l("Previous", $llang);
                echo "<a href='$url1' class='mfn-page-link mfn-page-link-prev'>$word</a>";
            }

            if ($news_items == $pagelen) {
                $params = http_build_query(array_merge($_GET, array('m-page' => $page + 1)));
                $url2 = $baseurl . "?" . $params;
                $llang = $lang;
                if ($l10nlang) {
                    $llang = $l10nlang;
                }
                $word = $l("Next", $llang);
                echo "<a href='$url2' class='mfn-page-link mfn-page-link-next'>$word</a>";
            }

        }
        echo "</div></div>";
    }

    public function form($instance)
    {
        $lang = $instance['lang'] ?? 'auto';
        $pagelen = $instance['pagelen'] ?? '20';
        $previewlen = $instance['previewlen'] ?? '';
        $showpagination = $instance['showpagination'] ?? '1';
        $showfilter = $instance['showfilter'] ?? '0';
        $showfilterlabel = $instance['showfilterlabel'] ?? '1';
        $filtertype = $instance['filtertype'] ?? 'dropdown';
        $showyears = $instance['showyears'] ?? '0';
        $showyearslabel = $instance['showyearslabel'] ?? '1';
        $showpreview = $instance['showpreview'] ?? '0';
        $groupbyyear = $instance['groupbyyear'] ?? '0';
        $tzLocation = $instance['tzLocation'] ?? 'Europe/Stockholm';
        // Format at https://www.php.net/manual/en/function.date.php#refsect1-function.date-parameters
        $timestampFormat = $instance['timestampFormat'] ?? 'Y-m-d H:i';
        $skipcustomtags = $instance['skipcustomtags'] ?? '0';

        if (isset($instance['template']) && $instance['template'] !== "") {
            $template = $instance['template'];
        } else {
            $template = "<div class='mfn-item'><div class='mfn-date'>[date]</div><div class='mfn-tags'>[tags]</div><div class='mfn-title'><a href='[url]'>[title]</a></div><div class='mfn-preview'>[preview]</div></div>";
        }

        if (isset($instance['tagtemplate']) && $instance['tagtemplate'] !== "") {
            $tagtemplate = $instance['tagtemplate'];
        } else {
            $tagtemplate = "<span class='mfn-tag mfn-tag-[slug]'>[tag]</span>";
        }

        if (isset($instance['yeartemplate']) && $instance['yeartemplate'] !== "") {
            $yeartemplate = $instance['yeartemplate'];
        } else {
            $yeartemplate = "<span class='mfn-year-header mfn-year mfn-year-header-[year][active]'><a href='[url]' class='mfn-year-header-link mfn-year-header-link-[year]'>[year]</a></span>";
        }

        $onlytagsallowed = $instance['onlytagsallowed'] ?? "";

        ?>

        <p>
            <input id="<?php echo esc_attr($this->get_field_id('showfilter')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('showfilter')); ?>" type="checkbox"
                   value="1" <?php checked('1', $showfilter); ?> />
            <label for="<?php echo esc_attr($this->get_field_id('showfilter')); ?>"><?php _e('Show Filter', 'text_domain'); ?></label>
        </p>

        <?php
        if ($showfilter) {
            echo '
            <div style="border: 1px solid #E0E0E0; padding: 0 10px 10px 10px;">
                <p>
                    <input id="' . esc_attr($this->get_field_id('showfilterlabel')) .'"
                           name="' . esc_attr($this->get_field_name('showfilterlabel')) . '" type="checkbox"
                           value="1" ' . checked('1', $showfilterlabel, false) . '/>
                    <label for="' . esc_attr($this->get_field_id('showfilterlabel')) . '">' . __('Show Filter Label', 'text_domain') . '</label>
                </p>
                <p>
                    <label for="' . $this->get_field_id('filtertype') . '">' . __('Filter Type', 'text_domain') . ':</label>
                    <select name="' . $this->get_field_name('filtertype') . '" id="' . $this->get_field_id('filtertype') . '" class="widefat">';

                        // Your options array
                        $options = array(
                            'dropdown' => __('Dropdown', 'text_domain'),
                            'buttons' => __('Buttons', 'text_domain'),
                        );

                        // Loop through options and add each one to the select dropdown
                        foreach ($options as $key => $name) {
                            echo '<option value="' . esc_attr($key) . '" id="' . esc_attr($key) . '" ' . selected($filtertype, $key, false) . '>' . $name . '</option>';
                        }
                    echo '
                    </select>
                </p>
            </div>';
        }
        echo '
        <p>
            <input id="' . esc_attr($this->get_field_id('showyears')) . '"
                   name="' . esc_attr($this->get_field_name('showyears')) .  '" type="checkbox"
                   value="1" ' . checked('1', $showyears, false) . ' />
            <label for="' . esc_attr($this->get_field_id('showyears')) . '">' . __('Show Years', 'text_domain') . '</label>
        </p>';

        if ($showyears) {
            echo '
            <div style="border: 1px solid #E0E0E0; padding: 0 10px 10px 10px;">
                <p>
                    <input id="' . esc_attr($this->get_field_id('showyearslabel')) . '"
                           name="' . esc_attr($this->get_field_name('showyearslabel')) . '" type="checkbox"
                           value="1" ' . checked('1', $showyearslabel, false) . ' />
                    <label for="' . esc_attr($this->get_field_id('showyearslabel')) . '">' . __('Show Years Label', 'text_domain') . '</label>
                </p>
                <p>
                    <label for="' .  $this->get_field_id('yearstype') .'">' . __('Years Type', 'text_domain') . '</label>
                    <select name="' . $this->get_field_name('yearstype') .'" id="' . $this->get_field_id('yearstype') . '" class="widefat">';

                        // Your options array
                        $options = array(
                            'dropdown' => __('Dropdown', 'text_domain'),
                            'links' => __('Links', 'text_domain'),
                        );

                        // Loop through options and add each one to the select dropdown
                        foreach ($options as $key => $name) {
                            echo '<option value="' . esc_attr($key) . '" id="' . esc_attr($key) . '" ' . selected($filtertype, $key, false) . '>' . $name . '</option>';
                        }
                        echo '
                    </select>
                </p>
            </div>';
            }
        echo '
        <p>
            <input id="' .  esc_attr($this->get_field_id('showpreview')) . '"
                   name="' . esc_attr($this->get_field_name('showpreview')) . '" type="checkbox"
                   value="1" ' . checked('1', $showpreview, false) . ' />
            <label for="' . esc_attr($this->get_field_id('showpreview')) . '">' . __('Show Preview', 'text_domain') . '</label>
        </p>
        ';

        if ($showpreview) {
            echo '
            <div style="border: 1px solid #E0E0E0; padding: 0 10px 10px 10px;">
                <p>
                    <label for="' . esc_attr($this->get_field_id('previewlen')) . '">' . __('Preview length (e.g. "150". Default is to leave this field empty):', 'text_domain') . '</label>
                    <input class="widefat" id="' . esc_attr($this->get_field_id('previewlen')) . '"
                           name="' . esc_attr($this->get_field_name('previewlen')) . '" type="number"
                           value="' . esc_attr($previewlen) . '"/>
                </p>
            </div>
            ';
        }

        echo '<p>
            <input id="' . esc_attr($this->get_field_id('groupbyyear')) . '"
                   name="' . esc_attr($this->get_field_name('groupbyyear')) . '" type="checkbox"
                   value="1" ' . checked('1', $groupbyyear, false) . ' />
            <label for="' .  esc_attr($this->get_field_id('groupbyyear')) . '">' . __('Group By Year', 'text_domain') . '</label>
        </p>

        <p>
            <input id="' . esc_attr($this->get_field_id('showpagination')) . '"
                   name="' . esc_attr($this->get_field_name('showpagination')) . '" type="checkbox"
                   value="1" ' . checked('1', $showpagination, false) . ' />
            <label for="' . esc_attr($this->get_field_id('showpagination')) .  '">' . __('Show Pagination', 'text_domain') . '</label>
        </p>

        <p>
            <label for="' . $this->get_field_id('lang') . '">' . __('Archive Language', 'text_domain') . ':</label>
            <select name="' . $this->get_field_name('lang') .'" id="' . $this->get_field_id('lang') . '" class="widefat">';

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
                }
            echo '    
            </select>
        </p>
        <p>
            <label for="' . $this->get_field_id('tzLocation') .'">' . __('Timestamp Location:', 'text_domain') . '</label>
            <select name="' . $this->get_field_name('tzLocation') . '"
                    id="' . $this->get_field_id('tzLocation') . '"
                    class="widefat">';

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
                }
            echo '
            </select>
        </p>

        <p>
            <label for="' . esc_attr($this->get_field_id('pagelen')) . '">' . __('# stories / page:', 'text_domain') . '</label>
            <input class="widefat" id="' . esc_attr($this->get_field_id('pagelen')) . '"
                   name="' . esc_attr($this->get_field_name('pagelen')) . '" type="number"
                   value="' . esc_attr($pagelen) . '"/>
        </p>

        <p>
            <label for="' . esc_attr($this->get_field_id('timestampFormat')) . '">' . __('Timestamp Format:', 'text_domain') . '</label>
            <input class="widefat" id="' . esc_attr($this->get_field_id('timestampFormat')) . '"
                   name="' . esc_attr($this->get_field_name('timestampFormat')) . '" type="text"
                   value="' . esc_attr($timestampFormat) . '"/>
        </p>

        <p>
            <label for="' . esc_attr($this->get_field_id('template')) . '">' . __('Template:', 'text_domain') . '</label>
            <textarea rows="8" class="widefat" id="' . esc_attr($this->get_field_id('template')) . '"
                      name="' . esc_attr($this->get_field_name('template')) . '">' . wp_kses_post($template) . '</textarea>
        </p>';

            if (!$showpreview && strpos($template, '[preview]') !== false) {
                echo '
                    <li style="border-color: #fedb75; background-color: #fff3d0; padding: 10px 20px 10px 20px; border-radius: 2px;">
                        <b>Notice:</b> Please remove the [preview] section from the template if "Show preview" is not checked.
                    </li>
                ';
            }
            else if ($showpreview && strpos($template, '[preview]') === false) {
                echo '
                    <li style="border-color: #fedb75; background-color: #fff3d0; padding: 10px 20px 10px 20px; border-radius: 2px;">
                        <b>Notice:</b> Please add ' . '"<i>' . htmlspecialchars("<div class='mfn-preview'>[preview]</div>") . '"</i>' . ' to the template if "Show preview" is checked.
                    </li>
                ';
            }
        echo '
        <p>
            <label for="' . esc_attr($this->get_field_id('tagtemplate')) . '">' . __('Tag Template:', 'text_domain') . '</label>
            <textarea rows="2" class="widefat" id="' . esc_attr($this->get_field_id('tagtemplate')) . '"
                      name="' . esc_attr($this->get_field_name('tagtemplate')) . '">' . wp_kses_post($tagtemplate) . '</textarea>
        </p>

        <p>
            <label for="' . esc_attr($this->get_field_id('yeartemplate')) . '">' . __('Year Template:', 'text_domain') . '</label>
            <textarea rows="2" class="widefat" id="' . esc_attr($this->get_field_id('yeartemplate')) . '"
                      name="' . esc_attr($this->get_field_name('yeartemplate')) . '">' . wp_kses_post($yeartemplate) . '</textarea>
        </p>

        <p>
            <label for="' . esc_attr($this->get_field_id('onlytagsallowed')) . '">' . __('Show Only Tags (eg. mfn-regulatory,mfn-regulatory-mar):', 'text_domain') . '</label>
            <textarea rows="2" class="widefat" id="' . esc_attr($this->get_field_id('onlytagsallowed')) . '"
                      name="' . esc_attr($this->get_field_name('onlytagsallowed')) . '">' . wp_kses_post($onlytagsallowed) . '</textarea>
        </p>

        <p>
            <input id="' . esc_attr($this->get_field_id('skipcustomtags')) . '"
                   name="' . esc_attr($this->get_field_name('skipcustomtags')) . '" type="checkbox"
                   value="1" ' . checked('1', $skipcustomtags, false) . ' />
            <label for="' .  esc_attr($this->get_field_id('skipcustomtags')) . '">' . __('Skip Custom Tags', 'text_domain') . '</label>
        </p>
        ';

    }
    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['lang'] = (!empty($new_instance['lang'])) ? wp_strip_all_tags($new_instance['lang']) : '';
        $instance['showpagination'] = (!empty($new_instance['showpagination'])) ? strip_tags($new_instance['showpagination']) : '';
        $instance['showfilter'] = (!empty($new_instance['showfilter'])) ? strip_tags($new_instance['showfilter']) : '';
        $instance['filtertype'] = (!empty($new_instance['filtertype'])) ? strip_tags($new_instance['filtertype']) : 'dropdown';
        $instance['showfilterlabel'] = (!empty($new_instance['showfilterlabel'])) ? strip_tags($new_instance['showfilterlabel']) : '';
        $instance['showyears'] = (!empty($new_instance['showyears'])) ? strip_tags($new_instance['showyears']) : '';
        $instance['showyearslabel'] = (!empty($new_instance['showyearslabel'])) ? strip_tags($new_instance['showyearslabel']) : '';
        $instance['yearstype'] = (!empty($new_instance['yearstype'])) ? strip_tags($new_instance['yearstype']) : 'links';
        $instance['showpreview'] = (!empty($new_instance['showpreview'])) ? strip_tags($new_instance['showpreview']) : '';
        $instance['groupbyyear'] = (!empty($new_instance['groupbyyear'])) ? strip_tags($new_instance['groupbyyear']) : '';
        $instance['pagelen'] = (!empty($new_instance['pagelen'])) ? wp_strip_all_tags($new_instance['pagelen']) : 20;
        $instance['previewlen'] = (!empty($new_instance['previewlen'])) ? wp_strip_all_tags($new_instance['previewlen']) : '';
        $instance['tzLocation'] = (!empty($new_instance['tzLocation'])) ? wp_strip_all_tags($new_instance['tzLocation']) : '';
        $instance['timestampFormat'] = (!empty($new_instance['timestampFormat'])) ? wp_strip_all_tags($new_instance['timestampFormat']) : '';
        $instance['template'] = (!empty($new_instance['template'])) ? wp_kses_post($new_instance['template']) : '';
        $instance['tagtemplate'] = (!empty($new_instance['tagtemplate'])) ? wp_kses_post($new_instance['tagtemplate']) : '';
        $instance['yeartemplate'] = (!empty($new_instance['yeartemplate'])) ? wp_kses_post($new_instance['yeartemplate']) : '';
        $instance['onlytagsallowed'] = (!empty($new_instance['onlytagsallowed'])) ? wp_kses_post($new_instance['onlytagsallowed']) : '';
        $instance['skipcustomtags'] = (!empty($new_instance['skipcustomtags'])) ? strip_tags($new_instance['skipcustomtags']) : '';
        return $instance;
    }
} //

add_shortcode('mfn_archive_v2_widget', 'load_shortcode_mfn_archive_v2_widget');

function load_shortcode_mfn_archive_widget($atts)
{
    ob_start();
    the_widget('mfn_archive_widget', $atts);
    return ob_get_clean();
}

function load_shortcode_mfn_archive_v2_widget($atts)
{
    ob_start();
    the_widget('mfn_archive_v2_widget', $atts);
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
