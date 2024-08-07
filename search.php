<?php

/**
 * Search results page
 *
 * Methods for \Timber\Helper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since   Timber 0.1
 */

use P4\MasterTheme\Search\SearchPage;

/**
 * Planet4 - Search functionality.
 */

// Limit access to GET method.
if ('GET' !== filter_input(INPUT_SERVER, 'REQUEST_METHOD')) {
    return;
}

global $wp_query;
$page = new SearchPage($wp_query);
$page->render();
