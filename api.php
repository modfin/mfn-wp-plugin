<?php

require_once(dirname(__FILE__) .'/consts.php');

class Report {
    public $timestamp;
    public $title;
    public $url;
    public $lang;
    public $type;
}


function MFN_get_reports($lang = 'all', $offset = 0, $limit = 100, $order = 'DESC')
{
    global $wpdb;


    $params = array();

    $query = "
SELECT posts.post_date_gmt date_gmt,
       posts.post_title title,
       lang.meta_value lang,
       t.slug report_type,
       (
         SELECT meta.meta_value ->> '$.url'
         FROM  $wpdb->postmeta meta
         WHERE posts.ID = meta.post_id
         AND meta.meta_key = 'mfn_news_attachment_data'
         ORDER BY JSON_CONTAINS(meta.meta_value, '\":primary\"', '$.tags') DESC
         LIMIT 1
       ) url
FROM $wpdb->terms t
       INNER JOIN $wpdb->term_taxonomy tax
                  ON t.term_id = tax.term_id
       INNER JOIN $wpdb->term_relationships r
                  ON r.term_taxonomy_id = tax.term_taxonomy_id
       INNER JOIN $wpdb->posts posts
                  ON posts.ID = r.object_id
      INNER JOIN $wpdb->postmeta lang
        ON posts.ID = lang.post_id AND lang.meta_key = 'mfn_news_lang'
WHERE (t.slug = 'mfn-report-annual' OR t.slug = 'mfn-report-interim')
  AND tax.taxonomy = 'mfn-news-tag'
";


    if($lang != "all"){
        $query .= " AND lang.meta_value = %s ";
        array_push($params, $lang);

    }

    if($order != "DESC"){
        $order = "ASC";
    }
    $query .= " ORDER BY post_date_gmt " . $order . " ";

    $query .= " LIMIT %d ";
    array_push($params, $limit);
    $query .= " OFFSET %d ";
    array_push($params, $offset);

    $q = $wpdb->prepare($query, $params);

    $res = $wpdb->get_results($q);

    $reports = array();
    foreach ($res as $r){
        if(strlen($r->url) < 5){
            continue;
        }

        $rr = new Report();
        $rr->timestamp = $r->date_gmt;
        $rr->title = $r->title;
        $rr->url = $r->url;
        $rr->lang = $r->lang;
        $rr->type = $r->report_type;
        array_push($reports, $rr);
    }

    return $reports;

}