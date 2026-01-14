<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Helper;

use Cake\Core\Exception\CakeException;
use Cake\Datasource\Paging\PaginatedInterface;
use Cake\Datasource\Paging\SortField;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\View\Helper;
use Cake\View\StringTemplate;
use Cake\View\StringTemplateTrait;
use Cake\View\View;
use InvalidArgumentException;
use function Cake\Core\h;
use function Cake\I18n\__;

/**
 * Pagination Helper class for easy generation of pagination links.
 *
 * PaginationHelper encloses all methods needed when working with pagination.
 *
 * @property \Cake\View\Helper\UrlHelper $Url
 * @property \Cake\View\Helper\NumberHelper $Number
 * @property \Cake\View\Helper\HtmlHelper $Html
 * @property \Cake\View\Helper\FormHelper $Form
 * @link https://book.cakephp.org/5/en/views/helpers/paginator.html
 */
class PaginatorHelper extends Helper
{
    use StringTemplateTrait;

    /**
     * List of helpers used by this helper
     *
     * @var array
     */
    protected array $helpers = ['Url', 'Number', 'Html', 'Form'];

    /**
     * Default config for this class
     *
     * Options: Holds the default options for pagination links
     *
     * The values that may be specified are:
     *
     * - `url` Url of the action. See Router::url()
     * - `url['?']['sort']` the key that the recordset is sorted.
     * - `url['?']['direction']` Direction of the sorting (default: 'asc').
     * - `url['?']['page']` Page number to use in links.
     * - `escape` Defines if the title field for the link should be escaped (default: true).
     * - `sortFormat` Format for sort URLs: 'separate' (default, ?sort=x&direction=y) or 'combined' (?sort=x-y).
     * - `routePlaceholders` An array specifying which paging params should be
     *   passed as route placeholders instead of query string parameters. The array
     *   can have values `'sort'`, `'direction'`, `'page'`.
     *
     * Templates: the templates used by this class
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'params' => [],
        'options' => [
            'sortFormat' => 'separate',
        ],
        'templates' => [
            'nextActive' => '<li class="next"><a rel="next" href="{{url}}">{{text}}</a></li>',
            'nextDisabled' => '<li class="next disabled"><a>{{text}}</a></li>',
            'prevActive' => '<li class="prev"><a rel="prev" href="{{url}}">{{text}}</a></li>',
            'prevDisabled' => '<li class="prev disabled"><a>{{text}}</a></li>',
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
        ],
    ];

    /**
     * Paginated results
     *
     * @var \Cake\Datasource\Paging\PaginatedInterface|null
     */
    protected ?PaginatedInterface $paginated = null;

    /**
     * Constructor. Overridden to merge passed args with URL options.
     *
     * @param \Cake\View\View $view The View this helper is being attached to.
     * @param array<string, mixed> $config Configuration settings for the helper.
     */
    public function __construct(View $view, array $config = [])
    {
        parent::__construct($view, $config);

        $query = $this->_View->getRequest()->getQueryParams();
        unset($query['page'], $query['limit'], $query['sort'], $query['direction']);
        $this->setConfig(
            'options.url',
            array_merge($this->_View->getRequest()->getParam('pass', []), ['?' => $query]),
        );
    }

    /**
     * Set paginated results.
     *
     * @param \Cake\Datasource\Paging\PaginatedInterface $paginated Instance to use.
     * @param array<string, mixed> $options Options array.
     * @return void
     */
    public function setPaginated(PaginatedInterface $paginated, array $options = []): void
    {
        $this->paginated = $paginated;
        $this->options($options);
    }

    /**
     * Get pagination instance.
     *
     * @return \Cake\Datasource\Paging\PaginatedInterface
     */
    protected function paginated(): PaginatedInterface
    {
        if (!isset($this->paginated)) {
            foreach ($this->_View->getVars() as $name) {
                $value = $this->_View->get($name);
                if ($value instanceof PaginatedInterface) {
                    $this->paginated = $value;
                }
            }
        }

        if (!isset($this->paginated)) {
            throw new CakeException('You must set a pagination instance using `setPaginated()` first');
        }

        return $this->paginated;
    }

    /**
     * Gets the current paging parameters from the result set for the given model
     *
     * @return array The array of paging parameters for the paginated result set.
     */
    public function params(): array
    {
        return $this->getConfig('params') + $this->paginated()->pagingParams();
    }

    /**
     * Convenience access to any of the paginator params.
     *
     * @param string $key Key of the paginator params array to retrieve.
     * @return mixed Content of the requested param.
     */
    public function param(string $key): mixed
    {
        return $this->params()[$key] ?? null;
    }

    /**
     * Sets default options for all pagination links
     *
     * @param array<string, mixed> $options Default options for pagination links.
     *   See PaginatorHelper::$options for list of keys.
     * @return void
     */
    public function options(array $options = []): void
    {
        if (!empty($options['paging'])) {
            $this->_config['params'] = $options['paging'];
            unset($options['paging']);
        }

        $this->_config['options'] = array_filter($options + $this->_config['options']);
        if (empty($this->_config['options']['url'])) {
            $this->_config['options']['url'] = [];
        }
    }

    /**
     * Gets the current page of the recordset for the given model
     *
     * @return int The current page number of the recordset.
     * @link https://book.cakephp.org/5/en/views/helpers/paginator.html#checking-the-pagination-state
     */
    public function current(): int
    {
        return $this->paginated()->currentPage();
    }

    /**
     * Gets the total number of pages in the recordset for the given model.
     *
     * @return int|null The total pages for the recordset.
     */
    public function total(): ?int
    {
        return $this->paginated()->pageCount();
    }

    /**
     * Gets the current direction the recordset is sorted
     *
     * @return string The direction by which the recordset is being sorted, or
     *  null if the results are not currently sorted.
     */
    protected function sortDir(): string
    {
        $dir = strtolower((string)$this->param('direction'));

        if ($dir === 'desc') {
            return 'desc';
        }

        return 'asc';
    }

    /**
     * Generate an active/inactive link for next/prev methods.
     *
     * @param string|false $text The enabled text for the link.
     * @param bool $enabled Whether the enabled/disabled version should be created.
     * @param array<string, mixed> $options An array of options from the calling method.
     * @param array<string, mixed> $templates An array of templates with the 'active' and 'disabled' keys.
     * @return string Generated HTML
     */
    protected function _toggledLink(string|false $text, bool $enabled, array $options, array $templates): string
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
        $newTemplates = $options['templates'] ?? false;
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

        $url = $this->generateUrl(
            ['page' => $this->paginated()->currentPage() + $options['step']],
            $options['url'],
        );

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
     * - `url` An array of additional URL options to use for link generation.
     * - `templates` An array of templates, or template file name containing the
     *   templates you'd like to use when generating the link for previous page.
     *   The helper's original templates will be restored once prev() is done.
     *
     * @param string $title Title for the link. Defaults to '<< Previous'.
     * @param array<string, mixed> $options Options for pagination link. See above for list of keys.
     * @return string A "previous" link or a disabled link.
     * @link https://book.cakephp.org/5/en/views/helpers/paginator.html#creating-jump-links
     */
    public function prev(string $title = '<< Previous', array $options = []): string
    {
        $defaults = [
            'url' => [],
            'disabledTitle' => $title,
            'escape' => true,
        ];
        $options += $defaults;
        $options['step'] = -1;

        $templates = [
            'active' => 'prevActive',
            'disabled' => 'prevDisabled',
        ];

        return $this->_toggledLink($title, $this->hasPrev(), $options, $templates);
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
     * - `url` An array of additional URL options to use for link generation.
     * - `templates` An array of templates, or template file name containing the
     *   templates you'd like to use when generating the link for next page.
     *   The helper's original templates will be restored once next() is done.
     *
     * @param string $title Title for the link. Defaults to 'Next >>'.
     * @param array<string, mixed> $options Options for pagination link. See above for list of keys.
     * @return string A "next" link or $disabledTitle text if the link is disabled.
     * @link https://book.cakephp.org/5/en/views/helpers/paginator.html#creating-jump-links
     */
    public function next(string $title = 'Next >>', array $options = []): string
    {
        $defaults = [
            'url' => [],
            'disabledTitle' => $title,
            'escape' => true,
        ];
        $options += $defaults;
        $options['step'] = 1;

        $templates = [
            'active' => 'nextActive',
            'disabled' => 'nextDisabled',
        ];

        return $this->_toggledLink($title, $this->hasNext(), $options, $templates);
    }

    /**
     * Generates a sorting link. Sets named parameters for the sort and direction. Handles
     * direction switching automatically.
     *
     * ### Options:
     *
     * - `escape` Whether you want the contents html entity encoded, defaults to true.
     * - `direction` The default direction to use when this link isn't active.
     * - `lock` Lock direction. Will only use the default direction then, defaults to false.
     *
     * @param string $key The name of the key that the recordset should be sorted.
     * @param array<string, mixed>|string|null $title Title for the link. If $title is null $key will be used
     *   for the title and will be generated by inflection. It can also be an array
     *   with keys `asc` and `desc` for specifying separate titles based on the direction.
     * @param array<string, mixed> $options Options for sorting link. See above for list of keys.
     * @return string A link sorting default by 'asc'. If the result set is sorted 'asc' by the specified
     *  key the returned link will sort by 'desc'.
     * @link https://book.cakephp.org/5/en/views/helpers/paginator.html#creating-sort-links
     */
    public function sort(string $key, array|string|null $title = null, array $options = []): string
    {
        $options += ['url' => [], 'escape' => true];
        $url = $options['url'];
        unset($options['url']);

        if (!$title) {
            $title = $key;

            if (str_contains($title, '.')) {
                $title = str_replace('.', ' ', $title);
            }

            $title = __(Inflector::humanize((string)preg_replace('/_id$/', '', $title)));
        }

        if (!isset($options['direction']) || !isset($options['lock'])) {
            $sortableFields = $this->param('sortableFields');
            if ($sortableFields && isset($sortableFields[$key])) {
                $fieldConfig = $sortableFields[$key];

                // Handle array of SortField objects
                if (is_array($fieldConfig) && isset($fieldConfig[0]) && $fieldConfig[0] instanceof SortField) {
                    $sortField = $fieldConfig[0];

                    if (!isset($options['direction'])) {
                        // Get the default direction (asc if not set, or the locked direction)
                        $options['direction'] = $sortField->getDirection(SortField::ASC, false);
                    }
                    if (!isset($options['lock'])) {
                        $options['lock'] = $sortField->isLocked();
                    }
                }
            }
        }

        $defaultDir = isset($options['direction']) ? strtolower($options['direction']) : 'asc';
        unset($options['direction']);

        $locked = $options['lock'] ?? false;
        unset($options['lock']);

        $sortKey = (string)$this->param('sort');
        $alias = $this->param('alias');
        [$table, $field] = explode('.', $key . '.');
        if (!$field) {
            $field = $table;
            $table = $alias;
        }
        $isSorted = (
            $sortKey === $table . '.' . $field ||
            $sortKey === $alias . '.' . $key ||
            $table . '.' . $field === $alias . '.' . $sortKey
        );

        $template = 'sort';
        $dir = $defaultDir;
        if ($isSorted) {
            if ($locked) {
                $template = $dir === 'asc' ? 'sortAscLocked' : 'sortDescLocked';
            } else {
                $dir = $this->sortDir() === 'asc' ? 'desc' : 'asc';
                $template = $dir === 'asc' ? 'sortDesc' : 'sortAsc';
            }
        }
        if (is_array($title) && array_key_exists($dir, $title)) {
            $title = $title[$dir];
        }

        $sortFormat = $this->getConfig('options.sortFormat', 'separate');
        if ($sortFormat === 'combined') {
            $paging = ['sort' => $key . '-' . $dir, 'direction' => null, 'page' => 1];
        } else {
            $paging = ['sort' => $key, 'direction' => $dir, 'page' => 1];
        }

        $vars = [
            'text' => $options['escape'] ? h($title) : $title,
            'url' => $this->generateUrl($paging, $url),
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
     * @param array<string, mixed> $options Pagination options.
     * @param array $url URL.
     * @param array<string, mixed> $urlOptions Array of options
     * @return string By default, returns a full pagination URL string for use
     *   in non-standard contexts (i.e. JavaScript)
     * @link https://book.cakephp.org/5/en/views/helpers/paginator.html#generating-pagination-urls
     */
    public function generateUrl(
        array $options = [],
        array $url = [],
        array $urlOptions = [],
    ): string {
        $urlOptions += [
            'escape' => true,
            'fullBase' => false,
        ];

        return $this->Url->build($this->generateUrlParams($options, $url), $urlOptions);
    }

    /**
     * Merges passed URL options with current pagination state to generate a pagination URL.
     *
     * @param array<string, mixed> $options Pagination/URL options array
     * @param array $url URL.
     * @return array An array of URL parameters
     */
    public function generateUrlParams(array $options = [], array $url = []): array
    {
        $paging = $this->params();
        $paging += ['currentPage' => null, 'sort' => null, 'direction' => null, 'limit' => null];
        $paging['page'] = $paging['currentPage'];
        unset($paging['currentPage']);

        if (
            !empty($paging['sort'])
            && !empty($options['sort'])
            && !str_contains($options['sort'], '.')
        ) {
            $paging['sort'] = $this->_removeAlias($paging['sort']);
        }
        if (
            !empty($paging['sortDefault'])
            && !empty($options['sort'])
            && !str_contains($options['sort'], '.')
        ) {
            $paging['sortDefault'] = $this->_removeAlias($paging['sortDefault'], $this->param('alias'));
        }

        $options += array_intersect_key(
            $paging,
            ['page' => null, 'limit' => null, 'sort' => null, 'direction' => null],
        );

        if (!empty($options['page']) && $options['page'] === 1) {
            $options['page'] = null;
        }

        if (
            isset($paging['sortDefault'], $paging['directionDefault'], $options['sort'], $options['direction'])
            && $options['sort'] === $paging['sortDefault']
            && strtolower($options['direction']) === strtolower($paging['directionDefault'])
        ) {
            $options['sort'] = $options['direction'] = null;
        }
        $baseUrl = $this->_config['options']['url'] ?? [];
        if (!empty($paging['scope'])) {
            $scope = $paging['scope'];
            if (isset($baseUrl['?'][$scope]) && is_array($baseUrl['?'][$scope])) {
                $options += $baseUrl['?'][$scope];
                unset($baseUrl['?'][$scope]);
            }
            $options = [$scope => $options];
        }

        if ($baseUrl) {
            $url = Hash::merge($url, $baseUrl);
        }

        $url['?'] ??= [];

        if (!empty($this->_config['options']['routePlaceholders'])) {
            $placeholders = array_flip($this->_config['options']['routePlaceholders']);
            $url += array_intersect_key($options, $placeholders);
            $url['?'] += array_diff_key($options, $placeholders);
        } else {
            $url['?'] += $options;
        }

        $url['?'] = Hash::filter($url['?']);

        return $url;
    }

    /**
     * Remove alias if needed.
     *
     * @param string $field Current field
     * @return string Unaliased field if applicable
     */
    protected function _removeAlias(string $field, ?string $alias = null): string
    {
        $currentModel = $alias ?: $this->param('alias');

        if (!str_contains($field, '.')) {
            return $field;
        }

        [$alias, $currentField] = explode('.', $field);

        if ($alias === $currentModel) {
            return $currentField;
        }

        return $field;
    }

    /**
     * Returns true if the given result set is not at the first page
     *
     * @return bool True if the result set is not at the first page.
     * @link https://book.cakephp.org/5/en/views/helpers/paginator.html#checking-the-pagination-state
     */
    public function hasPrev(): bool
    {
        return $this->paginated()->hasPrevPage();
    }

    /**
     * Returns true if the given result set is not at the last page
     *
     * @return bool True if the result set is not at the last page.
     * @link https://book.cakephp.org/5/en/views/helpers/paginator.html#checking-the-pagination-state
     */
    public function hasNext(): bool
    {
        return $this->paginated()->hasNextPage();
    }

    /**
     * Returns true if the given result set has the page number given by $page
     *
     * @param int $page The page number - if not set defaults to 1.
     * @return bool True if the given result set has the specified page number.
     * @throws \InvalidArgumentException
     * @link https://book.cakephp.org/5/en/views/helpers/paginator.html#checking-the-pagination-state
     */
    public function hasPage(int $page = 1): bool
    {
        return $page <= $this->paginated()->pageCount();
    }

    /**
     * Returns a counter string for the paged result set.
     *
     * ### Options
     *
     * @param string $format The format string you want to use, defaults to 'pages' Which generates output like '1 of 5'
     *   set to 'range' to generate output like '1 - 3 of 13'. Can also be set to a custom string, containing the
     *   following placeholders `{{page}}`, `{{pages}}`, `{{current}}`, `{{count}}`, `{{model}}`, `{{start}}`, `{{end}}`
     *   and any custom content you would like.
     * @return string Counter string.
     * @link https://book.cakephp.org/5/en/views/helpers/paginator.html#creating-a-page-counter
     */
    public function counter(string $format = 'pages'): string
    {
        $paging = $this->params();
        if (!$paging['pageCount']) {
            $paging['pageCount'] = 1;
        }

        switch ($format) {
            case 'range':
            case 'pages':
                $template = 'counter' . ucfirst($format);
                break;
            default:
                $template = 'counterCustom';
                $this->templater()->add([$template => $format]);
        }
        $map = array_map($this->Number->format(...), [
            'page' => (int)$paging['currentPage'],
            'pages' => (int)$paging['pageCount'],
            'current' => (int)$paging['count'],
            'count' => (int)$paging['totalCount'],
            'start' => (int)$paging['start'],
            'end' => (int)$paging['end'],
        ]);

        $alias = $this->param('alias');
        if ($alias) {
            $map += [
                'model' => strtolower(Inflector::humanize(Inflector::tableize($alias))),
            ];
        }

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
     * @param array<string, mixed> $options Options for the numbers.
     * @return string Numbers string.
     * @link https://book.cakephp.org/5/en/views/helpers/paginator.html#creating-page-number-links
     */
    public function numbers(array $options = []): string
    {
        $defaults = [
            'before' => null, 'after' => null,
            'modulus' => 8, 'first' => null, 'last' => null, 'url' => [],
        ];
        $options += $defaults;

        $params = $this->params() + ['currentPage' => 1];
        if ($params['pageCount'] <= 1) {
            return '';
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
     * @param array<string, mixed> $params Params from the numbers() method.
     * @param array<string, mixed> $options Options from the numbers() method.
     * @return array An array with the start and end numbers.
     * @phpstan-return array{0: int, 1: int}
     */
    protected function _getNumbersStartAndEnd(array $params, array $options): array
    {
        $half = (int)($options['modulus'] / 2);
        $end = max(1 + $options['modulus'], $params['currentPage'] + $half);
        $start = min(
            $params['pageCount'] - $options['modulus'],
            $params['currentPage'] - $half - $options['modulus'] % 2,
        );

        if ($options['first']) {
            $first = is_int($options['first']) ? $options['first'] : 1;

            if ($start <= $first + 2) {
                $start = 1;
            }
        }

        if ($options['last']) {
            $last = is_int($options['last']) ? $options['last'] : 1;

            if ($end >= $params['pageCount'] - $last - 1) {
                $end = $params['pageCount'];
            }
        }

        $end = (int)min($params['pageCount'], $end);
        $start = (int)max(1, $start);

        return [$start, $end];
    }

    /**
     * Formats a number for the paginator number output.
     *
     * @param \Cake\View\StringTemplate $templater StringTemplate instance.
     * @param array<string, mixed> $options Options from the numbers() method.
     * @return string
     */
    protected function _formatNumber(StringTemplate $templater, array $options): string
    {
        $vars = [
            'text' => $options['text'],
            'url' => $this->generateUrl(['page' => $options['page']], $options['url']),
        ];

        return $templater->format('number', $vars);
    }

    /**
     * Generates the numbers for the paginator numbers() method.
     *
     * @param \Cake\View\StringTemplate $templater StringTemplate instance.
     * @param array<string, mixed> $params Params from the numbers() method.
     * @param array<string, mixed> $options Options from the numbers() method.
     * @return string Markup output.
     */
    protected function _modulusNumbers(StringTemplate $templater, array $params, array $options): string
    {
        $out = '';
        $ellipsis = $templater->format('ellipsis', []);

        [$start, $end] = $this->_getNumbersStartAndEnd($params, $options);

        $out .= $this->_firstNumber($ellipsis, $params, $start, $options);
        $out .= $options['before'];

        for ($i = $start; $i < $params['currentPage']; $i++) {
            $out .= $this->_formatNumber($templater, [
                'text' => $this->Number->format($i),
                'page' => $i,
                'url' => $options['url'],
            ]);
        }

        $out .= $templater->format('current', [
            'text' => $this->Number->format((string)$params['currentPage']),
            'url' => $this->generateUrl(['page' => $params['currentPage']], $options['url']),
        ]);

        $start = (int)$params['currentPage'] + 1;
        $i = $start;
        while ($i < $end) {
            $out .= $this->_formatNumber($templater, [
                'text' => $this->Number->format($i),
                'page' => $i,
                'url' => $options['url'],
            ]);
            $i++;
        }

        if ($end !== $params['currentPage']) {
            $out .= $this->_formatNumber($templater, [
                'text' => $this->Number->format($i),
                'page' => $end,
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
     * @param string $ellipsis Ellipsis character.
     * @param array<string, mixed> $params Params from the numbers() method.
     * @param int $start Start number.
     * @param array<string, mixed> $options Options from the numbers() method.
     * @return string Markup output.
     */
    protected function _firstNumber(string $ellipsis, array $params, int $start, array $options): string
    {
        $out = '';
        $first = is_int($options['first']) ? $options['first'] : 0;
        if ($options['first'] && $start > 1) {
            $offset = $start <= $first ? $start - 1 : $options['first'];
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
     * @param string $ellipsis Ellipsis character.
     * @param array<string, mixed> $params Params from the numbers() method.
     * @param int $end End number.
     * @param array<string, mixed> $options Options from the numbers() method.
     * @return string Markup output.
     */
    protected function _lastNumber(string $ellipsis, array $params, int $end, array $options): string
    {
        $out = '';
        $last = is_int($options['last']) ? $options['last'] : 0;
        if ($options['last'] && $end < $params['pageCount']) {
            $offset = $params['pageCount'] < $end + $last ? $params['pageCount'] - $end : $options['last'];
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
     * @param array<string, mixed> $params Params from the numbers() method.
     * @param array<string, mixed> $options Options from the numbers() method.
     * @return string Markup output.
     */
    protected function _numbers(StringTemplate $templater, array $params, array $options): string
    {
        $out = '';
        $out .= $options['before'];

        for ($i = 1; $i <= $params['pageCount']; $i++) {
            if ($i === $params['currentPage']) {
                $out .= $templater->format('current', [
                    'text' => $this->Number->format($params['currentPage']),
                    'url' => $this->generateUrl(['page' => $i], $options['url']),
                ]);
            } else {
                $vars = [
                    'text' => $this->Number->format($i),
                    'url' => $this->generateUrl(['page' => $i], $options['url']),
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
     * - `escape` Whether to HTML escape the text.
     * - `url` An array of additional URL options to use for link generation.
     *
     * @param string|int $first if string use as label for the link. If numeric, the number of page links
     *   you want at the beginning of the range.
     * @param array<string, mixed> $options An array of options.
     * @return string Numbers string.
     * @link https://book.cakephp.org/5/en/views/helpers/paginator.html#creating-jump-links
     */
    public function first(string|int $first = '<< first', array $options = []): string
    {
        $options += [
            'url' => [],
            'escape' => true,
        ];

        if ($this->paginated()->pageCount() <= 1) {
            return '';
        }

        $out = '';

        if (is_int($first) && $this->paginated()->currentPage() >= $first) {
            for ($i = 1; $i <= $first; $i++) {
                $out .= $this->templater()->format('number', [
                    'url' => $this->generateUrl(['page' => $i], $options['url']),
                    'text' => $this->Number->format($i),
                ]);
            }
        } elseif ($this->paginated()->currentPage() > 1 && is_string($first)) {
            $first = $options['escape'] ? h($first) : $first;
            $out .= $this->templater()->format('first', [
                'url' => $this->generateUrl(['page' => 1], $options['url']),
                'text' => $first,
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
     * - `escape` Whether to HTML escape the text.
     * - `url` An array of additional URL options to use for link generation.
     *
     * @param string|int $last if string use as label for the link, if numeric print page numbers
     * @param array<string, mixed> $options Array of options
     * @return string Numbers string.
     * @link https://book.cakephp.org/5/en/views/helpers/paginator.html#creating-jump-links
     */
    public function last(string|int $last = 'last >>', array $options = []): string
    {
        $options += [
            'escape' => true,
            'url' => [],
        ];

        $pageCount = (int)$this->paginated()->pageCount();
        if ($pageCount <= 1) {
            return '';
        }

        $currentPage = $this->paginated()->currentPage();

        $out = '';
        $lower = $pageCount - (int)$last + 1;

        if (is_int($last) && $currentPage <= $lower) {
            for ($i = $lower; $i <= $pageCount; $i++) {
                $out .= $this->templater()->format('number', [
                    'url' => $this->generateUrl(['page' => $i], $options['url']),
                    'text' => $this->Number->format($i),
                ]);
            }
        } elseif ($currentPage < $pageCount && is_string($last)) {
            $last = $options['escape'] ? h($last) : $last;
            $out .= $this->templater()->format('last', [
                'url' => $this->generateUrl(['page' => $pageCount], $options['url']),
                'text' => $last,
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
     * - `block` The block name to append the output to, or false/absent to return as a string
     * - `prev` (default True) True to generate meta for previous page
     * - `next` (default True) True to generate meta for next page
     * - `first` (default False) True to generate meta for first page
     * - `last` (default False) True to generate meta for last page
     *
     * @param array<string, mixed> $options Array of options
     * @return string|null Meta links
     */
    public function meta(array $options = []): ?string
    {
        $options += [
            'block' => false,
            'prev' => true,
            'next' => true,
            'first' => false,
            'last' => false,
        ];

        $links = [];

        if ($options['prev'] && $this->hasPrev()) {
            $links[] = $this->Html->meta(
                'prev',
                $this->generateUrl(
                    ['page' => $this->paginated()->currentPage() - 1],
                    [],
                    ['escape' => false, 'fullBase' => true],
                ),
            );
        }

        if ($options['next'] && $this->hasNext()) {
            $links[] = $this->Html->meta(
                'next',
                $this->generateUrl(
                    ['page' => $this->paginated()->currentPage() + 1],
                    [],
                    ['escape' => false, 'fullBase' => true],
                ),
            );
        }

        if ($options['first']) {
            $links[] = $this->Html->meta(
                'first',
                $this->generateUrl(['page' => 1], [], ['escape' => false, 'fullBase' => true]),
            );
        }

        if ($options['last']) {
            $links[] = $this->Html->meta(
                'last',
                $this->generateUrl(
                    ['page' => $this->paginated()->pageCount()],
                    [],
                    ['escape' => false, 'fullBase' => true],
                ),
            );
        }

        $out = implode('', $links);

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
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [];
    }

    /**
     * Dropdown select for pagination limit.
     * This will generate a wrapping form.
     *
     * Options:
     *  - `steps`: If provided as an integer, will generate limit options in multiples of this value
     *     up to maxLimit (e.g., steps of 10 with maxLimit 50 generates [10, 20, 30, 40, 50]).
     *
     * @param array<string, string> $limits The options array.
     * @param int|null $default Default option for pagination limit. Defaults to `$this->param('perPage')`.
     * @param array<string, mixed> $options Options for Select tag attributes like class, id or event. Or steps.
     * @return string html output.
     */
    public function limitControl(array $limits = [], ?int $default = null, array $options = []): string
    {
        $steps = $options['steps'] ?? null;
        unset($options['steps']);

        $limits = $this->prepareLimitOptions($limits, $steps);

        $default ??= $this->paginated()->perPage();
        $scope = $this->param('scope');
        assert($scope === null || is_string($scope));

        $currentPage = $this->paginated()->currentPage();
        $query = $this->_View->getRequest()->getQueryParams();

        // Prepare query params to preserve as hidden fields
        $hiddenFields = $query;
        if ($scope) {
            // Remove limit and page from scoped params
            unset($hiddenFields[$scope]['limit'], $hiddenFields[$scope]['page']);
            if (isset($hiddenFields[$scope]) && empty($hiddenFields[$scope])) {
                unset($hiddenFields[$scope]);
            }
            // Set page to 1 if not on first page
            if ($currentPage > 1) {
                $hiddenFields[$scope]['page'] = 1;
            }
        } else {
            // Remove pagination params that will be handled separately
            unset($hiddenFields['limit'], $hiddenFields['page'], $hiddenFields['sort'], $hiddenFields['direction']);
            // Set page to 1 if not on first page
            if ($currentPage > 1) {
                $hiddenFields['page'] = 1;
            }
        }

        if ($scope) {
            $scope .= '.';
        }

        $out = $this->Form->create(null, ['type' => 'get', 'url' => []]);

        $out .= $this->generateHiddenFields($hiddenFields);

        $limit = $this->_View->getRequest()->getQuery('limit');
        $out .= $this->Form->control($scope . 'limit', $options + [
            'type' => 'select',
            'label' => __('View'),
            'default' => $default,
            'value' => $limit !== null ? (int)$limit : null,
            'options' => $limits,
            'onChange' => 'this.form.requestSubmit()',
        ]);

        $out .= $this->Form->end();

        return $out;
    }

    /**
     * Prepare and filter limit options for limitControl.
     *
     * Handles generating limits from steps, applying defaults, and filtering by maxLimit.
     *
     * @param array<string, string> $limits Explicit limit options
     * @param int|null $steps If provided, generates limits in multiples of this value
     * @return array<int|string, string> Prepared limit options
     */
    protected function prepareLimitOptions(array $limits, ?int $steps): array
    {
        // Generate limits based on steps if provided
        if ($steps !== null) {
            if ($limits !== []) {
                throw new InvalidArgumentException(
                    'Cannot use both `steps` option and explicit `$limits` array. ' .
                    'Use one or the other.',
                );
            }

            $maxLimit = $this->param('maxLimit');
            $upperLimit = $maxLimit ?? 100;
            $limits = [];
            for ($i = $steps; $i <= $upperLimit; $i += $steps) {
                $limits[$i] = (string)$i;
            }

            return $limits;
        }

        // Apply default limits if none provided
        $limits = $limits ?: [
            '20' => '20',
            '50' => '50',
            '100' => '100',
        ];

        // Filter out limits that exceed maxLimit
        $maxLimit = $this->param('maxLimit');
        if ($maxLimit !== null) {
            $limits = array_filter($limits, function ($limit) use ($maxLimit) {
                return (int)$limit <= $maxLimit;
            });
            if (!$limits) {
                $limits[$maxLimit] = (string)$maxLimit;
            }
        }

        return $limits;
    }

    /**
     * Recursively generate hidden form fields for nested arrays.
     *
     * This handles query parameters with arbitrary nesting levels by converting
     * nested arrays into dot-notation field names.
     *
     * Example:
     * ['filter' => ['date' => ['start' => '2024-01-01']]]
     * becomes: hidden field "filter.date.start" with value "2024-01-01"
     *
     * @param array $data The data to convert to hidden fields
     * @param string $prefix The current key prefix for nested fields
     * @return string HTML for hidden form fields
     */
    protected function generateHiddenFields(array $data, string $prefix = ''): string
    {
        $out = '';

        foreach ($data as $key => $value) {
            $fieldName = $prefix === '' ? $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                // Recursively handle nested arrays
                $out .= $this->generateHiddenFields($value, $fieldName);
            } else {
                // Generate hidden field for scalar values
                $out .= $this->Form->hidden(h($fieldName), ['value' => $value]);
            }
        }

        return $out;
    }
}
