<?php
require_once('./config.php');
require_once('./lib.php');
global $wp_query;

mfn_register_types();

$queries = array();
parse_str($_SERVER['QUERY_STRING'], $queries);

if (!isset($queries["news-id"]) && !isset($queries["slug"])) {
    http_response_code(400);
    die("Error: slug or news-id required for request");
}

$q = array(
    'meta_query' => array(
        'relation' => 'OR',
        'news_id_clause' => array(
            'key' => MFN_POST_TYPE . '_news_id',
            'value' => $queries["news-id"] ?? '',
        ),
        'news_slug' => array(
            'key' => MFN_POST_TYPE . '_news_slug',
            'value' => $queries["slug"] ?? '',
        ),
    ),
    'lang' => '',
    'post_type' => MFN_POST_TYPE
);

$query = new WP_Query($q);

$res = $query->get_posts();
$post_id = $res[0]->ID ?? '';

if (($post_id === '') || !get_permalink($post_id)) {
    $wp_query->set_404();
    status_header(404);
    get_template_part(404);
    exit();
}

wp_redirect(get_permalink($post_id), 301);