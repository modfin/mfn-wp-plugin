<?php // Silence is golden

require_once(dirname(__FILE__) . '/consts.php');
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

function mfn_generate_random_string($length = 32): string
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function mfn_sync()
{
    set_time_limit(300);
    $ops = get_option('mfn-wp-plugin');

    $queries = array();
    parse_str($_SERVER['QUERY_STRING'], $queries);

    $offset = $queries["offset"] ?? 0;
    $limit = $queries["limit"] ?? 48;

    $post_id = $queries["post_id"] ?? null;
    $news_id = null;
    $append_news_id = '';

    if ($post_id !== null) {
        $news_id = get_post_meta($post_id)[MFN_POST_TYPE . '_news_id'][0] ?? '';
    }
    if ($news_id !== null && $news_id !== '') {
        $append_news_id = '&news_id=' . $news_id;
        if (isset($post_id) && $post_id != '') {
            delete_post_meta($post_id, MFN_POST_TYPE . '_is_dirty');
        }
    }

    $entity_id = $ops['entity_id'] ?? "bad-entity-id";
    $sync_url = $ops['sync_url'] ?? "";

    $reset_cache = isset($ops['reset_cache']) && $ops['reset_cache'] == 'on';
    $cus_query = $ops['cus_query'] ?? "";

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

    $base_url = $sync_url . '/all/s.json?type=all&.author.entity_id=';
    $query_param_start = '&';
    if (strpos($sync_url, 'https://feed.mfn.') === 0) {
        $base_url = $sync_url . '/feed/';
        $query_param_start = '?';
    }

    $url = $base_url . $ops['entity_id'] . $query_param_start .
        'limit=' . $limit .
        "&offset=" . $offset .
        "&" . $cus_query .
        $append_news_id;

    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        die("sync-url-error:" . $response->get_error_message());
    }

    $json = wp_remote_retrieve_body($response);

    if (is_wp_error($json)) {
        die("sync-url-error:" . $json->get_error_message());
    }

    $obj = json_decode($json);

    if (is_wp_error($obj)) {
        die("sync-url-error:" . $obj->get_error_message());
    }

    $acc = 0;

    if (!isset($obj) || !isset($obj->version)) {
        die("sync-url-error:Couldn't sync, please check Sync URL.");
    }

    if (is_array($obj->items)) {
        foreach ($obj->items as $item) {
            $acc += mfn_upsert_item_full($item, '', '', $reset_cache);
        }
        echo sizeof($obj->items) . ' ' . $acc;
        return;
    }

    echo 0 . ' ' . $acc;
}

function mfn_ping_hub()
{
    $hub_url = mfn_fetch_hub_url();

    if (mfn_starts_with($hub_url, "http")) {

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

function mfn_clear_settings(): string
{
    update_option('mfn-wp-plugin', array());
    return "done";
}


function mfn_delete_attachments($post_id) {
    $existing_attachments = get_posts(array(
        'post_type' => 'attachment',
        'posts_per_page' => -1,
        'post_parent' => $post_id,
    ));

    $attachment_data = get_post_meta($post_id, MFN_POST_TYPE . "_attachment_data", false);

    $existing_meta_urls = array();
    foreach ($attachment_data as $d) {
        $a = json_decode($d);
        if (isset($a->url)) {
            $existing_meta_urls[] = $a->url;
        }
    }
    foreach ($existing_attachments as $a) {
        $u = get_post_meta($a->ID, MFN_POST_TYPE . "_attachment_url", true);
        if (in_array($u, $existing_meta_urls)) {
            wp_delete_attachment($a->ID, true);
        }
    }
}

function mfn_delete_all_tags(): array
{
	$i             = 0;
	$num_deleted   = 0;
	$taxonomy_name = MFN_TAXONOMY_NAME;

	$options      = get_option( "mfn-wp-plugin" );
	$wpml_enabled = defined( 'WPML_PLUGIN_BASENAME' ) && isset( $options['language_plugin'] ) && $options['language_plugin'] == 'wpml';
	$pll_enabled = isset($options['language_plugin']) && $options['language_plugin'] == 'pll';

	if ( $wpml_enabled ) {
		$terms = MFN_get_terms_wpml( '' );
	} else if ( $pll_enabled ) {
		$terms = get_terms( array(
			'taxonomy'   => MFN_TAXONOMY_NAME,
			'hide_empty' => false,
			'lang' => '',
		) );
	} else {
		$terms = get_terms( array(
			'taxonomy'   => MFN_TAXONOMY_NAME,
			'hide_empty' => false,
		) );
	}
	foreach ( $terms as $term ) {
		$deleted_term = mfn_delete_term( $wpml_enabled, $term->term_id, $taxonomy_name );
		if ( $deleted_term ) {
			$num_deleted ++;
		}
		$i ++;
	}

	return array( $i, $num_deleted );
}

function mfn_delete_term($wpml_enabled, $term_id, $taxonomy_name) {

	if (!$wpml_enabled) {
		return wp_delete_term($term_id, $taxonomy_name);
	}

	global $sitepress;
	/* remove wpml filters before fetching */
	$filter_terms_args = remove_filter('get_terms_args', array( $sitepress, 'get_terms_args_filter' ));
	$filter_get_term = remove_filter('get_term', array( $sitepress, 'get_term_adjust_id' ),1);
	$filter_terms_clauses = remove_filter('terms_clauses', array( $sitepress, 'terms_clauses' ));

	$delete_term = wp_delete_term($term_id, $taxonomy_name);

	/* re-add wpml filters after fetching */
	if ($filter_terms_args) {
		add_filter( 'get_terms_args', array( $sitepress, 'get_terms_args_filter' ), 10, 2 );
	}
	if ($filter_get_term) {
		add_filter( 'get_term', array( $sitepress, 'get_term_adjust_id' ), 1, 1 );
	}
	if ($filter_terms_clauses) {
		add_filter( 'terms_clauses', array( $sitepress, 'terms_clauses' ), 10, 3 );
	}

	return $delete_term;
}

function mfn_delete_all_posts(): array
{
    $queries = array();
    parse_str($_SERVER['QUERY_STRING'], $queries);
    $offset = $queries["offset"] ?? -1;
    $limit = $queries["limit"] ?? -1;
    $include_dirty = $queries["include-dirty"] ?? false;
    $delete_attachments = isset(get_option(MFN_PLUGIN_NAME)['thumbnail_allow_delete']);

    $i = 0;
    $stat = "ok";
    $num_deleted = 0;

    $all_posts = get_posts(
        array(
            'post_type' => MFN_POST_TYPE,
            'lang' => '',
            'numberposts' => $limit,
            'offset' => $offset,
            'post_status' => array('any', 'trash'),
        )
    );

    foreach ($all_posts as $each_post) {
        if ($each_post->post_type == MFN_POST_TYPE) {
            $i++;
            $is_dirty = mfn_post_is_dirty($each_post->ID);
            $is_trash = $each_post->post_status === 'trash';

            if ($is_dirty && $include_dirty == 'false') {
                continue;
            }

            if ($is_trash && $include_dirty == 'false') {
                continue;
            }

            if (get_post_meta($each_post->ID, MFN_POST_TYPE . "_group_id", true)) {
                if ($delete_attachments) {
                    mfn_delete_attachments($each_post->ID);
                }
                $delete_posts = wp_delete_post($each_post->ID, true);
                if ($delete_posts === null) {
                    $stat = "failed";
                }
                $num_deleted++;
            }
        }
    }

    return array($i, $num_deleted, $stat);
}

function mfn_verify_subscription()
{
    $sub = mfn_get_subscription_by_plugin_url(get_option("mfn-subscriptions"), mfn_plugin_url());
    if (!isset($sub['subscription_id'])) {
        return '';
    }
    return $sub['subscription_id'];
}

switch ($mode) {
    case "sync-tax":
        mfn_sync_taxonomy();
        die();

    case "sync":
        mfn_sync();
        die();

    case "ping";
        echo "pong";
        die();

    case "pinghub";
        mfn_ping_hub();
        die();

    case "subscribe":
        echo mfn_subscribe();
        die();

    case "unsubscribe":
        echo mfn_unsubscribe();
        die();

    case "clear-settings":
        echo mfn_clear_settings();
        die();

    case "delete-all-posts":
        $a = mfn_delete_all_posts();
        echo $a[0] . ';' . $a[1] . ';' . $a[2];
        die();

    case "delete-all-tags":
        $b = mfn_delete_all_tags();
        echo $b[0] . ';' . $b[1];
        die();

    case "fetch-posts-status":
        $a = mfn_fetch_posts_status();
        echo $a[0] . ';' . $a[1] . ';' . $a[2];
        die();

    case "verify-subscription":
        $a = mfn_verify_subscription();
        echo $a;
        die();

    default:
        echo "a mode must be provided [sync]";
        die();
}
