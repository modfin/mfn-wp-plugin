<?php


require_once(dirname(__FILE__) . '/api.php');
require_once(dirname(__FILE__) .'/consts.php');

// Register and load the widget
function mfn_load_widget()
{
    register_widget('mfn_archive_widget');
    register_widget('mfn_subscription_widget');
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




// Creating the widget
class mfn_subscription_widget extends WP_Widget
{
    private $l10n = array(
        'Press releases' => ['sv' => "Pressmeddelanden"],
        'Reports' => ['sv' => "Rapporter"],
        'Annual reports' => ['sv' => "Årsredovisning"],
        'Other news' => ['sv' => "Övriga nyheter"],
        'Subscribe'  => ['sv' => "Prenumerera"],
        'Approve' => ['sv' => "Godkänn"],
        'A real email address must be provided.' => ['sv' => "Välj en korrekt emailadress."],
        'The GDPR policy must be accepted' => ['sv' => "GDPR policyn måste godkännas"],
        'An email has been sent to confirm your subscription.' => ['sv' => "Ett email har skickats till adressen, bekräfta det för att slutföra prenumerationen."],
        'Check the languages you would like to subscribe to.'=> ['sv' => "Välj vilka språk du vill prenumerera på."],

        'sv-name' => ['sv' => "Svenska", 'en' => "Swedish"],
        'en-name' => ['sv' => "Engelska", 'en' => "English"],
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
            'mfn_subscription_widget',
            __('MFN Subscription Widget', 'mfn_subscription_widget_domain'),
            array('description' => __('A widget add a MFN subscription possibilities', 'mfn_subscription_widget_domain'),)
        );
    }

    public function widget($args, $instance)
    {


        $me = $this;
        $l = function ($word, $lang) use ($me) {
            return $me->translate($word, $lang);
        };

        echo $args['before_widget'];


        $lang = empty($instance['lang']) ? 'auto' : $instance['lang'];

        if($lang == "auto"){
            $locale = determine_locale();
            if (is_string($locale)) {
                $parts = explode("_", $locale);
                if (strlen($parts[0]) == 2) {
                    $lang = $parts[0];
                }
            }
        }
        if($lang == "auto"){
            $lang = "en";
        }


        $langs = strtolower(trim(empty($instance['langs']) ? 'sv,en' : $instance['langs']));
        $langs = explode(",", $langs);

        $privacy_policy = empty($instance['privacy_policy']) ? "https://mfn.se/privacy-policy" : $instance['privacy_policy'];


        $ops = get_option('mfn-wp-plugin');
        $entity_id = isset($ops['entity_id']) ? $ops['entity_id'] : "bad-entity-id";
        $hub_url = isset($ops['hub_url']) ? $ops['hub_url'] : "bad-hub-url";


        if(empty($instance['showlangs'])){
            echo "<style>.mfn-subscribe .mfn-languages{display: none}</style>";
        }

        if(empty($instance['showtypes'])){
            echo "<style>.mfn-subscribe .mfn-categories{display: none}</style>";
        }


        ?>

        <style>
            .mfn-subscribe .hidden {
                display: none;
            }
            #policy-text{
                border-bottom: 5px solid transparent;
            }
            #policy-text.alert{
                border-bottom-color: red;
            }
        </style>
        <div id="mfn-subscribe-div" class="mfn-subscribe">
            <input type="hidden" id="sub-hub-entity-id" name="hub.entityid" value="<?php echo $entity_id ?>">
            <input type="hidden" id="sub-hub-topic" name="hub.topic" value="/s">
            <input type="hidden" id="sub-hub-url" name="hub.url" value="<?php echo $hub_url ?>">
            <input type="hidden" id="sub-hub-lang" name="hub.lang" value="<?php echo $lang ?>">
            <input type="hidden" id="sub-hub-subscribe-to-widget-language" name="hub.subscribe-to-widget-language" value="{{.Settings.SubscribeToWidgetLanguage}}">

            <div class="mfn-info">

                <?php
                    if($lang == "sv"){
                        echo "
                            <p>Få kontinuerlig information från bolaget via email.</p>
                        ";
                    }
                    if($lang == "en"){
                        echo "
                            <p>Receive company data continuously to your inbox.</p>
                        ";
                    }
                ?>
            </div>

            <div class="mfn-info mfn-categories">

                <?php
                if($lang == "sv"){
                    echo "
                            <p>Välj vilka typer av meddelanden du vill prenumerera genom att fylla i checkboxen för respektive typ.</p>
                        ";
                }
                if($lang == "en"){
                    echo "
                            <p>Check the category of messages you would like to subscribe to below.</p>
                        ";
                }
                ?>
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
                    foreach ($langs as $la){
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
                    if($lang == "sv"){
                        echo "För att prenumerera på detta behöver du godkänna våra <a href=\"$privacy_policy\" target=\"_blank\">generella villkor</a> i syfte för GDPR.";
                    }
                    if($lang == "en"){
                        echo "To subscribe, please read and approve our <a href=\"$privacy_policy\" target=\"_blank\">data storage policy</a> to comply with GDPR.";
                    }
                    ?>

                    <label for="approve"><?php echo $l("Approve", $lang) ?></label>
                    <input id="approve" onclick="document.getElementById('gdpr-policy-fail').classList.add('hidden');" type="checkbox">
                </p>

            </div>


            <div class="subscription-wrapper">
                <form onsubmit="event.preventDefault(); return datablocks_SubscribeMail()">
                    <input id="sub-email" type="text" placeholder="Email" name="hub.callback">
                    <button type="submit">
                        <?php echo $l("Subscribe", $lang) ?>
                    </button>
                </form>
            </div>
            <div id="email-bad-input" class="hidden warning mfn-info alert">
                <?php echo $l("A real email address must be provided.", $lang) ?>
            </div>
            <div id="gdpr-policy-fail" class="hidden warning mfn-info alert">
                <?php echo $l("The GDPR policy must be accepted.", $lang) ?>
            </div>
            <div id="email-success" class="hidden success mfn-info">
                <?php echo $l("An email has been sent to confirm your subscription.", $lang) ?>
            </div>




        </div>
        <?php
        echo "<script>".JS_SUB_LIB."</script>";

        echo $args['after_widget'];
    }

    public function form($instance)
    {
        if (isset($instance['lang'])) {
            $lang = $instance['lang'];
        } else {
            $lang = 'auto';
        }

        if (isset($instance['langs'])) {
            $langs = strtolower(trim($instance['langs']));
        } else {
            $langs = 'sv,en';
        }


        if (isset($instance['privacy_policy'])) {
            $privacy_policy = $instance['privacy_policy'];
        } else {
            $privacy_policy = "https://mfn.se/privacy-policy" ;
        }


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
            $showtypes= '1';
        }



        ?>

        <p>
            <label for="<?php echo $this->get_field_id('lang'); ?>"><?php _e('Archive Language', 'text_domain'); ?></label>
            <select name="<?php echo $this->get_field_name('lang'); ?>" id="<?php echo $this->get_field_id('lang'); ?>"
                    class="widefat">
                <?php
                // Your options array
                $options = array(
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
            <label for="<?php echo esc_attr( $this->get_field_id( 'langs' ) ); ?>"><?php _e( 'Languages to select (eg sv,en)', 'text_domain' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'langs' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'langs' ) ); ?>" type="text" value="<?php echo esc_attr( $langs ); ?>" />
        </p>


        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'privacy_policy' ) ); ?>"><?php _e( 'GDPR Policy link', 'text_domain' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'privacy_policy' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'privacy_policy' ) ); ?>" type="text" value="<?php echo esc_attr( $privacy_policy ); ?>" />
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