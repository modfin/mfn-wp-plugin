<?php


require_once(dirname(__FILE__) . '/api.php');

// Register and load the widget
function mfn_load_widget()
{
    register_widget('mfn_archive_widget');
}

add_action('widgets_init', 'mfn_load_widget');


// Creating the widget
class mfn_archive_widget extends WP_Widget
{
    private $l10n = array(
        'Financial reports' => ['sv' => "Finansiella rapporter"],
        'All' => ['sv' => "Alla"],
        'Interim reports' => ['sv' => "Kvartalsrapport"],
        'Annual Reports' => ['sv' => "Årsredovisning"],
    );

    private function translate($word, $lang)
    {

        if (empty($this->l10n[$word])) {
            return $word;
        }
        if (empty($this->l10n[$word][$lang])) {
            return $word;
        }
        return $this->l10n[$word][$lang];
    }

    function __construct()
    {
        parent::__construct(
            'mfn_archive_widget',
            __('MFN Report Archive', 'mfn_archive_widget_domain'),
            array('description' => __('A widget that creates an archive for reports', 'mfn_archive_widget_domain'),)
        );
    }

    public function widget($args, $instance)
    {


        $me = $this;
        $l = function ($word, $lang) use ($me) {
            return $me->translate($word, $lang);
        };

        echo $args['before_widget'];

        $lang = 'en';

        $locale = determine_locale();
        if (is_string($locale)) {
            $parts = explode("_", $locale);
            if (strlen($parts[0]) == 2) {
                $lang = $parts[0];
            }
        }

        $pmlang = empty($instance['lang']) ? 'all' : $instance['lang'];
        if ($pmlang == 'auto') {
            $pmlang = $lang;
        }

        echo "<div class=\"mfn-report-container all\">";

        if (!empty($instance['showtitle'])) {
            echo "<h2>" . $l("Financial reports", $lang) . "</h2>";
        }

        $reports = MFN_get_reports($pmlang, 0, 500, 'DESC');

        if (sizeof($reports) < 1) {
            return;
        }

        if (empty($instance['showtitle'])) {
            echo "<style>.mfn-filter{display:none}</style>";
        }

        if (empty($instance['showyear'])) {
            echo "<style>.mfn-year-header{display:none}</style>";
        }

        if (empty($instance['showdate'])) {
            echo "<style>.mfn-report-date{display:none}</style>";
        }


        echo "
        <style>
            .mfn-filter ul{
                list-style: none;
                display: inline-block;  
            }
            .mfn-filter li{
                cursor: pointer;
                display: inline-block;
                padding-right: 1em;
            }
            ul.mfn-report-items{
                list-style: none;
                padding-left: 0;
            }
            .mfn-report-container.annual .mfn-report-interim{
                display: none; 
            }
            .mfn-report-container.interim .mfn-report-annual{
                display: none; 
            }
            .mfn-report-container.all .mfn-filter .all, 
            .mfn-report-container.annual .mfn-filter .annual, 
            .mfn-report-container.interim .mfn-filter .interim{
                text-decoration: underline;
            }
        </style>
        <script>
            function MFN_SET_FILTER(type){
                var list = document.querySelector('.mfn-report-container')
                list.classList.remove('all');
                list.classList.remove('annual');
                list.classList.remove('interim');
                list.classList.add(type);
            }
        </script>
        <div class=\"mfn-filter\">
        Filter:
            <ul>
                <li class=\"all\" onclick=\"MFN_SET_FILTER('all')\">" . $l('All', $lang) . "</li>
                <li class=\"interim\" onclick=\"MFN_SET_FILTER('interim')\">" . $l('Interim reports', $lang) . "</li>
                <li class=\"annual\" onclick=\"MFN_SET_FILTER('annual')\">" . $l('Annual Reports', $lang) . "</li>
            </ul>
        </div>

        ";

        $year = "";
        foreach ($reports as $r) {

            $y = substr($r->timestamp, 0, 4);
            if ($y != $year) {
                if ($year != "") {
                    echo "</ul>";
                }

                $year = $y;
                echo "<h3 class='mfn-year-header'>$year</h3>";
                echo "<ul class='mfn-report-items'>";
            }

            $date = substr($r->timestamp, 0, 10);

            echo "<li class='$r->type'> <span class='mfn-report-date'>$date</span> <a href=\"$r->url\" target=\"_blank\" rel='noopener'>$r->title</a></li>";

        }
        echo "</ul>";
        echo "<div>";

        echo $args['after_widget'];
    }

    public function form($instance)
    {
        if (isset($instance['lang'])) {
            $lang = $instance['lang'];
        } else {
            $lang = 'auto';
        }

        if (isset($instance['showtitle'])) {
            $showtitle = $instance['showtitle'];
        } else {
            $showtitle = '1';
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
                );

                // Loop through options and add each one to the select dropdown
                foreach ($options as $key => $name) {
                    echo '<option value="' . esc_attr($key) . '" id="' . esc_attr($key) . '" ' . selected($lang, $key, false) . '>' . $name . '</option>';

                } ?>
            </select>
        </p>


        <p>
            <input id="<?php echo esc_attr($this->get_field_id('showtitle')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('showtitle')); ?>" type="checkbox"
                   value="1" <?php checked('1', $showtitle); ?> />
            <label for="<?php echo esc_attr($this->get_field_id('showtitle')); ?>"><?php _e('Show title', 'text_domain'); ?></label>
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

        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['lang'] = (!empty($new_instance['lang'])) ? strip_tags($new_instance['lang']) : '';
        $instance['showtitle'] = (!empty($new_instance['showtitle'])) ? strip_tags($new_instance['showtitle']) : '';
        $instance['showfilter'] = (!empty($new_instance['showfilter'])) ? strip_tags($new_instance['showfilter']) : '';
        $instance['showyear'] = (!empty($new_instance['showyear'])) ? strip_tags($new_instance['showyear']) : '';
        $instance['showdate'] = (!empty($new_instance['showdate'])) ? strip_tags($new_instance['showdate']) : '';
        return $instance;
    }
} //