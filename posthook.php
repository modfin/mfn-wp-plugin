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

if ( !isset($queries["wp-name"]) ){
    echo "wp-name must be provided";
    http_response_code(400);
    die();
}



if ( !isset($ops['posthook_name']) ){
    echo "posthook name must exist in settings";
    http_response_code(400);
    die();
}

$posthook_name = $ops['posthook_name'];
$wp_name = $queries["wp-name"];

if ( $posthook_name != $wp_name){
    echo "post hook name must be the same as in options";
    http_response_code(400);
    die();
}


// Verifying intent
if ($_SERVER['REQUEST_METHOD'] == 'GET')
{
    if (!isset($queries["hub_mode"])){
        echo "mode must be set";
        http_response_code(400);
        die();
    }
    $mode = $queries["hub_mode"];

    if ($mode != 'subscribe' && $mode != 'unsubscribe') {
        echo "mode must be subscribe or unsubscribe";
        http_response_code(400);
        die();
    }

    if (!isset($queries["hub_challenge"])){
        echo "challenge must be set";
        http_response_code(400);
        die();
    }
    $challenge = $queries["hub_challenge"];

    if (!isset($queries["hub_topic"])){
        echo "topic topic be set";
        http_response_code(400);
        die();
    }
    $topic = $queries["hub_topic"];

    $ops['subscription_id'] = $challenge;

    update_option(MFN_PLUGIN_NAME, $ops);
    http_response_code(200);
    echo $challenge;
    die();
}

$hub_url = isset($ops['hub_url']) ? $ops['hub_url'] : "";

if (strpos($hub_url, 'https://feed.mfn.') === 0) {
    $method = $_SERVER['REQUEST_METHOD'];
    if ($method !== 'POST' && $method !== 'PUT' && $method !== 'DELETE') {
        http_response_code(400);
        echo "bad method";
        die();
    }
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE'];
    $content = file_get_contents("php://input");

    $verify_signature = isset($ops['verify_signature']) ? $ops['verify_signature'] : 'off';
    $reset_cache = isset($ops['reset_cache']) ? ($ops['reset_cache'] == 'on') : false;

    if ($verify_signature == 'on') {
        $parts = explode('=', $signature);
        $alg = $parts[0];
        $hmac = $parts[1];
        $key = $ops['posthook_secret'];

        $res = hash_hmac($alg, $content, $key);
        if ($hmac != $res){
            error_log("[MFN Post hook]: hmac: " . $hmac . " that was provided but does not match expected value");
            http_response_code(400);
            echo "could not verify hmac as correct";
            die();
        }
    }
    $news_item = json_decode($content);

//    // TODO Add ping
//    if ($queries["ping"])

    if ($method == 'DELETE') {
        if (!isset($news_item->news_id)) {
            http_response_code(400);
            echo "delete request without news_id";
            die();
        }
        unpublishItem($news_item->news_id);
    } else {
        upsertItemFull($news_item, $signature, $content, $reset_cache);
        do_action( 'mfn_after_posthook', $news_item);
    }

    die();
}




// News item being posted (old code path)
if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE'];
    $content = file_get_contents("php://input");

    do_action( 'mfn_before_posthook', $content);

    $verify_signature =  isset($ops['verify_signature']) ? $ops['verify_signature'] : 'off';
    $reset_cache =  isset($ops['reset_cache']) ? ($ops['reset_cache'] == 'on') : false;

    if($verify_signature == 'on'){
        $parts = explode('=', $signature);
        $alg = $parts[0];
        $hmac = $parts[1];
        $key = $ops['posthook_secret'];

        $res = hash_hmac($alg, $content, $key);
        if($hmac != $res){
            error_log("[MFN Post hook]: hmac: " . $hmac . " that was provided but does not match expected value");
            http_response_code(400);
            echo "could not verify hmac as correct";
            die();
        }
    }

    $news_item = json_decode($content);
    upsertItem($news_item, $signature, $content, $reset_cache);
    do_action( 'mfn_after_posthook', $news_item);

}
