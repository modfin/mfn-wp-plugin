<?php // Silence is golden

require_once(dirname(__FILE__) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');

$is_admin = current_user_can('manage_options');

if (!$is_admin) {
    echo "you are not admin";
    die();
}

$queries = array();
parse_str($_SERVER['QUERY_STRING'], $queries);

if (!isset($queries["mode"])) {
    echo "a mode must be provided [poll, longpoll or sync]";
    die();
}

$mode = $queries["mode"];

function MFN_generate_random_string($length = 32): string
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function MFN_sync()
{
    $ops = get_option('mfn-wp-plugin');
    $queries = array();
    parse_str($_SERVER['QUERY_STRING'], $queries);

    $offset = isset($queries["offset"]) ? $queries["offset"] : 0;
    $limit = isset($queries["limit"]) ? $queries["limit"] : 48;

    $entity_id = isset($ops['entity_id']) ? $ops['entity_id'] : "bad-entity-id";
    $sync_url = isset($ops['sync_url']) ? $ops['sync_url'] : "";
    $reset_cache = isset($ops['reset_cache']) ? ($ops['reset_cache'] == 'on') : false;

    $cus_query = isset($ops['cus_query']) ? $ops['cus_query'] : "";

    if ($entity_id == "") {
        echo -1;
        return;
    }

    if ($sync_url == "") {
        echo $sync_url;
        echo print_r($ops);
        echo -2;
        return;
    }

    $url = $sync_url . '/all/s.json?type=all&.author.entity_id=' . $ops['entity_id'] .
        '&limit=' . $limit .
        "&offset=" . $offset .
        "&" . $cus_query;

    $response = wp_remote_get($url);
    $json = wp_remote_retrieve_body($response);
    $obj = json_decode($json);

    $acc = 0;

    if (is_array($obj->items)) {
        foreach ($obj->items as $i => $item) {
            $acc += upsertItem($item, '', '', $reset_cache);
        }
        echo sizeof($obj->items) . ' ' . $acc;
        return;
    }

    echo 0 . ' ' . $acc;
}

function MFN_ping_hub()
{
    $ops = get_option('mfn-wp-plugin');
    $hub_url = isset($ops['hub_url']) ? $ops['hub_url'] : "";

    if (startsWith($hub_url, "http")) {

        $response = wp_remote_get($hub_url);
        $content = wp_remote_retrieve_body($response);

        if (strstr($content, 'https://www.w3.org/TR/websub')) {
            echo "ponghub";
            return;
        }

        echo "fail, endpoint does not contain WebSub info.";
        return;
    }
    die("fail, not a valid url.");
}

function MFN_clear_settings(): string
{
    update_option('mfn-wp-plugin', array());
    return "done";
}

function MFN_delete_all_posts(): int
{
    $queries = array();
    parse_str($_SERVER['QUERY_STRING'], $queries);
    $limit = isset($queries["limit"]) ? $queries["limit"] : -1;


    $i = 0;
    $allposts = get_posts(
        array(
            'post_type' => MFN_POST_TYPE,
            'lang' => '',
            'numberposts' => $limit
        )
    );
    foreach ($allposts as $eachpost) {
        if ($eachpost->post_type == MFN_POST_TYPE){
            $i++;
            wp_delete_post( $eachpost->ID, true );
        }
    }
    return $i;
}

switch ($mode) {
    case "sync-tax":
        sync_mfn_taxonomy();
        die();

    case "sync":
        MFN_sync();
        die();

    case "ping";
        echo "pong";
        die();

    case "pinghub";
        MFN_ping_hub();
        die();

    case "subscribe":
        MFN_subscribe();
        die();

    case "unsubscribe":
        echo MFN_unsubscribe();
        die();

    case "clear-settings":
        echo MFN_clear_settings();
        die();
    case "delete-all-posts":
        echo MFN_delete_all_posts();
        die();

    default:
        echo "a mode must be provided [sync]";
        die();
}