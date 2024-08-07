<?php

namespace P4\MasterTheme\Search;

use P4\MasterTheme\ActionPage;
use P4\MasterTheme\PostArchive;
use Timber\Timber;
use Timber\Loader;
use WP_Post;
use WP_Query;

class SearchPage
{
    public const POSTS_PER_LOAD = Search::POSTS_PER_LOAD;
    public const SHOW_SCROLL_TIMES = 2;
    public const DEFAULT_SORT = Search::DEFAULT_SORT;
    public const DEFAULT_CACHE_TTL = 600;

    public const PAGE_TEMPLATES = ['search.twig', 'archive.twig', 'index.twig'];
    public const RESULT_TEMPLATES = ['tease-search.twig'];
    public const DUMMY_THUMBNAIL = '/images/dummy-thumbnail.png';

    public WP_Query $query;
    public array $posts = [];
    public array $context = [];
    public array $filters = [];

    public static array $aggregations = [];
    public static ?int $query_time;

    public function __construct(WP_Query $query)
    {
        $this->query = $query;
        $this->posts = $query->posts;
        $this->context = Timber::context();

        $this->populate_context($query);
    }

    /**
     * Populate Timber context with common values
     */
    public function populate_context(WP_Query $query): void
    {
        if (is_user_logged_in()) {
            $this->context['query_time'] = static::$query_time ?? 0;
        }

        $this->context['posts'] = $this->get_populated_posts($query->posts);
        $this->context['paged_posts'] = $this->context['posts'];

        $paged = (int) $query->query_vars['paged'] ?? 1;
        $this->context['current_page'] = $paged >= 1 ? $paged : 1;

        $this->context['found_posts'] = $query->found_posts;
        $this->context['search_query'] = $query->query_vars['s'];
        $this->context['default_sort'] = self::DEFAULT_SORT;
        $this->context['selected_sort'] = $query->query['orderby'] ?? self::DEFAULT_SORT;
        $this->context['page_category'] = 'Search Page';
        $this->context['sort_options'] = [
            '_score' => [
                'name' => __('Most relevant', 'planet4-master-theme'),
                'order' => 'DESC',
            ],
            'post_date' => [
                'name' => __('Newest', 'planet4-master-theme'),
                'order' => 'DESC',
            ],
            'post_date_asc' => [
                'name' => __('Oldest', 'planet4-master-theme'),
                'order' => 'ASC',
            ],
        ];

        $this->context['load_more'] = [
            'posts_per_load' => self::POSTS_PER_LOAD,
            // Translators: %s = number of results per page.
            'button_text' => sprintf(__('Show %s more results', 'planet4-master-theme'), self::POSTS_PER_LOAD),
        ];
        $this->context['has_aggregation'] = ElasticSearch::is_active() && ElasticSearch::facets_is_active();
    }

    /**
     * Add information to posts for display
     */
    public function get_populated_posts(array $posts): array
    {
        foreach ($posts as &$post) {
            $thumbnail = get_the_post_thumbnail_url($post->ID, 'thumbnail');
            $post->thumbnail_alt = get_the_post_thumbnail_caption($post->ID);
            $post->thumbnail = $thumbnail;

            switch ($post->post_type) {
                case PostArchive::POST_TYPE:
                    $this->populate_archive_post($post);
                    break;
                case ActionPage::POST_TYPE:
                    $this->populate_action_post($post);
                    break;
                default:
                    $this->populate_post($post);
                    break;
            }
        }
        return $posts;
    }

    private function populate_post(WP_Post &$post): void
    {
        $post->link = $post->permalink;
        $post->preview = $post->excerpt;
        $thumbnail = get_the_post_thumbnail_url($post->ID, 'thumbnail');
        $post->thumbnail = $thumbnail;
        $post->thumbnail_alt = get_the_post_thumbnail_caption($post->ID);

        // @todo Ensure the term link is synced to ElasticSearch so we don't have to fetch it here.
        $post->tags = self::filter_existing_terms($post->terms['post_tag'] ?? []);
        $post->p4_page_types = self::filter_existing_terms($post->terms['p4-page-type'] ?? []);
    }

    private function populate_archive_post(WP_Post &$post): void
    {
        if (current_user_can('edit_posts')) {
            $post->edit_link = get_edit_post_link($post->ID);
        }
        $post->link = $post->guid;
        $post->post_date = $post->post_date_gmt;
    }

    private function populate_action_post(WP_Post &$post): void
    {
        $this->populate_post($post);

        $post_meta = get_post_meta($post->ID);
        if (isset($post_meta['action_button_text']) && $post_meta['action_button_text'][0]) {
            $post->button_text = $post_meta['action_button_text'][0];
        } else {
            $options = get_option('planet4_options');
            $post->button_text = $options['take_action_covers_button_text']
                ?? __('Take action', 'planet4-master-theme');
        }
    }

    /**
     * Set filters in context
     */
    protected function set_context_filters(): void
    {
        $new_ia = (bool) planet4_get_option('new_ia');
        $this->context[Filters\ActionTypes::CONTEXT_ID] = Filters\ActionTypes::get_filters($new_ia);
        $this->context[Filters\Categories::CONTEXT_ID] = Filters\Categories::get_filters();
        $this->context[Filters\ContentTypes::CONTEXT_ID] = Filters\ContentTypes::get_filters(
            Search::should_include_archive(),
            $new_ia
        );
        $this->context[Filters\PostTypes::CONTEXT_ID] = Filters\PostTypes::get_filters();
        $this->context[Filters\Tags::CONTEXT_ID] = Filters\Tags::get_filters();

        uasort(
            $this->context[Filters\Categories::CONTEXT_ID],
            fn($a, $b) => strcmp($a['name'], $b['name'])
        );
        uasort(
            $this->context[Filters\Tags::CONTEXT_ID],
            fn($a, $b) => strcmp($a['name'], $b['name'])
        );

        if (empty($this->query->query_vars['f'])) {
            return;
        }

        // Keep track of which filters are already checked
        foreach ($this->query->query_vars['f'] as $type => $values) {
            $context_name = match ($type) {
                Filters\Categories::QUERY_ID => Filters\Categories::CONTEXT_ID,
                Filters\PostTypes::QUERY_ID => Filters\PostTypes::CONTEXT_ID,
                Filters\ActionTypes::QUERY_ID => Filters\ActionTypes::CONTEXT_ID,
                Filters\ContentTypes::QUERY_ID => Filters\ContentTypes::CONTEXT_ID,
                Filters\Tags::QUERY_ID => Filters\Tags::CONTEXT_ID,
                default => $type,
            };
            $filter_values = array_values($values);
            foreach ($filter_values as $id) {
                $this->context[$context_name][$id]['checked'] = 'checked';
            }
        }

        // Active filters
        $this->context['filters'] = array_merge(...array_map(function ($type, $values) {
            $entries = [];
            foreach ($values as $name => $id) {
                $entries[] = ['id' => $id, 'name' => $name];
            }
            return [$type => $entries];
        }, array_keys($this->query->query_vars['f']), array_values($this->query->query_vars['f'])));
    }

    /**
     * Set aggregation in context
     */
    protected function set_context_aggregation(): void
    {
        if (!ElasticSearch::is_active() || empty(static::$aggregations)) {
            return;
        }

        $aggs = static::$aggregations['terms'] ?? [];
        $ct_to_id = Filters\ContentTypes::get_ids_map();
        foreach ($aggs as $key => $def) {
            if ($key === 'meta' || $key === 'doc_count') {
                continue;
            }

            $filter = Aggregations::get_matching_filter($key);
            if (!$filter) {
                continue;
            }

            $slugs_to_id = array_column($this->context[$filter] ?? [], 'id', 'slug');
            $buckets = $def['buckets'] ?? [];

            switch ($key) {
                case Aggregations::CATEGORIES:
                case Aggregations::TAGS:
                case Aggregations::P4_PAGE_TYPE:
                case Aggregations::ACTION_TYPE:
                    foreach ($buckets as $agg) {
                        $term_id = $slugs_to_id[$agg['key']] ?? -1;
                        if (!isset($this->context[$filter][$term_id])) {
                            continue;
                        }
                        $this->context[$filter][$term_id]['results'] = $agg['doc_count'];
                    }
                    break;
                case Aggregations::POST_TYPE:
                    foreach ($buckets as $agg) {
                        $type_id = $ct_to_id[$agg['key']] ?? 0;
                        $this->context[$filter][$type_id]['results'] = $agg['doc_count'];
                    }
                    break;
                case Aggregations::POST_PARENT:
                default:
                    break;
            }
        }
    }

    /**
     * Set title in context
     */
    protected function set_context_title(): void
    {
        if (empty($this->query->query_vars['s'])) {
            $this->context['page_title'] = sprintf(
                // translators: %d: Number of results.
                _n('%d result', '%d results', $this->context['found_posts'], 'planet4-master-theme'),
                $this->context['found_posts']
            );
            return;
        }

        $this->context['page_title'] = sprintf(
            // translators: %1$d: Number of results. %2$s: Search terms.
            _n(
                '%1$d result for \'%2$s\'',
                '%1$d results for \'%2$s\'',
                $this->context['found_posts'],
                'planet4-master-theme'
            ),
            $this->context['found_posts'],
            $this->query->query_vars['s']
        );
    }

    /**
     * Return only existing terms with their link.
     * We need to do this as the term might have been removed but ES could still have it.
     *
     * @param array $terms The terms to filter.
     *
     * @return ?array All existing terms, with link.
     */
    private static function filter_existing_terms(array $terms): ?array
    {
        return array_reduce($terms, static function ($carry, $term) {
            $link = get_term_link($term['term_id']);
            if (!is_wp_error($link)) {
                $term['link'] = $link;
                $carry[] = $term;
            }
            return $carry;
        }, []);
    }

    /**
     * Render only results, in HTML list
     * Used for "Load more" ajax requests
     */
    public function render_partial(): void
    {
        $paged_context = $this->context;
        $paged_context['dummy_thumbnail'] = get_template_directory_uri() . self::DUMMY_THUMBNAIL;

        foreach ($this->context['paged_posts'] as $index => $post) {
            $paged_context['post'] = $post;
            $paged_context['first_of_the_page'] = 0 === $index % self::POSTS_PER_LOAD;

            Timber::render(
                self::RESULT_TEMPLATES,
                $paged_context,
                self::DEFAULT_CACHE_TTL,
                Loader::CACHE_OBJECT
            );
        }
    }

    /**
     * Render whole page, including search form and filters
     */
    public function render(): void
    {
        // order matters for title
        $this->set_context_filters();
        $this->set_context_aggregation();
        $this->set_context_title();

        if ($this->context['found_posts'] <= 0) {
            add_filter('wp_robots', 'wp_robots_no_robots');
        }

        Timber::render(
            self::PAGE_TEMPLATES,
            $this->context,
            self::DEFAULT_CACHE_TTL,
            Loader::CACHE_OBJECT
        );
    }
}
