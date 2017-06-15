<?php
/**
 * Pagination Helper class file.
 *
 * Generates pagination links
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.View.Helper
 * @since         CakePHP(tm) v 1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('AppHelper', 'View/Helper');

/**
 * Pagination Helper class for easy generation of pagination links.
 *
 * PaginationHelper encloses all methods needed when working with pagination.
 *
 * @package       Cake.View.Helper
 * @property      HtmlHelper $Html
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html
 */
class PaginatorHelper extends AppHelper {

/**
 * Helper dependencies
 *
 * @var array
 */
	public $helpers = array('Html');

/**
 * The class used for 'Ajax' pagination links. Defaults to JsHelper. You should make sure
 * that JsHelper is defined as a helper before PaginatorHelper, if you want to customize the JsHelper.
 *
 * @var string
 */
	protected $_ajaxHelperClass = 'Js';

/**
 * Holds the default options for pagination links
 *
 * The values that may be specified are:
 *
 * - `format` Format of the counter. Supported formats are 'range' and 'pages'
 *    and custom (default). In the default mode the supplied string is parsed and constants are replaced
 *    by their actual values.
 *    placeholders: %page%, %pages%, %current%, %count%, %start%, %end% .
 * - `separator` The separator of the actual page and number of pages (default: ' of ').
 * - `url` Url of the action. See Router::url()
 * - `url['sort']`  the key that the recordset is sorted.
 * - `url['direction']` Direction of the sorting (default: 'asc').
 * - `url['page']` Page number to use in links.
 * - `model` The name of the model.
 * - `escape` Defines if the title field for the link should be escaped (default: true).
 * - `update` DOM id of the element updated with the results of the AJAX call.
 *     If this key isn't specified Paginator will use plain HTML links.
 * - `paging['paramType']` The type of parameters to use when creating links. Valid options are
 *     'querystring' and 'named'. See PaginatorComponent::$settings for more information.
 * - `convertKeys` - A list of keys in URL arrays that should be converted to querysting params
 *    if paramType == 'querystring'.
 *
 * @var array
 */
	public $options = array(
		'convertKeys' => array('page', 'limit', 'sort', 'direction')
	);

/**
 * Constructor for the helper. Sets up the helper that is used for creating 'AJAX' links.
 *
 * Use `public $helpers = array('Paginator' => array('ajax' => 'CustomHelper'));` to set a custom Helper
 * or choose a non JsHelper Helper. If you want to use a specific library with JsHelper declare JsHelper and its
 * adapter before including PaginatorHelper in your helpers array.
 *
 * The chosen custom helper must implement a `link()` method.
 *
 * @param View $View the view object the helper is attached to.
 * @param array $settings Array of settings.
 * @throws CakeException When the AjaxProvider helper does not implement a link method.
 */
	public function __construct(View $View, $settings = array()) {
		$ajaxProvider = isset($settings['ajax']) ? $settings['ajax'] : 'Js';
		$this->helpers[] = $ajaxProvider;
		$this->_ajaxHelperClass = $ajaxProvider;
		App::uses($ajaxProvider . 'Helper', 'View/Helper');
		$classname = $ajaxProvider . 'Helper';
		if (!class_exists($classname) || !method_exists($classname, 'link')) {
			throw new CakeException(
				__d('cake_dev', '%s does not implement a %s method, it is incompatible with %s', $classname, 'link()', 'PaginatorHelper')
			);
		}
		parent::__construct($View, $settings);
	}

/**
 * Before render callback. Overridden to merge passed args with URL options.
 *
 * @param string $viewFile View file name.
 * @return void
 */
	public function beforeRender($viewFile) {
		$this->options['url'] = array_merge($this->request->params['pass'], $this->request->params['named']);
		if (!empty($this->request->query)) {
			$this->options['url']['?'] = $this->request->query;
		}
		parent::beforeRender($viewFile);
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
			return array(
				'prevPage' => false,
				'nextPage' => true,
				'paramType' => 'named',
				'pageCount' => 1,
				'options' => array(),
				'page' => 1
			);
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
 * @param array|string $options Default options for pagination links. If a string is supplied - it
 *   is used as the DOM id element to update. See PaginatorHelper::$options for list of keys.
 * @return void
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::options
 */
	public function options($options = array()) {
		if (is_string($options)) {
			$options = array('update' => $options);
		}

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
		if (!empty($options['convertKeys'])) {
			$options['convertKeys'] = array_merge($this->options['convertKeys'], $options['convertKeys']);
		}
		$this->options = array_filter(array_merge($this->options, $options));
		if (!empty($this->options['model'])) {
			$this->defaultModel($this->options['model']);
		}
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
 * @return string|null The name of the key by which the recordset is being sorted, or
 *  null if the results are not currently sorted.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::sortKey
 */
	public function sortKey($model = null, $options = array()) {
		if (empty($options)) {
			$params = $this->params($model);
			$options = $params['options'];
		}
		if (isset($options['sort']) && !empty($options['sort'])) {
			return $options['sort'];
		}
		if (isset($options['order'])) {
			return is_array($options['order']) ? key($options['order']) : $options['order'];
		}
		if (isset($params['order'])) {
			return is_array($params['order']) ? key($params['order']) : $params['order'];
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
			$params = $this->params($model);
			$options = $params['options'];
		}

		if (isset($options['direction'])) {
			$dir = strtolower($options['direction']);
		} elseif (isset($options['order']) && is_array($options['order'])) {
			$dir = strtolower(current($options['order']));
		} elseif (isset($params['order']) && is_array($params['order'])) {
			$dir = strtolower(current($params['order']));
		}

		if ($dir === 'desc') {
			return 'desc';
		}
		return 'asc';
	}

/**
 * Generates a "previous" link for a set of paged records
 *
 * ### Options:
 *
 * - `url` Allows sending routing parameters such as controllers, actions or passed arguments.
 * - `tag` The tag wrapping tag you want to use, defaults to 'span'. Set this to false to disable this option
 * - `escape` Whether you want the contents html entity encoded, defaults to true
 * - `model` The model to use, defaults to PaginatorHelper::defaultModel()
 * - `disabledTag` Tag to use instead of A tag when there is no previous page
 *
 * @param string $title Title for the link. Defaults to '<< Previous'.
 * @param array $options Options for pagination link. See #options for list of keys.
 * @param string $disabledTitle Title when the link is disabled.
 * @param array $disabledOptions Options for the disabled pagination link. See #options for list of keys.
 * @return string A "previous" link or $disabledTitle text if the link is disabled.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::prev
 */
	public function prev($title = '<< Previous', $options = array(), $disabledTitle = null, $disabledOptions = array()) {
		$defaults = array(
			'rel' => 'prev'
		);
		$options = (array)$options + $defaults;
		return $this->_pagingLink('Prev', $title, $options, $disabledTitle, $disabledOptions);
	}

/**
 * Generates a "next" link for a set of paged records
 *
 * ### Options:
 *
 * - `url` Allows sending routing parameters such as controllers, actions or passed arguments.
 * - `tag` The tag wrapping tag you want to use, defaults to 'span'. Set this to false to disable this option
 * - `escape` Whether you want the contents html entity encoded, defaults to true
 * - `model` The model to use, defaults to PaginatorHelper::defaultModel()
 * - `disabledTag` Tag to use instead of A tag when there is no next page
 *
 * @param string $title Title for the link. Defaults to 'Next >>'.
 * @param array $options Options for pagination link. See above for list of keys.
 * @param string $disabledTitle Title when the link is disabled.
 * @param array $disabledOptions Options for the disabled pagination link. See above for list of keys.
 * @return string A "next" link or $disabledTitle text if the link is disabled.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::next
 */
	public function next($title = 'Next >>', $options = array(), $disabledTitle = null, $disabledOptions = array()) {
		$defaults = array(
			'rel' => 'next'
		);
		$options = (array)$options + $defaults;
		return $this->_pagingLink('Next', $title, $options, $disabledTitle, $disabledOptions);
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
	public function sort($key, $title = null, $options = array()) {
		$options += array('url' => array(), 'model' => null);
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

		$dir = $defaultDir;
		if ($isSorted) {
			$dir = $this->sortDir($options['model']) === 'asc' ? 'desc' : 'asc';
			$class = $dir === 'asc' ? 'desc' : 'asc';
			if (!empty($options['class'])) {
				$options['class'] .= ' ' . $class;
			} else {
				$options['class'] = $class;
			}
			if ($locked) {
				$dir = $defaultDir;
				$options['class'] .= ' locked';
			}
		}
		if (is_array($title) && array_key_exists($dir, $title)) {
			$title = $title[$dir];
		}

		$url = array_merge(array('sort' => $key, 'direction' => $dir), $url, array('order' => null));
		return $this->link($title, $url, $options);
	}

/**
 * Generates a plain or Ajax link with pagination parameters
 *
 * ### Options
 *
 * - `update` The Id of the DOM element you wish to update. Creates Ajax enabled links
 *    with the AjaxHelper.
 * - `escape` Whether you want the contents html entity encoded, defaults to true
 * - `model` The model to use, defaults to PaginatorHelper::defaultModel()
 *
 * @param string $title Title for the link.
 * @param string|array $url URL for the action. See Router::url()
 * @param array $options Options for the link. See #options for list of keys.
 * @return string A link with pagination parameters.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::link
 */
	public function link($title, $url = array(), $options = array()) {
		$options += array('model' => null, 'escape' => true);
		$model = $options['model'];
		unset($options['model']);

		if (!empty($this->options)) {
			$options += $this->options;
		}
		if (isset($options['url'])) {
			$url = array_merge((array)$options['url'], (array)$url);
			unset($options['url']);
		}
		unset($options['convertKeys']);

		$url = $this->url($url, true, $model);

		$obj = isset($options['update']) ? $this->_ajaxHelperClass : 'Html';
		return $this->{$obj}->link($title, $url, $options);
	}

/**
 * Merges passed URL options with current pagination state to generate a pagination URL.
 *
 * @param array $options Pagination/URL options array
 * @param bool $asArray Return the URL as an array, or a URI string
 * @param string $model Which model to paginate on
 * @return mixed By default, returns a full pagination URL string for use in non-standard contexts (i.e. JavaScript)
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::url
 */
	public function url($options = array(), $asArray = false, $model = null) {
		$paging = $this->params($model);
		$url = array_merge(array_filter($paging['options']), $options);

		if (isset($url['order'])) {
			$sort = $direction = null;
			if (is_array($url['order'])) {
				list($sort, $direction) = array($this->sortKey($model, $url), current($url['order']));
			}
			unset($url['order']);
			$url = array_merge($url, compact('sort', 'direction'));
		}
		$url = $this->_convertUrlKeys($url, $paging['paramType']);
		if (!empty($url['page']) && $url['page'] == 1) {
			$url['page'] = null;
		}
		if (!empty($url['?']['page']) && $url['?']['page'] == 1) {
			unset($url['?']['page']);
		}
		if (!empty($paging['queryScope'])) {
			$url = array($paging['queryScope'] => $url);
			if (empty($url[$paging['queryScope']]['page'])) {
				unset($url[$paging['queryScope']]['page']);
			}
		}

		if ($asArray) {
			return $url;
		}
		return parent::url($url);
	}

/**
 * Converts the keys being used into the format set by options.paramType
 *
 * @param array $url Array of URL params to convert
 * @param string $type Keys type.
 * @return array converted URL params.
 */
	protected function _convertUrlKeys($url, $type) {
		if ($type === 'named') {
			return $url;
		}
		if (!isset($url['?'])) {
			$url['?'] = array();
		}
		foreach ($this->options['convertKeys'] as $key) {
			if (isset($url[$key])) {
				$url['?'][$key] = $url[$key];
				unset($url[$key]);
			}
		}
		return $url;
	}

/**
 * Protected method for generating prev/next links
 *
 * @param string $which Link type: 'Prev', 'Next'.
 * @param string $title Link title.
 * @param array $options Options list.
 * @param string $disabledTitle Disabled link title.
 * @param array $disabledOptions Disabled link options.
 * @return string
 */
	protected function _pagingLink($which, $title = null, $options = array(), $disabledTitle = null, $disabledOptions = array()) {
		$check = 'has' . $which;
		$_defaults = array(
			'url' => array(), 'step' => 1, 'escape' => true, 'model' => null,
			'tag' => 'span', 'class' => strtolower($which), 'disabledTag' => null
		);
		$options = (array)$options + $_defaults;
		$paging = $this->params($options['model']);
		if (empty($disabledOptions)) {
			$disabledOptions = $options;
		}

		if (!$this->{$check}($options['model']) && (!empty($disabledTitle) || !empty($disabledOptions))) {
			if (!empty($disabledTitle) && $disabledTitle !== true) {
				$title = $disabledTitle;
			}
			$options = (array)$disabledOptions + array_intersect_key($options, $_defaults) + $_defaults;
		} elseif (!$this->{$check}($options['model'])) {
			return '';
		}

		foreach (array_keys($_defaults) as $key) {
			${$key} = $options[$key];
			unset($options[$key]);
		}

		if ($this->{$check}($model)) {
			$url = array_merge(
				array('page' => $paging['page'] + ($which === 'Prev' ? $step * -1 : $step)),
				$url
			);
			if ($tag === false) {
				return $this->link(
					$title,
					$url,
					compact('escape', 'model', 'class') + $options
				);
			}
			$link = $this->link($title, $url, compact('escape', 'model') + $options);
			return $this->Html->tag($tag, $link, compact('class'));
		}
		unset($options['rel']);
		if (!$tag) {
			if ($disabledTag) {
				$tag = $disabledTag;
				$disabledTag = null;
			} else {
				$tag = $_defaults['tag'];
			}
		}
		if ($disabledTag) {
			$title = $this->Html->tag($disabledTag, $title, compact('escape') + $options);
			return $this->Html->tag($tag, $title, compact('class'));
		}
		return $this->Html->tag($tag, $title, compact('escape', 'class') + $options);
	}

/**
 * Returns true if the given result set is not at the first page
 *
 * @param string $model Optional model name. Uses the default if none is specified.
 * @return bool True if the result set is not at the first page.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::hasPrev
 */
	public function hasPrev($model = null) {
		return $this->_hasPage($model, 'prev');
	}

/**
 * Returns true if the given result set is not at the last page
 *
 * @param string $model Optional model name. Uses the default if none is specified.
 * @return bool True if the result set is not at the last page.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::hasNext
 */
	public function hasNext($model = null) {
		return $this->_hasPage($model, 'next');
	}

/**
 * Returns true if the given result set has the page number given by $page
 *
 * @param string $model Optional model name. Uses the default if none is specified.
 * @param int $page The page number - if not set defaults to 1.
 * @return bool True if the given result set has the specified page number.
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
 * @param int $page Page number you are checking.
 * @return bool Whether model has $page
 */
	protected function _hasPage($model, $page) {
		$params = $this->params($model);
		return !empty($params) && $params[$page . 'Page'];
	}

/**
 * Gets or sets the default model of the paged sets
 *
 * @param string|null $model Model name to set
 * @return string|null Model name or null if the pagination isn't initialized.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::defaultModel
 */
	public function defaultModel($model = null) {
		if ($model !== null) {
			$this->_defaultModel = $model;
		}
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
 *    the following placeholders `{:page}`, `{:pages}`, `{:current}`, `{:count}`, `{:model}`, `{:start}`, `{:end}` and any
 *    custom content you would like.
 * - `separator` The separator string to use, default to ' of '
 *
 * The `%page%` style placeholders also work, but are deprecated and will be removed in a future version.
 *
 * @param array $options Options for the counter string. See #options for list of keys.
 * @return string Counter string.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::counter
 */
	public function counter($options = array()) {
		if (is_string($options)) {
			$options = array('format' => $options);
		}

		$options += array(
			'model' => $this->defaultModel(),
			'format' => 'pages',
			'separator' => __d('cake', ' of ')
		);

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
				if (!is_array($options['separator'])) {
					$options['separator'] = array(' - ', $options['separator']);
				}
				$out = $start . $options['separator'][0] . $end . $options['separator'][1];
				$out .= $paging['count'];
				break;
			case 'pages':
				$out = $paging['page'] . $options['separator'] . $paging['pageCount'];
				break;
			default:
				$map = array(
					'%page%' => $paging['page'],
					'%pages%' => $paging['pageCount'],
					'%current%' => $paging['current'],
					'%count%' => $paging['count'],
					'%start%' => $start,
					'%end%' => $end,
					'%model%' => strtolower(Inflector::humanize(Inflector::tableize($options['model'])))
				);
				$out = str_replace(array_keys($map), array_values($map), $options['format']);

				$newKeys = array(
					'{:page}', '{:pages}', '{:current}', '{:count}', '{:start}', '{:end}', '{:model}'
				);
				$out = str_replace($newKeys, array_values($map), $out);
		}
		return $out;
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
 * - `before` Content to be inserted before the numbers
 * - `after` Content to be inserted after the numbers
 * - `model` Model to create numbers for, defaults to PaginatorHelper::defaultModel()
 * - `modulus` how many numbers to include on either side of the current page, defaults to 8.
 * - `separator` Separator content defaults to ' | '
 * - `tag` The tag to wrap links in, defaults to 'span'
 * - `first` Whether you want first links generated, set to an integer to define the number of 'first'
 *    links to generate. If a string is set a link to the first page will be generated with the value
 *    as the title.
 * - `last` Whether you want last links generated, set to an integer to define the number of 'last'
 *    links to generate. If a string is set a link to the last page will be generated with the value
 *    as the title.
 * - `ellipsis` Ellipsis content, defaults to '...'
 * - `class` Class for wrapper tag
 * - `currentClass` Class for wrapper tag on current active page, defaults to 'current'
 * - `currentTag` Tag to use for current page number, defaults to null
 *
 * @param array|bool $options Options for the numbers, (before, after, model, modulus, separator)
 * @return string Numbers string.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::numbers
 */
	public function numbers($options = array()) {
		if ($options === true) {
			$options = array(
				'before' => ' | ', 'after' => ' | ', 'first' => 'first', 'last' => 'last'
			);
		}

		$defaults = array(
			'tag' => 'span', 'before' => null, 'after' => null, 'model' => $this->defaultModel(), 'class' => null,
			'modulus' => '8', 'separator' => ' | ', 'first' => null, 'last' => null, 'ellipsis' => '...',
			'currentClass' => 'current', 'currentTag' => null
		);
		$options += $defaults;

		$params = (array)$this->params($options['model']) + array('page' => 1);
		unset($options['model']);

		if (empty($params['pageCount']) || $params['pageCount'] <= 1) {
			return '';
		}

		extract($options);
		unset($options['tag'], $options['before'], $options['after'], $options['model'],
			$options['modulus'], $options['separator'], $options['first'], $options['last'],
			$options['ellipsis'], $options['class'], $options['currentClass'], $options['currentTag']
		);
		$out = '';

		if ($modulus && $params['pageCount'] > $modulus) {
			$half = (int)($modulus / 2);
			$end = $params['page'] + $half;

			if ($end > $params['pageCount']) {
				$end = $params['pageCount'];
			}
			$start = $params['page'] - ($modulus - ($end - $params['page']));
			if ($start <= 1) {
				$start = 1;
				$end = $params['page'] + ($modulus - $params['page']) + 1;
			}

			$firstPage = is_int($first) ? $first : 0;
			if ($first && $start > 1) {
				$offset = ($start <= $firstPage) ? $start - 1 : $first;
				if ($firstPage < $start - 1) {
					$out .= $this->first($offset, compact('tag', 'separator', 'ellipsis', 'class'));
				} else {
					$out .= $this->first($offset, compact('tag', 'separator', 'class', 'ellipsis') + array('after' => $separator));
				}
			}

			$out .= $before;

			for ($i = $start; $i < $params['page']; $i++) {
				$out .= $this->Html->tag($tag, $this->link($i, array('page' => $i), $options), compact('class')) . $separator;
			}

			if ($class) {
				$currentClass .= ' ' . $class;
			}
			if ($currentTag) {
				$out .= $this->Html->tag($tag, $this->Html->tag($currentTag, $params['page']), array('class' => $currentClass));
			} else {
				$out .= $this->Html->tag($tag, $params['page'], array('class' => $currentClass));
			}
			if ($i != $params['pageCount']) {
				$out .= $separator;
			}

			$start = $params['page'] + 1;
			for ($i = $start; $i < $end; $i++) {
				$out .= $this->Html->tag($tag, $this->link($i, array('page' => $i), $options), compact('class')) . $separator;
			}

			if ($end != $params['page']) {
				$out .= $this->Html->tag($tag, $this->link($i, array('page' => $end), $options), compact('class'));
			}

			$out .= $after;

			if ($last && $end < $params['pageCount']) {
				$lastPage = is_int($last) ? $last : 0;
				$offset = ($params['pageCount'] < $end + $lastPage) ? $params['pageCount'] - $end : $last;
				if ($offset <= $lastPage && $params['pageCount'] - $end > $lastPage) {
					$out .= $this->last($offset, compact('tag', 'separator', 'ellipsis', 'class'));
				} else {
					$out .= $this->last($offset, compact('tag', 'separator', 'class', 'ellipsis') + array('before' => $separator));
				}
			}

		} else {
			$out .= $before;

			for ($i = 1; $i <= $params['pageCount']; $i++) {
				if ($i == $params['page']) {
					if ($class) {
						$currentClass .= ' ' . $class;
					}
					if ($currentTag) {
						$out .= $this->Html->tag($tag, $this->Html->tag($currentTag, $i), array('class' => $currentClass));
					} else {
						$out .= $this->Html->tag($tag, $i, array('class' => $currentClass));
					}
				} else {
					$out .= $this->Html->tag($tag, $this->link($i, array('page' => $i), $options), compact('class'));
				}
				if ($i != $params['pageCount']) {
					$out .= $separator;
				}
			}

			$out .= $after;
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
 * - `tag` The tag wrapping tag you want to use, defaults to 'span'
 * - `after` Content to insert after the link/tag
 * - `model` The model to use defaults to PaginatorHelper::defaultModel()
 * - `separator` Content between the generated links, defaults to ' | '
 * - `ellipsis` Content for ellipsis, defaults to '...'
 *
 * @param string|int $first if string use as label for the link. If numeric, the number of page links
 *   you want at the beginning of the range.
 * @param array $options An array of options.
 * @return string Numbers string.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::first
 */
	public function first($first = '<< first', $options = array()) {
		$options = (array)$options + array(
			'tag' => 'span',
			'after' => null,
			'model' => $this->defaultModel(),
			'separator' => ' | ',
			'ellipsis' => '...',
			'class' => null
		);

		$params = array_merge(array('page' => 1), (array)$this->params($options['model']));
		unset($options['model']);

		if ($params['pageCount'] <= 1) {
			return '';
		}
		extract($options);
		unset($options['tag'], $options['after'], $options['model'], $options['separator'], $options['ellipsis'], $options['class']);

		$out = '';

		if ((is_int($first) || ctype_digit($first)) && $params['page'] >= $first) {
			if ($after === null) {
				$after = $ellipsis;
			}
			for ($i = 1; $i <= $first; $i++) {
				$out .= $this->Html->tag($tag, $this->link($i, array('page' => $i), $options), compact('class'));
				if ($i != $first) {
					$out .= $separator;
				}
			}
			$out .= $after;
		} elseif ($params['page'] > 1 && is_string($first)) {
			$options += array('rel' => 'first');
			$out = $this->Html->tag($tag, $this->link($first, array('page' => 1), $options), compact('class')) . $after;
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
 * - `tag` The tag wrapping tag you want to use, defaults to 'span'
 * - `before` Content to insert before the link/tag
 * - `model` The model to use defaults to PaginatorHelper::defaultModel()
 * - `separator` Content between the generated links, defaults to ' | '
 * - `ellipsis` Content for ellipsis, defaults to '...'
 *
 * @param string|int $last if string use as label for the link, if numeric print page numbers
 * @param array $options Array of options
 * @return string Numbers string.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/paginator.html#PaginatorHelper::last
 */
	public function last($last = 'last >>', $options = array()) {
		$options = (array)$options + array(
			'tag' => 'span',
			'before' => null,
			'model' => $this->defaultModel(),
			'separator' => ' | ',
			'ellipsis' => '...',
			'class' => null
		);

		$params = array_merge(array('page' => 1), (array)$this->params($options['model']));
		unset($options['model']);

		if ($params['pageCount'] <= 1) {
			return '';
		}

		extract($options);
		unset($options['tag'], $options['before'], $options['model'], $options['separator'], $options['ellipsis'], $options['class']);

		$out = '';
		$lower = $params['pageCount'] - (int)$last + 1;

		if ((is_int($last) || ctype_digit($last)) && $params['page'] <= $lower) {
			if ($before === null) {
				$before = $ellipsis;
			}
			for ($i = $lower; $i <= $params['pageCount']; $i++) {
				$out .= $this->Html->tag($tag, $this->link($i, array('page' => $i), $options), compact('class'));
				if ($i != $params['pageCount']) {
					$out .= $separator;
				}
			}
			$out = $before . $out;
		} elseif ($params['page'] < $params['pageCount'] && is_string($last)) {
			$options += array('rel' => 'last');
			$out = $before . $this->Html->tag(
				$tag, $this->link($last, array('page' => $params['pageCount']), $options), compact('class')
			);
		}
		return $out;
	}

/**
 * Returns the meta-links for a paginated result set.
 *
 * `echo $this->Paginator->meta();`
 *
 * Echos the links directly, will output nothing if there is neither a previous nor next page.
 *
 * `$this->Paginator->meta(array('block' => true));`
 *
 * Will append the output of the meta function to the named block - if true is passed the "meta"
 * block is used.
 *
 * ### Options:
 *
 * - `model` The model to use defaults to PaginatorHelper::defaultModel()
 * - `block` The block name to append the output to, or false/absent to return as a string
 *
 * @param array $options Array of options.
 * @return string|null Meta links.
 */
	public function meta($options = array()) {
		$model = isset($options['model']) ? $options['model'] : null;
		$params = $this->params($model);
		$urlOptions = isset($this->options['url']) ? $this->options['url'] : array();
		$links = array();
		if ($this->hasPrev()) {
			$links[] = $this->Html->meta(array(
				'rel' => 'prev',
				'link' => $this->url(array_merge($urlOptions, array('page' => $params['page'] - 1)), true)
			));
		}
		if ($this->hasNext()) {
			$links[] = $this->Html->meta(array(
				'rel' => 'next',
				'link' => $this->url(array_merge($urlOptions, array('page' => $params['page'] + 1)), true)
			));
		}
		$out = implode($links);
		if (empty($options['block'])) {
			return $out;
		}
		if ($options['block'] === true) {
			$options['block'] = __FUNCTION__;
		}
		$this->_View->append($options['block'], $out);
	}

}
