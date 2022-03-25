<?php
/**
 * Created by PhpStorm.
 * User: raz
 * Date: 2019-02-07
 * Time: 15:40
 */

require_once('./config.php');
require_once( './lib.php');

$queries = array();
parse_str($_SERVER['QUERY_STRING'], $queries);

$ops = get_option('mfn-wp-plugin');
$subscriptions = get_option("mfn-subscriptions");

$plugin_url = mfn_plugin_url();
$subscription = mfn_get_subscription_by_plugin_url($subscriptions, $plugin_url);

if (!isset($subscription)) {
    http_response_code(400);
    die("subscription doesn't exist");
}

if ( !isset($queries["wp-name"]) ) {
    http_response_code(400);
    die("wp-name must be provided");
}

if ( !isset($subscription['posthook_name']) ) {
    http_response_code(400);
    die("posthook name must exist in settings");
}

$posthook_name = $subscription['posthook_name'];
$wp_name = $queries["wp-name"];

if ($posthook_name != $wp_name) {
    http_response_code(400);
    die("post hook name must be the same as in options " . $posthook_name . " " . $wp_name);
}

// Verifying intent
if ($_SERVER['REQUEST_METHOD'] == 'GET')
{
    if (!isset($queries["hub_mode"])) {
        http_response_code(400);
        die("mode must be set");
    }
    $mode = $queries["hub_mode"];

    if ($mode != 'subscribe' && $mode != 'unsubscribe') {
        http_response_code(400);
        die("mode must be subscribe or unsubscribe");
    }

    if (!isset($queries["hub_challenge"])) {
        http_response_code(400);
        die("challenge must be set");
    }
    $challenge = $queries["hub_challenge"];

    if (!isset($queries["hub_topic"])) {
        http_response_code(400);
        die("topic topic be set");
    }
    $topic = $queries["hub_topic"];

    mfn_update_challenge_by_plugin_url($subscriptions, $plugin_url, $challenge);

    http_response_code(200);
    echo $challenge;
    die();
}

// Begin receiving content
$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST' && $method !== 'PUT' && $method !== 'DELETE') {
    http_response_code(400);
    echo "bad method";
    die();
}

$signature = null;
if (isset($_SERVER['HTTP_X_HUB_SIGNATURE'])) {
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE'];
}

$hub_ext_ping = null;
if (isset($_SERVER['HTTP_X_HUB_EXT_PING'])) {
    $hub_ext_ping = $_SERVER['HTTP_X_HUB_EXT_PING'];
}
$is_ping = isset($hub_ext_ping) && (strtolower($hub_ext_ping) === 'true' || $hub_ext_ping === '1');

$content = file_get_contents("php://input");

if (!$is_ping) {
    do_action( 'mfn_before_posthook', $content);
}

$verify_signature = $ops['verify_signature'] ?? 'off';
$reset_cache = isset($ops['reset_cache']) && $ops['reset_cache'] == 'on';

if ($verify_signature == 'on') {
    $parts = explode('=', $signature);
    $alg = $parts[0];
    $hmac = $parts[1];
    $key = $subscription['posthook_secret'];

    $res = hash_hmac($alg, $content, $key);

    if ($hmac != $res) {
        error_log("[MFN Post hook]: hmac: " . $hmac . " that was provided but does not match expected value");
        http_response_code(400);
        echo "could not verify hmac as correct";
        die();
    }
}

$news_item = json_decode($content);

if ($is_ping) {
    list ($pingResponse, $pingSignatureHeader, $err) = mfn_verify_ping_item($subscription, $method, $news_item);
    if ($pingResponse) {
        if ($pingSignatureHeader) {
            header('X-Hub-Signature:' . $pingSignatureHeader);
        }
        echo $pingResponse;
        die();
    } else {
        http_response_code(400);
        echo "could not verify ping: " . $err;
        die();
    }
}

if (isset($news_item->properties) && isset($news_item->properties->type) && $news_item->properties->type === 'ping') {
    http_response_code(500);
    echo "ping item, but incorrect X-Hub-Ext-Ping header";
    die();
}

if ($method == 'DELETE') {
    if (!isset($news_item->news_id)) {
        http_response_code(400);
        echo "delete request without news_id";
        die();
    }
    mfn_unpublish_item($news_item->news_id);
} else {
    mfn_upsert_item_full($news_item, $signature, $content, $reset_cache);
    do_action( 'mfn_after_posthook', $news_item);
}