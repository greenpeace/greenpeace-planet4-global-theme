<?php

/**
 * The template for displaying Taxonomy pages (Post types, Action types)
 *
 * @package  WordPress
 * @subpackage  Timber
 */

use P4\MasterTheme\ListingPage;
use Timber\Timber;

$templates = [ 'taxonomy.twig', 'index.twig' ];

$context = Timber::context();
$taxonomy = get_queried_object();

$context['taxonomy'] = $taxonomy;
$context['wp_title'] = $taxonomy->name;
$context['og_description'] = $taxonomy->description;

$page = new ListingPage($templates, $context);
