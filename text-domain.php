<?php

function mfn_text_domain($text): string
{
    return __($text, MFN_PLUGIN_NAME);
}

function mfn_get_text($key)
{
    $post_type_slug = MFN_POST_TYPE;
    if (isset(get_option(MFN_PLUGIN_NAME)['rewrite_post_type'])) {
        if (isset(unserialize(get_option(MFN_PLUGIN_NAME)['rewrite_post_type'])['slug'])) {
            $post_type_slug = unserialize(get_option(MFN_PLUGIN_NAME)['rewrite_post_type'])['slug'];
        }
    }

    $t = [
        /* headings */
        'heading_settings' => mfn_text_domain('Settings'),
        'heading_danger_zone' => mfn_text_domain('Danger Zone'),
        'heading_subscription' => mfn_text_domain('Subscription'),
        'heading_actions' => mfn_text_domain('Actions'),
        'heading_sync_feed' => mfn_text_domain('Sync Feed'),
        'heading_sync_taxonomy' => mfn_text_domain('Sync Taxonomy'),
        'heading_subscribe' => mfn_text_domain('Subscribe'),
        /* labels */
        'label_sync_url'  => mfn_text_domain('Sync URL'),
        'label_entity_id'  => mfn_text_domain('Feed ID'),
        'label_cus_query'  => mfn_text_domain('Custom Query'),
        'label_rewrite_settings'  => mfn_text_domain('Rewrite Settings'),
        'label_language_settings' => mfn_text_domain('Language Settings'),
        'label_advanced_settings' => mfn_text_domain('Advanced Settings'),
        'label_custom_post_type_slug'  => mfn_text_domain('Custom Post Type URL'),
        'label_custom_archive_name'  => mfn_text_domain('Custom Archive Name'),
        'label_custom_singular_name'  => mfn_text_domain('Custom Singular Name'),
        'label_disable_archive'  => mfn_text_domain('Disable Archive'),
        'label_rewrite_post_type_slug' => mfn_text_domain('Custom Post Type Slug'),
        'label_rewrite_taxonomy_slug' => mfn_text_domain('Custom Taxonomy Slug'),
        'label_rewrite_post_type_archive_name' => mfn_text_domain('Custom Archive Name'),
        'label_rewrite_post_type_singular_name' => mfn_text_domain('Custom Singular Name'),
        'label_language_plugin_none' => mfn_text_domain('No language plugin'),
        'label_language_plugin_wpml' => mfn_text_domain('Use WPML'),
        'label_language_plugin_pll' => mfn_text_domain('Use Polylang'),
        'label_thumbnail_on' => mfn_text_domain('Thumbnail Support (Requires Wordpress 4.8)'),
        'label_thumbnail_allow_delete' => mfn_text_domain('Thumbnail Support: Delete images with posts'),
        'label_verify_signature' => mfn_text_domain('Verify Signature'),
        'label_reset_cache' => mfn_text_domain('Reset Cache'),
        'label_enable_attachments' => mfn_text_domain('Enable Attachments Widget'),
        'label_taxonomy_disable_cus_prefix' => mfn_text_domain('Drop Custom Tag Prefix'),
        'label_meta_box_news_item_status' => mfn_text_domain(MFN_SINGULAR_NAME . ' Status'),
        /* small */
        'small_default_mfn_news' => mfn_text_domain('(Default: ' . MFN_POST_TYPE . ')'),
        'small_mfn-news-tag' => mfn_text_domain('(Default: ' . MFN_TAXONOMY_NAME . ')'),
        'small_default_mfn_news_item' => mfn_text_domain('(Default: ' . MFN_SINGULAR_NAME . ')'),
        'small_default_mfn_news_items' => mfn_text_domain('(Default: ' . MFN_ARCHIVE_NAME . ')'),
        'small_probably_feed_mfn_se'  => mfn_text_domain('(Probably: https://feed.mfn.se/v1)'),
        'small_know_what_im_doing'  => mfn_text_domain('(I know what I\'m doing)'),
        'small_experimental'  => mfn_text_domain('(Experimental)'),
        'small_disable_archive_permalinks' => mfn_text_domain(
            '(Makes the news archive unreachable - eg. ' .  rtrim(get_home_url(), '/') . '/' . $post_type_slug . '. You might need to update <a href="' . get_home_url() . '/wp-admin/options-permalink.php">permalinks</a> after saving to activate this setting.)'
        ),
        'small_thumbnail_on_description' => mfn_text_domain(
            '(Experimental: Upload image attachments to the Media Library and set post thumbnail. Warning: Makes insertion/syncing slower and requires a lot of disk space)<br>(Can make integration into certain themes easier)'
        ),
        'small_thumbnail_allow_delete_description' => mfn_text_domain(
            '("Delete all MFN posts" will also delete all attached images from the Media Library) <strong>(Recommended)</strong>'
        ),
        'small_verify_signature_description' => mfn_text_domain(
            '(Cryptographically ensures that mfn.se is indeed the sender of the story) <strong>(Strongly recommended)</strong>'
        ),
        'small_reset_cache_description' => mfn_text_domain(
            '(On every new item insert, if checked, this will reset the db cache) <strong>(Enabled by default)</strong>'
        ),
        'small_enable_attachments_description' => mfn_text_domain(
            '(If enabled, our plugin will handle the listing of attachments and will bypass the default mfn-attachment footer) <strong>(Enabled by default)</strong>'
        ),
        'small_taxonomy_disable_cus_prefix_description' => mfn_text_domain(
            '(If enabled, when custom tags are inserted their slug will not have the "mfn-cus-" prefix)'
        ),
        /* buttons */
        'button_unlock' => mfn_text_domain('Unlock'),
        'button_clear_mfn_settings' => mfn_text_domain('Clear all MFN settings'),
        'button_delete_mfn_tags' => mfn_text_domain('Delete all MFN tags'),
        'button_delete_mfn_posts' => mfn_text_domain('Delete all MFN posts'),
        'button_sync_latest' => mfn_text_domain('Sync Latest'),
        'button_sync_all' => mfn_text_domain('Sync All'),
        'button_sync_taxonomy' => mfn_text_domain('Sync Taxonomy'),
        'button_subscribe' => mfn_text_domain('Subscribe'),
        'button_unsubscribe' => mfn_text_domain('Unsubscribe'),
        'button_restore' => mfn_text_domain('Restore'),
        /* status */
        'status_subscription_id' => mfn_text_domain('Subscription Id'),
        'status_post_hook_secret' => mfn_text_domain('Post Hook Secret'),
        'status_post_hook_name' => mfn_text_domain('Post Hook Name'),
        'status_sync_url' => mfn_text_domain('Sync URL'),
        'status_feed_id' => mfn_text_domain('Feed ID'),
        'status_hub_url' => mfn_text_domain('Hub URL'),
        'status_plugin_url' => mfn_text_domain('Plugin URL'),
        'status_not_subscribed' => mfn_text_domain('(Not subscribed)'),
        /* tooltips */
        'tooltip_cus_query' => mfn_text_domain(
            'Add a query parameter to the request, mostly used for specific filtering purposes.'
        ),
        'tooltip_rewrite_post_type_slug' => mfn_text_domain(
            'Rewrite the slug (' . MFN_POST_TYPE . ') in the URL - eg. "press-releases".'
        ),
        'tooltip_taxonomy_rewrite_slug' => mfn_text_domain(
            'Rewrite the taxonomy slug (' . MFN_TAXONOMY_NAME . ') in the URL'
        ),
        'tooltip_rewrite_post_type_archive_name' => mfn_text_domain(
            'Set a custom name of the news archive page  - eg. "Press Releases".'
        ),
        'tooltip_rewrite_post_type_singular_name' => mfn_text_domain(
            'Set a custom name of a single news item - eg. "Press release".'
        ),
        'tooltip_clear_mfn_settings' => mfn_text_domain(
            'Clears all the MFN settings from the options.'
        ),
        'tooltip_delete_mfn_tags' => mfn_text_domain(
            'Deletes all the MFN tags.'
        ),
        'tooltip_delete_mfn_posts' => mfn_text_domain(
            'Deletes all MFN posts except manually added ones.'
        ),
        'tooltip_sync' => mfn_text_domain(
            'Imports/upserts the 10 latest (or all) press releases from MFN.se and creates a press releases feed in the Wordpress database.'
        ),
        'tooltip_sync_taxonomy' => mfn_text_domain(
            'Imports the MFN tags taxonomy from MFN.se and assigns their language translations.'
        ),
        'tooltip_subscribe' => mfn_text_domain(
            'Subscribing establishes a posthook/webhook on MFN.se that enables new press releases to be automatically injected into the press releases feed.'
        ),

        'tooltip_restore_item_info' => mfn_text_domain(
            'Clicking \'Restore\' will re-sync this item from the MFN feed and restore it to its original state.'
        ),
        /* alerts */
        'alert_subscribe_warning' => mfn_text_domain('You must subscribe in order to automatically retrieve releases from MFN.'),
        'alert_restore_warning' => mfn_text_domain('This will re-sync the item from MFN which will restore it to its initial state. All local changes will be lost.'),

        /* modal */
        'modal_restore_body' => mfn_text_domain('Are you sure that you want to restore this ' . MFN_SINGULAR_NAME . '?'),
        'modal_restore_heading' => mfn_text_domain('Restore ' . MFN_SINGULAR_NAME),
        'modal_explanation_delete_posts' => mfn_text_domain('Clicking delete will remove all MFN posts (which are listed under ' . MFN_ARCHIVE_NAME . ' but will keep manually added or modified posts (Local).'),
        'modal_explanation_delete_tags' => mfn_text_domain('Clicking delete will remove all MFN tags. This might be a good idea if you eg. imported using different language plugins and need to clear out the tags. You can then use \'Sync All\' which will re-import all tags that are associated with MFN posts.'),
        'modal_explanation_clear_settings' => mfn_text_domain('Clearing will delete all the saved settings from options. Only do this if you want to reset to the initial state of a clean installation.'),

        /* placeholders */
        'placeholder_clear_mfn_settings' => mfn_text_domain('write \'clear\' to confirm'),
        'placeholder_delete_mfn_tags' => mfn_text_domain('write \'delete\' to confirm'),
        'placeholder_delete_mfn_posts' => mfn_text_domain('write \'delete\' to confirm'),
        /* actions */
        'action_include_modified_posts' => mfn_text_domain('<b>Include modified posts</b><br> <b style="color: #DC3232;">(Warning)</b>  This will delete <u>all posts</u> including the ones that have been modified as well as those in <i>Trash</i>. Manually added or modified posts (Local) will however always be skipped.</b>'),
        /* other text */
        'text_in' => mfn_text_domain('in'),
        'text_and' => mfn_text_domain('and'),
        'text_pll_settings_section' => mfn_text_domain('Custom post types and Taxonomies'),
        'text_wpml_settings_section' => mfn_text_domain('Post Types Translation and Taxonomies Translation'),
        'text_language_usage_msg' => mfn_text_domain('Please select it to ensure that syncing properly maps the languages for news and tags.'),
        'text_missing' => mfn_text_domain('Missing'),
        'text_mfn_post_type' => mfn_text_domain('MFN post type'),
        'text_mfn_taxonomy' => mfn_text_domain('MFN taxonomy'),
        'text_pll_settings' => mfn_text_domain('Polylang settings'),
        'text_wpml_settings' => mfn_text_domain('WPML settings'),
        'text_plugin_detected' => mfn_text_domain('(Plugin was detected)'),
        'text_modified' => mfn_text_domain('(Modified)'),
        'text_trash' => mfn_text_domain('(Modified/Trash)'),
        'text_local' => mfn_text_domain('(Local)'),
        'text_mfn_news_item_status_local' => mfn_text_domain('<span style="color: limegreen;"><strong>Local:</strong></span> This news item was added manually. (It will be preserved if you use the \'Delete All MFN Posts\' option in the MFN Feed settings).'),
        'text_mfn_news_item_status_pure' => mfn_text_domain('<span style="color: limegreen;"><strong>Unmodified:</strong></span> This news item hasn\'t been updated/changed.'),
        'text_mfn_news_item_status_unpure' => mfn_text_domain('<span style="color: #F56E28;"><strong>Modified:</strong></span> This news item has been updated/changed. You can restore it by clicking the button below, and this particular news item will be re-synced from the MFN feed.'),
    ];

    return $t[$key];
}

function mfn_parse_label($key, $class = ''): string
{
    $id = str_replace('label_', '', $key);
    $append_class = '';
    if ($class !== '') {
        $append_class = ' class="' . $class  . '"';
    }
    return '<label' . $append_class . ' for="' . MFN_PLUGIN_NAME . '-' . $id . '">' . mfn_get_text($key) . '</label>
            <legend class="screen-reader-text">' . mfn_get_text($key). '</legend>';
}

function mfn_parse_small($key): string
{
    return '<small>' . mfn_get_text($key) . '</small>';
}

function mfn_parse_heading($key, $type, $class = ''): string
{
    $append_class = '';
    if ($class !== '') {
        $append_class = ' class="' . $class  . '"';
    }
    return '<'. $type . $append_class .'>' . mfn_get_text($key) . '</'. $type .'>';
}

function mfn_parse_language_msg($pluginName): string
{
    $message = 'We could detect ' . $pluginName . ' plugin but it\'s not selected.';
    $message .= ' - Please select the <code>Use ' . $pluginName . '</code> option above and click save to activate translations for MFN posts and tags.';
    $message .= '<div style="display: flex; align-items: flex-end;">';
    $message .= '<span style="margin-right: 13px;"><button class="button mfn-unlock-settings-button mfn-do-fade" id="mfn-unlock-settings-btn-language"><span class="dashicons dashicons-lock mfn-unlock-icon"></span>' . mfn_get_text('button_unlock') . '</button></span>';
    $message .= '</span><input type="submit" name="save-submit-btn" id="save-submit-btn" class="button button-primary" value="Save" disabled></span>';
    $message .= '</div>';
    return $message;
}
