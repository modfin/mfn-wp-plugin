<?php

const MFN_PLUGIN_NAME = 'mfn-wp-plugin';
const MFN_PLUGIN_NAME_VERSION = '0.0.5';
const MFN_TAXONOMY_NAME = 'mfn-news-tag';
const MFN_TAG_PREFIX = 'mfn';
const MFN_POST_TYPE = 'mfn_news';


// If ABSPATH not defined, php app is initiated from plugin folder.
// Lets try to find and run wp-config.php
if ( !defined('ABSPATH') ) {
    $dir = __DIR__;
    for($i = 0; $i < 3; $i++){
        if(file_exists($dir . "/wp-config.php")){
            break;
        }
        $dir = dirname($dir);
    }
    if(!file_exists($dir . "/wp-config.php")){
        echo "could not find wp-config.php";
        die();
    }
    require_once ($dir.  '/wp-config.php');
}



require_once (ABSPATH . 'wp-settings.php');
require_once (ABSPATH . 'wp-load.php');
require_once (ABSPATH . 'wp-admin/includes/taxonomy.php' );
require_once (ABSPATH . 'wp-includes/taxonomy.php');



function register_mfn_types(){

    register_post_type('mfn_news',
        array(
            'labels'      => array(
                'name'          => __('MFN News Items'),
                'singular_name' => __('MFN News Item'),
            ),
            'public'      => true,
            'has_archive' => true,
        )
    );


    $labels = array(
        'name' => _x('News Tags', 'MFN News tags'),
        'singular_name' => _x('News Tag', 'MFN News tag'),
        'search_items' => __('Search News Tags'),
        'all_items' => __('All News Tags'),
        'parent_item' => __('Parent News Tag'),
        'parent_item_colon' => __('Parent News Tag:'),
        'edit_item' => __('Edit News Tag'),
        'update_item' => __('Update News Tag'),
        'add_new_item' => __('Add New News Tag'),
        'new_item_name' => __('New News Tag Name'),
        'menu_name' => __('News Tags'),
    );

    register_taxonomy(MFN_TAXONOMY_NAME, array(MFN_POST_TYPE), array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => array('slug' => MFN_TAXONOMY_NAME),
    ));

}