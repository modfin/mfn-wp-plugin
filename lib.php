<?php
require_once(dirname(__FILE__) . '/config.php');


function startsWith($haystack, $needle)
{
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}

function createTags($item)
{


    $tags = isset($item->properties->tags) ? $item->properties->tags : array();
    $lang = isset($item->properties->lang) ? $item->properties->lang : 'xx';
    $type = isset($item->properties->type) ? $item->properties->type : 'ir';

    $newtag = array();

    array_push($newtag, MFN_TAG_PREFIX);
    array_push($newtag, MFN_TAG_PREFIX . '-lang-' . $lang);
    array_push($newtag, MFN_TAG_PREFIX . '-type-' . $type);

    foreach ($tags as $i => $tag) {
        if (startsWith($tag, ':correction')) {
            array_push($newtag, MFN_TAG_PREFIX . '-correction');
            continue;
        }

        $tag = str_replace('sub:', '', $tag);
        $tag = trim($tag, ' :');
        $tag = str_replace(':', '-', $tag);
        $tag = MFN_TAG_PREFIX . '-' . $tag;
        array_push($newtag, $tag);
    }

    $options = get_option(MFN_PLUGIN_NAME);
    $use_wpml = isset($options['use_wpml']) ? $options['use_wpml'] : 'off';

    if ($use_wpml == 'on' && $lang != 'en') {
        foreach ($newtag as $i => $t) {
            $newtag[$i] = $t . "_" . $lang;
        }
    }

    return $newtag;
}


function upsertAttachments($post_id, $attachments)
{
    foreach ($attachments as $i => $attachment) {
        $title = $attachment->file_title;
        $content_type = $attachment->content_type;
        $url = $attachment->url;

        $a = "<a href='$url' content='$content_type' target='_blank' rel='noopener'>$title</a>";
        update_post_meta($post_id, MFN_POST_TYPE . "_attachment_link", $a);
        update_post_meta($post_id, MFN_POST_TYPE . "_attachment_data", json_encode($attachment));
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

    if ($use_wpml == 'on') {
        global $wpdb;
        $tableName = $wpdb->prefix . 'icl_translations';

        $q = $wpdb->prepare("
            SELECT min(t.trid)
            FROM $wpdb->postmeta m
            INNER JOIN $tableName t
            ON m.post_id = t.element_id AND t.element_type = 'post_mfn_news'
            WHERE m.meta_key = 'mfn_news_group_id'
              AND m.meta_value = %s
      ", $groupId);
        $trid = $wpdb->get_var($q);

        $wpdb->update($tableName, array('language_code' => $lang, 'trid' => $trid), array('element_id' => $post_id));


        do_action( 'wpml_sync_all_custom_fields', $post_id);

    }

}


function upsertItem($item, $signature = '', $raw_data = '', $reset_cache = false)
{
    global $wpdb;

    $newsid = $item->news_id;
    $groupId = $item->group_id;
    $lang = isset($item->properties->lang) ? $item->properties->lang : 'xx';
    $entity_id = $item->author->entity_id;

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
        'post_date' => $publish_date,
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

    return 1;
}


function subscribe()
{

    $ops = get_option('mfn-wp-plugin');

    $subscription_id = isset($ops['subscription_id']) ? $ops['subscription_id'] : "N/A";


    if (strlen($subscription_id) == 36) {
        return "a subscription is already active";
    }

    $hub_url = isset($ops['hub_url']) ? $ops['hub_url'] : "";
    $entity_id = isset($ops['entity_id']) ? $ops['entity_id'] : "";
    $plugin_url = isset($ops['plugin_url']) ? $ops['plugin_url'] : "";


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
            'hub.topic' => '/mfn/s.json?type=all&.author.entity_id=' . $entity_id,
            'hub.callback' => $plugin_url . '/posthook.php?wp-name=' . $posthook_name,
            'hub.secret' => $posthook_secret
        )
    );


    $response = wp_remote_post($hub_url, $args);
    $result = wp_remote_retrieve_body($response);

    if ($result === FALSE) {
        return "did not work...";
    }


    return "success";
}


function unsubscribe()
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