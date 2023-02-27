<?php
namespace TypeRocket\Pro\Elements;

use TypeRocket\Core\Config;
use TypeRocket\Database\ResultsPaged;
use TypeRocket\Elements\Traits\Attributes;
use TypeRocket\Elements\Traits\DisplayPermissions;
use TypeRocket\Html\Html;
use TypeRocket\Http\Request;
use TypeRocket\Models\Model;
use TypeRocket\Register\Page;
use TypeRocket\Utility\Helper;
use TypeRocket\Utility\Sanitize;
use TypeRocket\Elements\BaseForm;
use TypeRocket\Utility\Str;
use \WP_Query;
use \WP_User_Query;
use \WP_Term_Query;
use \WP_Comment_Query;

class Table
{
    use Attributes, DisplayPermissions;

    /** @var ResultsPaged */
    protected $results;
    protected $columns;
    protected $links;

    /** @var Model|null $model */
    protected $model;
    /** @var WP_Query|WP_Term_Query|WP_User_Query|WP_Comment_Query|null $wpQuery */
    protected $wpQuery;
    protected $primary = 'id';

    /** @var null|Page  */
    protected $page = null;
    protected $routeArgsCallback;
    /** @var Request */
    protected $request;
    protected $searchColumns;
    protected $checkboxes = false;
    protected $search = true;
    protected $paging = true;
    protected $searchOnlyCustom = false;
    protected $searchFormFilter;
    protected $searchQueryFilter;
    protected $searchModelFilter;
    protected $limit;
    protected $bulkActions;
    /** @var null|BaseForm  */
    protected $bulkForm = null;
    protected $tag;
    protected $settings = ['update_column' => 'id'];

    /**
     * Tables constructor.
     *
     * @param Model|false $model when false model system is ignored
     * @param int $limit
     *
     */
    public function __construct( $model = null, $limit = 25 )
    {
        global $_tr_page, $_tr_resource;

        $this->request = new Request;

        if(!empty($_tr_page) && $_tr_page instanceof Page ) {
            $this->page = $_tr_page;
        }

        if($model === false) {
            $this->model = null;
        } elseif( $model instanceof Model) {
            $this->setModel($model);
        } elseif(is_string($model) && $model[0] == '@') {
            $this->setModel(Helper::modelClass($model));
        } elseif(is_string($model) && class_exists($model)) {
            $this->setModel(new $model);
        } elseif(!empty($_tr_resource) && $_tr_resource instanceof Model ) {
            $this->setModel($_tr_resource);
        } else {
            throw new \Exception('TypeRocket table model is invalid or could not be detected. Add a model manually.');
        }

        $this->setLimit($limit);

        do_action('typerocket_table_init', $this);
    }

    /**
     * Add Bulk Form
     *
     * @param BaseForm $form
     *
     * @param null|callable|array $actions
     *
     * @return $this
     */
    public function setBulkActions(BaseForm $form, $actions = null)
    {
        $this->bulkForm = $form;
        $this->checkboxes = true;
        $this->bulkActions = $actions;

        return $this;
    }

    /**
     * Set table limit
     *
     * @param string $limit
     *
     * @return $this
     */
    public function setLimit( $limit )
    {
        $this->limit = (int) $limit;

        return $this;
    }

    /**
     * Remove Search
     *
     * @return $this
     */
    public function removeSearch()
    {
        $this->search = false;
        return $this;
    }

    /**
     * Remove Paging
     *
     * @return $this
     */
    public function removePaging()
    {
        $this->paging = false;
        return $this;
    }

    /**
     * Set Custom Search Only
     *
     * @param bool $bool
     *
     * @return $this
     */
    public function setCustomSearchOnly($bool = true)
    {
        $this->searchOnlyCustom = $bool;
        return $this;
    }

    /**
     * Set table search columns
     *
     * @param array $columns
     *
     * @return $this
     */
    public function setSearchColumns( $columns ) {
        $this->searchColumns = $columns;

        return $this;
    }

    /**
     * Get Search Columns
     *
     * @return array
     */
    public function getSearchColumns()
    {
        $columns = $this->searchColumns ? $this->searchColumns : $this->columns;

        // Do not load nested fields
        foreach ($columns as $name => $value) {
            if (strpos($name, '.') !== false || (is_array($value) && isset($value['exclude_search_select']) && $value['exclude_search_select'] === true)) {
                unset($columns[$name]);
            } elseif(is_int($name)) {
                unset($columns[$name]);
                $columns[$value] = $value;
            }
        }

        return $columns;
    }

    /**
     * Set Table Sorting
     *
     * @param string $column
     * @param string $direction
     *
     * @return $this
     * @internal param $ $
     *
     */
    public function setOrder( string $column, string $direction = 'ASC' )
    {
        if( empty( $_GET['order'] ) && empty( $_GET['orderby'] ) ) {
            $this->model->orderBy($column, $direction);
        }

        return $this;
    }

    /**
     * Set the Tables Columns
     *
     * @param array $columns
     * @param string|null $primary set the main column
     *
     * @return Table
     */
    public function setColumns(array $columns, ?string $primary = null)
    {
        $this->columns = $columns;
        $this->primary = $primary ?? $this->primary;

        return $this;
    }

    /**
     * Set Primary Column
     *
     * @param $name
     *
     * @return $this
     */
    public function setPrimaryColumn($name)
    {
        $this->primary = $name;

        return $this;
    }

    /**
     * Set Tag
     *
     * Used to detect the table is using hooks.
     *
     * @param $tag
     *
     * @return $this
     */
    public function setTag($tag)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Has Tag
     *
     * @param $tag
     *
     * @return bool
     */
    public function hasTag($tag)
    {
        return $this->tag === $tag;
    }

    /**
     * Set the page the table is connected to.
     *
     * @param Page $page
     *
     * @return $this
     */
    public function setPage( Page $page)
    {
        $this->page = $page;

        return $this;
    }

    /**
     * Set Model
     *
     * @param Model $model
     *
     * @return $this
     */
    public function setModel(Model $model)
    {
        $this->model = clone $model;

        return $this;
    }

    /**
     * Get Model
     *
     * @return Model|null
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Set Model
     *
     * @return $this
     */
    protected function setupModel()
    {
        /** @var \wpdb $wpdb */
        global $wpdb;

        $args = [];

        if(!$this->model) {
            return $this;
        }

        if( $this->isValidSearch() ) {
            $condition = strtolower($this->request->getDataGet('condition'));
            $search = $this->request->getDataGet('s');
            $before = $after = '';

            switch ($condition) {
                case 'like':
                    $condition = 'like';
                    $before = $after = '%';
                    break;
                case 'starts':
                    $condition = 'like';
                    $after = '%';
                    break;
                case 'ends':
                    $condition = 'like';
                    $before = '%';
                    break;
                case 'equal':
                default :
                    $condition = '=';
                    break;
            }

            $args = [
                'on' => Sanitize::underscore($_GET['on']),
                'condition' => $condition,
                'after' => $after,
                'before' => $before,
                'search' => $search,
            ];

            $args = $this->searchQueryFilter ? call_user_func($this->searchQueryFilter, $args, $this->model, $this) : $args;

            if($condition == 'like') {
                $args['search'] = $wpdb->esc_like($args['search']);
            }

            $search = $args['before'].$args['search'].$args['after'];

            $this->model->where( Sanitize::underscore($args['on']) , $args['condition'], $search );
        }

        if( !empty( $_GET['order'] ) && !empty( $_GET['orderby'] ) ) {
            $this->model->orderBy($_GET['orderby'], $_GET['order']);
        }

        if($this->searchModelFilter) {
            call_user_func($this->searchModelFilter, $args, $this->model, $this);
        }

        do_action('typerocket_table_model', $this->model, $this);

        return $this;
    }

    /**
     * Add Search Query Filter
     *
     * Add the ability to override the default search.
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function addSearchQueryFilter( callable $callback )
    {
        $this->searchQueryFilter = $callback;
        return $this;
    }

    /**
     * Add Custom Search Model Filter
     *
     * Add the ability to append other input fields to the
     * Model when searching.
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function addSearchModelFilter( callable $callback )
    {
        $this->searchModelFilter = $callback;
        return $this;
    }

	/**
     * Add HTML Search From Filters
     *
     * Add the ability to append other input fields and HTML
     * inside the search section of the filter table area.
     *
	 * @param callable $callback
	 *
	 * @return $this
	 */
	public function addSearchFormFilter( callable $callback )
    {
        $this->searchFormFilter = $callback;
        return $this;
    }

    /**
     * @param null|ResultsPaged $results
     *
     * @return $this
     */
    public function setResultsPaged(?ResultsPaged $results)
    {
        $this->results = $results;

        return $this;
    }

    /**
     * @return ResultsPaged
     */
    protected function getResultsPaged()
    {
        return $this->results ?? $this->model->paginate($this->limit);
    }

    /**
     * @param $record
     *
     * @return mixed
     */
    protected function buildTableRow($record)
    {
        return new TableRow($record);
    }

    /**
     * Render Table
     */
    public function render()
    {
        if(!$this->canDisplay()) { return; }

        $this->setupModel();

        $this->setResultsPaged($this->getResultsPaged());

        $table = Html::table(['class' => 'tr-list-table wp-list-table widefat striped'], [
            $head = Html::el('thead'),
            $body = Html::el('tbody', ['class' => 'the-list']),
            $foot = Html::el('tfoot'),
        ]);

        if( ! $this->columns && !empty($this->results) ) {
            $item = $this->results->current();

            if(is_array($item)) {
                $column_keys = array_keys($item);
            } else {
                $column_keys = array_keys($item->getProperties());
            }

            $this->setColumns($column_keys);
        }
        elseif( ! $this->columns && empty($this->results) ) {
            $this->setColumns([$this->request->getDataGet('on') ?? __('Results', 'typerocket-domain')]);
        }

        // thead
        $th_row = Html::tr(['class' => 'manage-column']);

	    if($this->checkboxes && !empty($this->results)) {
		    $th_row->nest(Html::td( ['class' => "manage-column column-cb check-column"], '<input type="checkbox" class="check-all" /></td>'));
	    }

	    /**
         * @var string $column
         * @var string|array $data
         */
        foreach ( $this->columns as $column => $data ) {
            $classes = Str::classNames('manage-column', [
                'column-primary' => $this->primary == $column,
            ]);

            if( !is_string($column) ) {
                $th_row->nest(Html::th(['class' => $classes], ucfirst($data)));
            } else {
                $label = is_string($data) ? $data : $data['label'];
                if( !empty($data['sort']) && $this->page && strpos($column, '.') === false) {
                    $order_direction = !empty( $_GET['order'] ) && $_GET['order'] == 'ASC' ? 'DESC' : 'ASC';
                    $order_direction_now = !empty( $_GET['order'] ) && $_GET['order'] == 'ASC' ? 'ASC' : 'DESC';

                    $url_params = ['orderby' => $column, 'order' => $order_direction];

                    if( $this->isValidSearch() ) {
                        $url_params = array_merge($url_params, $this->request->getDataGet());
                    }

                    $order_link = $this->page->getUrl($url_params);
                    if( !empty($_GET['orderby']) &&  $column == $_GET['orderby']) {
                        $classes .= ' sorted ' . strtolower($order_direction_now);
                    } else {
                        $classes .= ' sortable ' . strtolower($order_direction_now);
                    }

                    $label = "<a href=\"{$order_link}\"><span>$label</span><span class=\"sorting-indicator\"></span></a>";
                }

                $th_row->nest(Html::th( ['class' => $classes], $label));
            }
        }

        $head->nest($th_row);
        $foot->nest($th_row);

        // tbody
        if(!empty($this->results)) {
            /** @var Model $result */
            foreach ($this->results as $result) {
                $tableRow = $this->buildTableRow($result);
                $id =  $tableRow->getID();
                $row_id = 'result-row-' . Sanitize::dash($id);
                $td_row = Html::tr(['class' => 'manage-column', 'id' => $row_id]);

	            if($this->checkboxes) {
                    $td = Html::th(['class' => 'check-column'], '<input type="checkbox" name="bulk[]" value="'.esc_attr($id).'" />');
		            $td_row->nest($td);
	            }

                foreach ($this->columns as $column => $data) {
                    // get columns if none set
                    if (!is_string($column)) {
                        $column = $data;
                    }

                    $text = $tableRow->getDeepValue($column);

                    if( !empty($data['callback']) && is_callable($data['callback']) ) {
                        $text = call_user_func_array($data['callback'], [$text, $result] );
                    }

                    if(is_array($text) || is_object($text)) {
                        $text = esc_html(json_encode($text, JSON_PRETTY_PRINT));
                        $text = '<div style="max-height: 350px;overflow-y: scroll; word-break: break-word">' . $text . '</div>';
                    }

                    if($result instanceof Model) {
                        $text = $this->getPageActionLinks($text, $result, $row_id, $data);
                    } else {
                        $text = TableRow::buildTableRowActions($text, [], $tableRow, $this, $data, []);
                    }

                    $classes = null;
                    if($this->primary == $column) {
                        $classes = 'column-primary';
                        $details_text = __('Show more details', 'typerocket-domain');

                        $text .= "<button type=\"button\" class=\"toggle-row\"><span class=\"screen-reader-text\">{$details_text}</span></button>";
                    }

                    $td_row->nest( Html::td(['class' => $classes], $text) );
                }
                $body->nest($td_row);
            }
        } else {
            $results_text = __('No results.', 'typerocket-domain');
            $total_columns = count( $this->columns );
            $body->nest(Html::tr("<td colspan='{$total_columns}'>{$results_text}</td>"));
        }

        if($this->results) {
            $current = $this->getPageLinks()['current'];
        } else {
            $current = $this->request->getUriFull();
        }

        $this->attrClass( 'tr-table-container');

        echo Html::div($this->getAttributes())->nest([
            Html::form($current, 'GET', [], [
                '<div class="tablenav top">',
                $this->search ? $this->searchForm() : '',
                $this->paging ? $this->paginationLinks() : '',
                '</div>',
            ]),
            $this->checkboxes ? $this->bulkForm->open(['class' => 'tr-table-form-bulk-actions'], ['tr_bulk_actions' => 'yes']) : null,
            '<div class="tr-table-wrapper">',
            $table,
            '</div>',
            '<div class="tablenav bottom">',
            $this->checkboxes ? $this->bulkActions() : null,
            $this->paging ? $this->paginationLinks() : '',
            '</div>',
            $this->checkboxes ? $this->bulkForm->close() : null,
        ]);
    }

    /**
     * Bulk Actions
     */
    protected function bulkActions()
    {
        $html = '';
        $html .= '<div class="actions bulkactions">';

        $actions = $this->bulkActions;

        if(is_callable($actions)) {
            $html = call_user_func($actions, $this);
        }
        elseif (!is_array($actions)) {
            $actions = [
                __('Custom Action', 'typerocket-domain') => '1',
            ];
        }

        if (is_array($actions)) {
            $options = [];
            $options[] = Html::option(['value' => '-1'], esc_html(__('Bulk Actions', 'typerocket-domain')));

            foreach ($actions as $key => $value) {
                $options[] = Html::option(['value' => $value], esc_html($key));
            }

            $html .= Html::select(['name' => 'tr[tr_bulk_action]'], $options);
        }

        $html .= '<button class="button">'.__('Apply', 'typerocket-domain').'</button>';
        $html .= '</div>';
        return $html;
    }

    protected function searchForm()
    {
        $get_page = $this->request->getDataGet('page');
        $get_search_current = $this->request->getDataGet('s');
        $get_condition_current = $this->request->getDataGet('condition');
        $get_on_current = $this->request->getDataGet('on');

        $select_condition = [
            'like' => __('Contains', 'typerocket-domain'),
            'starts' => __('Starts With', 'typerocket-domain'),
            'ends' => __('Ends With', 'typerocket-domain'),
            'equals' => __('Is Exactly', 'typerocket-domain'),
        ];

        $searchColumns = $this->getSearchColumns();
        ob_start();
        $hash = Helper::hash();
        ?>
        <?php if(is_callable($this->searchFormFilter)) {
            echo '<div class="actions">';
            call_user_func($this->searchFormFilter, $this, $searchColumns);
            echo '</div>';
        } ?>
        <?php do_action('typerocket_table_search', $this, $searchColumns);
        if(!$this->searchOnlyCustom) :
        ?>
        <div class="actions">
            <select name="on">
                <optgroup label="<?php echo __('Search By', 'typerocket-domain'); ?>">
                    <?php foreach ($searchColumns as $column_name => $column) :
                        $selected = $get_on_current == $column_name ? 'selected="selected"' : '';
                        ?>
                        <option <?php echo $selected; ?> value="<?php echo esc_attr($column_name); ?>">
                            <?php echo !empty($column['label']) ? $column['label'] : $column; ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
            </select>
        </div>
        <div class="actions">
            <select name="condition">
                <optgroup label="<?php echo __('Search Type', 'typerocket-domain'); ?>">
                <?php foreach ($select_condition as $column_name => $label) :
                    $selected = $get_condition_current == $column_name ? 'selected="selected"' : '';
                    ?>
                    <option <?php echo $selected; ?> value="<?php echo esc_attr($column_name); ?>">
                        <?php echo $label; ?>
                    </option>
                <?php endforeach; ?>
                </optgroup>
            </select>
        </div>
        <?php endif; ?>
        <div class="actions">
            <label class="screen-reader-text" for="search-input-<?php echo $hash; ?>"><?php _e('Search Query:'); ?></label>
            <input type="hidden" name="page" value="<?php echo esc_attr($get_page); ?>">
            <input type="hidden" name="paged" value="1">
            <?php if (!empty($_GET['orderby'])) : ?>
                <input type="hidden" name="orderby" value="<?php echo esc_attr($_GET['orderby']); ?>">
            <?php endif; ?>
            <?php if (!empty($_GET['order'])) : ?>
                <input type="hidden" name="order" value="<?php echo esc_attr($_GET['order']); ?>">
            <?php endif; ?>
            <input
                    type="search"
                    id="search-input-<?php echo $hash; ?>"
                    name="s"
                    placeholder="<?php _e('Type search...'); ?>"
                    value="<?php echo esc_attr($get_search_current); ?>"
            >
            <button class="button">Search</button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * @return array
     */
    protected function getPageLinks()
    {
        if($this->links) {
            return $this->links;
        }

        $this->links = [];

        if($this->page instanceof Page) {
            $this->links['current'] = $this->page->getUrlWithParams(['paged' => $this->results->getCurrentPage()]);
            $this->links['next'] = $this->page->getUrlWithParams(['paged' => $this->results->getNextPage()]);
            $this->links['previous'] = $this->page->getUrlWithParams(['paged' => $this->results->getPreviousPage() ]);
            $this->links['last'] = $this->page->getUrlWithParams(['paged' => $this->results->getLastPage() ]);
            $this->links['first'] = $this->page->getUrlWithParams(['paged' => $this->results->getFirstPage() ]);
        } else {
            $this->links = $this->results->getLinks();
        }

        return $this->links;
    }

    /**
     * Pagination Links
     */
    protected function paginationLinks()
    {
        if(!$this->results) {
            return null;
        }

        $links = $this->getPageLinks();

        $last = $links['last'];
        $first = $links['first'];
        $prev = $links['previous'];
        $next = $links['next'];
        $page = $this->results->getCurrentPage();
        $pages = $this->results->getNumberOfPages();
        $count = $this->results->getCount();

        $item_word = __('items', 'typerocket-domain');

        if($count < 2) {
            $item_word = __('item', 'typerocket-domain');
        }

        ob_start();

        echo "<div class=\"tablenav-pages\"><span class=\"displaying-num\">{$count} {$item_word}</span>";
        echo "<span class=\"pagination-links\">";
        $last_text = __('Last page', 'typerocket-domain');
        $next_text = __('Next page', 'typerocket-domain');

        if($first && $pages > 2) {
            if( (int) $page === 1 ) {
                echo ' <span class="tablenav-pages-navspan  button disabled" aria-hidden="true">&laquo;</span> ';
            } else {
                echo " <a class=\"last-page button\" href=\"{$first}\"><span class=\"screen-reader-text\">{$last_text}</span><span aria-hidden=\"true\">&laquo;</span></a> ";
            }
        }

        if( $page < 2 ) {
            echo " <span class=\"tablenav-pages-navspan button disabled\" aria-hidden=\"true\">&lsaquo;</span> ";
        } else {
            echo " <a class=\"prev-page button\" href=\"{$prev}\" aria-hidden=\"true\">&lsaquo;</a> ";
        }
        echo " <span id=\"table-paging\" class=\"paging-input\">{$page} of <span class=\"total-pages\">{$pages}</span></span> ";
        if( $page < $pages ) {
            echo " <a class=\"next-page button\" href=\"{$next}\"><span class=\"screen-reader-text\">{$next_text}</span><span aria-hidden=\"true\">&rsaquo;</span></a> ";
        } else {
            echo " <span class=\"tablenav-pages-navspan button disabled\" aria-hidden=\"true\">&rsaquo;</span> ";
        }

        if($last && $pages > 2) {
            if( (int) $pages === $page  ) {
                echo ' <span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span> ';
            } else {
                echo " <a class=\"last-page button\" href=\"{$last}\"><span class=\"screen-reader-text\">{$last_text}</span><span aria-hidden=\"true\">&raquo;</span></a> ";
            }

        }

        echo "</span></div>";

        return ob_get_clean();
    }

    /**
     * Set Route Args Callback
     *
     * @param callable $callback
     */
    public function setRouteArgsCallback(callable $callback)
    {
        $this->routeArgsCallback = $callback;
    }

    /**
     * @param string $text
     * @param Model $result
     * @param string $row_id
     * @param array|string $data
     *
     * @return string
     */
    protected function getPageActionLinks($text, Model $result, $row_id, $data)
    {
        static $linkFound = 0;
        $show_url = $edit_url = $delete_url = $main_link = null;
        $route_args = ['route_args' => [$result->getID()]];

        if($this->routeArgsCallback) {
            $route_args = call_user_func($this->routeArgsCallback, $result, $route_args, $this);
        }

        if ($this->page instanceof Page)
        {
            if ($this->page->isAction('edit')) {
                $edit_url = $this->page->getUrl($route_args);
                $main_link = "<strong><a href=\"{$edit_url}\">{$text}</a></strong>";
                $linkFound++;
            } elseif ($this->page->isAction('show')) {
                $show_url = $this->page->getUrl($route_args);
                $main_link = "<strong><a href=\"{$show_url}\">{$text}</a></strong>";
                $linkFound++;
            }

            /** @var Page[] $pages */
            $pages = $this->page->getPages();

            foreach ($pages as $page) {
                /** @var Page $page */
                if ($page->isAction('edit')) {
                    $edit_url = $page->getUrl($route_args);
                    $main_link = "<strong><a href=\"{$edit_url}\">{$text}</a></strong>";
                } elseif ($page->isAction('show')) {
                    $show_url = $page->getUrl($route_args);
                } elseif ($page->isAction('delete')) {
                    $delete_url = $page->getUrl($route_args);
                }
            }
        }

        if($linkFound) {
            $text = $main_link ?? $text;
        }

        if ( !empty($data['actions']) )
        {
            $text = $main_link ?? $text;

            $links = [];
            foreach ($data['actions'] as $index => $action)
            {
                if($action == 'edit')
                {
                    $edit_text = __('Edit', 'typerocket-domain');

                    if( !empty($data['edit_url']) && is_callable($data['edit_url']) ) {
                        $edit_url = call_user_func_array($data['edit_url'], [$edit_url, $result]);
                    }

                    if($edit_url) {
                        $links[] =  "<span class=\"edit\"><a href=\"{$edit_url}\">{$edit_text}</a></span>";
                    }
                }
                elseif($action == 'delete' && $delete_url)
                {
                    $delete_class = '';

                    if( !isset($data['delete_ajax']) || $data['delete_ajax'] === true ) {
                        $delete_url = wp_nonce_url($delete_url, 'form_' . Config::get('app.seed'), '_tr_nonce_form');
                        $delete_class = 'class="tr-delete-row-rest-button"';
                    }

                    $del_text = __('Delete', 'typerocket-domain');

                    if( !empty($data['delete_url']) && is_callable($data['delete_url']) ) {
                        $delete_url = call_user_func_array($data['delete_url'], [$delete_url, $result]);
                    }

                    if($delete_url) {
                        $links[] = "<span class=\"delete\"><a data-target=\"#{$row_id}\" {$delete_class} href=\"{$delete_url}\">{$del_text}</a></span>";
                    }
                }
                elseif($action == 'view')
                {
                    $view_text = __('View', 'typerocket-domain');
                    if( !empty($data['view_url']) && is_callable($data['view_url']) ) {
                        $show_url = call_user_func_array($data['view_url'], [$show_url, $result]);
                    }

                    if($show_url) {
                        $links[] = "<span class=\"view\"><a href=\"{$show_url}\">{$view_text}</a></span>";
                    }
                }
                elseif(is_callable($action))
                {
                    if($this->routeArgsCallback) {
                        $links[] = $action($result, $route_args, $this);
                    } else {
                        $links[] = $action($result);
                    }
                }
            }

            $text = TableRow::buildTableRowActions($text, $links, $result, $this, $data, $route_args);
        }

        return $text;
    }

    /**
     * Is Valid Search
     *
     * @return bool
     */
    protected function isValidSearch()
    {
        if( !empty($_GET['s']) && !empty($_GET['on'] && !empty($_GET['condition'])) ) {
            if(is_string($_GET['s']) && is_string($_GET['on']) && is_string($_GET['condition'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function getString() : string
    {
        return (string) $this;
    }

    /**
     * To String
     *
     * @return string
     */
    public function __toString()
    {
        ob_start();
        $this->render();
        return ob_get_clean();
    }

    /**
     * @param mixed ...$args
     *
     * @return static
     */
    public static function new(...$args)
    {
        return new static(...$args);
    }

}