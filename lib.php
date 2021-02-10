<?php

require_once(dirname(__FILE__) . '/config.php');

function startsWith($haystack, $needle): bool
{
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}

function createTags($item): array
{
    $tags = isset($item->properties->tags) ? $item->properties->tags : array();
    $lang = isset($item->properties->lang) ? $item->properties->lang : 'xx';
    $type = isset($item->properties->type) ? $item->properties->type : 'ir';

    $newtag = array();

    $slug_prefix = (MFN_TAG_PREFIX !== '' && MFN_TAG_PREFIX !== null ? MFN_TAG_PREFIX . '-' : '');

    array_push($newtag, MFN_TAG_PREFIX);
    array_push($newtag, $slug_prefix . 'lang-' . $lang);
    array_push($newtag, $slug_prefix . 'type-' . $type);

    foreach ($tags as $i => $tag) {
        if (startsWith($tag, ':correction')) {
            array_push($newtag, $slug_prefix . '-correction');
            continue;
        }

        $tag = str_replace('sub:', '', $tag);
        $tag = trim($tag, ' :');
        $tag = str_replace(':', '-', $tag);
        $tag = $slug_prefix . $tag;
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
        foreach ($newtag as $i => $t) {
            $newtag[$i] = $t . "_" . $lang;
        }
    }
    return $newtag;
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
        pll_set_post_language($post_id, $lang);

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
            $translations[$_lang] = $_post_id;
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

function upsertItem($item, $signature = '', $raw_data = '', $reset_cache = false): int
{
    do_action('mfn_before_upsertitem', $item);
    global $wpdb;

    $newsid = $item->news_id;
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

    $outro = function ($post_id) use ($reset_cache, $groupId, $lang, $attachments, $tags) {
        if ($reset_cache) {
            wp_cache_flush();
        }

        wp_set_object_terms($post_id, $tags, MFN_TAXONOMY_NAME, false);
        upsertLanguage($post_id, $groupId, $lang);
        upsertAttachments($post_id, $attachments);

        if ($reset_cache) {
            wp_cache_flush();
        }

    };

    if ($post_id) {
        $outro($post_id);
        return 0;
    }
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

function subscribe(): string
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

    $ops['posthook_name'] = isset($ops['posthook_name']) ? $ops['posthook_name'] : generateRandomString();
    $ops['posthook_secret'] = isset($ops['posthook_secret']) ? $ops['posthook_secret'] : generateRandomString();

    $posthook_name = $ops['posthook_name'];
    $posthook_secret = $ops['posthook_secret'];

    update_option(MFN_PLUGIN_NAME, $ops);

    $args = array(
        'method' => 'POST',
        'headers' => array("content-type" => "application/x-www-form-urlencoded"),
        'body' => array(
            'hub.mode' => 'subscribe',
            'hub.topic' => '/mfn/s.json?type=all&.author.entity_id=' . $entity_id .  "&" . $cus_query,
            'hub.callback' => $plugin_url . '/posthook.php?wp-name=' . $posthook_name,
            'hub.secret' => $posthook_secret,
            'hub.metadata' => '{"synchronize": true}'
        )
    );

    $response = wp_remote_post($hub_url, $args);
    $result = wp_remote_retrieve_body($response);

    if ($result === FALSE) {
        return "did not work...";
    }

    return "success";
}

function unsubscribe(): string
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