<?php

function mfn_post_is_dirty($post_id): bool
{
    return get_post_meta($post_id, MFN_POST_TYPE . "_is_dirty", true);
}

function mfn_post_is_local($post_id) {
    $post_meta = get_post_meta($post_id);
    return $post_meta[MFN_POST_TYPE . '_news_id'] ?? null;
}

function mfn_fetch_tags_status(): array
{

    $terms = get_terms( array(
        'taxonomy' => MFN_TAXONOMY_NAME,
        'hide_empty' => false,
        'lang' => ''
    ));

    return [sizeof($terms)];
}

function mfn_fetch_posts_status(): array
{
    $all_posts = get_posts(
        array(
            'post_type' => MFN_POST_TYPE,
            'lang'     => '',
            'numberposts' => '-1',
            'post_status' => 'Trash',
        )
    );

    $i = 0;
    $num_modified = 0;
    $num_trash = 0;

    foreach ($all_posts as $each_post) {
        if ($each_post->post_type == MFN_POST_TYPE) {
            if (mfn_post_is_local($each_post->ID) !== null) {
                if ($each_post->post_status == 'trash') {
                    $num_trash++;
                }
                if (mfn_post_is_dirty($each_post->ID)) {
                    if ($each_post->post_status !== 'trash') {
                        $num_modified++;
                    }
                }
                $i++;
            }
        }
    }

    return [$i, $num_modified, $num_trash];
}

function mfn_fetch_hub_url()
{
    $sync_url = get_option(MFN_PLUGIN_NAME)['sync_url'] ?? '';
    $dev_sync_url = 'https://widget.datablocks.se/proxy/mfn-dev';

    $hub_url = 'https://feed.mfn.se/v1';

    $compare_feed_url = array(
        'https://feed.mfn.se/v1',
        'https://feed.mfn.modfin.se/v1',
    );

    $compare_mfn_url = array(
        'https://mfn.se',
        'https://mfn.modfin.se',
        $dev_sync_url
    );

    if (in_array($sync_url, $compare_feed_url)) {
        if ($sync_url === $compare_feed_url[0] || $sync_url === $compare_feed_url[1]) {
            $hub_url = $sync_url;
        }
    } else if (in_array($sync_url, $compare_mfn_url)) {
        $hub_url = 'https://hub.mfn.se';
        if ($sync_url === $compare_mfn_url[1] || $sync_url === $dev_sync_url) {
            $hub_url = 'https://hub.mfn.modfin.se';
        }
    }

    return $hub_url;
}

function mfn_parse_language_usage_msg($check)
{
    $append = ' ' . mfn_get_text('text_language_usage_msg');
    $prepend = 'Missing ';
    $and =  ' ' . mfn_get_text('text_and') . ' ';
    $post_type = 'MFN post type "' . MFN_POST_TYPE . '"';
    $taxonomy_name = 'MFN taxonomy "' . MFN_TAXONOMY_NAME . '"';

    // pll
    if ($check->is_pll_missing) {
        $msg = $prepend;
        $pll_text = ' ' . mfn_get_text('text_in') . ' <i>' . mfn_get_text('text_pll_settings_section') . '</i> ' . mfn_get_text('text_in') . ' <a href="' . get_admin_url() . 'admin.php?page=mlang_settings' . '">' . mfn_get_text('text_pll_settings') . '</a>.';

        if (!$check->has_pll_mfn_post_type) {
            $msg .= $post_type;
        }

        $add = !$check->has_pll_mfn_post_type ? $and : '';

        if (!$check->has_pll_mfn_taxonomies) {
            $msg .= $add . $taxonomy_name;
        }

        $msg_pll = $msg . $pll_text . $append;
        $check->languageUsageMsgPll = $msg_pll;
    }

    // wpml
    if ($check->is_wpml_missing) {
        $msg = $prepend;
        $wpml_text = ' ' . mfn_get_text('text_in') . ' <i>' . mfn_get_text('text_wpml_settings_section') . '</i> ' . mfn_get_text('text_in') . ' <a href="' . get_admin_url() . 'admin.php?page=sitepress%2Fmenu%2Ftranslation-options.php' . '">' . mfn_get_text('text_wpml_settings') . '</a>.';

        if (!$check->has_wpml_mfn_post_type) {
            $msg .= $post_type;
        }

        $add = !$check->has_wpml_mfn_post_type ? $and : '';

        if (!$check->has_wpml_mfn_taxonomies) {
            $msg .= $add . $taxonomy_name;
        }

        $msg_wpml = $msg . $wpml_text . $append;
        $check->languageUsageMsgWpml = $msg_wpml;
    }
}

function mfn_language_plugin_usage_check(): stdClass
{
    $c = new stdClass();
    $c->is_pll_missing = false;
    $c->is_wpml_missing = false;
    $c->has_pll_mfn_post_type = false;
    $c->has_pll_mfn_taxonomies = false;
    $c->has_wpml_mfn_post_type = false;
    $c->has_wpml_mfn_taxonomies = false;
    $c->languageUsageMsgPll = '';
    $c->languageUsageMsgWpml = '';

    if (get_option('polylang')) {
        $pll_post_types = get_option('polylang')['post_types'];
        if (isset($pll_post_types)) {
            foreach ($pll_post_types as $v) {
                if ($v === MFN_POST_TYPE) {
                    $c->has_pll_mfn_post_type = true;
                }
            }
        }
        $pll_taxonomies = get_option('polylang')['taxonomies'];
        if (isset($pll_taxonomies)) {
            foreach ($pll_taxonomies as $v) {
                if ($v === MFN_TAXONOMY_NAME) {
                    $c->has_pll_mfn_taxonomies = true;
                }
            }
        }
    }

    $wpml_options = get_option('icl_sitepress_settings');

    if (isset($wpml_options)) {
        $wpml_mfn_post_type = $wpml_options['custom_posts_sync_option'][MFN_POST_TYPE] ?? 0;
        $wpml_mfn_taxonomies = $wpml_options['taxonomies_sync_option'][MFN_TAXONOMY_NAME] ?? 0;

        if ($wpml_mfn_post_type > 0)  {
            $c->has_wpml_mfn_post_type = true;
        }
        if ($wpml_mfn_taxonomies > 0)  {
            $c->has_wpml_mfn_taxonomies = true;
        }
    }

    if (!$c->has_pll_mfn_post_type || !$c->has_pll_mfn_taxonomies) {
        $c->is_pll_missing = true;
    }
    if (!$c->has_wpml_mfn_post_type || !$c->has_wpml_mfn_taxonomies) {
        $c->is_wpml_missing = true;
    }

    mfn_parse_language_usage_msg($c);
    return $c;
}

function mfn_language_plugin_check($use_pll, $has_pll, $use_wpml, $has_wpml): stdClass
{
    $check = new stdClass();
    $check->languageMsg = '';
    $check->detected_pll = false;
    $check->detected_wpml = false;
    $check->plugin_detected = false;

    if (($has_pll && !$use_pll) || ($has_wpml && !$use_wpml)) {
        $check->plugin_detected = true;
    }
    if ($has_pll && !$use_pll) {
        $check->detected_pll = true;
        $check->languageMsg = mfn_parse_language_msg('Polylang');
    }
    if ($has_wpml && !$use_wpml) {
        $check->detected_wpml = true;
        $check->languageMsg = mfn_parse_language_msg('WPML');
    }
    return $check;
}

function mfn_starts_with($haystack, $needle): bool
{
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}

function mfn_create_tags($item): array
{
    $tags = $item->properties->tags ?? array();
    $lang = $item->properties->lang ?? 'xx';
    $type = $item->properties->type ?? 'ir';

    $newtag = array();

    $slug_prefix = (MFN_TAG_PREFIX !== '' && MFN_TAG_PREFIX !== null ? MFN_TAG_PREFIX . '-' : '');

    $newtag[] = MFN_TAG_PREFIX;
    $newtag[] = $slug_prefix . 'lang-' . $lang;
    $newtag[] = $slug_prefix . 'type-' . $type;

    foreach ($tags as $tag) {
        if (mfn_starts_with($tag, ':correction')) {
            $newtag[] = $slug_prefix . '-correction';
            continue;
        }

        $tag = str_replace('sub:', '', $tag);
        $tag = trim($tag, ' :');
        $tag = str_replace(':', '-', $tag);
        $tag = $slug_prefix . $tag;
        $newtag[] = $tag;
    }

    $options = get_option(MFN_PLUGIN_NAME);
    $use_wpml = isset($options['language_plugin']) && $options['language_plugin'] == 'wpml';
    $use_pll = isset($options['language_plugin']) && $options['language_plugin'] == 'pll';
    if ($use_wpml && $lang != 'en') {
        foreach ($newtag as $i => $t) {
            $newtag[$i] = $t . "_" . $lang;
        }
    }
    if ($use_pll && $lang != 'en') {
        if (function_exists('pll_languages_list')) {
            $pllLangMapping = array();
            foreach (pll_languages_list(array('fields' => array())) as $pll_lang) {
                $l = explode('_', $pll_lang->locale)[0];
                $pllLangMapping[$l] = $pll_lang->slug;
            };
            foreach ($newtag as $i => $t) {

                if (!isset($pllLangMapping[$lang])) {
                    continue;
                }

                $newtag[$i] = $t . "_" . $pllLangMapping[$lang];
            }
        }
    }
    return $newtag;
}

function mfn_get_storage_url()
{
    $ops = get_option(MFN_PLUGIN_NAME);
    return isset($ops['sync_url'])
        ? ((strpos($ops['sync_url'], 'https://feed.mfn.') === 0)
            ? str_replace('//feed.mfn', '//storage.mfn', str_replace('/v1', '', $ops['sync_url']))
            : str_replace('//mfn', '//storage.mfn', $ops['sync_url']))
        : null;
}

function mfn_get_proxied_url($url, $vanityFileName): string
{
    $storageUrl = mfn_get_storage_url();

    return $storageUrl !== null && $storageUrl !== '' && (strpos($url, $storageUrl) !== 0)
        ? "$storageUrl/proxy/$vanityFileName?url=" . urlencode($url) . "&size=w-2560"
        : $url . "?size=w-2560";
}

function mfn_list_post_attachments(): string
{
    $attachments_content = '<div class="mfn-attachments-container">';
    foreach (mfn_fetch_post_attachments() as $attachment) {
        $icon_type_slug = empty($attachment->content_type) ? 'admin-links' : 'media-default';

        list ($url, $preview_url) = mfn_get_proxied_preview_url($attachment->url, $attachment->file_title, $attachment->content_type);
        if (empty($preview_url)) {
            $icon = '<span class="mfn-attachment-icon"><span class="dash dashicons dashicons-' . $icon_type_slug . '"></span></span>';
        } else {
            $icon = '<span class="mfn-attachment-icon"><img src="' . $preview_url. '"></span>';
        }
        $link = '<a class="mfn-attachment-link" href="' . $url . '">' . $icon . $attachment->file_title . '</a>';
        $attachments_content .= '<div class="mfn-attachment">' . $link . '</div>';
    }
    $attachments_content .= '</div>';
    return $attachments_content;
}

function mfn_get_proxied_preview_url($url, $file_title, $content_type): array
{
    $mime_to_ext = array(
        "application/pdf" => "pdf",
        "image/jpg" => "jpg",
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/tiff" => "tiff",
        'audio/mpeg' => 'mp3',
        'audio/mpeg3' => 'mp3',
        'audio/mp3' => 'mp3',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'application/x-zip' => 'zip',
        'application/zip' => 'zip',
        'application/x-zip-compressed' => 'zip',
        'application/s-compressed' => 'zip',
        'multipart/x-zip' => 'zip',
        'video/mp4' => 'mp4',
        'video/mpeg' => 'mpeg',
        'video/quicktime' => 'mov',
    );

    $vanity_part = '';
    if (isset($mime_to_ext[$content_type])) {
        $ext = $mime_to_ext[$content_type];
        $vanity_part = '/' . sanitize_title($file_title) . "." . $ext;
    }

    $storageUrl = mfn_get_storage_url();

    if ($storageUrl === null || $storageUrl === '' || $content_type === null || $content_type === '') {
        return array($url, '');
    }

    $isStorageAttachment = strpos($url, $storageUrl) === 0;

    $outUrl = $isStorageAttachment
        ? $url
        : "$storageUrl/proxy$vanity_part?url=" . urlencode($url);

    $previewUrl = '';

    if ($content_type === 'application/pdf') {
        $previewUrl = $isStorageAttachment
            ? $url . '?type=jpg'
            : $outUrl . '&type=jpg';
    }
    if (strpos($content_type, 'image/') === 0) {
        $previewUrl = $isStorageAttachment
            ? $url . '?size=w-512'
            : $outUrl . '&size=w-512';
    }

    return array($outUrl, $previewUrl);
}

function mfn_fetch_post_attachments(): array
{
    $attachments = array();
    foreach (get_post_meta(get_the_ID(), MFN_POST_TYPE . '_attachment_data') as $data)
    {
        $d = json_decode($data);
        array_push($attachments, $d);
    }
    return $attachments;
}

function mfn_remove_regular_attachment_footer(): string
{
    return '<script>
                Array.prototype.slice.call(document.querySelectorAll(".mfn-footer.mfn-attachment")).forEach(function (el) { el.remove() });
            </script>
        ';
}

$upsert_thumbnails_dependencies_included = false;

function mfn_upsert_thumbnails($post_id, $attachments)
{
    global $wp_version;
    if (!version_compare($wp_version, '4.8', '>=')) {
        return;
    }

    $image_attachments = array();

    foreach ($attachments as $a) {
        if (strpos($a->content_type, 'image/') !== 0) {
            continue;
        }

        $mime_to_ext = array(
            "image/jpg" => "jpeg",
            "image/jpeg" => "jpeg",
            "image/png" => "png",
            "image/tiff" => "tiff"
        );

        $ext = $mime_to_ext[$a->content_type];
        if (!isset($ext)) {
            $ext = "jpeg";
        }
        $filename = sanitize_title($a->file_title) . "." . $ext;

        $image = clone($a);
        $image->proxied_url = mfn_get_proxied_url($a->url, $filename);

        $image_attachments[] = $image;
    }

    if (count($image_attachments) === 0) {
        return;
    }

    global $upsert_thumbnails_dependencies_included;
    if (!$upsert_thumbnails_dependencies_included) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $upsert_thumbnails_dependencies_included = true;
    }

    $existing_attachments = get_posts(array(
        'post_type' => 'attachment',
        'posts_per_page' => -1,
        'post_parent' => $post_id,
    ));

    $existing_urls = array();
    foreach ($existing_attachments as $a) {
        $u = get_post_meta($a->ID, MFN_POST_TYPE . "_attachment_url", true);
        if (isset($u)) {
            $existing_urls[$u] = $a->ID;
        }
    }

    $thumbnail_attachment_id = 0;

    foreach ($image_attachments as $a) {
        $attachment_id = 0;
        if (isset($existing_urls[$a->url])) {
            $attachment_id = $existing_urls[$a->url];
        }

        if ($attachment_id === 0) {
            $file_title = $a->file_title;
            $file_title = apply_filters( 'mfn_attachment_file_title', $file_title, $post_id);
            set_error_handler( '__return_true', E_WARNING);
            $attachment_id = media_sideload_image($a->proxied_url, $post_id, $file_title, 'id');
            restore_error_handler();
            if (is_wp_error($attachment_id)) {
                continue;
            }
            add_post_meta($attachment_id, MFN_POST_TYPE . "_attachment_url", $a->url);
            add_post_meta($attachment_id, MFN_POST_TYPE . "_attachment_proxied_url", $a->proxied_url);
        }
        if ($thumbnail_attachment_id === 0) {
            $thumbnail_attachment_id = $attachment_id;
        }

        if (isset($a->tags) && in_array('image:primary', $a->tags)) {
            $thumbnail_attachment_id = $attachment_id;
        }
    }
    if ($thumbnail_attachment_id !== 0) {
        set_post_thumbnail($post_id, $thumbnail_attachment_id);
    }
}

function mfn_upsert_attachments($post_id, $attachments)
{
    delete_post_meta($post_id,  MFN_POST_TYPE . "_attachment_link");
    delete_post_meta($post_id,  MFN_POST_TYPE . "_attachment_data");

    foreach ($attachments as $i => $attachment) {
        $title = $attachment->file_title;
        $content_type = $attachment->content_type;
        $url = $attachment->url;

        $attachment_link = "<a href='$url' content='$content_type' target='_blank' rel='noopener'>$title</a>";
        $attachment_data = wp_slash(json_encode($attachment, JSON_UNESCAPED_UNICODE));

        add_post_meta($post_id, MFN_POST_TYPE . "_attachment_link", $attachment_link);
        add_post_meta($post_id, MFN_POST_TYPE . "_attachment_data", $attachment_data);
    }
    if (isset(get_option(MFN_PLUGIN_NAME)['thumbnail_on'])) {
        mfn_upsert_thumbnails($post_id, $attachments);
    }
}

function mfn_upsert_language($post_id, $group_id, $lang)
{

    $meta = get_post_meta($post_id, MFN_POST_TYPE . "_group_id", true);
    if (!$meta) {
        update_post_meta($post_id, MFN_POST_TYPE . "_group_id", $group_id);
    }
    update_post_meta($post_id, MFN_POST_TYPE . "_lang", $lang);

    $options = get_option(MFN_PLUGIN_NAME);
    $use_wpml = isset($options['language_plugin']) && $options['language_plugin'] == 'wpml';
    $use_pll = isset($options['language_plugin']) && $options['language_plugin'] == 'pll';

    if ($use_pll) {
        if (function_exists('pll_languages_list')) {
            $pllLangMapping = array();
            foreach (pll_languages_list(array('fields' => array())) as $pll_lang) {
                $l = explode('_', $pll_lang->locale)[0];
                $pllLangMapping[$l] = $pll_lang->slug;
            };

            if (isset($pllLangMapping[$lang])) {
                pll_set_post_language($post_id, $pllLangMapping[$lang]);
            }

            global $wpdb;
            $q = $wpdb->prepare("
            SELECT lang.post_id, lang.meta_value as lang
            FROM  $wpdb->postmeta grp
            INNER JOIN  $wpdb->postmeta lang
            ON grp.post_id = lang.post_id AND lang.meta_key = '" . MFN_POST_TYPE . "_lang'
            WHERE grp.meta_value = %s
              AND grp.meta_key = '" . MFN_POST_TYPE . "_group_id';
            ", $group_id);

            $res = $wpdb->get_results($q);

            $translations = array();
            foreach ($res as $i => $post){
                $_post_id = $post->post_id;
                $_lang = $post->lang;

                if (!isset($pllLangMapping[$_lang])) {
                    continue;
                }

                $translations[$pllLangMapping[$_lang]] = $_post_id;
            }
            pll_save_post_translations( $translations );
        }
    }

    if ($use_wpml) {
        // This since WPML has some sort of race condition when creating a multiple posts at once
        // It should really be done in the call wp_insert_post
        do_action( 'wpml_set_element_language_details', array(
            'element_id'    => $post_id,
            'element_type'  => 'post_' . MFN_POST_TYPE,
            'trid'   => false,
            'language_code'   => $lang
        ));

        global $wpdb;
        $tableName = $wpdb->prefix . 'icl_translations';

        $q = $wpdb->prepare("
            SELECT min(t.trid)
            FROM $wpdb->postmeta m
            INNER JOIN $tableName t
            ON m.post_id = t.element_id AND t.element_type = 'post_" . MFN_POST_TYPE . "'
            WHERE m.meta_key = '" . MFN_POST_TYPE . "_group_id'
              AND m.meta_value = %s
      ", $group_id);
        $trid = $wpdb->get_var($q);

        // $wpdb->update($tableName, array('language_code' => $lang, 'trid' => $trid), array('element_id' => $post_id));
        do_action( 'wpml_set_element_language_details', array(
            'element_id'    => $post_id,
            'element_type'  => 'post_' . MFN_POST_TYPE,
            'trid'   => $trid,
            'language_code'   => $lang
        ));

    }
}

function mfn_upsert_news_meta($post_id, $news_id, $slug) {
    update_post_meta($post_id, MFN_POST_TYPE . "_news_id", $news_id);
    update_post_meta($post_id, MFN_POST_TYPE . "_news_slug", $slug);
}

function mfn_upsert_item_full($item, $signature = '', $raw_data = '', $reset_cache = false): int
{
    global $wpdb;

    $news_id = $item->news_id;
    $group_id = $item->group_id;
    $lang = $item->properties->lang ?? 'xx';
    $title = $item->content->title;
    $slug = $item->content->slug;
    $publish_date = $item->content->publish_date;
    $preamble = $item->content->preamble ?? '';
    $html = $item->content->html;
    $attachments = $item->content->attachments ?? array();

    $post_id = $wpdb->get_var($wpdb->prepare(
        "
            SELECT post_id
            FROM $wpdb->postmeta
            WHERE meta_key = %s
            LIMIT 1
        ",
        MFN_POST_TYPE . "_" . $news_id
    ));

    $is_dirty = mfn_post_is_dirty($post_id);

    if ($is_dirty) {
        return 0;
    }

    do_action('mfn_before_upsertitem', $item);

    $tags = mfn_create_tags($item);

    $outro = function ($post_id) use ($reset_cache, $group_id, $news_id, $lang, $slug, $attachments, $tags) {
        if ($reset_cache) {
            wp_cache_flush();
        }

        wp_set_object_terms($post_id, $tags, MFN_TAXONOMY_NAME, false);
        mfn_upsert_language($post_id, $group_id, $lang);
        mfn_upsert_news_meta($post_id, $news_id, $slug);
        mfn_upsert_attachments($post_id, $attachments);

        if ($reset_cache) {
            wp_cache_flush();
        }

    };

    if (empty($html)) {
        $html = '';
    }

    if ($post_id) {
        $post = get_post($post_id);
        if ($post !== null && $post->post_type === MFN_POST_TYPE) {
            $post_id = wp_update_post(array(
                'ID' => $post_id,
                'post_content' => $html,
                'post_title' => $title,
                'post_excerpt' => $preamble,
                'post_status' => 'publish',
                'post_date_gmt' => $publish_date,
            ));
        }
    } else {
        $post_id = wp_insert_post(array(
            'post_content' => $html,
            'post_title' => $title,
            'post_excerpt' => $preamble,
            'post_status' => 'publish',
            'post_type' => MFN_POST_TYPE,
            'post_date_gmt' => $publish_date,
        ));
    }

    if ($post_id != 0) {
        add_post_meta($post_id, MFN_POST_TYPE . "_" . $news_id, $publish_date);

        if ($signature != '') {
            add_post_meta($post_id, MFN_POST_TYPE . "_signature_" . $news_id, $signature);
        }
        if ($raw_data != '') {
            add_post_meta($post_id, MFN_POST_TYPE . "_data_" . $news_id, $raw_data);
        }
        $outro($post_id);
    }

    // run callback
    do_action('mfn_after_upsertitem', $post_id);

    return 1;
}

function mfn_unpublish_item($news_id) {
    $mfn_post_type = MFN_POST_TYPE;
    global $wpdb;
    echo $wpdb->query($wpdb->prepare(
        "UPDATE $wpdb->posts p
         JOIN $wpdb->postmeta pm ON p.ID = pm.post_id
         SET p.post_status = 'trash'
         WHERE pm.meta_key = %s;",
        $mfn_post_type . "_" . $news_id
    ));
}

function mfn_verify_ping_item($subscription, $method, $item): array
{
    $valid = isset($item->properties->type) && $item->properties->type === 'ping' &&
        isset($item->news_id) && $item->news_id === '00000000-0000-0000-0000-000000000000' &&
        isset($item->source) && $item->source === 'mfn' &&
        isset($item->content->slug);

    if (!$valid) {
        return array (null, null, 'structure of item not valid');
    }

    $challenge = str_replace('ping-challenge-', '', $item->content->slug);

    $resp = new stdClass();
    $resp->pong = new stdClass();
    $resp->pong->method = $method;
    $resp->pong->challenge = $challenge;
    $resp->metadata = new stdClass();
    $resp->metadata->user_agent = MFN_PLUGIN_NAME;
    $resp->metadata->version = MFN_PLUGIN_NAME_VERSION;

    $content = json_encode($resp, JSON_UNESCAPED_UNICODE);
    if (!$content) {
        return array (null, null, 'json_encode failed');
    }
    if (empty($subscription['posthook_secret'])) {
        return array (null, null, '"posthook_secret" is empty');
    }
    $key = $subscription['posthook_secret'];

    $pingSignatureHeader = 'sha256=' . hash_hmac('sha256', $content, $key);

    return array ($content, $pingSignatureHeader, null);
}

function mfn_subscribe_to_websub($pluginUrl, $posthookName, $posthookSecret, $topic, $hubUrl): string
{
    $args = array(
        'method' => 'POST',
        'headers' => array("content-type" => "application/x-www-form-urlencoded"),
        'body' => array(
            'hub.mode' => 'subscribe',
            'hub.topic' => $topic,
            'hub.callback' => $pluginUrl . '/posthook.php?wp-name=' . $posthookName,
            'hub.secret' => $posthookSecret,
            'hub.metadata' => '{"synchronize": true}',
            'hub.ext.ping' => true,
            'hub.ext.event' => true,
        )
    );

    $response = wp_remote_post($hubUrl, $args);
    if (is_wp_error($response)) {
        return $response->get_error_message();
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code >= 200 && $code <= 299) {
        return "";
    }
    return $code . ' ' .  wp_remote_retrieve_body($response);
}

function mfn_save_subscriptions($subscriptions) {
    update_option("mfn-subscriptions", $subscriptions);
}

function mfn_get_subscription_by_plugin_url($subscriptions, $pluginUrl) {
    $subscription = [];
    if (isset($subscriptions) && is_array($subscriptions)) {
        foreach ($subscriptions as $s) {
            if (isset($s['plugin_url']) && $s['plugin_url'] === $pluginUrl) {
                $subscription = $s;
            }
        }
    }
    return $subscription;
}

function mfn_update_challenge_by_plugin_url($subscriptions, $pluginUrl, $challenge) {
    if (isset($subscriptions) && is_array($subscriptions)) {
        foreach ($subscriptions as $key => $s) {
            if ($s['plugin_url'] === $pluginUrl) {
                $subscriptions[$key]['subscription_id'] = $challenge;
            }
        }
        mfn_save_subscriptions($subscriptions);
    }
    return $subscriptions;
}

function mfn_add_subscription($subscriptions, $pluginUrl, $posthookName, $posthookSecret) {
    if (!isset($subscriptions) || !is_array($subscriptions)) {
        $subscriptions = [];
    }
    $subscriptions[] = array(
            'subscription_id' => '',
            'plugin_url' => $pluginUrl,
            'posthook_name' => $posthookName,
            'posthook_secret' => $posthookSecret,
    );
    mfn_save_subscriptions($subscriptions);
}

function mfn_subscribe(): string {
    $ops = get_option(MFN_PLUGIN_NAME);
    $subscriptions = get_option("mfn-subscriptions");
    $plugin_url = mfn_plugin_url();

    $cus_query = $ops['cus_query'] ?? '';
    $entity_id = $ops['entity_id'] ?? '';
    $hub_url = mfn_fetch_hub_url();

    $posthook_name = mfn_generate_random_string();
    $posthook_secret = mfn_generate_random_string();

    $subscription = mfn_get_subscription_by_plugin_url($subscriptions, $plugin_url);

    $topic = '/mfn/s.json?type=all&.author.entity_id=' . $entity_id .  "&" . $cus_query;

    if (strpos($hub_url, 'https://feed.mfn.') === 0) {
        $topic = '/feed/' . $entity_id .  "?" . $cus_query;
    }

    if (isset($subscription['posthook_name']) && $subscription['posthook_name']) {
        $posthook_name = $subscription['posthook_name'];
    }

    if (isset($subscription['posthook_secret']) && $subscription['posthook_secret']) {
        $posthook_secret = $subscription['posthook_secret'];
    }

    mfn_add_subscription($subscriptions, $plugin_url, $posthook_name, $posthook_secret);

    return mfn_subscribe_to_websub($plugin_url, $posthook_name, $posthook_secret, $topic, $hub_url);
}

function mfn_delete_subscription($subscriptions, $subscription) {
    if (isset($subscriptions) && is_array($subscriptions) && isset($subscription['plugin_url'])) {
        $result = [];
        foreach ($subscriptions as $s) {
            if (!isset($s['plugin_url'])) {
                continue;
            }
            if ($s['plugin_url'] === $subscription['plugin_url']) {
                continue;
            }
            $result[] = $s;
        }
        mfn_save_subscriptions($result);
    }
}

function mfn_unsubscribe(): string {
    $subscriptions = get_option("mfn-subscriptions");

    $subscription = mfn_get_subscription_by_plugin_url($subscriptions, mfn_plugin_url());
    $subscription_id = $subscription['subscription_id'] ?? "s";

    if (strlen($subscription_id) != 36) {
        return "There is no active subscription.";
    }

    $hub_url = mfn_fetch_hub_url();

    $request = $hub_url . '/verify/' . $subscription_id . "?hub.mode=unsubscribe";
    $response = wp_remote_get($request);
    wp_remote_retrieve_body($response);

    mfn_delete_subscription($subscriptions, $subscription);

    return "success";
}

function mfn_add_custom_meta_box() {
    add_meta_box(
        'mfn-custom-meta-boxdiv',
        MFN_SINGULAR_NAME . ' status',
        'mfn_custom_meta_box_html',
        MFN_POST_TYPE,
        'side'
    );
}

function mfn_custom_meta_box_html($post) {

    $is_dirty = mfn_post_is_dirty($post->ID);
    $is_local = mfn_post_is_local($post->ID) === null;
    $disabled = !$is_dirty ? 'disabled' : '';

    if ($is_local) {
        echo '<div class="mfn-news-item-actions-text">' . mfn_get_text('text_mfn_news_item_status_local') . '</div><div id="mfn-news-item-actions">
        </div>';
    } else {
        if ($is_dirty) {
            echo '<div class="mfn-news-item-actions-text">' . mfn_get_text('text_mfn_news_item_status_unpure') . '</div><div id="mfn-news-item-actions">';
        } else {
            echo '<div class="mfn-news-item-actions-text">' . mfn_get_text('text_mfn_news_item_status_pure') . '</div><div id="mfn-news-item-actions">';
        }
        echo '<div id="mfn-item-restore-action"><div id="mfn-item-restore-status" style="width: 100%;">';
        if ($is_dirty) {
            echo '<div class="mfn-tooltip-box">
                  <span class="mfn-info-icon-wrapper"><i class="dashicons dashicons-info-outline"></i></span>
                  <span class="mfn-tooltip-text">' . mfn_get_text('tooltip_restore_item_info') . '</span>
              </div>';
        }
        echo '</div>
              <button
                type="button"
                name="mfn-item-restore-button"
                id="mfn-item-restore-button"
                class="button button-primary button-large mfn-restore-item-button"
                data-mfn-post-id="' . $post->ID . '"
                value="restore-item" ' . $disabled . '
              >
                ' . mfn_get_text('button_restore') . '
              </button>
          </div>
	      <div class="clear"></div>
	      </div>';
    }
}

function mfn_metabox_order( $order ) {
    $order['side'] = 'submitdiv,mfn-custom-meta-boxdiv,mfn-news-tagdiv,icl_div,,ml_box';
    return $order;
}

function mfn_get_post_language($post_id)
{
    $lang_slug = "";

    if (defined('POLYLANG_BASENAME')) {
        $lang_slug = pll_get_post_language($post_id);
    } else if (defined('WPML_PLUGIN_BASENAME')) {
        $lang_slug = apply_filters('wpml_post_language_details', NULL, $post_id)["language_code"];
    }

    return $lang_slug;
}

function mfn_create_tags_by_lang_suffix($lang, $lang_suffix): array
{
    $lang_suffix = empty($lang_suffix) ? '' : '_' . $lang_suffix;
    return [
        // mfn_[lang_suffix]
        MFN_TAG_PREFIX . $lang_suffix,
        // mfn-tag-pr_[lang_suffix]
        MFN_TAG_PREFIX . "-type-pr" . $lang_suffix,
        // mfn-lang-[lang]_[lang_suffix]
        MFN_TAG_PREFIX . "-lang-" . $lang . $lang_suffix
    ];
}

function mfn_news_post_saved($post_id)
{
    // set as dirty
    if ( is_admin() && current_user_can( 'manage_options' )  ) {
        // skip dirty if post was added manually
        if (mfn_post_is_local($post_id) !== null) {
            add_post_meta($post_id, MFN_POST_TYPE . '_is_dirty', 'true', true);
        }
    }

    // do not interfere with upsert item
    if ( did_action('mfn_before_upsertitem') ) return;

    if (get_post_meta($post_id, MFN_POST_TYPE . "_group_id", true)) return;

    $lang_slug = mfn_get_post_language($post_id);

    $terms = wp_get_object_terms($post_id, MFN_TAXONOMY_NAME);
    $needle = MFN_TAG_PREFIX . '-lang-';

    $needles = mfn_create_tags_by_lang_suffix('', '');

    $matching_terms = array();
    $lang_by_terms = '';
    $has_ir = false;

    foreach ($terms as $term) {
        foreach ($needles as $needle) {
            if (strpos($term->slug, $needle) === 0) {
                if ($needle === 'mfn' && !($term->slug === 'mfn' || strpos($term->slug, 'mfn_') === 0)) {
                    continue;
                }
                array_push($matching_terms, $term->slug);
            }
        }
        if (strpos($term->slug, MFN_TAG_PREFIX . "-lang-") === 0) {
            $lang_by_terms = explode($needle, $term->slug)[1];
        }
        if (strpos($term->slug, MFN_TAG_PREFIX . "-type-ir") === 0) {
            $has_ir = true;
        }
    }

    if (empty($lang_slug) && empty($lang_by_terms)) {
        delete_post_meta($post_id, MFN_POST_TYPE . "_lang");
        return;
    }

    $primary_lang = 'en'; // TODO? main WPML/polylang language that doesn't have slug lang-suffix for wp_terms

    $mode_normal = empty($lang_slug) && !empty($lang_by_terms);

    if ($mode_normal) {
        $lang_slug = $lang_by_terms;
    }

    update_post_meta(
        $post_id,
        MFN_POST_TYPE . "_lang",
        $lang_slug
    );

    $lang_suffix = ($mode_normal || $primary_lang === $lang_slug) ? '' : $lang_slug;
    $tags_to_insert = mfn_create_tags_by_lang_suffix($lang_slug, $lang_suffix);

    if ($has_ir) {
        foreach($tags_to_insert as $k => $v) {
            if (strpos($v, MFN_TAG_PREFIX . "-type-pr") === 0) {
                array_splice($tags_to_insert, $k, 1);
            }
        }
    }

    wp_remove_object_terms($post_id, $matching_terms, MFN_TAXONOMY_NAME);
    wp_set_object_terms($post_id, $tags_to_insert, MFN_TAXONOMY_NAME, true);
}

function mfn_news_edit_post_change_title_in_list() {
    global $post_type;

    if ($post_type === MFN_POST_TYPE) {
        add_filter(
            'the_title',
            MFN_POST_TYPE . '_construct_new_title',
            100,
            2
        );
    }
}

function mfn_news_construct_new_title($title, $post_id) {
    $is_dirty = mfn_post_is_dirty($post_id);
    $is_local = mfn_post_is_local($post_id) === null;
    $is_trash = get_post_status($post_id) === 'trash';
    if ($is_local) {
        $title = $title . ' ' . mfn_get_text('text_local');
    } else if ($is_trash) {
        $title = $title . ' ' . mfn_get_text('text_trash');
    } else {
        if($is_dirty) {
            $title = $title . ' ' . mfn_get_text('text_modified');
        }
    }
    return $title;
}