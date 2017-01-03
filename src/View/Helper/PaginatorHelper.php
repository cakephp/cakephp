<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Helper;

use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\View\Helper;
use Cake\View\StringTemplateTrait;
use Cake\View\View;

/**
 * Pagination Helper class for easy generation of pagination links.
 *
 * PaginationHelper encloses all methods needed when working with pagination.
 *
 * @property \Cake\View\Helper\UrlHelper $Url
 * @property \Cake\View\Helper\NumberHelper $Number
 * @property \Cake\View\Helper\HtmlHelper $Html
 * @link http://book.cakephp.org/3.0/en/views/helpers/paginator.html
 */
class PaginatorHelper extends Helper
{

    use StringTemplateTrait;

    /**
     * List of helpers used by this helper
     *
     * @var array
     */
    public $helpers = ['Url', 'Number', 'Html'];

    /**
     * Default config for this class
     *
     * Options: Holds the default options for pagination links
     *
     * The values that may be specified are:
     *
     * - `url` Url of the action. See Router::url()
     * - `url['sort']`  the key that the recordset is sorted.
     * - `url['direction']` Direction of the sorting (default: 'asc').
     * - `url['page']` Page number to use in links.
     * - `model` The name of the model.
     * - `escape` Defines if the title field for the link should be escaped (default: true).
     *
     * Templates: the templates used by this class
     *
     * @var array
     */
    protected $_defaultConfig = [
        'options' => [],
        'templates' => [
            'nextActive' => '<li class="next"><a rel="next" href="{{url}}">{{text}}</a></li>',
            'nextDisabled' => '<li class="next disabled"><a href="" onclick="return false;">{{text}}</a></li>',
            'prevActive' => '<li class="prev"><a rel="prev" href="{{url}}">{{text}}</a></li>',
            'prevDisabled' => '<li class="prev disabled"><a href="" onclick="return false;">{{text}}</a></li>',
            'counterRange' => '{{start}} - {{end}} of {{count}}',
            'counterPages' => '{{page}} of {{pages}}',
            'first' => '<li class="first"><a href="{{url}}">{{text}}</a></li>',
            'last' => '<li class="last"><a href="{{url}}">{{text}}</a></li>',
            'number' => '<li><a href="{{url}}">{{text}}</a></li>',
            'current' => '<li class="active"><a href="">{{text}}</a></li>',
            'ellipsis' => '<li class="ellipsis">&hellip;</li>',
            'sort' => '<a href="{{url}}">{{text}}</a>',
            'sortAsc' => '<a class="asc" href="{{url}}">{{text}}</a>',
            'sortDesc' => '<a class="desc" href="{{url}}">{{text}}</a>',
            'sortAscLocked' => '<a class="asc locked" href="{{url}}">{{text}}</a>',
            'sortDescLocked' => '<a class="desc locked" href="{{url}}">{{text}}</a>',
        ]
    ];

    /**
     * Default model of the paged sets
     *
     * @var string
     */
    protected $_defaultModel;

    /**
     * Constructor. Overridden to merge passed args with URL options.
     *
     * @param \Cake\View\View $View The View this helper is being attached to.
     * @param array $config Configuration settings for the helper.
     */
    public function __construct(View $View, array $config = [])
    {
        parent::__construct($View, $config);

        $query = $this->request->getQueryParams();
        unset($query['page'], $query['limit'], $query['sort'], $query['direction']);
        $this->config(
            'options.url',
            array_merge($this->request->getParam('pass'), ['?' => $query])
        );
    }

    /**
     * Gets the current paging parameters from the resultset for the given model
     *
     * @param string|null $model Optional model name. Uses the default if none is specified.
     * @return array The array of paging parameters for the paginated resultset.
     */
    public function params($model = null)
    {
        if (empty($model)) {
            $model = $this->defaultModel();
        }
        if (!$this->request->getParam('paging') || !$this->request->getParam('paging.' . $model)) {
            return [];
        }

        return $this->request->getParam('paging.' . $model);
    }

    /**
     * Convenience access to any of the paginator params.
     *
     * @param string $key Key of the paginator params array to retrieve.
     * @param string|null $model Optional model name. Uses the default if none is specified.
     * @return mixed Content of the requested param.
     */
    public function param($key, $model = null)
    {
        $params = $this->params($model);
        if (!isset($params[$key])) {
            return null;
        }

        return $params[$key];
    }

    /**
     * Sets default options for all pagination links
     *
     * @param array $options Default options for pagination links.
     *   See PaginatorHelper::$options for list of keys.
     * @return void
     */
    public function options(array $options = [])
    {
        if (!empty($options['paging'])) {
            if (!$this->request->getParam('paging')) {
                $this->request->params['paging'] = [];
            }
            $this->request->params['paging'] = $options['paging'] + $this->request->getParam('paging');
            unset($options['paging']);
        }
        $model = $this->defaultModel();

        if (!empty($options[$model])) {
            if (!$this->request->getParam('paging.' . $model)) {
                $this->request->params['paging'][$model] = [];
            }
            $this->request->params['paging'][$model] = $options[$model] + $this->request->getParam('paging.' . $model);
            unset($options[$model]);
        }
        $this->_config['options'] = array_filter($options + $this->_config['options']);
        if (empty($this->_config['options']['url'])) {
            $this->_config['options']['url'] = [];
        }
        if (!empty($this->_config['options']['model'])) {
            $this->defaultModel($this->_config['options']['model']);
        }
    }

    /**
     * Gets the current page of the recordset for the given model
     *
     * @param string|null $model Optional model name. Uses the default if none is specified.
     * @return int The current page number of the recordset.
     * @link http://book.cakephp.org/3.0/en/views/helpers/paginator.html#checking-the-pagination-state
     */
    public function current($model = null)
    {
        $params = $this->params($model);

        if (isset($params['page'])) {
            return $params['page'];
        }

        return 1;
    }

    /**
     * Gets the total number of pages in the recordset for the given model.
     *
     * @param string|null $model Optional model name. Uses the default if none is specified.
     * @return int The total pages for the recordset.
     */
    public function total($model = null)
    {
        $params = $this->params($model);

        if (isset($params['pageCount'])) {
            return $params['pageCount'];
        }

        return 0;
    }

    /**
     * Gets the current key by which the recordset is sorted
     *
     * @param string|null $model Optional model name. Uses the default if none is specified.
     * @param array $options Options for pagination links. See #options for list of keys.
     * @return string|null The name of the key by which the recordset is being sorted, or
     *  null if the results are not currently sorted.
     * @link http://book.cakephp.org/3.0/en/views/helpers/paginator.html#creating-sort-links
     */
    public function sortKey($model = null, array $options = [])
    {
        if (empty($options)) {
            $options = $this->params($model);
        }
        if (!empty($options['sort'])) {
            return $options['sort'];
        }

        return null;
    }

    /**
     * Gets the current direction the recordset is sorted
     *
     * @param string|null $model Optional model name. Uses the default if none is specified.
     * @param array $options Options for pagination links. See #options for list of keys.
     * @return string The direction by which the recordset is being sorted, or
     *  null if the results are not currently sorted.
     * @link http://book.cakephp.org/3.0/en/views/helpers/paginator.html#creating-sort-links
     */
    public function sortDir($model = null, array $options = [])
    {
        $dir = null;

        if (empty($options)) {
            $options = $this->params($model);
        }

        if (isset($options['direction'])) {
            $dir = strtolower($options['direction']);
        }

        if ($dir === 'desc') {
            return 'desc';
        }

        return 'asc';
    }

    /**
     * Generate an active/inactive link for next/prev methods.
     *
     * @param string|bool $text The enabled text for the link.
     * @param bool $enabled Whether or not the enabled/disabled version should be created.
     * @param array $options An array of options from the calling method.
     * @param array $templates An array of templates with the 'active' and 'disabled' keys.
     * @return string Generated HTML
     */
    protected function _toggledLink($text, $enabled, $options, $templates)
    {
        $template = $templates['active'];
        if (!$enabled) {
            $text = $options['disabledTitle'];
            $template = $templates['disabled'];
        }

        if (!$enabled && $text === false) {
            return '';
        }
        $text = $options['escape'] ? h($text) : $text;

        $templater = $this->templater();
        $newTemplates = !empty($options['templates']) ? $options['templates'] : false;
        if ($newTemplates) {
            $templater->push();
            $templateMethod = is_string($options['templates']) ? 'load' : 'add';
            $templater->{$templateMethod}($options['templates']);
        }

        if (!$enabled) {
            $out = $templater->format($template, [
                'text' => $text,
            ]);

            if ($newTemplates) {
                $templater->pop();
            }

            return $out;
        }
        $paging = $this->params($options['model']);

        $url = array_merge(
            $options['url'],
            ['page' => $paging['page'] + $options['step']]
        );
        $url = $this->generateUrl($url, $options['model']);

        $out = $templater->format($template, [
            'url' => $url,
            'text' => $text,
        ]);

        if ($newTemplates) {
            $templater->pop();
        }

        return $out;
    }

    /**
     * Generates a "previous" link for a set of paged records
     *
     * ### Options:
     *
     * - `disabledTitle` The text to used when the link is disabled. This
     *   defaults to the same text at the active link. Setting to false will cause
     *   this method to return ''.
     * - `escape` Whether you want the contents html entity encoded, defaults to true
     * - `model` The model to use, defaults to PaginatorHelper::defaultModel()
     * - `url` An array of additional URL options to use for link generation.
     * - `templates` An array of templates, or template file name containing the
     *   templates you'd like to use when generating the link for previous page.
     *   The helper's original templates will be restored once prev() is done.
     *
     * @param string $title Title for the link. Defaults to '<< Previous'.
     * @param array $options Options for pagination link. See above for list of keys.
     * @return string A "previous" link or a disabled link.
     * @link http://book.cakephp.org/3.0/en/views/helpers/paginator.html#creating-jump-links
     */
    public function prev($title = '<< Previous', array $options = [])
    {
        $defaults = [
            'url' => [],
            'model' => $this->defaultModel(),
            'disabledTitle' => $title,
            'escape' => true,
        ];
        $options += $defaults;
        $options['step'] = -1;

        $enabled = $this->hasPrev($options['model']);
        $templates = [
            'active' => 'prevActive',
            'disabled' => 'prevDisabled'
        ];

        return $this->_toggledLink($title, $enabled, $options, $templates);
    }

    /**
     * Generates a "next" link for a set of paged records
     *
     * ### Options:
     *
     * - `disabledTitle` The text to used when the link is disabled. This
     *   defaults to the same text at the active link. Setting to false will cause
     *   this method to return ''.
     * - `escape` Whether you want the contents html entity encoded, defaults to true
     * - `model` The model to use, defaults to PaginatorHelper::defaultModel()
     * - `url` An array of additional URL options to use for link generation.
     * - `templates` An array of templates, or template file name containing the
     *   templates you'd like to use when generating the link for next page.
     *   The helper's original templates will be restored once next() is done.
     *
     * @param string $title Title for the link. Defaults to 'Next >>'.
     * @param array $options Options for pagination link. See above for list of keys.
     * @return string A "next" link or $disabledTitle text if the link is disabled.
     * @link http://book.cakephp.org/3.0/en/views/helpers/paginator.html#creating-jump-links
     */
    public function next($title = 'Next >>', array $options = [])
    {
        $defaults = [
            'url' => [],
            'model' => $this->defaultModel(),
            'disabledTitle' => $title,
            'escape' => true,
        ];
        $options += $defaults;
        $options['step'] = 1;

        $enabled = $this->hasNext($options['model']);
        $templates = [
            'active' => 'nextActive',
            'disabled' => 'nextDisabled'
        ];

        return $this->_toggledLink($title, $enabled, $options, $templates);
    }

    /**
     * Generates a sorting link. Sets named parameters for the sort and direction. Handles
     * direction switching automatically.
     *
     * ### Options:
     *
     * - `escape` Whether you want the contents html entity encoded, defaults to true.
     * - `model` The model to use, defaults to PaginatorHelper::defaultModel().
     * - `direction` The default direction to use when this link isn't active.
     * - `lock` Lock direction. Will only use the default direction then, defaults to false.
     *
     * @param string $key The name of the key that the recordset should be sorted.
     * @param string|null $title Title for the link. If $title is null $key will be used
     *   for the title and will be generated by inflection.
     * @param array $options Options for sorting link. See above for list of keys.
     * @return string A link sorting default by 'asc'. If the resultset is sorted 'asc' by the specified
     *  key the returned link will sort by 'desc'.
     * @link http://book.cakephp.org/3.0/en/views/helpers/paginator.html#creating-sort-links
     */
    public function sort($key, $title = null, array $options = [])
    {
        $options += ['url' => [], 'model' => null, 'escape' => true];
        $url = $options['url'];
        unset($options['url']);

        if (empty($title)) {
            $title = $key;

            if (strpos($title, '.') !== false) {
                $title = str_replace('.', ' ', $title);
            }

            $title = __(Inflector::humanize(preg_replace('/_id$/', '', $title)));
        }
        $defaultDir = isset($options['direction']) ? strtolower($options['direction']) : 'asc';
        unset($options['direction']);

        $locked = isset($options['lock']) ? $options['lock'] : false;
        unset($options['lock']);

        $sortKey = $this->sortKey($options['model']);
        $defaultModel = $this->defaultModel();
        $model = $options['model'] ?: $defaultModel;
        list($table, $field) = explode('.', $key . '.');
        if (!$field) {
            $field = $table;
            $table = $model;
        }
        $isSorted = (
            $sortKey === $table . '.' . $field ||
            $sortKey === $defaultModel . '.' . $key ||
            $table . '.' . $field === $defaultModel . '.' . $sortKey
        );

        $template = 'sort';
        $dir = $defaultDir;
        if ($isSorted) {
            if ($locked) {
                $template = $dir === 'asc' ? 'sortDescLocked' : 'sortAscLocked';
            } else {
                $dir = $this->sortDir($options['model']) === 'asc' ? 'desc' : 'asc';
                $template = $dir === 'asc' ? 'sortDesc' : 'sortAsc';
            }
        }
        if (is_array($title) && array_key_exists($dir, $title)) {
            $title = $title[$dir];
        }

        $url = array_merge(
            ['sort' => $key, 'direction' => $dir],
            $url,
            ['order' => null]
        );
        $vars = [
            'text' => $options['escape'] ? h($title) : $title,
            'url' => $this->generateUrl($url, $options['model']),
        ];

        return $this->templater()->format($template, $vars);
    }

    /**
     * Merges passed URL options with current pagination state to generate a pagination URL.
     *
     * ### Url options:
     *
     * - `escape`: If false, the URL will be returned unescaped, do only use if it is manually
     *    escaped afterwards before being displayed.
     * - `fullBase`: If true, the full base URL will be prepended to the result
     *
     * @param array $options Pagination/URL options array
     * @param string|null $model Which model to paginate on
     * @param array|bool $urlOptions Array of options or bool `fullBase` for BC reasons.
     * @return string By default, returns a full pagination URL string for use in non-standard contexts (i.e. JavaScript)
     * @link http://book.cakephp.org/3.0/en/views/helpers/paginator.html#generating-pagination-urls
     */
    public function generateUrl(array $options = [], $model = null, $urlOptions = false)
    {
        if (!is_array($urlOptions)) {
            $urlOptions = ['fullBase' => $urlOptions];
        }
        $urlOptions += [
            'escape' => true,
            'fullBase' => false
        ];

        return $this->Url->build($this->generateUrlParams($options, $model), $urlOptions);
    }

    /**
     * Merges passed URL options with current pagination state to generate a pagination URL.
     *
     * @param array $options Pagination/URL options array
     * @param string|null $model Which model to paginate on
     * @return array An array of URL parameters
     */
    public function generateUrlParams(array $options = [], $model = null)
    {
        $paging = $this->params($model);
        $paging += ['page' => null, 'sort' => null, 'direction' => null, 'limit' => null];

        $url = [
            'page' => $paging['page'],
            'limit' => $paging['limit'],
            'sort' => $paging['sort'],
            'direction' => $paging['direction'],
        ];

        if (!empty($this->_config['options']['url'])) {
            $key = implode('.', array_filter(['options.url', Hash::get($paging, 'scope', null)]));
            $url = array_merge($url, Hash::get($this->_config, $key, []));
        }

        $url = array_filter($url, function ($value) {
            return ($value || is_numeric($value));
        });
        $url = array_merge($url, $options);

        if (!empty($url['page']) && $url['page'] == 1) {
            $url['page'] = false;
        }
        if (isset($paging['sortDefault'], $paging['directionDefault'], $url['sort'], $url['direction']) &&
            $url['sort'] === $paging['sortDefault'] &&
            $url['direction'] === $paging['directionDefault']
        ) {
            $url['sort'] = $url['direction'] = null;
        }

        if (!empty($paging['scope'])) {
            $scope = $paging['scope'];
            $currentParams = $this->_config['options']['url'];
            // Merge existing query parameters in the scope.
            if (isset($currentParams['?'][$scope]) && is_array($currentParams['?'][$scope])) {
                $url += $currentParams['?'][$scope];
                unset($currentParams['?'][$scope]);
            }
            $url = [$scope => $url] + $currentParams;
            if (empty($url[$scope]['page'])) {
                unset($url[$scope]['page']);
            }
        }

        return $url;
    }

    /**
     * Returns true if the given result set is not at the first page
     *
     * @param string|null $model Optional model name. Uses the default if none is specified.
     * @return bool True if the result set is not at the first page.
     * @link http://book.cakephp.org/3.0/en/views/helpers/paginator.html#checking-the-pagination-state
     */
    public function hasPrev($model = null)
    {
        return $this->_hasPage($model, 'prev');
    }

    /**
     * Returns true if the given result set is not at the last page
     *
     * @param string|null $model Optional model name. Uses the default if none is specified.
     * @return bool True if the result set is not at the last page.
     * @link http://book.cakephp.org/3.0/en/views/helpers/paginator.html#checking-the-pagination-state
     */
    public function hasNext($model = null)
    {
        return $this->_hasPage($model, 'next');
    }

    /**
     * Returns true if the given result set has the page number given by $page
     *
     * @param string|null $model Optional model name. Uses the default if none is specified.
     * @param int $page The page number - if not set defaults to 1.
     * @return bool True if the given result set has the specified page number.
     * @link http://book.cakephp.org/3.0/en/views/helpers/paginator.html#checking-the-pagination-state
     */
    public function hasPage($model = null, $page = 1)
    {
        if (is_numeric($model)) {
            $page = $model;
            $model = null;
        }
        $paging = $this->params($model);
        if ($paging === []) {
            return false;
        }

        return $page <= $paging['pageCount'];
    }

    /**
     * Does $model have $page in its range?
     *
     * @param string $model Model name to get parameters for.
     * @param int $page Page number you are checking.
     * @return bool Whether model has $page
     */
    protected function _hasPage($model, $page)
    {
        $params = $this->params($model);

        return !empty($params) && $params[$page . 'Page'];
    }

    /**
     * Gets or sets the default model of the paged sets
     *
     * @param string|null $model Model name to set
     * @return string|null Model name or null if the pagination isn't initialized.
     */
    public function defaultModel($model = null)
    {
        if ($model !== null) {
            $this->_defaultModel = $model;
        }
        if ($this->_defaultModel) {
            return $this->_defaultModel;
        }
        if (!$this->request->getParam('paging')) {
            return null;
        }
        list($this->_defaultModel) = array_keys($this->request->getParam('paging'));

        return $this->_defaultModel;
    }

    /**
     * Returns a counter string for the paged result set
     *
     * ### Options
     *
     * - `model` The model to use, defaults to PaginatorHelper::defaultModel();
     * - `format` The format string you want to use, defaults to 'pages' Which generates output like '1 of 5'
     *    set to 'range' to generate output like '1 - 3 of 13'. Can also be set to a custom string, containing
     *    the following placeholders `{{page}}`, `{{pages}}`, `{{current}}`, `{{count}}`, `{{model}}`, `{{start}}`, `{{end}}` and any
     *    custom content you would like.
     *
     * @param string|array $options Options for the counter string. See #options for list of keys.
     *   If string it will be used as format.
     * @return string Counter string.
     * @link http://book.cakephp.org/3.0/en/views/helpers/paginator.html#creating-a-page-counter
     */
    public function counter($options = [])
    {
        if (is_string($options)) {
            $options = ['format' => $options];
        }

        $options += [
            'model' => $this->defaultModel(),
            'format' => 'pages',
        ];

        $paging = $this->params($options['model']);
        if (!$paging['pageCount']) {
            $paging['pageCount'] = 1;
        }
        $start = 0;
        if ($paging['count'] >= 1) {
            $start = (($paging['page'] - 1) * $paging['perPage']) + 1;
        }
        $end = $start + $paging['perPage'] - 1;
        if ($paging['count'] < $end) {
            $end = $paging['count'];
        }

        switch ($options['format']) {
            case 'range':
            case 'pages':
                $template = 'counter' . ucfirst($options['format']);
                break;
            default:
                $template = 'counterCustom';
                $this->templater()->add([$template => $options['format']]);
        }
        $map = array_map([$this->Number, 'format'], [
            'page' => $paging['page'],
            'pages' => $paging['pageCount'],
            'current' => $paging['current'],
            'count' => $paging['count'],
            'start' => $start,
            'end' => $end
        ]);

        $map += [
            'model' => strtolower(Inflector::humanize(Inflector::tableize($options['model'])))
        ];

        return $this->templater()->format($template, $map);
    }

    /**
     * Returns a set of numbers for the paged result set
     * uses a modulus to decide how many numbers to show on each side of the current page (default: 8).
     *
     * ```
     * $this->Paginator->numbers(['first' => 2, 'last' => 2]);
     * ```
     *
     * Using the first and last options you can create links to the beginning and end of the page set.
     *
     * ### Options
     *
     * - `before` Content to be inserted before the numbers, but after the first links.
     * - `after` Content to be inserted after the numbers, but before the last links.
     * - `model` Model to create numbers for, defaults to PaginatorHelper::defaultModel()
     * - `modulus` How many numbers to include on either side of the current page, defaults to 8.
     *    Set to `false` to disable and to show all numbers.
     * - `first` Whether you want first links generated, set to an integer to define the number of 'first'
     *    links to generate. If a string is set a link to the first page will be generated with the value
     *    as the title.
     * - `last` Whether you want last links generated, set to an integer to define the number of 'last'
     *    links to generate. If a string is set a link to the last page will be generated with the value
     *    as the title.
     * - `templates` An array of templates, or template file name containing the templates you'd like to
     *    use when generating the numbers. The helper's original templates will be restored once
     *    numbers() is done.
     * - `url` An array of additional URL options to use for link generation.
     *
     * The generated number links will include the 'ellipsis' template when the `first` and `last` options
     * and the number of pages exceed the modulus. For example if you have 25 pages, and use the first/last
     * options and a modulus of 8, ellipsis content will be inserted after the first and last link sets.
     *
     * @param array $options Options for the numbers.
     * @return string|false Numbers string.
     * @link http://book.cakephp.org/3.0/en/views/helpers/paginator.html#creating-page-number-links
     */
    public function numbers(array $options = [])
    {
        $defaults = [
            'before' => null, 'after' => null, 'model' => $this->defaultModel(),
            'modulus' => 8, 'first' => null, 'last' => null, 'url' => []
        ];
        $options += $defaults;

        $params = (array)$this->params($options['model']) + ['page' => 1];
        if ($params['pageCount'] <= 1) {
            return false;
        }

        $templater = $this->templater();
        if (isset($options['templates'])) {
            $templater->push();
            $method = is_string($options['templates']) ? 'load' : 'add';
            $templater->{$method}($options['templates']);
        }

        if ($options['modulus'] !== false && $params['pageCount'] > $options['modulus']) {
            $out = $this->_modulusNumbers($templater, $params, $options);
        } else {
            $out = $this->_numbers($templater, $params, $options);
        }

        if (isset($options['templates'])) {
            $templater->pop();
        }

        return $out;
    }

    /**
     * Calculates the start and end for the pagination numbers.
     *
     * @param array $params Params from the numbers() method.
     * @param array $options Options from the numbers() method.
     * @return array An array with the start and end numbers.
     */
    protected function _getNumbersStartAndEnd($params, $options)
    {
        $half = (int)($options['modulus'] / 2);
        $end = $params['page'] + $half;

        if ($end > $params['pageCount']) {
            $end = $params['pageCount'];
        }
        $start = $params['page'] - ($options['modulus'] - ($end - $params['page']));
        if ($start <= 1) {
            $start = 1;
            $end = $params['page'] + ($options['modulus'] - $params['page']) + 1;
        }

        return [$start, $end];
    }

    /**
     * Formats a number for the paginator number output.
     *
     * @param \Cake\View\StringTemplate $templater StringTemplate instance.
     * @param array $options Options from the numbers() method.
     * @return string
     */
    protected function _formatNumber($templater, $options)
    {
        $url = array_merge($options['url'], ['page' => $options['page']]);
        $vars = [
            'text' => $options['text'],
            'url' => $this->generateUrl($url, $options['model']),
        ];

        return $templater->format('number', $vars);
    }

    /**
     * Generates the numbers for the paginator numbers() method.
     *
     * @param \Cake\View\StringTemplate $templater StringTemplate instance.
     * @param array $params Params from the numbers() method.
     * @param array $options Options from the numbers() method.
     * @return string Markup output.
     */
    protected function _modulusNumbers($templater, $params, $options)
    {
        $out = '';
        $ellipsis = $templater->format('ellipsis', []);

        list($start, $end) = $this->_getNumbersStartAndEnd($params, $options);

        $out .= $this->_firstNumber($ellipsis, $params, $start, $options);
        $out .= $options['before'];

        for ($i = $start; $i < $params['page']; $i++) {
            $out .= $this->_formatNumber($templater, [
                'text' => $this->Number->format($i),
                'page' => $i,
                'model' => $options['model'],
                'url' => $options['url'],
            ]);
        }

        $url = array_merge($options['url'], ['page' => $params['page']]);
        $out .= $templater->format('current', [
            'text' => $this->Number->format($params['page']),
            'url' => $this->generateUrl($url, $options['model']),
        ]);

        $start = $params['page'] + 1;
        for ($i = $start; $i < $end; $i++) {
            $out .= $this->_formatNumber($templater, [
                'text' => $this->Number->format($i),
                'page' => $i,
                'model' => $options['model'],
                'url' => $options['url'],
            ]);
        }

        if ($end != $params['page']) {
            $out .= $this->_formatNumber($templater, [
                'text' => $this->Number->format($i),
                'page' => $end,
                'model' => $options['model'],
                'url' => $options['url'],
            ]);
        }

        $out .= $options['after'];
        $out .= $this->_lastNumber($ellipsis, $params, $end, $options);

        return $out;
    }

    /**
     * Generates the first number for the paginator numbers() method.
     *
     * @param \Cake\View\StringTemplate $ellipsis StringTemplate instance.
     * @param array $params Params from the numbers() method.
     * @param int $start Start number.
     * @param array $options Options from the numbers() method.
     * @return string Markup output.
     */
    protected function _firstNumber($ellipsis, $params, $start, $options)
    {
        $out = '';
        $first = is_int($options['first']) ? $options['first'] : 0;
        if ($options['first'] && $start > 1) {
            $offset = ($start <= $first) ? $start - 1 : $options['first'];
            $out .= $this->first($offset, $options);
            if ($first < $start - 1) {
                $out .= $ellipsis;
            }
        }

        return $out;
    }

    /**
     * Generates the last number for the paginator numbers() method.
     *
     * @param \Cake\View\StringTemplate $ellipsis StringTemplate instance.
     * @param array $params Params from the numbers() method.
     * @param int $end End number.
     * @param array $options Options from the numbers() method.
     * @return string Markup output.
     */
    protected function _lastNumber($ellipsis, $params, $end, $options)
    {
        $out = '';
        $last = is_int($options['last']) ? $options['last'] : 0;
        if ($options['last'] && $end < $params['pageCount']) {
            $offset = ($params['pageCount'] < $end + $last) ? $params['pageCount'] - $end : $options['last'];
            if ($offset <= $options['last'] && $params['pageCount'] - $end > $last) {
                $out .= $ellipsis;
            }
            $out .= $this->last($offset, $options);
        }

        return $out;
    }

    /**
     * Generates the numbers for the paginator numbers() method.
     *
     * @param \Cake\View\StringTemplate $templater StringTemplate instance.
     * @param array $params Params from the numbers() method.
     * @param array $options Options from the numbers() method.
     * @return string Markup output.
     */
    protected function _numbers($templater, $params, $options)
    {
        $out = '';
        $out .= $options['before'];
        for ($i = 1; $i <= $params['pageCount']; $i++) {
            $url = array_merge($options['url'], ['page' => $i]);
            if ($i == $params['page']) {
                $out .= $templater->format('current', [
                    'text' => $this->Number->format($params['page']),
                    'url' => $this->generateUrl($url, $options['model']),
                ]);
            } else {
                $vars = [
                    'text' => $this->Number->format($i),
                    'url' => $this->generateUrl($url, $options['model']),
                ];
                $out .= $templater->format('number', $vars);
            }
        }
        $out .= $options['after'];

        return $out;
    }

    /**
     * Returns a first or set of numbers for the first pages.
     *
     * ```
     * echo $this->Paginator->first('< first');
     * ```
     *
     * Creates a single link for the first page. Will output nothing if you are on the first page.
     *
     * ```
     * echo $this->Paginator->first(3);
     * ```
     *
     * Will create links for the first 3 pages, once you get to the third or greater page. Prior to that
     * nothing will be output.
     *
     * ### Options:
     *
     * - `model` The model to use defaults to PaginatorHelper::defaultModel()
     * - `escape` Whether or not to HTML escape the text.
     * - `url` An array of additional URL options to use for link generation.
     *
     * @param string|int $first if string use as label for the link. If numeric, the number of page links
     *   you want at the beginning of the range.
     * @param array $options An array of options.
     * @return string|false Numbers string.
     * @link http://book.cakephp.org/3.0/en/views/helpers/paginator.html#creating-jump-links
     */
    public function first($first = '<< first', array $options = [])
    {
        $options += [
            'url' => [],
            'model' => $this->defaultModel(),
            'escape' => true
        ];

        $params = $this->params($options['model']);

        if ($params['pageCount'] <= 1) {
            return false;
        }

        $out = '';

        if (is_int($first) && $params['page'] >= $first) {
            for ($i = 1; $i <= $first; $i++) {
                $url = array_merge($options['url'], ['page' => $i]);
                $out .= $this->templater()->format('number', [
                    'url' => $this->generateUrl($url, $options['model']),
                    'text' => $this->Number->format($i)
                ]);
            }
        } elseif ($params['page'] > 1 && is_string($first)) {
            $first = $options['escape'] ? h($first) : $first;
            $out .= $this->templater()->format('first', [
                'url' => $this->generateUrl(['page' => 1], $options['model']),
                'text' => $first
            ]);
        }

        return $out;
    }

    /**
     * Returns a last or set of numbers for the last pages.
     *
     * ```
     * echo $this->Paginator->last('last >');
     * ```
     *
     * Creates a single link for the last page. Will output nothing if you are on the last page.
     *
     * ```
     * echo $this->Paginator->last(3);
     * ```
     *
     * Will create links for the last 3 pages. Once you enter the page range, no output will be created.
     *
     * ### Options:
     *
     * - `model` The model to use defaults to PaginatorHelper::defaultModel()
     * - `escape` Whether or not to HTML escape the text.
     * - `url` An array of additional URL options to use for link generation.
     *
     * @param string|int $last if string use as label for the link, if numeric print page numbers
     * @param array $options Array of options
     * @return string|false Numbers string.
     * @link http://book.cakephp.org/3.0/en/views/helpers/paginator.html#creating-jump-links
     */
    public function last($last = 'last >>', array $options = [])
    {
        $options += [
            'model' => $this->defaultModel(),
            'escape' => true,
            'url' => []
        ];
        $params = $this->params($options['model']);

        if ($params['pageCount'] <= 1) {
            return false;
        }

        $out = '';
        $lower = (int)$params['pageCount'] - (int)$last + 1;

        if (is_int($last) && $params['page'] <= $lower) {
            for ($i = $lower; $i <= $params['pageCount']; $i++) {
                $url = array_merge($options['url'], ['page' => $i]);
                $out .= $this->templater()->format('number', [
                    'url' => $this->generateUrl($url, $options['model']),
                    'text' => $this->Number->format($i)
                ]);
            }
        } elseif ($params['page'] < $params['pageCount'] && is_string($last)) {
            $last = $options['escape'] ? h($last) : $last;
            $out .= $this->templater()->format('last', [
                'url' => $this->generateUrl(['page' => $params['pageCount']], $options['model']),
                'text' => $last
            ]);
        }

        return $out;
    }

    /**
     * Returns the meta-links for a paginated result set.
     *
     * ```
     * echo $this->Paginator->meta();
     * ```
     *
     * Echos the links directly, will output nothing if there is neither a previous nor next page.
     *
     * ```
     * $this->Paginator->meta(['block' => true]);
     * ```
     *
     * Will append the output of the meta function to the named block - if true is passed the "meta"
     * block is used.
     *
     * ### Options:
     *
     * - `model` The model to use defaults to PaginatorHelper::defaultModel()
     * - `block` The block name to append the output to, or false/absent to return as a string
     * - `prev` (default True) True to generate meta for previous page
     * - `next` (default True) True to generate meta for next page
     * - `first` (default False) True to generate meta for first page
     * - `last` (default False) True to generate meta for last page
     *
     * @param array $options Array of options
     * @return string|null Meta links
     */
    public function meta(array $options = [])
    {
        $options += [
                'model' => null,
                'block' => false,
                'prev' => true,
                'next' => true,
                'first' => false,
                'last' => false
            ];

        $model = isset($options['model']) ? $options['model'] : null;
        $params = $this->params($model);
        $links = [];

        if ($options['prev'] && $this->hasPrev()) {
            $links[] = $this->Html->meta('prev', $this->generateUrl(['page' => $params['page'] - 1], null, true));
        }

        if ($options['next'] && $this->hasNext()) {
            $links[] = $this->Html->meta('next', $this->generateUrl(['page' => $params['page'] + 1], null, true));
        }

        if ($options['first']) {
            $links[] = $this->Html->meta('first', $this->generateUrl(['page' => 1], null, true));
        }

        if ($options['last']) {
            $links[] = $this->Html->meta('last', $this->generateUrl(['page' => $params['pageCount']], null, true));
        }

        $out = implode($links);

        if ($options['block'] === true) {
            $options['block'] = __FUNCTION__;
        }

        if ($options['block']) {
            $this->_View->append($options['block'], $out);

            return null;
        }

        return $out;
    }

    /**
     * Event listeners.
     *
     * @return array
     */
    public function implementedEvents()
    {
        return [];
    }
}
