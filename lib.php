<?php

require_once(dirname(__FILE__) . '/config.php');

function startsWith($haystack, $needle): bool
{
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}

function mfn_create_base_tags($item, $prefix)
{
    $tags = isset($item->properties->tags) ? $item->properties->tags : array();
    $lang = isset($item->properties->lang) ? $item->properties->lang : 'xx';
    $type = isset($item->properties->type) ? $item->properties->type : 'ir';

    $prefix = empty($prefix) ? '' : ($prefix . '-');

    $base_tags = array();

    $base_tags[] = $prefix . 'lang-' . $lang;
    $base_tags[] = $prefix . 'type-' . $type;

    foreach ($tags as $tag) {
        if (startsWith($tag, ':correction')) {
            $base_tags[] = $prefix . 'correction';
            continue;
        }

        $tag = str_replace('sub:', '', $tag);
        $tag = trim($tag, ' :');
        $base_tags[] = $prefix . str_replace(':', '-', $tag);
    }
    return $base_tags;
}

function createTags($item): array
{
    // TODO: use mfn_create_base_tags() then add slug-prefix etc
    $options = get_option(MFN_PLUGIN_NAME);
    $drop_custom_tag_prefix = isset($options['taxonomy_disable_cus_prefix']) && $options['taxonomy_disable_cus_prefix']  === 'on';

    $tags = isset($item->properties->tags) ? $item->properties->tags : array();
    $lang = isset($item->properties->lang) ? $item->properties->lang : 'xx';
    $type = isset($item->properties->type) ? $item->properties->type : 'ir';

    $newtag = array();

    $slug_prefix = (MFN_TAG_PREFIX !== '' && MFN_TAG_PREFIX !== null ? MFN_TAG_PREFIX . '-' : '');

    array_push($newtag, MFN_TAG_PREFIX);
    array_push($newtag, $slug_prefix . 'lang-' . $lang);
    array_push($newtag, $slug_prefix . 'type-' . $type);

    $skipped_tags = [
        'sub:ci:gm:notice:extra',
        'sub:ci:gm:info:extra'
    ];

    foreach ($tags as $i => $tag) {
        if (startsWith($tag, ':correction')) {
            array_push($newtag, $slug_prefix . '-correction');
            continue;
        }
        if (strpos($tag, 'sub:') !== 0 && strpos($tag, 'cus:') !== 0 && strpos($tag, ':regulatory') !== 0) {
            continue;
        }
        if (in_array($tag, $skipped_tags, true)) {
            continue;
        }
        $tag = str_replace('sub:', '', $tag);
        $tag = trim($tag, ' :');
        $tag = str_replace(':', '-', $tag);
        if ($drop_custom_tag_prefix && strpos($tag, 'cus-') === 0) {
            $tag = str_replace('cus-', '', $tag);
            if (strlen($tag) < 2) {
                continue;
            }
        } else {
            $tag = $slug_prefix . $tag;
        }
        array_push($newtag, $tag);
    }

    $options = get_option(MFN_PLUGIN_NAME);
    $use_wpml = isset($options['use_wpml']) ? $options['use_wpml'] : 'off';
    $use_pll = isset($options['use_pll']) ? $options['use_pll'] : 'off';
    if ($use_wpml == 'on' && $lang != 'en') {
        foreach ($newtag as $i => $t) {
            $newtag[$i] = $t . "_" . $lang;
        }
    }
    if ($use_pll == 'on' && $lang != 'en') {
        $pllLangMapping = array();
        foreach (pll_languages_list(array('fields' => array())) as $pll_lang) {
            $l = explode('_', $pll_lang->locale)[0];
            $pllLangMapping[$l] = $pll_lang->slug;
        };
        foreach ($newtag as $i => $t) {
            $newtag[$i] = $t . "_" . $pllLangMapping[$lang];
        }
    }
    return $newtag;
}

function getProxiedUrl($url, $vanityFileName) {
    $ops = get_option('mfn-wp-plugin');
    $storageUrl = isset($ops['sync_url'])
        ? ((strpos($ops['sync_url'], 'https://feed.mfn.') === 0)
            ? str_replace('//feed.mfn', '//storage.mfn', str_replace('/v1', '', $ops['sync_url']))
            : str_replace('//mfn', '//storage.mfn', $ops['sync_url']))
        : null;

    return $storageUrl !== null && $storageUrl !== '' && (strpos($url, $storageUrl) !== 0)
        ? "$storageUrl/proxy/$vanityFileName?url=" . urlencode($url) . "&size=w-2560"
        : $url . "?size=w-2560";
}

$upsert_thumbnails_dependencies_included = false;

function upsertThumbnails($post_id, $attachments)
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
        $image->proxied_url = getProxiedUrl($a->url, $filename);

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

function upsertAttachments($post_id, $attachments)
{
    delete_post_meta($post_id,  MFN_POST_TYPE . "_attachment_link");
    delete_post_meta($post_id,  MFN_POST_TYPE . "_attachment_data");

    foreach ($attachments as $i => $attachment) {
        $title = $attachment->file_title;
        $content_type = $attachment->content_type;
        $url = $attachment->url;

        $a = "<a href='$url' content='$content_type' target='_blank' rel='noopener'>$title</a>";

        add_post_meta($post_id, MFN_POST_TYPE . "_attachment_link", $a);
        add_post_meta($post_id, MFN_POST_TYPE . "_attachment_data", json_encode($attachment, JSON_UNESCAPED_UNICODE));
    }
    if (isset(get_option(MFN_PLUGIN_NAME)['thumbnail_on'])) {
        upsertThumbnails($post_id, $attachments);
    }
}

function upsertLanguage($post_id, $groupId, $lang)
{

    $meta = get_post_meta($post_id, MFN_POST_TYPE . "_group_id", true);
    if (!$meta) {
        update_post_meta($post_id, MFN_POST_TYPE . "_group_id", $groupId);
    }
    update_post_meta($post_id, MFN_POST_TYPE . "_lang", $lang);

    $options = get_option(MFN_PLUGIN_NAME);
    $use_wpml = isset($options['use_wpml']) ? $options['use_wpml'] : 'off';
    $use_pll = isset($options['use_pll']) ? $options['use_pll'] : 'off';

    if ($use_pll == 'on') {
        $pllLangMapping = array();
        foreach (pll_languages_list(array('fields' => array())) as $pll_lang) {
            $l = explode('_', $pll_lang->locale)[0];
            $pllLangMapping[$l] = $pll_lang->slug;
        };
        pll_set_post_language($post_id, $pllLangMapping[$lang]);

        global $wpdb;
        $q = $wpdb->prepare("
        SELECT lang.post_id, lang.meta_value as lang
        FROM  $wpdb->postmeta grp
        INNER JOIN  $wpdb->postmeta lang
        ON grp.post_id = lang.post_id AND lang.meta_key = '" . MFN_POST_TYPE . "_lang'
        WHERE grp.meta_value = %s
          AND grp.meta_key = '" . MFN_POST_TYPE . "_group_id';
        ", $groupId);

        $res = $wpdb->get_results($q);

        $translations = array();
        foreach ($res as $i => $post){
            $_post_id= $post->post_id;
            $_lang = $post->lang;
            $translations[$pllLangMapping[$_lang]] = $_post_id;
        }
        pll_save_post_translations( $translations );
    }

    if ($use_wpml == 'on') {
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
      ", $groupId);
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

function upsertNewsMeta($post_id, $newsid, $slug) {
    update_post_meta($post_id, MFN_POST_TYPE . "_news_id", $newsid);
    update_post_meta($post_id, MFN_POST_TYPE . "_news_slug", $slug);
}

function mfn_upsert_category($post_id, $item)
{
    $categories_enabled = isset(get_option(MFN_PLUGIN_NAME)['category_on']) && get_option(MFN_PLUGIN_NAME)['category_on'] === 'on';
    if (!$categories_enabled) {
        return;
    }

    $lang = isset($item->properties->lang) ? $item->properties->lang : '';
    $type = isset($item->properties->type) ? $item->properties->type : '';

    if (empty($lang) || empty($type)) {
        return;
    }

    $pll_enabled = function_exists('pll_is_translated_taxonomy')
        && function_exists('pll_is_translated_post_type')
        && function_exists('pll_languages_list')
        && function_exists('pll_get_term_language')
        && function_exists('pll_set_term_language')
        && pll_is_translated_taxonomy('category')
        && pll_is_translated_post_type(MFN_POST_TYPE);

    $categories = [
        [
            "slug" => "mfn-ir",
            "base_name" => "MFN IR",
            "filter" => ["mfn-type-ir"]
        ],
        [
            "slug" => "mfn-pr",
            "base_name" => "MFN PR",
            "filter" => ["mfn-type-pr"]
        ],
        [
            "slug" => "mfn-regulatory",
            "filter" => ["mfn-regulatory"],
        ],
        [
            "slug" => "mfn-not-regulatory",
            "filter" => ["-mfn-regulatory"],
        ],
        [
            "slug" => "mfn-report",
            "filter" => ["mfn-report"],
        ],
        [
            "slug" => "mfn-annual-report",
            "filter" => ["mfn-report-annual"],
        ],
    ];

    if (!$pll_enabled) {
        $categories[] = [
            "slug" => "mfn-lang-" . $lang,
            "filter" => ["mfn-lang-" . $lang],
        ];
    }

    $categories = apply_filters('mfn_define_categories', $categories);

    $base_tags = mfn_create_base_tags($item, "mfn");
    $has_base_tag = array();
    foreach ($base_tags as $t) {
        $has_base_tag[$t] = true;
    }

    $pllLangMapping = array();
    $langs = array();
    if ($pll_enabled) {
        foreach (pll_languages_list(array('fields' => array())) as $pll_lang) {
            $l = explode('_', $pll_lang->locale)[0];
            $pllLangMapping[$l] = $pll_lang->slug;
            $langs[] = $l;
        }
        if (!in_array($lang, $langs)) {
            return;
        }
    }

    $matching_categories = array();
    foreach ($categories as $c) {
        if (!isset($c["filter"]) || !is_array($c["filter"]) || !isset($c["slug"]) || trim($c["slug"]) === "") continue;
        $matching = true;
        foreach ($c["filter"] as $f) {
            if ($f[0] === '-') {
                if (isset($has_base_tag[substr($f, 1)])) {
                    $matching = false;
                    break;
                }
            } else if (!isset($has_base_tag[$f])) {
                $matching = false;
                break;
            }
        }
        if ($matching && count($c["filter"]) > 0) {
            $matching_categories[] = $c;
        }
    }

    $insertTerm = function ($c) use ($pllLangMapping, $lang, $pll_enabled) {
        $base_slug = $c["slug"];

        $meta_query = array(
            array(
                'key' => 'mfn_category_slug',
                'compare' => '=',
                'value' => $base_slug
            )
        );
        if ($pll_enabled) {
            $meta_query[] = array(
                'key' => 'mfn_category_lang',
                'compare' => '=',
                'value' => $lang
            );
        }
        $terms = get_terms([
            'taxonomy' => "category",
            'lang' => '',
            'meta_query' => $meta_query,
            'hide_empty' => false
        ]);

        if (isset($terms[0])) {
            return $terms[0]->term_id;
        }

        $name = str_replace("mfn", "MFN", $base_slug);
        $name = ucwords(str_replace("-", " ", $name));
        if (isset($c["base_name"]) && trim($c["base_name"]) !== "") {
            $name = trim($c["base_name"]);
        }
        $slug = $base_slug;
        if ($pll_enabled) {
            $slug .= "-" . $lang;
            $name .= " (" . $lang . ")";
        }
        if (isset($c["name"]) && trim($c["name"]) !== "") {
            $name = trim($c["name"]);
        }
        $ids = wp_insert_term($name, "category", array('slug' => $slug));
        if (is_wp_error($ids)) {
            echo $ids->get_error_message();
            return null;
        }
        if ($ids == null) {
            return null;
        }

        $term_id = $ids["term_id"];
        add_term_meta($term_id, "mfn_category_slug", $base_slug, true);

        if ($pll_enabled) {
            add_term_meta($term_id, "mfn_category_lang", $lang, true);
            pll_set_term_language($term_id, $pllLangMapping[$lang]);

            $terms = get_terms([
                'taxonomy' => "category",
                'lang' => '',
                'meta_query' => array(
                    array(
                        'key' => 'mfn_category_slug',
                        'compare' => '=',
                        'value' => $base_slug
                    )
                ),
                'hide_empty' => false
            ]);

            $translations = array();
            foreach ($terms as $t) {
                $pllLang = pll_get_term_language($t->term_id);
                if ($pllLang) {
                    $translations[$pllLang] = $t->term_id;
                }
            }
            if (count($translations) > 0) {
                pll_save_term_translations($translations);
            }
        }
        return $term_id;
    };

    $termsToSet = array();
    foreach ($matching_categories as $c) {
        $termId = $insertTerm($c);

        if ($termId === null) {
            return;
        }
        $termsToSet[] = $termId;
    }

    wp_set_object_terms($post_id, $termsToSet, "category", false);
}

function upsertItem($item, $signature = '', $raw_data = '', $reset_cache = false): int
{
    do_action('mfn_before_upsertitem', $item);
    global $wpdb;

    $newsid = $item->news_id;
    $slug = $item->content->slug;
    $groupId = $item->group_id;
    $lang = isset($item->properties->lang) ? $item->properties->lang : 'xx';

    $title = $item->content->title;
    $publish_date = $item->content->publish_date;
    $preamble = isset($item->content->preamble) ? $item->content->preamble : '';
    $html = $item->content->html;
    $attachments = isset($item->content->attachments) ? $item->content->attachments : array();

    $post_id = $wpdb->get_var($wpdb->prepare(
        "
            SELECT post_id
            FROM $wpdb->postmeta
            WHERE meta_key = %s
            LIMIT 1
        ",
        MFN_POST_TYPE . "_" . $newsid
    ));

    $tags = createTags($item);

    $outro = function ($post_id) use ($reset_cache, $groupId, $lang, $attachments, $tags, $newsid, $slug, $item) {
        if ($reset_cache) {
            wp_cache_flush();
        }

        wp_set_object_terms($post_id, $tags, MFN_TAXONOMY_NAME, false);
        upsertLanguage($post_id, $groupId, $lang);
        upsertNewsMeta($post_id, $newsid, $slug);
        mfn_upsert_category($post_id, $item);
        upsertAttachments($post_id, $attachments);

        if ($reset_cache) {
            wp_cache_flush();
        }

    };

    if ($post_id) {
        $outro($post_id);
        return 0;
    }

    if (empty($html)) {
        $html = '';
    }
    $html = "[mfn_before_post]\n" . $html . "\n[mfn_after_post]";

    $post_id = wp_insert_post(array(
        'post_content' => $html,
        'post_title' => $title,
        'post_excerpt' => $preamble,
        'post_status' => 'publish',
        'post_type' => MFN_POST_TYPE,
        'post_date_gmt' => $publish_date,
    ));

    if ($post_id != 0) {
        add_post_meta($post_id, MFN_POST_TYPE . "_" . $newsid, $publish_date);

        if ($signature != '') {
            add_post_meta($post_id, MFN_POST_TYPE . "_signature_" . $newsid, $signature);
        }
        if ($raw_data != '') {
            add_post_meta($post_id, MFN_POST_TYPE . "_data_" . $newsid, $raw_data);
        }
        $outro($post_id);
    }

    // run callback
    do_action('mfn_after_upsertitem', $post_id);

    return 1;
}

function upsertItemFull($item, $signature = '', $raw_data = '', $reset_cache = false): int
{
    do_action('mfn_before_upsertitem', $item);
    global $wpdb;

    $newsid = $item->news_id;
    $slug = $item->content->slug;
    $groupId = $item->group_id;
    $lang = isset($item->properties->lang) ? $item->properties->lang : 'xx';

    $title = $item->content->title;
    $publish_date = $item->content->publish_date;
    $preamble = isset($item->content->preamble) ? $item->content->preamble : '';
    $html = $item->content->html;
    $attachments = isset($item->content->attachments) ? $item->content->attachments : array();

    $post_id = $wpdb->get_var($wpdb->prepare(
        "
            SELECT post_id
            FROM $wpdb->postmeta
            WHERE meta_key = %s
            LIMIT 1
        ",
        MFN_POST_TYPE . "_" . $newsid
    ));

    $tags = createTags($item);

    $outro = function ($post_id) use ($reset_cache, $groupId, $lang, $attachments, $tags, $newsid, $slug, $item) {
        if ($reset_cache) {
            wp_cache_flush();
        }

        wp_set_object_terms($post_id, $tags, MFN_TAXONOMY_NAME, false);
        upsertLanguage($post_id, $groupId, $lang);
        upsertNewsMeta($post_id, $newsid, $slug);
        mfn_upsert_category($post_id, $item);
        upsertAttachments($post_id, $attachments);

        if ($reset_cache) {
            wp_cache_flush();
        }

    };

    if (empty($html)) {
        $html = '';
    }
    $html = "[mfn_before_post]\n" . $html . "\n[mfn_after_post]";

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
        add_post_meta($post_id, MFN_POST_TYPE . "_" . $newsid, $publish_date);

        if ($signature != '') {
            add_post_meta($post_id, MFN_POST_TYPE . "_signature_" . $newsid, $signature);
        }
        if ($raw_data != '') {
            add_post_meta($post_id, MFN_POST_TYPE . "_data_" . $newsid, $raw_data);
        }
        $outro($post_id);
    }

    // run callback
    do_action('mfn_after_upsertitem', $post_id);

    return 1;
}

function unpublishItem($news_id) {
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

function verifyPingItem($method, $item) {
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

    $ops = get_option('mfn-wp-plugin');
    if (!$ops) {
        return array (null, null, '"mfn-wp-plugin" option missing');
    }
    if (empty($ops['posthook_secret'])) {
        return array (null, null, '"posthook_secret" is empty');
    }
    $key = $ops['posthook_secret'];

    $pingSignatureHeader = 'sha256=' . hash_hmac('sha256', $content, $key);

    return array ($content, $pingSignatureHeader, null);
}

function MFN_subscribe(): string
{

    $ops = get_option('mfn-wp-plugin');

    $subscription_id = isset($ops['subscription_id']) ? $ops['subscription_id'] : "N/A";

    if (strlen($subscription_id) == 36) {
        return "a subscription is already active";
    }

    $hub_url = isset($ops['hub_url']) ? $ops['hub_url'] : "";
    $entity_id = isset($ops['entity_id']) ? $ops['entity_id'] : "";
    $plugin_url = isset($ops['plugin_url']) ? $ops['plugin_url'] : "";
    $cus_query = isset($ops['cus_query']) ? $ops['cus_query'] : "";

    $ops['posthook_name'] = isset($ops['posthook_name']) ? $ops['posthook_name'] : MFN_generate_random_string();
    $ops['posthook_secret'] = isset($ops['posthook_secret']) ? $ops['posthook_secret'] : MFN_generate_random_string();

    $posthook_name = $ops['posthook_name'];
    $posthook_secret = $ops['posthook_secret'];

    update_option(MFN_PLUGIN_NAME, $ops);

    $topic = '/mfn/s.json?type=all&.author.entity_id=' . $entity_id .  "&" . $cus_query;

    if (strpos($hub_url, 'https://feed.mfn.') === 0) {
        $topic = '/feed/' . $entity_id .  "?" . $cus_query;
    }

    $args = array(
        'method' => 'POST',
        'headers' => array("content-type" => "application/x-www-form-urlencoded"),
        'body' => array(
            'hub.mode' => 'subscribe',
            'hub.topic' => $topic,
            'hub.callback' => $plugin_url . '/posthook.php?wp-name=' . $posthook_name,
            'hub.secret' => $posthook_secret,
            'hub.metadata' => '{"synchronize": true}',
            'hub.ext.ping' => true,
            'hub.ext.event' => true,
        )
    );

    $response = wp_remote_post($hub_url, $args);
    $code = wp_remote_retrieve_response_code($response);
    if ($code >= 200 && $code <= 299) {
        return "";
    }
    return $code . ' ' .  wp_remote_retrieve_body($response);
}

function MFN_unsubscribe(): string
{
    $ops = get_option('mfn-wp-plugin');

    $subscription_id = isset($ops['subscription_id']) ? $ops['subscription_id'] : "N/A";

    if (strlen($subscription_id) != 36) {
        return "there is no active subscription";
    }

    $hub_url = isset($ops['hub_url']) ? $ops['hub_url'] : "";

    $request = $hub_url . '/verify/' . $subscription_id . "?hub.mode=unsubscribe";
    $response = wp_remote_get($request);
    $result = wp_remote_retrieve_body($response);

    if ($result === FALSE) {
        return "did not work...";
    }

    unset($ops['subscription_id']);
    update_option(MFN_PLUGIN_NAME, $ops);

    return "success";
}
