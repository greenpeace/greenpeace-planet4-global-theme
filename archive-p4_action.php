<?php

/**
 * The template for displaying P4 Action pages.
 *
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * @package  WordPress
 * @subpackage  Timber
 */

use Timber\Timber;
use P4\MasterTheme\ListingPage;

$templates = [ 'all-actions.twig', 'archive.twig', 'index.twig' ];

$context = Timber::context();

$context['custom_body_classes'] = 'tax-p4-page-type';
$context['header_title'] = post_type_archive_title('', false);

$page = new ListingPage($templates, $context);
