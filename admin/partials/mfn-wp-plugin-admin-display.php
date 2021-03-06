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

<div class="wrap">

    <h2>
        <?php echo esc_html( get_admin_page_title() ); ?>
        <span class="mfn-plugin-version-card">
            v<?php echo file_get_contents(dirname(__FILE__) . "/../../version") ?>
        </span>
    </h2>

    <div class="mcol-1-2">

    <form method="post" name="cleanup_options" action="options.php">

        <?php
        // Check if WPML/Polylang plugin exists
        $has_wpml = defined('WPML_PLUGIN_BASENAME');
        $has_pll = defined('POLYLANG_BASENAME');

        // Grab all options
        $options = get_option($this->plugin_name);

        // Cleanup
        $hub_url = isset($options['hub_url']) && $options['hub_url'] !== "" ? $options['hub_url'] : "https://hub.mfn.se";
        $sync_url = isset($options['sync_url']) && $options['sync_url'] !== "" ? $options['sync_url'] : "https://mfn.se";
        $plugin_url = isset($options['plugin_url']) && $options['plugin_url'] !== "" ? $options['plugin_url'] : plugins_url() . "/mfn-wp-plugin";
        $entity_id = isset($options['entity_id']) ? $options['entity_id'] : "";

        $cus_query = isset($options['cus_query']) ? $options['cus_query'] : "";

        $disable_archive =  isset($options['disable_archive']) ? $options['disable_archive'] : 'off';
        $verify_signature =  isset($options['verify_signature']) ? $options['verify_signature'] : 'off';
        $use_wpml =  isset($options['use_wpml']) ? $options['use_wpml'] : 'off';
        $use_pll =  isset($options['use_pll']) ? $options['use_pll'] : 'off';

        $reset_cache =  isset($options['reset_cache']) ? $options['reset_cache'] : 'off';

        $subscription_id = isset($options['subscription_id']) ? $options['subscription_id'] : "N/A";
        $posthook_secret = isset($options['posthook_secret']) ? $options['posthook_secret'] : "N/A";
        $posthook_name = isset($options['posthook_name']) ? $options['posthook_name'] : "N/A";

        settings_fields($this->plugin_name);
        do_settings_sections($this->plugin_name);

        $is_subscribed = strlen($subscription_id) == 36;
        $is_disabled = $is_subscribed == true ? 'disabled' : '';

        $wpml_enabled = ($use_wpml === "on" && $has_wpml);
        $polylang_enabled = ($use_pll === "on" && $has_pll);

        $default_lang = '';
        $all_languages = [];

        // Get rewrite options
        $rewrite_post_type = isset($options['rewrite_post_type']) ? unserialize($options['rewrite_post_type']) : null;

        if($wpml_enabled) {
            if (function_exists('icl_get_default_language')) {
                $default_lang = icl_get_default_language();
                if (function_exists('icl_get_languages')) {
                    $all_languages = icl_get_languages('skip_missing=1');
                }
            }
        } else if($polylang_enabled) {
            if (function_exists('pll_default_language')) {
                $default_lang = pll_default_language();
                if (function_exists('pll_languages_list')) {
                    $all_languages = pll_languages_list();
                }
            }
        }

        $default_tab = null;
        $tab = isset($_GET['tab']) ? $_GET['tab'] : $default_tab;
        ?>

        <script>
            function toggleQueryInput(e) {
                var el = document.getElementById("<?php echo $this->plugin_name; ?>-cus_query");
                el.setAttribute("disabled", "");
                if (e.checked) {
                    el.removeAttribute("disabled");
                }
            }
        </script>

        <h2><?php _e('Settings', $this->plugin_name); ?></h2>

        <table class="mfn-settings-table">
            <tbody>
                <tr>
                    <th>
                        <p>
                            <label for="<?php echo $this->plugin_name; ?>-sync_url"><?php _e('Sync URL', $this->plugin_name); ?></label>
                            <legend class="screen-reader-text"><?php _e('Sync URL', $this->plugin_name); ?></legend>
                            <small>(probably https://mfn.se)</small>
                        </p>
                    </th>
                </tr>
                <tr>
                    <td>
                        <input required pattern="\S+" class="regular-text wide" name="<?php echo $this->plugin_name; ?>[sync_url]" type="text" id="<?php echo $this->plugin_name; ?>-sync_url" value="<?php echo $sync_url; ?>" <?php echo $is_disabled; ?>>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <p>
                            <label for="<?php echo $this->plugin_name; ?>-hub_url"><?php _e('Hub URL', $this->plugin_name); ?></label>
                            <legend class="screen-reader-text"><?php _e('Hub URL', $this->plugin_name); ?></legend>
                            <small>(<?php _e('probably https://hub.mfn.se', $this->plugin_name); ?>)</small>
                        </p>
                    </th>
                </tr>
                <tr>
                    <td>
                        <input required pattern="\S+" class="regular-text wide" name="<?php echo $this->plugin_name; ?>[hub_url]" type="text" id="<?php echo $this->plugin_name; ?>-hub_url" value="<?php echo $hub_url; ?>" <?php echo $is_disabled; ?>>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <p>
                            <label for="<?php echo $this->plugin_name; ?>-plugin_url"><?php _e('Plugin URL', $this->plugin_name); ?></label>
                            <legend class="screen-reader-text"><?php _e('Plugin URL', $this->plugin_name); ?></legend>
                            <small>(<?php _e('probably ' . plugins_url() . '/mfn-wp-plugin', $this->plugin_name); ?>)</small>
                        </p>
                    </th>
                </tr>
                <tr>
                    <td>
                        <input required pattern="\S+" class="regular-text wide" name="<?php echo $this->plugin_name; ?>[plugin_url]" type="text" id="<?php echo $this->plugin_name; ?>-plugin_url" value="<?php echo $plugin_url; ?>" <?php echo $is_disabled ?>>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <p>
                            <label for="<?php echo $this->plugin_name; ?>-entity_id"><?php _e('Entity ID', $this->plugin_name); ?></label>
                            <legend class="screen-reader-text"><?php _e('Entity ID', $this->plugin_name); ?></legend>
                        </p>
                    </th>
                </tr>
                <tr>
                    <td>
                        <input required pattern="\S+" class="regular-text wide" name="<?php echo $this->plugin_name; ?>[entity_id]" type="text" id="<?php echo $this->plugin_name; ?>-entity_id" value="<?php echo $entity_id; ?>" <?php echo $is_disabled; ?>>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <p>
                            <label for="<?php echo $this->plugin_name; ?>-cus_query"><?php _e('Custom Query', $this->plugin_name); ?></label>
                            <legend class="screen-reader-text"><?php _e('Custom Query', $this->plugin_name); ?></legend>
                            <small>(I know what I'm doing <input type="checkbox" onchange="toggleQueryInput(this)" <?php echo $is_disabled; ?>/>)</small>
                        </p>
                    </th>
                </tr>
                <tr>
                    <td>
                        <input class="regular-text wide" name="<?php echo $this->plugin_name; ?>[cus_query]" type="text" id="<?php echo $this->plugin_name; ?>-cus_query" value="<?php echo $cus_query; ?>" <?php echo $is_disabled; ?> disabled>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <p>
                            <label for="<?php echo $this->plugin_name; ?>-rewrite_post_type"><?php _e('Rewrite settings', $this->plugin_name); ?></label>
                            <legend class="screen-reader-text"><?php _e('Rewrite settings', $this->plugin_name); ?></legend>
                        </p>
                    </th>
                </tr>
            </tbody>
        </table>
        <?php
        if ($wpml_enabled || $polylang_enabled && sizeof($all_languages) > 0) {
            echo '
                <nav class="nav-tab-wrapper mfn-nav-tab-wrapper">
                    <div class="mfn-tooltip-box">
                        <span class="mfn-info-icon-wrapper"><i class="dashicons dashicons-info-outline"></i></span>
                        <span class="mfn-tooltip-text">Listing all languages detected.</span>
                    </div>
            ';

            foreach ($all_languages as $lang) {
                $lang = $wpml_enabled ? $lang["language_code"] : $lang;
                $is_default_lang = $default_lang === $lang ? " <small>(" . "Primary" . ")</small>" : '';

                echo '
                    <a data-lang=' . $lang . ' class="nav-tab mfn-nav-tab">' . $lang . $is_default_lang . '</a>
                ';
            }

            echo "</nav>";
            echo '<div class="mfn-tab-content">';

            foreach ($all_languages as $lang) {
                $lang = $wpml_enabled ? $lang["language_code"] : $lang;

                $slug = isset($rewrite_post_type['slug_' . $lang]) && $rewrite_post_type['slug_' . $lang] !== '' ? $rewrite_post_type['slug_' . $lang] : MFN_POST_TYPE . '_' . $lang;
                $archive_name = isset($rewrite_post_type['archive-name_' . $lang]) && $rewrite_post_type['archive-name_' . $lang] !== '' ? $rewrite_post_type['archive-name_' . $lang] : MFN_ARCHIVE_NAME;
                $singular_name = isset($rewrite_post_type['singular-name_' . $lang]) && $rewrite_post_type['singular-name_' . $lang] !== '' ? $rewrite_post_type['singular-name_' . $lang] : MFN_SINGULAR_NAME;

                echo '
                <table class="mfn-hide mfn-lang-table mfn-lang-table-' . $lang . '">
                    <tbody>
                        <tr>
                            <td>
                ';
                ?>
                                <label>
                                    <?php echo _e('Custom Post Type URL Slug', $this->plugin_name) . ' <small>(Default: ' . MFN_POST_TYPE . ')</small>'; ?>
                                </label>
                                <legend class="screen-reader-text">
                                    <?php _e('Custom Post Type URL Slug', $this->plugin_name); ?>
                                </legend>
                <?php
                echo '
                            </td>
                        </tr>
                        <tr>
                            <td class="mfn-lang-td">
                                <input type="text" class="regular-text" name="' . $this->plugin_name . '[rewrite_post_type][slug_' . $lang . ']' . '" value="' . $slug . '" ' . $is_disabled . '>
                                <div class="mfn-tooltip-box">
                                    <span class="mfn-info-icon-wrapper"><i class="dashicons dashicons-info-outline"></i></span>
                                    <span class="mfn-tooltip-text">Rewrite the slug (' . MFN_POST_TYPE . ') in the URL - eg. "press-release"</span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                ';
                ?>
                                <label>
                                    <?php echo _e('Custom Archive Name', $this->plugin_name) . ' <small>(Default: ' . MFN_ARCHIVE_NAME . ')</small>'; ?>
                                </label>
                                <legend class="screen-reader-text">
                                    <?php _e('Custom Archive Name', $this->plugin_name); ?>
                                </legend>
                <?php
                echo '
                            </td>
                        </tr>
                        <tr>
                            <td class="mfn-lang-td">
                                <input type="text" class="regular-text" name="' . $this->plugin_name . '[rewrite_post_type][archive-name_' . $lang . ']' . '" value="' . $archive_name . '" ' . $is_disabled . '>
                                <div class="mfn-tooltip-box">
                                    <span class="mfn-info-icon-wrapper"><i class="dashicons dashicons-info-outline"></i></span>
                                    <span class="mfn-tooltip-text">Set a custom name of the news archive page  - eg. "Press Releases"</span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                ';
                ?>
                                <label>
                                    <?php echo _e('Custom Singular Name', $this->plugin_name) . ' <small>(Default: ' . MFN_SINGULAR_NAME . ')</small>'; ?>
                                </label>
                                <legend class="screen-reader-text">
                                    <?php _e('Custom Singular Name', $this->plugin_name); ?>
                                </legend>
                <?php
                echo '
                            </td>
                        </tr>
                        <tr>
                            <td class="mfn-lang-td">
                                <input type="text" class="regular-text" name="' . $this->plugin_name . '[rewrite_post_type][singular-name_' . $lang . ']' . '" value="' . $singular_name . '" ' . $is_disabled . '>
                                <div class="mfn-tooltip-box">
                                    <span class="mfn-info-icon-wrapper"><i class="dashicons dashicons-info-outline"></i></span>
                                    <span class="mfn-tooltip-text">Set a custom name of a single news item - eg. "Press release"</span>
                                </div>
                            </td>
                         </tr>';
                        if(!isset($rewrite_post_type['slug_' . $lang])) {
                            echo '
                            <tr>
                                <td class="mfn-info-td">
                                    <span class="mfn-info-box do-fade"><i class="dashicons dashicons-warning"></i> <span class="mfn-info-box-text">Unsaved!</span></span>
                                </td>
                            </tr>';
                        }
                        echo '
                    </tbody>
                </table>
                ';
            }
                echo "</div>";
        } else {
                $single_slug = isset($rewrite_post_type['single-slug']) && $rewrite_post_type['single-slug'] !== '' ? $rewrite_post_type['single-slug'] : MFN_POST_TYPE;
                $archive_name = isset($rewrite_post_type['archive-name']) && $rewrite_post_type['archive-name'] !== '' ? $rewrite_post_type['archive-name'] : MFN_ARCHIVE_NAME;
                $singular_name = isset($rewrite_post_type['singular-name']) && $rewrite_post_type['singular-name'] !== '' ? $rewrite_post_type['singular-name'] : MFN_SINGULAR_NAME;

                echo '
                <table class="mfn-hide mfn-lang-table">
                    <tbody>
                        <tr>
                            <td>
                ';
                ?>
                                <label>
                                    <?php echo _e('Custom Post Type URL Slug', $this->plugin_name) . ' <small>(Default: ' . MFN_POST_TYPE . ')</small>'; ?>
                                </label>
                                <legend class="screen-reader-text">
                                    <?php _e('Custom Post Type URL Slug', $this->plugin_name); ?>
                                </legend>
                <?php
                echo '
                            </td>
                        </tr>
                        <tr>
                            <td class="mfn-lang-td">     
                                <input type="text" class="regular-text" name="' . $this->plugin_name . '[rewrite_post_type][single-slug]' . '" value="' . $single_slug . '" ' . $is_disabled . '>
                                <div class="mfn-tooltip-box">
                                    <span class="mfn-info-icon-wrapper"><i class="dashicons dashicons-info-outline"></i></span>
                                    <span class="mfn-tooltip-text">Rewrite the slug (' . MFN_POST_TYPE . ') in the URL - eg. "press-releases"</span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                ';
                ?>
                                <label>
                                    <?php echo _e('Custom Archive Name', $this->plugin_name) . ' <small>(Default: MFN News Items)</small>'; ?>
                                </label>
                                <legend class="screen-reader-text">
                                    <?php _e('Custom Archive Name', $this->plugin_name); ?>
                                </legend>
                <?php
                echo '
                            </td>
                        </tr>
                        <tr>
                            <td class="mfn-lang-td">     
                                <input type="text" class="regular-text" name="' . $this->plugin_name . '[rewrite_post_type][archive-name]' . '" value="' . $archive_name . '" ' . $is_disabled . '>
                                <div class="mfn-tooltip-box">
                                    <span class="mfn-info-icon-wrapper"><i class="dashicons dashicons-info-outline"></i></span>
                                    <span class="mfn-tooltip-text">Set a custom name of the news archive page  - eg. "Press Releases"</span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                ';
            ?>
                                <label>
                                    <?php echo _e('Custom Singular Name', $this->plugin_name) . ' <small>(Default: MFN News Item)</small>'; ?>
                                </label>
                                <legend class="screen-reader-text">
                                    <?php _e('Custom Singular Name', $this->plugin_name); ?>
                                </legend>
            <?php
            echo '
                            </td>
                        </tr>
                        <tr>
                            <td class="mfn-lang-td">     
                                <input type="text" class="regular-text" name="' . $this->plugin_name . '[rewrite_post_type][singular-name]' . '" value="' . $singular_name . '" ' . $is_disabled . '>
                                <div class="mfn-tooltip-box">
                                    <span class="mfn-info-icon-wrapper"><i class="dashicons dashicons-info-outline"></i></span>
                                    <span class="mfn-tooltip-text">Set a custom name of a single news item - eg. "Press release"</span>
                                </div>
                            </td>
                        </tr>';
            if(!isset($rewrite_post_type['single-slug'])) {
                echo '
                         <tr>
                            <td class="mfn-info-td">
                                <span class="mfn-info-box do-fade"><i class="dashicons dashicons-warning"></i> <span class="mfn-info-box-text">Unsaved!</span></span>
                            </td>
                         </tr>
                ';
                }
                echo '
                    </tbody>
                </table>
                ';
            }
        ?>
        <table>
            <tbody>
                <tr>
                    <td>
                        <p>
                            <input type="checkbox" id="<?php echo $this->plugin_name; ?>-disable_archive" name="<?php echo $this->plugin_name; ?>[disable_archive]" <?php checked($disable_archive, "on"); ?> value="on" <?php echo $is_disabled; ?>>
                            <label for="<?php echo $this->plugin_name; ?>-disable_archive"><?php _e('Disable Archive', $this->plugin_name); ?></label>
                            <legend class="screen-reader-text"><?php _e('Disable Archive', $this->plugin_name); ?></legend>
                            <br>
                            <small>(<?php _e('Makes the news archive unreachable - eg. ' .  get_home_url() . '/' . get_post_type_object('mfn_news')->rewrite['slug']); ?>)</small>
                        </p>
                    <td>
                </tr>
                <tr>
                    <td>
                        <p>
                            <input type="checkbox" id="<?php echo $this->plugin_name; ?>-verify_signature" name="<?php echo $this->plugin_name; ?>[verify_signature]" <?php checked($verify_signature, "on"); ?> value="on" <?php echo $is_disabled; ?>>
                            <label for="<?php echo $this->plugin_name; ?>-verify_signature"><?php _e('Verify Signature', $this->plugin_name); ?></label>
                            <legend class="screen-reader-text"><?php _e('Verify Signature', $this->plugin_name); ?></legend>
                            <br>
                            <small>(<?php _e('Cryptographically ensures that mfn.se is indeed the sender of the story'); ?>)</small>
                        </p>
                    <td>
                </tr>
                <tr>
                    <td>
                        <p>
                            <input type="checkbox" id="<?php echo $this->plugin_name; ?>-use_wpml" name="<?php echo $this->plugin_name; ?>[use_wpml]" <?php checked($use_wpml, "on"); ?> value="on" <?php echo $is_subscribed == true || $has_wpml == false  ? 'disabled' : '' ?>>
                            <label for="<?php echo $this->plugin_name; ?>-use_wpml"><?php _e('Use WPML', $this->plugin_name); ?></label>
                            <legend class="screen-reader-text"><?php _e('Use WPML', $this->plugin_name); ?></legend>
                            <br>
                            <small>(<?php _e('Make plugin compliant with https://wpml.org locale management. Mapping story content only works with stories sent by mfn.se', $this->plugin_name); ?>)</small>
                        </p>
                    <td>
                </tr>
                <tr>
                    <td>
                        <p>
                            <input type="checkbox" id="<?php echo $this->plugin_name; ?>-use_pll" name="<?php echo $this->plugin_name; ?>[use_pll]" <?php checked($use_pll, "on"); ?> value="on" <?php echo $is_subscribed == true || $has_pll == false  ? 'disabled' : '' ?>>
                            <label for="<?php echo $this->plugin_name; ?>-use_pll"><?php _e('Use Polylang', $this->plugin_name); ?></label>
                            <legend class="screen-reader-text"><?php _e('Use Polylang', $this->plugin_name); ?></legend>
                            <br>
                            <small>(<?php _e('Make plugin compliant with https://polylang.pro locale management. Mapping story content only works with stories sent by mfn.se', $this->plugin_name); ?>)</small>
                        </p>
                    <td>
                </tr>
                <tr>
                    <td>
                        <p>
                            <input type="checkbox" id="<?php echo $this->plugin_name; ?>-reset_cache" name="<?php echo $this->plugin_name; ?>[reset_cache]" <?php checked($reset_cache, "on"); ?> value="on" <?php echo $is_disabled; ?>>
                            <label for="<?php echo $this->plugin_name; ?>-reset_cache"><?php _e('Reset Cache', $this->plugin_name); ?></label>
                            <legend class="screen-reader-text"><?php _e('Reset Cache', $this->plugin_name); ?></legend>
                            <br>
                            <small>(<?php _e('On every new item insert, if checked, this will reset the db cache', $this->plugin_name); ?>)</small>
                        </p>
                    <td>
                </tr>
            </tbody>
        </table>

        <div style="display: inline-block;">
            <?php submit_button('Save', 'primary','submit', true, $is_disabled); ?>
        </div>

    </form>
        <h3 class="mfn-danger-zone-heading"><?php _e('Danger Zone', $this->plugin_name); ?></h3>
        <table>
            <tbody>
                <tr>
                    <td>
                        <p>
                            <button class="button mfn-danger-zone-btn" id="clear-settings-btn"><?php _e('Clear all MFN settings', $this->plugin_name); ?></button>
                        </p>
                    </td>
                    <td>
                        <p>
                            <input type="text" placeholder="write 'clear' to confirm" id="clear-settings-input">
                        </p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <button class="button mfn-danger-zone-btn" id="delete-posts-btn"><?php _e('Delete all MFN posts', $this->plugin_name); ?></button>
                    </td>
                    <td>
                        <input type="text" placeholder="write 'delete' to confirm" id="delete-posts-input">
                    </td>
                    <td>
                        <span id="delete-posts-info"></span>
                    </td>
                </tr>

            </tbody>
        </table>
    </div>

    <div class="mcol-1-2">
        <h3><?php _e('Status', $this->plugin_name); ?></h3>
        <table class="mfn-status-table">
            <tbody>
                <tr>
                    <th><?php _e('Subscription Id', $this->plugin_name); ?>:</th>
                    <td>
                        <?php echo $subscription_id; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Post hook Secret', $this->plugin_name); ?>:</th>
                    <td>
                        <?php echo $posthook_secret; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Post hook Name', $this->plugin_name); ?>:</th>
                    <td>
                        <?php echo $posthook_name; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Sync URL', $this->plugin_name); ?>:</th>
                    <td id="sync-url-test"></td>
                </tr>
                <tr>
                    <th><?php _e('Hub URL', $this->plugin_name); ?>:</th>
                    <td id="hub-url-test"></td>
                </tr>
                <tr>
                    <th><?php _e('Plugin URL', $this->plugin_name); ?>:</th>
                    <td id="plugin-url-test"></td>
                </tr>
            </tbody>
        </table>

        <h3><?php _e('Actions', $this->plugin_name); ?></h3>

        <h4 class="mfn-h4"><?php _e('Sync', $this->plugin_name); ?></h4>
        <div class="mfn-tooltip-box">
            <span class="mfn-info-icon-wrapper"><i class="dashicons dashicons-info-outline"></i></span>
            <span class="mfn-tooltip-text"><?php _e('Fetch the 10 latest or all press releases from MFN.se to the local Wordpress feed', $this->plugin_name); ?>.</span>
        </div>
        <div>
            <button id="sync-latest" class="button mfn-button"><?php _e('Sync Latest', $this->plugin_name); ?></button>
            <button class="button-primary mfn-button" id="sync-all"><?php _e('Sync All', $this->plugin_name); ?></button>
            <span id="sync-status"></span>
        </div>
        <h4 class="mfn-h4"><?php _e('Sync Taxonomy', $this->plugin_name); ?></h4>
        <div class="mfn-tooltip-box">
            <span class="mfn-info-icon-wrapper"><i class="dashicons dashicons-info-outline"></i></span>
            <span class="mfn-tooltip-text"><?php _e('Fetches and updates the local tags with the translations from MFN.se', $this->plugin_name); ?>.</span>
        </div>
        <div>
            <button class="button mfn-button" id="sync-tax"><?php _e('Sync Taxonomy', $this->plugin_name); ?></button>
            <span id="sync-tax-status"></span>
        </div>

        <h4 class="mfn-h4"><?php _e('Subscription', $this->plugin_name); ?></h4>
        <div class="mfn-tooltip-box">
            <span class="mfn-info-icon-wrapper"><i class="dashicons dashicons-info-outline"></i></span>
            <span class="mfn-tooltip-text"><?php _e('Subscribing enables new press releases to be automatically injected into the Wordpress feed', $this->plugin_name); ?>.</span>
        </div>

        <div>
            <button class="button mfn-button" id="sub-button" <?php echo $is_disabled; ?>><?php _e('Subscribe', $this->plugin_name); ?></button>
            <button class="button mfn-button" id="unsub-button"><?php _e('Unsubscribe', $this->plugin_name); ?></button>
            <span id="sync-status"></span>
        </div>
    </div>

</div>

<script>
    window.PLUGIN_URL = '<?php echo $plugin_url; ?>';
</script>