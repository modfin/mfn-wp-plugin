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

// Grab all options
$options = get_option($this->plugin_name);

// Subscription settings
$subscriptions = get_option("mfn-subscriptions");
$subscription = mfn_get_subscription_by_plugin_url($subscriptions, mfn_plugin_url());
$subscription_id = $subscription['subscription_id'] ?? '';

// Defaults if cleared options
if ($options === false || (is_array($options) && sizeof($options) === 0)) {
    $options['verify_signature'] = 'on';
    $options['enable_attachments'] = 'on';
    $options['thumbnail_allow_delete'] = 'on';
}

// Settings
$plugin_url = plugins_url() . "/" . MFN_PLUGIN_NAME; // *determined

$sync_url = isset($options['sync_url']) && $options['sync_url'] !== "" ? $options['sync_url'] : "https://feed.mfn.se/v1";

$entity_id = $options['entity_id'] ?? "";

$has_entity_id = strlen($entity_id) == 36;
$subscribe_button_disabled = strlen($subscription_id) == 36 || !$has_entity_id;
$unsubscribe_button_disabled = strlen($subscription_id) !== 36 || !$has_entity_id || $sync_url === '';
$is_readonly = $has_entity_id == true ? 'readonly' : '';

$cus_query = $options['cus_query'] ?? "";

// Language settings
$has_wpml = defined('WPML_PLUGIN_BASENAME');
$has_pll = defined('POLYLANG_BASENAME');
$use_wpml = isset($options['language_plugin']) && $options['language_plugin'] == 'wpml';
$use_pll = isset($options['language_plugin']) && $options['language_plugin'] == 'pll';
$language_check = mfn_language_plugin_check($use_pll, $has_pll, $use_wpml, $has_wpml);
$language_usage_check = mfn_language_plugin_usage_check();

$has_language_plugins = $use_wpml || $use_pll;
$append_none_radio_classes = !$has_language_plugins || (isset($options['language_plugin']) && $options['language_plugin'] === 'none') ? ' mfn-selected-radio-option' : '';
$checked_none = (!$has_pll && !$has_wpml) || !isset($options['language_plugin']) ? 'checked' : checked('none', $options['language_plugin'], false);

$append_wpml_detected_classes = $language_check->detected_wpml ? ' do-fade mfn-warning-input' : '';
$append_wpml_detected_classes .= $use_wpml && $has_wpml ? ' mfn-selected-radio-option': '';
$wpml_detected = $language_check->detected_wpml ? ' <span style="color: green;">' . mfn_get_text('text_plugin_detected') . '</span>' : '';
$wpml_msg = $language_check->detected_wpml ? '<p><span class="dashicons dashicons-warning mfn-warning-icon mfn-do-fade"></span> ' . $language_check->languageMsg . '</p>' : '';

$append_pll_detected_classes = $language_check->detected_pll ? ' do-fade mfn-warning-input' : '';
$append_pll_detected_classes .= $use_pll && $has_pll ? ' mfn-selected-radio-option': '';
$pll_detected = $language_check->detected_pll ? ' <span style="color: green;">' . mfn_get_text('text_plugin_detected') . '</span>' : '';
$pll_msg = $language_check->detected_pll ? '<p><span class="dashicons dashicons-warning mfn-warning-icon mfn-do-fade"></span> ' . $language_check->languageMsg . '</p>' : '';

$disable_archive = $options['disable_archive'] ?? 'off';

// Advanced settings
$thumbnail_on = $options['thumbnail_on'] ?? 'off';
$thumbnail_allow_delete = $options['thumbnail_allow_delete'] ?? 'off';
$verify_signature = $options['verify_signature'] ?? 'off';
$reset_cache = $options['reset_cache'] ?? 'on';
$enable_attachments = $options['enable_attachments'] ?? 'off';
$taxonomy_disable_cus_prefix = $options['taxonomy_disable_cus_prefix'] ?? "off";

// Rewrite settings
$rewrite = isset($options['rewrite_post_type']) ? unserialize($options['rewrite_post_type']) : null;
$slug = (isset($rewrite['slug']) && $rewrite['slug'] !== '' ? $rewrite['slug'] : MFN_POST_TYPE);
$archive_name = (isset($rewrite['archive-name']) && $rewrite['archive-name'] !== '' ? $rewrite['archive-name'] : MFN_ARCHIVE_NAME);
$singular_name = (isset($rewrite['singular-name']) && $rewrite['singular-name'] !== '' ? $rewrite['singular-name'] : MFN_SINGULAR_NAME);
$taxonomy_rewrite_slug = $options['taxonomy_rewrite_slug'] ?? '';

// HTML
echo '
<div class="wrap" id="mfn-admin-wrapper">
    <h2>' . esc_html( get_admin_page_title() ) . '
        <span class="mfn-plugin-version-card">
            <a href="https://github.com/modfin/mfn-wp-plugin" target="_blank">
                ' . "v" . file_get_contents(dirname(__FILE__) . "/../../version") . '
            </a>
        </span>
    </h2>

    <div class="mcol-1-2">
    
    <form method="POST" id="mfn-form"  name="cleanup_options" action="options.php">
';
    settings_fields($this->plugin_name);
    do_settings_sections($this->plugin_name);
    echo '
        <script>
            function toggleQueryInput(e) {
                var el = document.getElementById("' . $this->plugin_name . '-cus_query");
                el.setAttribute("readonly", "");
                if (e.checked) {
                    el.removeAttribute("readonly");
                }
            }
        </script>

        ' . mfn_parse_heading('heading_settings', 'h2') . '
        
        <table class="mfn-settings-table">
            <tbody>
                <tr>
                    <th>
                        <p>
                            ' . mfn_parse_label('label_sync_url') . '
                            ' . mfn_parse_small('small_probably_feed_mfn_se') . '
                        </p>
                    </th>
                </tr>
                <tr>
                    <td>
                        <input required pattern="\S+" class="regular-text wide" name="' . $this->plugin_name . '[sync_url]" type="text" id="' . $this->plugin_name . '-sync_url" value="' . $sync_url . '" ' . $is_readonly . '>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <p>
                            ' . mfn_parse_label('label_entity_id') . '
                        </p>
                    </th>
                </tr>
                <tr>
                    <td>
                        <input required pattern="\S+" class="regular-text wide" name="' . $this->plugin_name . '[entity_id]" type="text" id="' . $this->plugin_name . '-entity_id" value="' . $entity_id . '" ' . $is_readonly . '>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <p>
                            ' . mfn_parse_label('label_cus_query') . '
                            ' . mfn_parse_small('small_know_what_im_doing') . '
                            <input type="checkbox" id="' . $this->plugin_name . '-cus_query_checkbox" name="' . $this->plugin_name . '[cus_query]" ' . checked($cus_query, "on", false) . ' value="on" onclick="toggleQueryInput(this)" ' . $is_readonly . '>
                        </p>
                    </th>
                </tr>
                <tr>
                    <td class="mfn-inline-td">
                        <input class="regular-text wide" name="' . $this->plugin_name . '[cus_query]" type="text" id="' . $this->plugin_name . '-cus_query" value="' . $cus_query . '" readonly>
                        <div class="mfn-tooltip-box">
                            <span class="mfn-info-icon-wrapper"><i class="dashicons dashicons-info-outline"></i></span>
                            <span class="mfn-tooltip-text">' . mfn_get_text('tooltip_cus_query') . '</span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <hr>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <p>
                            ' . mfn_parse_label('label_rewrite_settings', 'mfn-h2-label') . '
                        </p>
                    </th>
                </tr>
            </tbody>
        </table>
        <div id="mfn-rewrite-wrapper">
            <table class="mfn-settings-table">
                <tbody>
                    <tr>
                        <td>
                        ' . mfn_parse_label('label_rewrite_post_type_slug') . '
                        ' . mfn_parse_small('small_default_mfn_news') . '
                        </td>
                    </tr>
                    <tr>
                        <td class="mfn-inline-td">
                            <input type="text" class="regular-text wide" name="' . $this->plugin_name . '[rewrite_post_type][slug]' . '" id="' . $this->plugin_name . '-rewrite_post_type_slug"' . ' value="' . $slug . '" ' . $is_readonly . '>
                            <div class="mfn-tooltip-box">
                                <span class="mfn-info-icon-wrapper"><i class="dashicons dashicons-info-outline"></i></span>
                                <span class="mfn-tooltip-text">' . mfn_get_text('tooltip_rewrite_post_type_slug') . '</span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                        ' . mfn_parse_label('label_rewrite_taxonomy_slug') . '
                        ' . mfn_parse_small('small_mfn-news-tag') . '
                        </td>
                    </tr>
                    <tr>
                        <td class="mfn-inline-td">
                            <input type="text" class="regular-text wide" pattern="\S+" name="' . $this->plugin_name . '[taxonomy_rewrite_slug]' . '" id="' . $this->plugin_name . '-taxonomy_rewrite_slug"' . ' value="' . $taxonomy_rewrite_slug . '" ' . $is_readonly . ' placeholder="' . MFN_TAXONOMY_NAME . '">
                            <div class="mfn-tooltip-box">
                                <span class="mfn-info-icon-wrapper"><i class="dashicons dashicons-info-outline"></i></span>
                                <span class="mfn-tooltip-text">' . mfn_get_text('tooltip_taxonomy_rewrite_slug') . '</span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                        ' . mfn_parse_label('label_rewrite_post_type_archive_name') . '
                        ' . mfn_parse_small('small_default_mfn_news_items') . '
                        </td>
                    </tr>
                    <tr>
                        <td class="mfn-inline-td">
                            <input type="text" class="regular-text wide" name="' . $this->plugin_name . '[rewrite_post_type][archive-name]' . '" id="' . $this->plugin_name . '-rewrite_post_type_archive_name' . '" value="' . $archive_name . '" ' . $is_readonly . '>
                            <div class="mfn-tooltip-box">
                                <span class="mfn-info-icon-wrapper"><i class="dashicons dashicons-info-outline"></i></span>
                                <span class="mfn-tooltip-text">' . mfn_get_text('tooltip_rewrite_post_type_archive_name') . '</span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                        ' . mfn_parse_label('label_rewrite_post_type_singular_name') . '
                        ' . mfn_parse_small('small_default_mfn_news_item') . '
                        </td>
                    </tr>
                    <tr>
                        <td class="mfn-inline-td">
                            <input type="text" class="regular-text wide" name="' . $this->plugin_name . '[rewrite_post_type][singular-name]' . '" id="' . $this->plugin_name . '-rewrite_post_type_singular_name' . '" value="' . $singular_name . '" ' . $is_readonly . '>
                            <div class="mfn-tooltip-box">
                                <span class="mfn-info-icon-wrapper"><i class="dashicons dashicons-info-outline"></i></span>
                                <span class="mfn-tooltip-text">' . mfn_get_text('tooltip_rewrite_post_type_singular_name') . '</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <table class="mfn-settings-table">
            <tbody>
                <tr>
                    <td>
                        <p>
                            <input type="checkbox" id="' . $this->plugin_name . '-disable_archive" name="' . $this->plugin_name . '[disable_archive]" ' . checked($disable_archive, "on", false) . ' value="on" ' . $is_readonly . '>
                            ' . mfn_parse_label('label_disable_archive') . '
                            <br>
                            ' . mfn_parse_small('small_disable_archive_permalinks') . '
                        </p>
                    <td>
                </tr>
                <tr>
                    <td>
                        <hr>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <p>
                            ' . mfn_parse_label('label_language_settings', 'mfn-h2-label') . '
                        </p>
                    </th>
                </tr>
                <tr>
                ';
                echo '
                    <td>
                        <div class="mfn-radio-button-container' . $append_none_radio_classes . '">
                            <input type="radio" id="' . $this->plugin_name . '-language_plugin_none" name="' . $this->plugin_name . '[language_plugin]" value="none" ' . $checked_none . ' ' . $is_readonly . '>
                            ' . mfn_parse_label('label_language_plugin_none') . '
                        <div>
                    <td>
                </tr>
                <tr>
                    <td>
                        <div class="mfn-radio-button-container' . $append_wpml_detected_classes . '">
                            <input type="radio" id="' . $this->plugin_name . '-language_plugin_wpml" name="' . $this->plugin_name . '[language_plugin]" value="wpml" ' . checked('wpml', $options['language_plugin'] ?? false, false) . ' ' . $is_readonly . '>
                            ' . mfn_parse_label('label_language_plugin_wpml') . '
                            ' . mfn_parse_small('small_experimental') . '
                            ' . $wpml_detected . '
                            ' . $wpml_msg . '
                        <div>
                    <td>
                </tr>
                ';
                if ($use_wpml && !$has_wpml) {
                echo '
                    <tr>
                        <td>
                            <div class="mfn-radio-button-container mfn-warning-input do-fade">
                                <p><span class="dashicons dashicons-warning mfn-warning-icon do-fade"></span> This option can\'t be applied as the WPML plugin is currently not activated.</p>
                            <div>
                        <td>
                    </tr>
                ';
                }
                if ($use_wpml && $has_wpml && $language_usage_check->is_wpml_missing) {
                echo '
                    <tr>
                        <td>
                            <div class="mfn-radio-button-container mfn-warning-input do-fade">
                                <p><span class="dashicons dashicons-warning mfn-warning-icon do-fade"></span> ' . $language_usage_check->languageUsageMsgWpml . '</p>
                            <div>
                        <td>
                    </tr>
                ';
                }
                echo '
                <tr>
                    <td>
                        <div class="mfn-radio-button-container' . $append_pll_detected_classes . '">
                            <input type="radio" id="' . $this->plugin_name . '-language_plugin_pll" name="' . $this->plugin_name . '[language_plugin]" value="pll" ' . checked('pll', $options['language_plugin'] ?? false, false) . ' ' . $is_readonly . '>
                            ' . mfn_parse_label('label_language_plugin_pll') . '
                            ' . mfn_parse_small('small_experimental') . '
                            ' . $pll_detected . '
                            ' . $pll_msg . '
                            <div>
                    <td>
                </tr>';
                if ($use_pll && !$has_pll) {
                echo '
                    <tr>
                        <td>
                            <div class="mfn-radio-button-container mfn-warning-input do-fade">
                                <p><span class="dashicons dashicons-warning mfn-warning-icon do-fade"></span> This option can\'t be applied as the Polylang plugin is currently not activated.</p>
                            <div>
                        <td>
                    </tr>
                ';
                }
                if ($use_pll && $has_pll && $language_usage_check->is_pll_missing) {
                    echo '
                        <tr>
                            <td>
                                <div class="mfn-radio-button-container mfn-warning-input do-fade">
                                    <p><span class="dashicons dashicons-warning mfn-warning-icon do-fade"></span> ' . $language_usage_check->languageUsageMsgPll . '</p>
                                <div>
                            <td>
                        </tr>
                    ';
                }
        echo '
            </tbody>
        </table>
        <hr>
        <div id="mfn-advanced-settings-container" class="do-fade">
            <table class="mfn-settings-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <p>
                                ' . mfn_parse_label('label_advanced_settings', 'mfn-h2-label') . '
                            </p>
                        </th>
                    </tr>
                    <tr>
                        <td>
                            <p>
                                <input type="checkbox" id="' . $this->plugin_name . '-thumbnail_on" name="' . $this->plugin_name . '[thumbnail_on]" ' . checked($thumbnail_on, "on", false) . ' value="on" ' . $is_readonly . '>
                                ' . mfn_parse_label('label_thumbnail_on') . '
                                <br>
                                ' . mfn_parse_small('small_thumbnail_on_description') . '
                            </p>
                        <td>
                    </tr>
                    <tr>
                        <td>
                            <p>
                                <input type="checkbox" id="' . $this->plugin_name . '-thumbnail_allow_delete" name="' . $this->plugin_name . '[thumbnail_allow_delete]" ' . checked($thumbnail_allow_delete, "on", false) . ' value="on" ' . $is_readonly . '>
                                ' . mfn_parse_label('label_thumbnail_allow_delete') . '
                                <br>
                                ' . mfn_parse_small('small_thumbnail_allow_delete_description') . '
                            </p>
                        <td>
                    </tr>
                    <tr>
                    <td>
                        <p>
                            <input type="checkbox" id="' . $this->plugin_name . '-verify_signature" name="' . $this->plugin_name . '[verify_signature]" ' . checked($verify_signature, "on", false) . ' value="on" ' . $is_readonly . '>
                            ' . mfn_parse_label('label_verify_signature') . '
                            <br>
                            ' . mfn_parse_small('small_verify_signature_description') . '
                        </p>
                    <td>
                </tr>
                <tr>
                    <td>
                        <p>
                            <input type="checkbox" id="' . $this->plugin_name . '-reset_cache" name="' . $this->plugin_name . '[reset_cache]" ' . checked($reset_cache, "on", false) . ' value="on" ' . $is_readonly . '>
                            ' . mfn_parse_label('label_reset_cache') . '
                            <br>
                            ' .  mfn_parse_small('small_reset_cache_description') . '
                        </p>
                    <td>
                </tr>
                <tr>
                    <td>
                        <p>
                            <input type="checkbox" id="' . $this->plugin_name . '-enable_attachments" name="' . $this->plugin_name . '[enable_attachments]" ' . checked($enable_attachments, "on", false) . ' value="on" ' . $is_readonly . '>
                            ' . mfn_parse_label('label_enable_attachments') . '
                            <br>
                            ' . mfn_parse_small('small_enable_attachments_description') . '
                        </p>
                    <td>
                </tr>
                <tr>
                    <td>
                        <p>
                            <input type="checkbox" id="' . $this->plugin_name . '-taxonomy_disable_cus_prefix" name="' . $this->plugin_name . '[taxonomy_disable_cus_prefix]" ' . checked($taxonomy_disable_cus_prefix, "on", false) . ' value="on" ' . $is_readonly . '>
                            ' . mfn_parse_label('label_taxonomy_disable_cus_prefix') . '
                            <br>
                            ' . mfn_parse_small('small_taxonomy_disable_cus_prefix_description') . '
                        </p>
                    <td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="mfn-save-buttons-container">
            <table>
                <tbody>
                    <tr>
                        <td>
                        ' . get_submit_button('Save', 'primary','save-submit-btn') . '
                        </td>
                        <td>';
                        $disabled_unlock_btn = !isset($options['entity_id']) ? 'disabled' : '';
                        echo '
                            <button class="button mfn-unlock-settings-button" id="unlock-settings-btn" ' . $disabled_unlock_btn . '>
                                <span class="dashicons dashicons-lock mfn-unlock-icon"></span>
                                <span>
                                ' . mfn_get_text('button_unlock') . '
                                </span>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </form>
    <p>' . mfn_parse_heading('heading_danger_zone', 'h3', 'mfn-danger-zone-heading') . '</p>
    <table class="mfn-danger-zone-table">
        <tbody>
            <th>' . mfn_get_text('heading_actions') . '</th>
            <tr>
                <td>
                    <button class="button mfn-danger-zone-btn" id="mfn-clear-settings-btn">
                        <span class="dashicons dashicons-admin-generic mfn-clear-settings-icon"></span>
                        ' . mfn_get_text('button_clear_mfn_settings') . '
                    </button>
                </td>
                <td>
                    <div class="mfn-tooltip-box mfn-do-fade-top">
                        <span class="mfn-info-icon-wrapper"><i class="dashicons dashicons-info-outline"></i></span>
                        <span class="mfn-tooltip-text">' . mfn_get_text('tooltip_clear_mfn_settings') . '</span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <button class="button mfn-danger-zone-btn" id="mfn-delete-tags-btn">
                        <span class="dashicons dashicons-tag mfn-clear-tags-icon"></span>
                        ' . mfn_get_text('button_delete_mfn_tags') . '
                    </button>
                </td>
                <td>
                    <div class="mfn-tooltip-box mfn-do-fade-top">
                        <span class="mfn-info-icon-wrapper"><i class="dashicons dashicons-info-outline"></i></span>
                        <span class="mfn-tooltip-text">' . mfn_get_text('tooltip_delete_mfn_tags') . '</span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <button class="button mfn-danger-zone-btn" id="mfn-delete-posts-btn">
                        <span class="dashicons dashicons-admin-post mfn-clear-posts-icon"></span>
                        ' . mfn_get_text('button_delete_mfn_posts') . '
                    </button>
                </td>
                <td>
                    <div class="mfn-tooltip-box mfn-do-fade-top">
                        <span class="mfn-info-icon-wrapper"><i class="dashicons dashicons-info-outline"></i></span>
                        <span class="mfn-tooltip-text">' . mfn_get_text('tooltip_delete_mfn_posts') . '</span>
                    </div>
                </td>

            </tr>
        </tbody>
    </table>
    <div id="mfn-danger-zone-status"></div>
</div>
<div class="mcol-1-2">
    ' . mfn_parse_heading('heading_subscription', 'h2') . '
    <div id="mfn-status-container" class="do-fade"></div>
    ' . mfn_parse_heading('heading_actions', 'h3') . '
    <div class="mfn-action-heading-wrapper">
        <h4 class="mfn-h4">' . mfn_get_text('heading_sync_feed') . '</h4>
        <div class="mfn-tooltip-box">
            <span class="mfn-info-icon-wrapper"><i class="dashicons dashicons-info-outline"></i></span>
            <span class="mfn-tooltip-text">' . mfn_get_text('tooltip_sync') . '</span>
        </div>
    </div>
    <span class="mfn-action-buttons-container">
        <button id="mfn-sync-latest" class="button mfn-button">
        <span class="dashicons dashicons-image-rotate mfn-sync-icon"></span>
            ' . mfn_get_text('button_sync_latest') . '
        </button>
        <button class="button-primary mfn-button" id="mfn-sync-all">
        <span class="dashicons dashicons-image-rotate mfn-sync-icon"></span>
            ' . mfn_get_text('button_sync_all') . '
        </button>
    </span>
    <span id="mfn-sync-status"></span>
    <div>
        <div class="mfn-action-heading-wrapper">
            <h4 class="mfn-h4">' . mfn_get_text('heading_sync_taxonomy') . '</h4>
            <div class="mfn-tooltip-box">
                <span class="mfn-info-icon-wrapper"><i class="dashicons dashicons-info-outline"></i></span>
                <span class="mfn-tooltip-text">' . mfn_get_text('tooltip_sync_taxonomy') . '</span>
            </div>
        </div>
        <div class="mfn-row-container">
            <div class="mfn-buttons-container">
                <button class="button mfn-button" id="mfn-sync-tax">
                    <span class="dashicons dashicons-image-rotate mfn-sync-icon"></span>
                    ' . mfn_get_text('button_sync_taxonomy') . '
                </button>
            </div>
            <span id="mfn-sync-tax-status"></span>
        </div>
    </div>
    <div class="mfn-action-heading-wrapper">
        <h4 class="mfn-h4">' . mfn_get_text('heading_subscribe') . '</h4>
        <div class="mfn-tooltip-box">
            <span class="mfn-info-icon-wrapper"><i class="dashicons dashicons-info-outline"></i></span>
            <span class="mfn-tooltip-text">' . mfn_get_text('tooltip_subscribe') . '</span>
        </div>
    </div>
    <div class="mfn-row-container">
        <div class="mfn-buttons-container">';
        $sub_disabled = $subscribe_button_disabled ? 'disabled' : '';
        echo '
            <button class="button-primary mfn-button" id="mfn-sub-button" ' . $sub_disabled . '>
                <span class="dashicons dashicons-admin-links mfn-subscribe-icon"></span>
                ' . mfn_get_text('button_subscribe') . '
            </button>';
        $unsub_disabled = $unsubscribe_button_disabled ? 'disabled' : '';
        echo '
            <button class="button mfn-button" id="mfn-unsub-button" ' . $unsub_disabled . '>
                <span class="dashicons dashicons-editor-unlink mfn-subscribe-icon"></span>
                ' . mfn_get_text('button_unsubscribe') . '
            </button>
        </div>
        <span id="mfn-subscription-status"></span>
    </div>
</div>
</div>
';