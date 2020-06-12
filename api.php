<?php

require_once(dirname(__FILE__) .'/consts.php');

class Report {
    public $timestamp;
    public $title;
    public $url;
    public $lang;
    public $type;
    public $tags;
    public $post_id;
}

function cmp($a, $b)
{
    $aScore = strtotime($a->timestamp);
    $bScore = strtotime($b->timestamp);

    if($a->tags !== null && in_array(":primary", $a->tags, true)) {
        $aScore++;
    }

    if($b->tags !== null && in_array(":primary", $b->tags, true)) {
        $bScore++;
    }

    return $aScore < $bScore;
}

function MFN_get_reports($lang = 'all', $offset = 0, $limit = 100, $order = 'DESC')
{
    global $wpdb;

    $params = array();

    $query = "
        SELECT posts.ID post_id,
               posts.post_date_gmt date_gmt,
               posts.post_title title,
               lang.meta_value lang,
               t.slug report_type,
               meta.meta_value attachment_meta_value
        FROM $wpdb->terms t
            INNER JOIN $wpdb->term_taxonomy tax
                ON t.term_id = tax.term_id
            INNER JOIN $wpdb->term_relationships r
                ON r.term_taxonomy_id = tax.term_taxonomy_id
            INNER JOIN $wpdb->posts posts
                ON posts.ID = r.object_id
            INNER JOIN $wpdb->postmeta lang
                ON posts.ID = lang.post_id AND lang.meta_key = 'mfn_news_lang'
            LEFT JOIN $wpdb->postmeta meta
                ON posts.ID = meta.post_id AND meta.meta_key = 'mfn_news_attachment_data'
            WHERE (
                t.slug = 'mfn-report-annual'
                OR t.slug = 'mfn-report-interim'
                OR t.slug like 'mfn-report-annual_%'
                OR t.slug like 'mfn-report-interim_%'
            )
            AND tax.taxonomy = 'mfn-news-tag'
    ";

    if($lang !== "all") {
        $query .= " AND lang.meta_value = %s ";
        $params[] = $lang;
    }

    if($order !== "DESC") {
        $order = "ASC";
    }

    $q = $wpdb->prepare($query, $params);
    $res = $wpdb->get_results($q);

    $reports = array();
    foreach ($res as $r) {
        $rr = new Report();
        $rr->post_id = $r->post_id;
        $rr->timestamp = $r->date_gmt;
        $rr->title = $r->title;
        $rr->tags = json_decode($r->attachment_meta_value, true)["tags"];
        $rr->url = json_decode($r->attachment_meta_value, true)["url"];
        $rr->lang = $r->lang;
        $rr->type = $r->report_type;
        $reports[] = $rr;
    }

    usort($reports, "cmp");

    if ($order !== "DESC" ) {
        $reports = array_reverse($reports);
    }

    $exists = array();
    $filtered_reports = array();

    foreach ($reports as $r){
        if(strlen($r->url) < 5){
            continue;
        }

        if(empty($exists[$r->post_id])){
            $filtered_reports[] = $r;
        }

        $exists[$r->post_id] = 1;
    }

    return array_slice($filtered_reports, $offset, $limit);
}

function MFN_get_feed_min_max_years(){
    global $wpdb;
    $params = array();
    $query = "
    SELECT max(YEAR(post_date_gmt)) max_year, min(YEAR(post_date_gmt)) min_year 
    FROM $wpdb->posts
    WHERE post_type = 'mfn_news'
      AND post_date_gmt IS NOT NULL
      AND post_date_gmt <> 0
    ";
    $res = $wpdb->get_results($query);
    if (sizeof($res) > 0) {
        return $res[0];
    }
    return $res;
}


function MFN_get_feed($lang = 'all', $year = "", $hasTags = array(), $hasNotTags = array(), $offset = 0, $limit = 30)
{

    global $wpdb;
    $params = array();

    $query = "
SELECT post_date_gmt, p.post_title, tags, lang.meta_value lang, post_name
FROM $wpdb->posts p
INNER JOIN $wpdb->postmeta lang
ON p.ID = lang.post_id
INNER JOIN (
  SELECT po.ID, group_concat(CONCAT(ter.name, ':', ter.slug)) AS tags, group_concat(ter.slug) AS tag_slugs
  FROM $wpdb->posts po
         INNER JOIN $wpdb->term_relationships r
                    ON r.object_id = po.ID
         INNER JOIN $wpdb->term_taxonomy tax
                    USING (term_taxonomy_id)
         INNER JOIN $wpdb->terms ter
                    USING (term_id)
  WHERE po.post_type = 'mfn_news'
    AND po.post_status = 'publish'
  GROUP BY po.ID
) t  ON t.ID = p.ID

WHERE p.post_type = 'mfn_news'
  AND lang.meta_key = 'mfn_news_lang'
  AND p.post_status = 'publish'
 ";


    if($lang != "all"){
        $query .= " AND lang.meta_value = %s ";
        array_push($params, $lang);
    }

    foreach ($hasTags as $tag) {
        $query .= " AND FIND_IN_SET(%s, t.tag_slugs) > 0 ";
        array_push($params, $tag);
    }
    foreach ($hasNotTags as $tag) {
        $query .= " AND FIND_IN_SET(%s, t.tag_slugs) = 0 ";
        array_push($params, $tag);
    }
    foreach ($hasNotTags as $tag) {
        $query .= " AND FIND_IN_SET(%s, t.tag_slugs) = 0 ";
        array_push($params, $tag);
    }
    if($year != ""){
        $query .= " AND YEAR(p.post_date_gmt) = %s ";
        array_push($params, $year);
    }



    $query .= " ORDER BY p.post_date_gmt DESC ";

    $query .= " LIMIT %d ";
    array_push($params, $limit);
    $query .= " OFFSET %d ";
    array_push($params, $offset);

    $q = $wpdb->prepare($query, $params);
    $res = $wpdb->get_results($q);

    foreach ($res as $r){
        $r->tags = explode(",", $r->tags);
    }

    return $res;
}