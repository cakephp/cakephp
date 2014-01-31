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
 * @since         CakePHP(tm) v 1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Helper;

use Cake\Utility\Inflector;
use Cake\View\Helper;
use Cake\View\View;

/**
 * Pagination Helper class for easy generation of pagination links.
 *
 * PaginationHelper encloses all methods needed when working with pagination.
 *
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html
 */
class PaginatorHelper extends Helper {

	use StringTemplateTrait;

/**
 * Holds the default options for pagination links
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
 * @var array
 */
	public $options = [];

/**
 * The default templates used by PaginatorHelper.
 *
 * @var array
 */
	protected $_defaultTemplates = [
		'nextActive' => '<li class="next"><a rel="next" href="{{url}}">{{text}}</a></li>',
		'nextDisabled' => '<li class="next disabled"><span>{{text}}</span></li>',
		'prevActive' => '<li class="prev"><a rel="prev" href="{{url}}">{{text}}</a></li>',
		'prevDisabled' => '<li class="prev disabled"><span>{{text}}</span></li>',
		'counterRange' => '{{start}} - {{end}} of {{count}}',
		'counterPages' => '{{page}} of {{pages}}',
		'first' => '<li class="first"><a href="{{url}}">{{text}}</a></li>',
		'last' => '<li class="last"><a href="{{url}}">{{text}}</a></li>',
		'number' => '<li><a href="{{url}}">{{text}}</a></li>',
		'current' => '<li class="active"><span>{{text}}</span></li>',
		'ellipsis' => '<li class="ellipsis">...</li>',
		'sort' => '<a href="{{url}}">{{text}}</a>',
		'sortAsc' => '<a class="asc" href="{{url}}">{{text}}</a>',
		'sortDesc' => '<a class="desc" href="{{url}}">{{text}}</a>',
		'sortAscLocked' => '<a class="asc locked" href="{{url}}">{{text}}</a>',
		'sortDescLocked' => '<a class="desc locked" href="{{url}}">{{text}}</a>',
	];

/**
 * Constructor
 *
 * @param View $View The View this helper is being attached to.
 * @param array $settings Configuration settings for the helper.
 */
	public function __construct(View $View, $settings = []) {
		parent::__construct($View, $settings);
		$this->initStringTemplates($this->_defaultTemplates);
	}

/**
 * Get/set templates to use.
 *
 * @param string|null|array $templates null or string allow reading templates. An array
 *   allows templates to be added.
 * @return void|string|array
 */
	public function templates($templates = null) {
		if ($templates === null || is_string($templates)) {
			return $this->_templater->get($templates);
		}
		return $this->_templater->add($templates);
	}

/**
 * Before render callback. Overridden to merge passed args with URL options.
 *
 * @param Cake\Event\Event $event The event instance.
 * @param string $viewFile
 * @return void
 */
	public function beforeRender($event, $viewFile) {
		$this->options['url'] = array_merge($this->request->params['pass'], $this->request->query);
	}

/**
 * Gets the current paging parameters from the resultset for the given model
 *
 * @param string $model Optional model name. Uses the default if none is specified.
 * @return array The array of paging parameters for the paginated resultset.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::params
 */
	public function params($model = null) {
		if (empty($model)) {
			$model = $this->defaultModel();
		}
		if (!isset($this->request->params['paging']) || empty($this->request->params['paging'][$model])) {
			return null;
		}
		return $this->request->params['paging'][$model];
	}

/**
 * Convenience access to any of the paginator params.
 *
 * @param string $key Key of the paginator params array to retrieve.
 * @param string $model Optional model name. Uses the default if none is specified.
 * @return mixed Content of the requested param.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::params
 */
	public function param($key, $model = null) {
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
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::options
 */
	public function options($options = array()) {
		if (!empty($options['paging'])) {
			if (!isset($this->request->params['paging'])) {
				$this->request->params['paging'] = array();
			}
			$this->request->params['paging'] = array_merge($this->request->params['paging'], $options['paging']);
			unset($options['paging']);
		}
		$model = $this->defaultModel();

		if (!empty($options[$model])) {
			if (!isset($this->request->params['paging'][$model])) {
				$this->request->params['paging'][$model] = array();
			}
			$this->request->params['paging'][$model] = array_merge(
				$this->request->params['paging'][$model], $options[$model]
			);
			unset($options[$model]);
		}
		$this->options = array_filter(array_merge($this->options, $options));
	}

/**
 * Gets the current page of the recordset for the given model
 *
 * @param string $model Optional model name. Uses the default if none is specified.
 * @return string The current page number of the recordset.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::current
 */
	public function current($model = null) {
		$params = $this->params($model);

		if (isset($params['page'])) {
			return $params['page'];
		}
		return 1;
	}

/**
 * Gets the current key by which the recordset is sorted
 *
 * @param string $model Optional model name. Uses the default if none is specified.
 * @param array $options Options for pagination links. See #options for list of keys.
 * @return string The name of the key by which the recordset is being sorted, or
 *  null if the results are not currently sorted.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::sortKey
 */
	public function sortKey($model = null, $options = array()) {
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
 * @param string $model Optional model name. Uses the default if none is specified.
 * @param array $options Options for pagination links. See #options for list of keys.
 * @return string The direction by which the recordset is being sorted, or
 *  null if the results are not currently sorted.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::sortDir
 */
	public function sortDir($model = null, $options = array()) {
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
 * @param string $text The enabled text for the link.
 * @param boolean $enabled Whether or not the enabled/disabled version should be created.
 * @param array $options An array of options from the calling method.
 * @param array $templates An array of templates with the 'active' and 'disabled' keys.
 * @return string Generated HTML
 */
	protected function _toggledLink($text, $enabled, $options, $templates) {
		$template = $templates['active'];
		if (!$enabled) {
			$text = $options['disabledTitle'];
			$template = $templates['disabled'];
		}

		if (!$enabled && $text === false) {
			return '';
		}
		$text = $options['escape'] ? h($text) : $text;

		if (!$enabled) {
			return $this->_templater->format($template, [
				'text' => $text,
			]);
		}
		$paging = $this->params($options['model']);

		$url = array_merge(
			$options['url'],
			['page' => $paging['page'] + $options['step']]
		);
		$url = $this->url($url, $options['model']);
		return $this->_templater->format($template, [
			'url' => $url,
			'text' => $text,
		]);
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
 * - `url` Additional URL parameters to use in the generated URL.
 *
 * @param string $title Title for the link. Defaults to '<< Previous'.
 * @param array $options Options for pagination link. See above for list of keys.
 * @return string A "previous" link or a disabled link.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::prev
 */
	public function prev($title = '<< Previous', $options = []) {
		$defaults = [
			'url' => [],
			'model' => $this->defaultModel(),
			'disabledTitle' => $title,
			'escape' => true,
		];
		$options = array_merge($defaults, (array)$options);
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
 * - `url` Additional URL parameters to use in the generated URL.
 *
 * @param string $title Title for the link. Defaults to 'Next >>'.
 * @param array $options Options for pagination link. See above for list of keys.
 * @return string A "next" link or $disabledTitle text if the link is disabled.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::next
 */
	public function next($title = 'Next >>', $options = []) {
		$defaults = [
			'url' => [],
			'model' => $this->defaultModel(),
			'disabledTitle' => $title,
			'escape' => true,
		];
		$options = array_merge($defaults, (array)$options);
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
 * @param string $title Title for the link. If $title is null $key will be used
 *		for the title and will be generated by inflection.
 * @param array $options Options for sorting link. See above for list of keys.
 * @return string A link sorting default by 'asc'. If the resultset is sorted 'asc' by the specified
 *  key the returned link will sort by 'desc'.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::sort
 */
	public function sort($key, $title = null, $options = []) {
		$options = array_merge(
			['url' => array(), 'model' => null, 'escape' => true],
			$options
		);
		$url = $options['url'];
		unset($options['url']);

		if (empty($title)) {
			$title = $key;

			if (strpos($title, '.') !== false) {
				$title = str_replace('.', ' ', $title);
			}

			$title = __(Inflector::humanize(preg_replace('/_id$/', '', $title)));
		}
		$defaultDir = isset($options['direction']) ? $options['direction'] : 'asc';
		unset($options['direction']);

		$locked = isset($options['lock']) ? $options['lock'] : false;
		unset($options['lock']);

		$sortKey = $this->sortKey($options['model']);
		$defaultModel = $this->defaultModel();
		$isSorted = (
			$sortKey === $key ||
			$sortKey === $defaultModel . '.' . $key ||
			$key === $defaultModel . '.' . $sortKey
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
			'url' => $this->url($url, $options['model']),
		];
		return $this->_templater->format($template, $vars);
	}

/**
 * Merges passed URL options with current pagination state to generate a pagination URL.
 *
 * @param array $options Pagination/URL options array
 * @param string $model Which model to paginate on
 * @return mixed By default, returns a full pagination URL string for use in non-standard contexts (i.e. JavaScript)
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::url
 */
	public function url($options = array(), $model = null) {
		$paging = $this->params($model);
		$paging += ['page' => null, 'sort' => null, 'direction' => null, 'limit' => null];
		$url = [
			'page' => $paging['page'],
			'limit' => $paging['limit'],
			'sort' => $paging['sort'],
			'direction' => $paging['direction'],
		];

		if (!empty($this->options['url'])) {
			$url = array_merge($this->options['url'], $url);
		}
		$url = array_merge(array_filter($url), $options);

		if (!empty($url['page']) && $url['page'] == 1) {
			$url['page'] = null;
		}
		if (isset($paging['sortDefault']) && isset($paging['directionDefault']) && $url['sort'] == $paging['sortDefault'] && $url['direction'] == $paging['directionDefault'] ) {
			$url['sort'] = $url['direction'] = null;
		}
		return parent::url($url);
	}

/**
 * Returns true if the given result set is not at the first page
 *
 * @param string $model Optional model name. Uses the default if none is specified.
 * @return boolean True if the result set is not at the first page.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::hasPrev
 */
	public function hasPrev($model = null) {
		return $this->_hasPage($model, 'prev');
	}

/**
 * Returns true if the given result set is not at the last page
 *
 * @param string $model Optional model name. Uses the default if none is specified.
 * @return boolean True if the result set is not at the last page.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::hasNext
 */
	public function hasNext($model = null) {
		return $this->_hasPage($model, 'next');
	}

/**
 * Returns true if the given result set has the page number given by $page
 *
 * @param string $model Optional model name. Uses the default if none is specified.
 * @param integer $page The page number - if not set defaults to 1.
 * @return boolean True if the given result set has the specified page number.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::hasPage
 */
	public function hasPage($model = null, $page = 1) {
		if (is_numeric($model)) {
			$page = $model;
			$model = null;
		}
		$paging = $this->params($model);
		return $page <= $paging['pageCount'];
	}

/**
 * Does $model have $page in its range?
 *
 * @param string $model Model name to get parameters for.
 * @param integer $page Page number you are checking.
 * @return boolean Whether model has $page
 */
	protected function _hasPage($model, $page) {
		$params = $this->params($model);
		return !empty($params) && $params[$page . 'Page'];
	}

/**
 * Gets the default model of the paged sets
 *
 * @return string Model name or null if the pagination isn't initialized.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::defaultModel
 */
	public function defaultModel() {
		if ($this->_defaultModel) {
			return $this->_defaultModel;
		}
		if (empty($this->request->params['paging'])) {
			return null;
		}
		list($this->_defaultModel) = array_keys($this->request->params['paging']);
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
 * @param array $options Options for the counter string. See #options for list of keys.
 * @return string Counter string.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::counter
 */
	public function counter($options = []) {
		if (is_string($options)) {
			$options = array('format' => $options);
		}

		$options = array_merge(
			[
				'model' => $this->defaultModel(),
				'format' => 'pages',
			],
		$options);

		$paging = $this->params($options['model']);
		if (!$paging['pageCount']) {
			$paging['pageCount'] = 1;
		}
		$start = 0;
		if ($paging['count'] >= 1) {
			$start = (($paging['page'] - 1) * $paging['limit']) + 1;
		}
		$end = $start + $paging['limit'] - 1;
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
				$this->_templater->add([$template => $options['format']]);
		}
		$map = [
			'page' => $paging['page'],
			'pages' => $paging['pageCount'],
			'current' => $paging['current'],
			'count' => $paging['count'],
			'start' => $start,
			'end' => $end,
			'model' => strtolower(Inflector::humanize(Inflector::tableize($options['model'])))
		];
		return $this->_templater->format($template, $map);
	}

/**
 * Returns a set of numbers for the paged result set
 * uses a modulus to decide how many numbers to show on each side of the current page (default: 8).
 *
 * `$this->Paginator->numbers(array('first' => 2, 'last' => 2));`
 *
 * Using the first and last options you can create links to the beginning and end of the page set.
 *
 * ### Options
 *
 * - `before` Content to be inserted before the numbers, but after the first links.
 * - `after` Content to be inserted after the numbers, but before the last links.
 * - `model` Model to create numbers for, defaults to PaginatorHelper::defaultModel()
 * - `modulus` how many numbers to include on either side of the current page, defaults to 8.
 * - `first` Whether you want first links generated, set to an integer to define the number of 'first'
 *    links to generate.
 * - `last` Whether you want last links generated, set to an integer to define the number of 'last'
 *    links to generate.
 *
 * The generated number links will include the 'ellipsis' template when the `first` and `last` options
 * and the number of pages exceed the modulus. For example if you have 25 pages, and use the first/last
 * options and a modulus of 8, ellipsis content will be inserted after the first and last link sets.
 *
 * @param array $options Options for the numbers, (before, after, model, modulus)
 * @return string numbers string.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::numbers
 */
	public function numbers($options = array()) {
		if ($options === true) {
			$options = array(
				'first' => 'first', 'last' => 'last'
			);
		}

		$defaults = array(
			'before' => null, 'after' => null, 'model' => $this->defaultModel(),
			'modulus' => 8, 'first' => null, 'last' => null,
		);
		$options += $defaults;

		$params = (array)$this->params($options['model']) + array('page' => 1);

		if ($params['pageCount'] <= 1) {
			return false;
		}

		$out = '';
		$ellipsis = $this->_templater->format('ellipsis', []);

		if ($options['modulus'] && $params['pageCount'] > $options['modulus']) {
			$half = intval($options['modulus'] / 2);
			$end = $params['page'] + $half;

			if ($end > $params['pageCount']) {
				$end = $params['pageCount'];
			}
			$start = $params['page'] - ($options['modulus'] - ($end - $params['page']));
			if ($start <= 1) {
				$start = 1;
				$end = $params['page'] + ($options['modulus'] - $params['page']) + 1;
			}

			if ($options['first'] && $start > 1) {
				$offset = ($start <= (int)$options['first']) ? $start - 1 : $options['first'];
				$out .= $this->first($offset);
				if ($offset < $start - 1) {
					$out .= $ellipsis;
				}
			}

			$out .= $options['before'];

			for ($i = $start; $i < $params['page']; $i++) {
				$vars = [
					'text' => $i,
					'url' => $this->url(['page' => $i], $options['model']),
				];
				$out .= $this->_templater->format('number', $vars);
			}

			$out .= $this->_templater->format('current', [
				'text' => $params['page'],
				'url' => $this->url(['page' => $params['page']], $options['model']),
			]);

			$start = $params['page'] + 1;
			for ($i = $start; $i < $end; $i++) {
				$vars = [
					'text' => $i,
					'url' => $this->url(['page' => $i], $options['model']),
				];
				$out .= $this->_templater->format('number', $vars);
			}

			if ($end != $params['page']) {
				$vars = [
					'text' => $i,
					'url' => $this->url(['page' => $end], $options['model']),
				];
				$out .= $this->_templater->format('number', $vars);
			}

			$out .= $options['after'];

			if ($options['last'] && $end < $params['pageCount']) {
				$offset = ($params['pageCount'] < $end + (int)$options['last']) ? $params['pageCount'] - $end : $options['last'];
				if ($offset <= $options['last'] && $params['pageCount'] - $end > $offset) {
					$out .= $ellipsis;
				}
				$out .= $this->last($offset);
			}

		} else {
			$out .= $options['before'];

			for ($i = 1; $i <= $params['pageCount']; $i++) {
				if ($i == $params['page']) {
					$out .= $this->_templater->format('current', [
						'text' => $params['page'],
						'url' => $this->url(['page' => $params['page']], $options['model']),
					]);
				} else {
					$vars = [
						'text' => $i,
						'url' => $this->url(['page' => $i], $options['model']),
					];
					$out .= $this->_templater->format('number', $vars);
				}
			}

			$out .= $options['after'];
		}

		return $out;
	}

/**
 * Returns a first or set of numbers for the first pages.
 *
 * `echo $this->Paginator->first('< first');`
 *
 * Creates a single link for the first page. Will output nothing if you are on the first page.
 *
 * `echo $this->Paginator->first(3);`
 *
 * Will create links for the first 3 pages, once you get to the third or greater page. Prior to that
 * nothing will be output.
 *
 * ### Options:
 *
 * - `model` The model to use defaults to PaginatorHelper::defaultModel()
 * - `escape` Whether or not to HTML escape the text.
 *
 * @param string|integer $first if string use as label for the link. If numeric, the number of page links
 *   you want at the beginning of the range.
 * @param array $options An array of options.
 * @return string numbers string.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::first
 */
	public function first($first = '<< first', $options = []) {
		$options = array_merge(
			['model' => $this->defaultModel(), 'escape' => true],
			(array)$options
		);

		$params = $this->params($options['model']);

		if ($params['pageCount'] <= 1) {
			return false;
		}

		$out = '';

		if (is_int($first) && $params['page'] >= $first) {
			for ($i = 1; $i <= $first; $i++) {
				$out .= $this->_templater->format('number', [
					'url' => $this->url(['page' => $i], $options['model']),
					'text' => $i
				]);
			}
		} elseif ($params['page'] > 1 && is_string($first)) {
			$first = $options['escape'] ? h($first) : $first;
			$out .= $this->_templater->format('first', [
				'url' => $this->url(['page' => 1], $options['model']),
				'text' => $first
			]);
		}
		return $out;
	}

/**
 * Returns a last or set of numbers for the last pages.
 *
 * `echo $this->Paginator->last('last >');`
 *
 * Creates a single link for the last page. Will output nothing if you are on the last page.
 *
 * `echo $this->Paginator->last(3);`
 *
 * Will create links for the last 3 pages. Once you enter the page range, no output will be created.
 *
 * ### Options:
 *
 * - `model` The model to use defaults to PaginatorHelper::defaultModel()
 * - `escape` Whether or not to HTML escape the text.
 *
 * @param string|integer $last if string use as label for the link, if numeric print page numbers
 * @param array $options Array of options
 * @return string numbers string.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::last
 */
	public function last($last = 'last >>', $options = array()) {
		$options = array_merge(
			['model' => $this->defaultModel(), 'escape' => true],
			(array)$options
		);

		$params = $this->params($options['model']);

		if ($params['pageCount'] <= 1) {
			return false;
		}

		$out = '';
		$lower = $params['pageCount'] - $last + 1;

		if (is_int($last) && $params['page'] <= $lower) {
			for ($i = $lower; $i <= $params['pageCount']; $i++) {
				$out .= $this->_templater->format('number', [
					'url' => $this->url(['page' => $i], $options['model']),
					'text' => $i
				]);
			}
		} elseif ($params['page'] < $params['pageCount'] && is_string($last)) {
			$last = $options['escape'] ? h($last) : $last;
			$out .= $this->_templater->format('last', [
				'url' => $this->url(['page' => $params['pageCount']], $options['model']),
				'text' => $last
			]);
		}
		return $out;
	}

}
