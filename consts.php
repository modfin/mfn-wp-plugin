<?php

/*
 * !! WARNING !!
 * !! These constants can only be changed initially before the plugin is used !!
 *    Reason being that when you use functions like Sync press releases, taxonomy or when press releases
 *    are received through the post hook, data is written into the database using the following constants
 */
const MFN_PLUGIN_NAME_VERSION = '0.0.79';
const MFN_PLUGIN_NAME = 'mfn-wp-plugin';
const MFN_TAXONOMY_NAME = 'mfn-news-tag';
const MFN_TAG_PREFIX = 'mfn';
const MFN_POST_TYPE = 'mfn_news';
const MFN_ARCHIVE_NAME = 'MFN News Items';
const MFN_SINGULAR_NAME = 'MFN News Item';
const DATABLOCKS_LOADER_URL = 'https://widget.datablocks.se/api/rose';
const DATABLOOCKS_LOADER_VERSION = 'v4';
