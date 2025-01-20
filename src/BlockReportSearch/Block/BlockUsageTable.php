<?php

/**
 * Table displaying blocks usage
 */

namespace P4\MasterTheme\BlockReportSearch\Block;

use InvalidArgumentException;
use WP_List_Table;
use WP_Block_Type_Registry;
use P4\MasterTheme\BlockReportSearch\RowActions;
use P4\MasterTheme\BlockReportSearch\Block\Query\Parameters;

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
if (! class_exists('WP_List_Table')) {
    require_once ABSPATH . '/wp-admin/includes/class-wp-list-table.php';
}
// phpcs:enable PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Show block usage, using native WordPress table
 */
class BlockUsageTable extends WP_List_Table
{
    public const ACTION_NAME = 'block_usage';

    public const DEFAULT_GROUP_BY = 'block_type';

    public const DEFAULT_POST_STATUS = [ 'publish', 'private', 'draft', 'pending', 'future' ];

    public const PLURAL = 'blocks';

    private BlockUsage $block_usage;

    private WP_Block_Type_Registry $block_registry;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
    private $search_params = [];

    private string $group_by = self::DEFAULT_GROUP_BY;

    private ?array $sort_by = [ 'post_title', 'post_id' ];

    private array $allowed_groups = [ 'block_type', 'post_id', 'post_title' ];

    /**
     * @var string[]|null Columns name => title.
     */
    private ?array $columns = null;

    /**
     * @var string|int|null Latest row content displayed.
     */
    private $latest_row = null;

    /**
     * @var string[]|null Blocks namespaces.
     */
    private ?array $blocks_ns = null;

    /**
     * @var string[]|null Blocks types.
     */
    private ?array $blocks_types = null;

    /**
     * @var string[]|null Blocks registered.
     */
    private ?array $blocks_registered = null;

    /**
     * @var string[]|null Blocks allowed.
     */
    private ?array $blocks_allowed = null;

    /**
     * @var ?string Special filter.
     */
    private ?string $special = null;

    /**
     * @param array $args Args.
     * @throws InvalidArgumentException Throws on missing parameter.
     * @see WP_List_Table::__construct()
     */
    public function __construct(array $args = [])
    {
        $args['plural'] = self::PLURAL;
        parent::__construct($args);

        $this->block_usage = $args['block_usage'] ?? null;
        $this->block_registry = $args['block_registry'] ?? null;

        if (! ( $this->block_usage instanceof BlockUsage )) {
            throw new InvalidArgumentException(
                'Table requires a BlockUsage instance.'
            );
        }
        if (! ( $this->block_registry instanceof WP_Block_Type_Registry )) {
            throw new InvalidArgumentException(
                'Table requires a WP_Block_Type_Registry instance.'
            );
        }
    }

    /**
     * Prepares table data.
     *
     * @param Parameters $search_params Search parameters.
     * @param string     $group_by      Grouping dimension.
     * @param ?string    $special  Unregistered blocks only.
     */
    public function prepare_items(
        ?Parameters $search_params = null,
        ?string $group_by = null,
        ?string $special = null
    ): void {
        if (in_array($group_by, $this->allowed_groups, true)) {
            $this->group_by = $group_by;
        }

        $this->search_params = $search_params
            ->with_post_status(self::DEFAULT_POST_STATUS)
            ->with_post_type(
                array_filter(
                    \get_post_types([ 'show_in_rest' => true ]),
                    fn ($t) => \post_type_supports($t, 'editor')
                )
            )
            ->with_order(array_merge([ $this->group_by ], $this->sort_by));

        $items = $this->block_usage->get_blocks($this->search_params);

        $this->special = $special;
        if ('unregistered' === $this->special) {
            $items = $this->filter_for_unregistered($items);
        }
        if ('unallowed' === $this->special) {
            $items = $this->filter_for_unallowed($items);
        }

        // Pagination handling.
        $total_items = count($items);
        $per_page = 50;
        $current_page = $this->get_pagenum();
        $this->items = array_slice($items, ( ( $current_page - 1 ) * $per_page ), $per_page);
        $this->set_pagination_args(
            [
                'total_items' => $total_items,
                'per_page' => $per_page,
                'total_pages' => ceil($total_items / $per_page),
            ]
        );

        $this->set_block_filters();
        $this->_column_headers = $this->get_column_headers();
    }

    /**
     * Filter items to keep unregistered blocks only.
     *
     * @param array $items Blocks not registered.
     */
    private function filter_for_unregistered(array $items): array
    {
        $this->set_registered_blocks();
        return array_filter(
            $items,
            fn ($i) => ! in_array($i['block_type'], $this->blocks_registered, true) && 'core-embed' !== $i['block_ns']
        );
    }

    /**
     * Filter items to keep unallowed blocks only.
     *
     * @param array $items Blocks not registered.
     */
    private function filter_for_unallowed(array $items): array
    {
        $this->set_allowed_blocks();
        return array_filter(
            $items,
            fn ($i) => ! in_array($i['block_type'], $this->blocks_allowed, true)
        );
    }

    /**
     * Set dropdown filters content.
     */
    private function set_block_filters(): void
    {
        $this->set_registered_blocks();
        $this->set_allowed_blocks();
        $this->blocks_types = array_unique(
            array_merge(
                $this->blocks_registered,
                $this->blocks_allowed
            )
        );

        $namespaces = array_filter(
            array_unique(
                array_map(
                    static function (string $name) {
                        return explode('/', $name)[0] ?? null;
                    },
                    $this->blocks_types
                )
            )
        );
        sort($namespaces);
        $this->blocks_ns = $namespaces;
    }

    /**
     * Set the registered blocks list.
     */
    private function set_registered_blocks(): void
    {
        $names = array_keys(
            $this->block_registry->get_all_registered()
        );
        sort($names);
        $this->blocks_registered = $names;
    }

    /**
     * Set the allowed blocks list.
     */
    private function set_allowed_blocks(): void
    {
        $post_types = array_filter(
            get_post_types([ 'show_in_rest' => true ]),
            fn ($t) => post_type_supports($t, 'editor')
        );

        $allowed = [];
        foreach ($post_types as $type) {
            $context = new \WP_Block_Editor_Context(
                [ 'post' => (object) [ 'post_type' => $type ] ]
            );
            $on_type = get_allowed_block_types($context);
            if (! is_array($on_type)) {
                $on_type = $on_type ? $this->blocks_registered : [];
            }
            $allowed = array_merge($allowed, array_values($on_type));
        }

        $allowed = array_unique($allowed);
        sort($allowed);
        $this->blocks_allowed = $allowed;
    }

    /**
     * Columns list for table.
     */
    public function get_columns(): ?array
    {
        if (null !== $this->columns) {
            return $this->columns;
        }

        $default_columns = [
            'post_title' => 'Title',
            'block_type' => 'Block',
            'block_styles' => 'Style',
            'block_attrs' => 'Attributes',
            'post_date' => 'Created',
            'post_modified' => 'Modified',
            'post_id' => 'ID',
            'post_status' => 'Status',
        ];

        // This is a temporary fix to identify problems with the columns migrated here:
        // https://jira.greenpeace.org/browse/PLANET-7489
        if (isset($_GET['name']) && $_GET['name'] === 'planet4-blocks/columns') {
            $default_columns['block_cols'] = 'Columns';
        }

        $this->columns = array_merge(
            [ $this->group_by => $default_columns[ $this->group_by ] ],
            $default_columns
        );

        return $this->columns;
    }

    /**
     * All, hidden and sortable columns.
     */
    private function get_column_headers(): array
    {
        return [
            $this->get_columns(),
            [],
            [ 'post_title', 'post_date', 'post_modified' ],
        ];
    }

    /**
     * Available grouping as views.
     */
    protected function get_views(): array
    {
        $link_tpl = '<a href="%s">%s</a>';
        $active_link_tpl = '<a class="current" href="%s">%s</a>';
        return [
            'block_type' => sprintf(
                'block_type' === $this->group_by ? $active_link_tpl : $link_tpl,
                add_query_arg('group', 'block_type'),
                'Group by block name'
            ),
            'post_title' => sprintf(
                'post_title' === $this->group_by ? $active_link_tpl : $link_tpl,
                add_query_arg('group', 'post_title'),
                'Group by post title'
            ),
            'post_id' => sprintf(
                'post_id' === $this->group_by ? $active_link_tpl : $link_tpl,
                add_query_arg('group', 'post_id'),
                'Group by post ID'
            ),
        ];
    }

    /**
     * Displays the list of views available on this table.
     */
    public function views(): void
    {
        parent::views();

        $link_tpl = '<a href="%s">%s</a>';
        $active_link_tpl = '<a class="current" href="%s">%s</a>';
        $unique_views = [
            'unregistered' => sprintf(
                'unregistered' === $this->special ? $active_link_tpl : $link_tpl,
                'unregistered' === $this->special
                    ? self::url()
                    : add_query_arg([ 'unregistered' => '' ], self::url()),
                'Not registered'
            ),
            'unallowed' => sprintf(
                'unallowed' === $this->special ? $active_link_tpl : $link_tpl,
                'unallowed' === $this->special
                    ? self::url()
                    : add_query_arg([ 'unallowed' => '' ], self::url()),
                'Not allowed'
            ),
        ];

        $views = [];
        echo '<div style="clear: both;"><ul class="subsubsub" style="margin: 0;">';
        foreach ($unique_views as $class => $view) {
            $views[ $class ] = "\t<li class='$class'>$view";
        }
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo implode(" |</li>\n", $views) . "</li>\n";
        echo '</ul></div>';
    }

    /**
     * Select blocks namespaces.
     */
    private function blockns_dropdown(): void
    {
        sort($this->blocks_ns);
        $filter = $this->search_params->namespace() ?? null;

        echo '<select name="namespace" id="filter-by-ns" onchange="filterBlockNames();">';
        echo '<option value="">- All namespaces -</option>';
        foreach ($this->blocks_ns as $ns) {
            echo sprintf(
                '<option value="%s" %s>%s</option>',
                esc_attr($ns),
                esc_attr($filter === $ns ? 'selected' : ''),
                esc_html($ns)
            );
        }
        echo '</select>';
    }

    /**
     * Select blocks types.
     */
    private function blocktype_dropdown(): void
    {
        sort($this->blocks_types);
        $filter = $this->search_params->name() ?? null;

        echo '<select name="name" id="filter-by-name">';
        echo '<option value="">- All blocks -</option>';
        foreach ($this->blocks_types as $type) {
            echo sprintf(
                '<option value="%s" %s>%s</option>',
                esc_attr($type),
                esc_attr($filter === $type ? 'selected' : ''),
                esc_html($type)
            );
        }
        echo '</select>';

        echo "<script>
			const filterBlockNames = () => {
				let selectedNs = document.getElementById('filter-by-ns').selectedOptions[0].value;
				let select = document.getElementById('filter-by-name');
				for (let option of select.options) {
					let display = selectedNs.length <= 0
						|| option.value.length <= 0
						|| option.value.startsWith(`\${selectedNs}/`);
					option.style.display = display ? 'inline' : 'none';
				}
				if ( selectedNs.length >= 1 ) {
					select.value = '';
				}
			}
			filterBlockNames();
		</script>";
    }

    /**
     * Add filters to table.
     *
     * @param string $which Tablenav identifier.
     * @phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter, SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    protected function extra_tablenav($which): void
    {
        echo '<div class="actions">';
        $this->blockns_dropdown();
        $this->blocktype_dropdown();
        submit_button(
            __('Filter', 'planet4-blocks-backend'),
            '',
            'filter_action',
            false,
            [ 'id' => 'block-query-submit' ]
        );
        echo '</div>';
    }

    /**
     * Add pagination information to table.
     *
     * @param string $which Tablenav identifier.
     * @phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter, SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    protected function pagination($which): void
    {
        echo esc_html(parent::pagination('top'));
    }
    // @phpcs:enable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter, SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint

    /**
     * Default column value representation.
     *
     * @param array  $item Item.
     * @param string $column_name Column name.
     * @phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     * @return mixed
     */
    public function column_default($item, $column_name)
    {
        return $item[ $column_name ] ?? '';
    }

    /**
     * Block option display.
     *
     * @param array $item Item.
     * @phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function column_block_attrs($item): string
    {
        $content = $item['block_attrs'] ?? null;
        if (empty($content)) {
            return '';
        }

		//phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r , Squiz.PHP.DiscouragedFunctions.Discouraged
        $content = print_r($content, true);
        $content = trim(substr($content, 5, strlen($content)));

        return sprintf(
            '<span title="%s">%s</span>',
            esc_attr($content),
            esc_html(
                strlen($content) > 30
                ? substr($content, 0, 30) . '...'
                : $content
            )
        );
    }

    /**
     * Add the number of columns to the table.
     *
     * This is a temporary fix to identify problems with the columns migrated here:
     * https://jira.greenpeace.org/browse/PLANET-7489
     * We need this function to identify P4 columns blocks using more than 4 columns.
     *
     * @param array $item Item.
     * @phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function column_block_cols($item): string
    {
        $content = $item['block_attrs'] ?? null;

        if (empty($content)) {
            return '';
        }

        $cols = $content['columns'] ? count($content['columns']) : '0';

        if ($cols > 4) {
            return "<span style='color: red;'>$cols - Fix needed!</span>";
        }

        return (string)$cols;
    }

    /**
     * Block styles display.
     *
     * @param array $item Item.
     * @phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function column_block_styles($item): string
    {
        return sprintf(
            '%s',
            implode(',', $item['block_styles'])
        );
    }

    /**
     * Post title display.
     *
     * @param array $item Item.
     * @phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function column_post_title($item): string
    {
        $content = $item['post_title'] ?? null;
        if (empty($content)) {
            return '';
        }

        $title_tpl = '%2$s';
        $link_tpl = '<a href="%s" title="%s">%s</a>';
        // The post id may be empty if we removed it when creating the rows (see single_row function).
        $post_id = empty($item['post_id']) ? $this->latest_row : $item['post_id'];
        // If the post is still a draft we don't want to show a link in the title column.
        $page_uri = $item['post_status'] === 'draft' ? '' : get_permalink($post_id);

        return sprintf(
            empty($page_uri) ? $title_tpl : $link_tpl,
            $page_uri,
            esc_attr($content),
            ( strlen($content) > 45 ? substr($content, 0, 45) . '...' : $content )
        );
    }

    /**
     * Post ID display.
     *
     * @param array $item Item.
     * @phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function column_post_id($item): string
    {
        return sprintf(
            '<a href="%s">%s</a>',
            get_edit_post_link($item['post_id']),
            $item['post_id']
        );
    }

    /**
     * Full row display, edited for grouping functionality.
     *
     * @param array $item Item.
     * @phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function single_row($item): void
    {
        $cols = $this->get_columns();
        $colspan = count($cols);
        $first_col = array_key_first($cols);

        if ($this->latest_row !== $item[ $first_col ]) {
            echo '<tr>';
            echo sprintf(
                '<th colspan="%s"><strong>%s</strong></th>',
                esc_attr($colspan),
                esc_html($item[ $first_col ])
            );
            echo '</tr>';
        }

        $this->latest_row = $item[ $first_col ];
        $item[ $first_col ] = '';
        parent::single_row($item);
    }

    /**
     * Add action links to a row
     *
     * @param array  $item        Item.
     * @param string $column_name Current column name.
     * @param string $primary     Primary column name.
     *
	 * phpcs:disable WordPress.WP.I18n.TextDomainMismatch, SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint, SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
     */
    protected function handle_row_actions($item, $column_name, $primary)
    {
        return $this->row_actions(
            ( new RowActions() )->get_post_actions($item, $column_name, $primary, $this->latest_row)
        );
    }
    // phpcs:enable SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint

    /**
     * Show only top tablenav (duplicate form post bug)
     *
     * @param string $which Tablenav identifier.
     * phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    protected function display_tablenav($which): void
    {
        if ('bottom' === $which) {
            echo '<div class="tablenav bottom">';
            echo esc_html(parent::pagination($which));
            echo '</div>';
            return;
        }
        parent::display_tablenav($which);
    }
    // phpcs:enable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint

    /**
     * Search parameters
     */
    public function get_search_params(): Parameters
    {
        return $this->search_params;
    }

    /**
     * Table URL
     */
    public static function url(): string
    {
        return admin_url('admin.php?page=plugin_blocks_report');
    }

    /**
     * Set table hooks
     */
    public static function set_hooks(): void
    {
        // Add redirection for filter action.
        add_action(
            'admin_action_' . self::ACTION_NAME,
            function (): void {
                $nonce = $_GET['_wpnonce'] ?? null;
                if (! wp_verify_nonce($nonce, 'bulk-' . self::PLURAL)) {
                    wp_safe_redirect(self::url());
                    exit;
                }

                $redirect_query = remove_query_arg(
                    [ '_wp_http_referer', '_wpnonce', 'action', 'filter_action' ],
                    \wp_parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY)
                );
                \parse_str($redirect_query, $args);
                $args = array_filter(
                    $args,
                    fn($e) => ! empty($e) && '0' !== $e
                );

                wp_safe_redirect(add_query_arg($args, self::url()));
                exit;
            },
            10
        );
    }
}
