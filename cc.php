<?php // Silence is golden

require_once(  dirname(__FILE__) . '/config.php');

require_once(  dirname(__FILE__) . '/lib.php' );



$is_admin = current_user_can( 'manage_options');

if (!$is_admin){
    echo "you are not admin";
    die();
}



$queries = array();
parse_str($_SERVER['QUERY_STRING'], $queries);

if (!isset($queries["mode"])){
    echo "a mode must be provided [poll, longpoll or sync]";
    die();
}
$mode = $queries["mode"];



function generateRandomString($length = 32) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}



function sync(){
    $ops = get_option('mfn-wp-plugin');
    $queries = array();
    parse_str($_SERVER['QUERY_STRING'], $queries);

    $offset = isset($queries["offset"]) ? $queries["offset"] : 0;
    $limit = isset($queries["limit"]) ? $queries["limit"] : 48;

    $entity_id = isset($ops['entity_id']) ? $ops['entity_id'] : "bad-entity-id";
    $sync_url =  isset($ops['sync_url']) ? $ops['sync_url'] : "";
    if ($entity_id == ""){
        echo -1;
        return;
    }

    if ($sync_url == ""){
        echo $sync_url;
        echo print_r($ops);
        echo -2;
        return;
    }


    $json = file_get_contents($sync_url . '/all/s.json?.author.entity_id=' . $ops['entity_id'] . '&limit='. $limit . "&offset=" . $offset);
    $obj = json_decode($json);

    $acc = 0;

    if (is_array($obj->items)){
        foreach($obj->items as $i => $item) {
            $acc += upsertItem($item);
        }
        echo  sizeof($obj->items) . ' ' . $acc;
        return;
    }

    echo  0 . ' ' . $acc;
}



function pingHub(){

    $ops = get_option('mfn-wp-plugin');
    $hub_url =  isset($ops['hub_url']) ? $ops['hub_url'] : "";

    if(startsWith($hub_url, "http")){
        $content = file_get_contents($hub_url);

        if(strstr($content, 'https://www.w3.org/TR/websub')){
            echo "ponghub";
            return;
        }

        echo "fail, enpoint does not contain websub info";
        return;
    }
    echo "fail, not a valid url";
    return;
}



switch ($mode){
    case "sync":
        sync();
        break;
    case "ping";
        echo "pong";
        die();
    case "pinghub";
        pingHub();
        die();

    case "subscribe":
        subscribe();
        die();

    case "unsubscribe":
        echo unsubscribe();
        die();
    default:
        echo "a mode must be provided [sync]";
        die();
}





