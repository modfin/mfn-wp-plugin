<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://github.com/crholm
 * @since      1.0.0
 *
 * @package    Mfn_Wp_Plugin
 * @subpackage Mfn_Wp_Plugin/admin/partials
 */
?>



<div class="wrap" style="margin-top: 60px">

    <h2 ><?php echo esc_html( get_admin_page_title() ); ?></h2>

    <div class="mcol-1-2">
        <h2>Settings</h2>

    <form method="post" name="cleanup_options" action="options.php">

        <?php

        //Grab all options
        $options = get_option($this->plugin_name);



        // Cleanup
        $hub_url =  isset($options['hub_url']) ? $options['hub_url'] : "";
        $sync_url =  isset($options['sync_url']) ? $options['sync_url'] : "";
        $plugin_url = isset($options['plugin_url']) ? $options['plugin_url'] : "";
        $entity_id = isset($options['entity_id']) ? $options['entity_id'] : "";



        $verify_signature =  isset($options['verify_signature']) ? $options['verify_signature'] : 'off';

        $subscription_id = isset($options['subscription_id']) ? $options['subscription_id'] : "N/A";
        $posthook_secret = isset($options['posthook_secret']) ? $options['posthook_secret'] : "N/A";
        $posthook_name = isset($options['posthook_name']) ? $options['posthook_name'] : "N/A";


        settings_fields($this->plugin_name);
        do_settings_sections($this->plugin_name);

        $is_subscribed = strlen($subscription_id) == 36 ? true : false;

        ?>


        <fieldset>
            <fieldset>
                <p>Sync URL <small>(probably https://mfn.se)</small></p>
                <legend class="screen-reader-text"><span><?php _e('input the api URL', $this->plugin_name); ?></span></legend>
                <input class="regular-text wide" id="<?php echo $this->plugin_name; ?>-sync_url" name="<?php echo $this->plugin_name; ?>[sync_url]" value="<?php echo $sync_url; ?>" <?php echo $is_subscribed == true ? 'disabled' : ''?>/>
            </fieldset>
        </fieldset>


        <fieldset>
            <fieldset>
                <p>Hub URL <small>(probably https://hub.mfn.se)</small></p>
                <legend class="screen-reader-text"><span><?php _e('input the api URL', $this->plugin_name); ?></span></legend>
                <input class="regular-text wide" id="<?php echo $this->plugin_name; ?>-hub_url" name="<?php echo $this->plugin_name; ?>[hub_url]" value="<?php echo $hub_url; ?>" <?php echo $is_subscribed == true ? 'disabled' : ''?>/>
            </fieldset>
        </fieldset>

        <fieldset>
            <fieldset>
                <p>Plugin URL <small>(probably <?php echo plugins_url()?>/mfn-wp-plugin)</small></p>
                <legend class="screen-reader-text"><span><?php _e('input the api URL', $this->plugin_name); ?> </span> </legend>
                <input class="regular-text wide" id="<?php echo $this->plugin_name; ?>-plugin_url" name="<?php echo $this->plugin_name; ?>[plugin_url]" value="<?php echo $plugin_url; ?>" <?php echo $is_subscribed == true ? 'disabled' : ''?>/>
            </fieldset>
        </fieldset>

        <fieldset>
            <fieldset>
                <p>Entity Id</p>
                <legend class="screen-reader-text"><span><?php _e('input the api URL', $this->plugin_name); ?></span></legend>
                <input class="regular-text wide" id="<?php echo $this->plugin_name; ?>-entity_id" name="<?php echo $this->plugin_name; ?>[entity_id]" value="<?php echo $entity_id; ?>" <?php echo $is_subscribed == true ? 'disabled' : ''?>/>
            </fieldset>
        </fieldset>


        <fieldset>
            <fieldset>
                <p>Verify Signature</p>
                <input type="checkbox" id="<?php echo $this->plugin_name; ?>-verify_signature" name="<?php echo $this->plugin_name; ?>[verify_signature]" <?php checked($verify_signature, "on"); ?> value="on" <?php echo $is_subscribed == true ? 'disabled' : ''?>/>
            </fieldset>
        </fieldset>

        <div style="display: inline-block">
            <?php submit_button('Save', 'primary','submit', TRUE, $is_subscribed ? 'disabled' : ''); ?>
        </div>

<!--        <div style="display: inline-block">-->
<!--            --><?php //submit_button('Unsubscribe', 'warning','submit', TRUE); ?>
<!--        </div>-->

    </form>


    </div>

    <div class="mcol-1-2">

        <h3>Status</h3>
        <div>
            <pre>
Subscription Id: <?php echo $subscription_id; ?>

Posthook Secret: <?php echo $posthook_secret; ?>

  Posthook Name: <?php echo $posthook_name; ?>

     Plugin URL: <span id="plugin-url-test"></span>
        Hub URL: <span id="hub-url-test"></span>

            </pre>
        </div>

        <h3>Actions</h3>

        <h4>Sync</h4>
        <div>
            <button class="button" id="sync-latest">Sync Latest</button>
            <button class="button" id="sync-all">Sync All</button>
            <span id="sync-status"></span>
        </div>

        <h4>Subscription</h4>
        <div >
            <button class="button" id="sub-button"   <?php echo $is_subscribed == true  ? 'disabled' : ''?>>Subscribe</button>
            <button class="button" id="unsub-button" <?php echo $is_subscribed == false ? 'disabled' : ''?>>Unsubscribe</button>
            <span id="sync-status"></span>
        </div>

<!--        <pre>-->
<!--            --><?php //echo print_r($options); ?>
<!--        </pre>-->


    </div>

</div>



<script>
    window.PLUGIN_URL = '<?php echo $plugin_url?>';
</script>

<!---->
<!--http://192.168.2.90:9090/api/dogmatix-->
<!--http://192.168.2.90:2020/wp-content/plugins/mfn-wp-plugin/posthook.php-->
<!--69f289ed-e8de-4b73-80fe-76a86380d417-->