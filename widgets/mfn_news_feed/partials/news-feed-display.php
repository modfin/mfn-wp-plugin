<?php // news feed display
    require_once('../../../../../../wp-load.php');
    require_once('../class-mfn-news-feed.php');

    $news_feed_instance = new News_feed();

    // query params
    $year = $news_feed_instance->queryParam('m-year', "");
    $tags = $news_feed_instance->handleTags();

    // requests the news feed
    $items = MFN_get_feed(
        'sv',
        $year,
        $tags['hasTags'],
        $tags['hasNotTags'],
        0,
        20,
        false
    );

    print_r($items);

//    function prepare_param($params, $type, $default) {
//        $value = $params['payload'][$type];
//        return isset($value) ? $value : $default;
//    }
/*
    // parameters for api request
    $api_params = new stdClass();
    $api_params->pmlang = prepare_param($payload, 'pmlang', 'all');
    $api_params->year = prepare_param($payload, 'year', '');

    $api_params->hasTags = array();
    array_push($api_params->hasTags, $payload['payload']['hasTags']);

    $api_params->hasNotTags = array();
    array_push($api_params->hasNotTags, $payload['payload']['hasNotTags']);

    $api_params->offset = prepare_param($payload, 'offset', 0);
    $api_params->pagelen = prepare_param($payload, 'pagelen', 0);
    $api_params->showpreview = prepare_param($payload, 'showpreview', 0);

    // parameters for list news items class
    $news_list_params = new stdClass();
    $news_list_params->onlytagsallowed = array();
    $news_list_params->skipcustomtags = prepare_param($payload, 'skipcustomtags',0);
    $news_list_params->showpagination = prepare_param($payload, 'showpagination',0);
    $news_list_params->groupbyear = prepare_param($payload, 'groupbyyear',0);
    $news_list_params->tzLocation = prepare_param($payload, 'tzLocation','Europe/Stockholm');
    $news_list_params->timestampFormat = prepare_param($payload, 'timestampFormat','Y-m-d H:i');
    $news_list_params->pagelen =  prepare_param($payload, 'pagelen',20);
    $news_list_params->previewlen = prepare_param($payload, 'previewlen',200);
    $news_list_params->template = base64_decode(prepare_param($payload, 'template', ''));
    $news_list_params->tagtemplate =  base64_decode(prepare_param($payload, 'tagtemplate',''));

    // requests the news feed
    $items = MFN_get_feed(
        $api_params->pmlang,
        $api_params->year,
        $api_params->hasTags,
        $api_params->hasNotTags,
        $api_params->offset,
        $news_list_params->pagelen,
        $api_params->showpreview
    );

    // parses the newsfeed html
    $news_feed = new News_feed();

    $response = new stdClass();
    $response->instance_id = 1234;
    $response->count = sizeof($items);
    $response->html = $news_feed->list_news_items(
       $items,
       $news_list_params->tzLocation,
       $news_list_params->timestampFormat,
       $news_list_params->onlytagsallowed,
       $news_list_params->tagtemplate,
       $news_list_params->template,
       $news_list_params->groupbyear,
       $news_list_params->skipcustomtags,
       $api_params->showpreview,
       $news_list_params->previewlen
    );

    echo json_encode($response);
*/