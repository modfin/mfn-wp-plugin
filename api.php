<?php

require_once(dirname(__FILE__) .'/consts.php');

class Report {
    public $post_id;
    public $group_id;
    public $title;
    public $url;
    public $lang;
    public $type;
    public $tags;
    public $year;
    public $timestamp;
    public $report_start_date;
    public $report_end_date;
}

function MFN_report_cmp($a, $b)
{

    $aScore = 0;
    $bScore = 0;

    if ($a->report_end_date && $b->report_end_date) {
        $aScore += $a->report_end_date->year * 100000;
        $bScore += $b->report_end_date->year * 100000;
        $aScore += $a->report_end_date->month * 1000;
        $bScore += $b->report_end_date->month * 1000;

        $aScore += ($a->type === 'mfn-report-annual' ? 100 : 0);
        $bScore += ($b->type === 'mfn-report-annual' ? 100 : 0);
    } else {
        $aScore += strtotime($a->timestamp);
        $bScore += strtotime($b->timestamp);
    }

    if($a->tags !== null && in_array(":primary", $a->tags, true)) {
        $aScore+=2;
    }

    if($b->tags !== null && in_array(":primary", $b->tags, true)) {
        $bScore+=2;
    }

    // sort 'mfn-report-interim-q2' before 'mfn-report-interim'
    $aTypeLen = strlen($a->type);
    $bTypeLen = strlen($b->type);
    if ($aTypeLen > $bTypeLen) $aScore++;
    if ($bTypeLen > $aTypeLen) $bScore++;

    return $aScore > $bScore;
}

class MFN_CustomDate
{
    public $year;
    public $month;

    function __construct($year, $month)
    {
        $this->year = $year;
        $this->month = $month;
    }
    function __toString()
    {
        return $this->year . "-" . $this->month;
    }
    function add_months($months): MFN_CustomDate
    {
	    $x = $this->month - 1 + $months;
	    $addToYear = (int) ($x / 12);
	    $month = $x % 12;
	    if ($month < 0) {
            $month += 12;
		    $addToYear -= 1;
	    }
	    return new MFN_CustomDate($this->year + $addToYear, $month + 1);
    }
    function before(MFN_CustomDate $d2): bool {
        if ($this->year > $d2->year) {
            return false;
        }
        if ($this->year === $d2->year && $this->month >= $d2->month) {
            return false;
        }
        return true;
    }
    function before_or_equal(MFN_CustomDate $d2): bool {
        if ($this->year > $d2->year) {
            return false;
	    }
        if ($this->year === $d2->year && $this->month > $d2->month) {
            return false;
	    }
        return true;
    }
}

function get_report_period($publish_date_string, $report_type, $fiscal_year_offset)
{
    // normalize fiscal year offset
    $fiscal_year_offset = ($fiscal_year_offset + 12) % 12;

    try {
        $publish_date = new DateTimeImmutable($publish_date_string);
    } catch (Exception $e) {
        return null;
    }

    $slug_prefix = (MFN_TAG_PREFIX !== '' && MFN_TAG_PREFIX !== null ? MFN_TAG_PREFIX . '-' : '');

    $report_period_months = null;
    switch ($report_type) {
        case $slug_prefix . "report-interim-q1":
            $report_period_months = 3;
            break;
	    case $slug_prefix . "report-interim-q2":
            $report_period_months = 6;
            break;
	    case $slug_prefix . "report-interim-q3":
            $report_period_months = 9;
            break;
	    case $slug_prefix . "report-interim-q4":
	    case $slug_prefix . "report-annual":
            $report_period_months = 12;
	}
	if (!$report_period_months) return null;

    $publish_date_year = intval($publish_date->format('Y'));
    $publish_date_month = intval($publish_date->format('m'));

    if ($publish_date_year === 0 || $publish_date_month === 0) return null;

    $date = new MFN_CustomDate($publish_date_year, $publish_date_month);

    for ($year = $date->year - 1; $year <= $date->year; $year++) {
        $candidate_report_start_date = (new MFN_CustomDate($year, 1))
            ->add_months($fiscal_year_offset);
        $candidate_report_end_date = $candidate_report_start_date
            ->add_months($report_period_months-1);

        if ($candidate_report_end_date->before($date) && $date->before_or_equal($candidate_report_end_date->add_months(7))) {

            return array(
                'report_start_date' => $candidate_report_start_date,
                'report_end_date' => $candidate_report_end_date,
            );
        }
    }

    return null;
}

function MFN_get_reports($lang = 'all', $from_year, $to_year, $offset = 0, $limit = 100, $order = 'DESC', $fiscal_year_offset = null)
{
    global $wpdb;

    $params = array();

    $slug_prefix = (MFN_TAG_PREFIX !== '' && MFN_TAG_PREFIX !== null ? MFN_TAG_PREFIX . '-' : '');
    $annual_slug = $slug_prefix . 'report-annual';
    $interim_slug = $slug_prefix . 'report-interim';

    $query = "
        SELECT posts.ID post_id,
               posts.post_date_gmt date_gmt,
               posts.post_title title,
               lang.meta_value lang,
               group_id.meta_value group_id,
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
                ON posts.ID = lang.post_id AND lang.meta_key = '" . MFN_POST_TYPE . "_lang'
            INNER JOIN $wpdb->postmeta group_id
                ON posts.ID = group_id.post_id AND group_id.meta_key = '" . MFN_POST_TYPE . "_group_id'
            LEFT JOIN $wpdb->postmeta meta
                ON posts.ID = meta.post_id AND meta.meta_key = '" . MFN_POST_TYPE . "_attachment_data'
            WHERE (
                t.slug = '" . $annual_slug . "'
                OR t.slug = '" . $interim_slug . "'
                OR t.slug like '" . $annual_slug . "_%'
                OR t.slug like '" . $interim_slug . "_%'
            )
            AND tax.taxonomy = '" . MFN_TAXONOMY_NAME . "'
    ";

    if($lang !== "all") {
        $query .= " AND lang.meta_value = %s ";
        $params[] = $lang;
    }

    $q = $wpdb->prepare($query, $params);
    $res = $wpdb->get_results($q);

    $reports = array();
    foreach ($res as $r) {
        $rr = new Report();
        $rr->post_id = $r->post_id;
        $rr->group_id = $r->group_id;
        $rr->timestamp = $r->date_gmt;
        $rr->title = $r->title;
        $rr->tags = json_decode($r->attachment_meta_value, true)["tags"];
        $rr->url = json_decode($r->attachment_meta_value, true)["url"];
        $rr->lang = $r->lang;
        $rr->type = $r->report_type;
        $rr->year = substr($r->date_gmt, 0, 4);

        if ($fiscal_year_offset !== null) {
            $period = get_report_period($rr->timestamp, $rr->type, (int)$fiscal_year_offset);
            $rr->report_start_date = $period['report_start_date'];
            $rr->report_end_date = $period['report_end_date'];
        }

        $reports[] = $rr;
    }

    usort($reports, "MFN_report_cmp");

    if (strtoupper($order) !== "ASC") {
        $reports = array_reverse($reports);
    }

    $exists = array();
    $unique_reports = array();

    foreach ($reports as $r){
        if(strlen($r->url) < 5){
            continue;
        }

        if(empty($exists[$r->post_id])){
            // try to group reports by fiscal year, instead of publish_date

            if ($r->report_start_date) {
                $year = $r->report_start_date->year;
                if ($year !== null) {
                    $r->year = $year . ((int)$fiscal_year_offset !== 0 ? ("/" . ($year + 1)) : "");
                } else {
                    // the '* is a warning, it means either:
                    // - there is a bug in get_report_fiscal_year()
                    // - $fiscalYearOffset is incorrect for this company
                    // - some reports are not tagged correctly
                    $r->year .= " *";
                }
            }
            $unique_reports[] = $r;
        }

        $exists[$r->post_id] = 1;
    }

    // filter using year-filter
    $filtered_reports = array();
    foreach ($unique_reports as $r){
        $y = ($r->report_start_date && $r->report_start_date->year)
            ? $r->report_start_date->year
            : substr($r->timestamp, 0, 4);

        if ($y && $from_year && (int)$y < (int)$from_year) {
            continue;
        }
        if ($y && $to_year && (int)$y > (int)$to_year) {
            continue;
        }

        $filtered_reports[] = $r;
    }

    return array_slice($filtered_reports, $offset, $limit);
}

function MFN_get_feed_min_max_years($lang = 'all'){
    global $wpdb;
    $params = array();
    $query = "
    SELECT max(YEAR(post_date_gmt)) max_year, min(YEAR(post_date_gmt)) min_year, lang.meta_value lang
    FROM $wpdb->posts p
    INNER JOIN $wpdb->postmeta lang
    ON p.ID = lang.post_id
    WHERE post_type = '" . MFN_POST_TYPE . "'
      AND post_date_gmt IS NOT NULL
      AND post_date_gmt <> 0
    ";

    if($lang !== "all") {
        $query .= " AND lang.meta_value = %s ";
        $params[] = $lang;
    }

    $q = $wpdb->prepare($query, $params);
    $res = $wpdb->get_results($q);

    if (sizeof($res) > 0) {
        return $res[0];
    }
    return $res;
}


function MFN_get_feed($lang = 'all', $year = "", $hasTags = array(), $hasNotTags = array(), $offset = 0, $limit = 30, $include_content)
{

    global $wpdb;
    $params = array();

    $query = "
SELECT post_date_gmt, p.post_title, tags, lang.meta_value lang, post_name" . ($include_content ? ', post_content' : '' ) . "
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
  WHERE po.post_type = '" . MFN_POST_TYPE . "'
    AND po.post_status = 'publish'
    GROUP BY po.ID
) t  ON t.ID = p.ID

  WHERE p.post_type = '" . MFN_POST_TYPE . "'
    AND lang.meta_key = '" . MFN_POST_TYPE . "_lang'
    AND p.post_status = 'publish'
 ";

    if($lang != "all") {
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