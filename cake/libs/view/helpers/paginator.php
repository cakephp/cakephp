<?php
/**
 * Pagination Helper class file.
 *
 * Generates pagination links
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.libs.view.helpers
 * @since         CakePHP(tm) v 1.2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Pagination Helper class for easy generation of pagination links.
 *
 * PaginationHelper encloses all methods needed when working with pagination.
 *
 * @package       cake.libs.view.helpers
 * @link http://book.cakephp.org/view/1458/Paginator
 */
class PaginatorHelper extends AppHelper {

/**
 * Helper dependencies
 *
 * @var array
 */
	public $helpers = array('Html');

/**
 * Holds the default model for paged recordsets
 *
 * @var string
 */
	private $__defaultModel = null;

/**
 * The class used for 'Ajax' pagination links.
 *
 * @var string
 */
	protected $_ajaxHelperClass = 'Js';

/**
 * Holds the default options for pagination links
 *
 * The values that may be specified are:
 *
 *  - `$options['format']` Format of the counter. Supported formats are 'range' and 'pages'
 *    and custom (default). In the default mode the supplied string is parsed and constants are replaced
 *    by their actual values.
 *    Constants: %page%, %pages%, %current%, %count%, %start%, %end% .
 *  - `$options['separator']` The separator of the actual page and number of pages (default: ' of ').
 *  - `$options['url']` Url of the action. See Router::url()
 *  - `$options['url']['sort']`  the key that the recordset is sorted.
 *  - `$options['url']['direction']` Direction of the sorting (default: 'asc').
 *  - `$options['url']['page']` Page # to display.
 *  - `$options['model']` The name of the model.
 *  - `$options['escape']` Defines if the title field for the link should be escaped (default: true).
 *  - `$options['update']` DOM id of the element updated with the results of the AJAX call.
 *     If this key isn't specified Paginator will use plain HTML links.
 *  - `$options['indicator']` DOM id of the element that will be shown when doing AJAX requests. **Only supported by
 *     AjaxHelper**
 *
 * @var array
 * @access public
 */
	public $options = array();

/**
 * Constructor for the helper. Sets up the helper that is used for creating 'AJAX' links.
 *
 * Use `public $helpers = array('Paginator' => array('ajax' => 'CustomHelper'));` to set a custom Helper
 * or choose a non JsHelper Helper.  If you want to use a specific library with JsHelper declare JsHelper and its
 * adapter before including PaginatorHelper in your helpers array.
 *
 * The chosen custom helper must implement a `link()` method.
 *
 * @param View $View the view object the helper is attached to.
 * @param array $settings Array of settings.
 * @throws CakeException When the AjaxProvider helper does not implement a link method.
 */
	function __construct(View $View, $settings = array()) {
		parent::__construct($View, $settings);
		$ajaxProvider = isset($settings['ajax']) ? $settings['ajax'] : 'Js';
		$this->helpers[] = $ajaxProvider;
		$this->_ajaxHelperClass = $ajaxProvider;
		if (!class_exists($ajaxProvider . 'Helper')) {
			App::import('Helper', $ajaxProvider);
		}
		$classname = $ajaxProvider . 'Helper';
		if (!method_exists($classname, 'link')) {
			throw new CakeException(sprintf(
				__('%s does not implement a link() method, it is incompatible with PaginatorHelper'), $classname
			));
		}
	}

/**
 * Before render callback. Overridden to merge passed args with url options.
 *
 * @return void
 */
	public function beforeRender($viewFile) {
		$this->options['url'] = array_merge($this->request->params['pass'], $this->request->params['named']);

		parent::beforeRender($viewFile);
	}

/**
 * Gets the current paging parameters from the resultset for the given model
 *
 * @param string $model Optional model name.  Uses the default if none is specified.
 * @return array The array of paging parameters for the paginated resultset.
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
 * Sets default options for all pagination links
 *
 * @param mixed $options Default options for pagination links. If a string is supplied - it
 *   is used as the DOM id element to update. See PaginatorHelper::$options for list of keys.
 * @return void
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
		$this->options = array_filter(array_merge($this->options, $options));
	}

/**
 * Gets the current page of the recordset for the given model
 *
 * @param string $model Optional model name.  Uses the default if none is specified.
 * @return string The current page number of the recordset.
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
 * @param string $model Optional model name.  Uses the default if none is specified.
 * @param mixed $options Options for pagination links. See #options for list of keys.
 * @return string The name of the key by which the recordset is being sorted, or
 *  null if the results are not currently sorted.
 */
	public function sortKey($model = null, $options = array()) {
		if (empty($options)) {
			$params = $this->params($model);
			$options = array_merge($params['defaults'], $params['options']);
		}

		if (isset($options['sort']) && !empty($options['sort'])) {
			return $options['sort'];
		} elseif (isset($options['order']) && is_array($options['order'])) {
			return key($options['order']);
		} elseif (isset($options['order']) && is_string($options['order'])) {
			return $options['order'];
		}
		return null;
	}

/**
 * Gets the current direction the recordset is sorted
 *
 * @param string $model Optional model name.  Uses the default if none is specified.
 * @param mixed $options Options for pagination links. See #options for list of keys.
 * @return string The direction by which the recordset is being sorted, or
 *  null if the results are not currently sorted.
 */
	public function sortDir($model = null, $options = array()) {
		$dir = null;

		if (empty($options)) {
			$params = $this->params($model);
			$options = array_merge($params['defaults'], $params['options']);
		}

		if (isset($options['direction'])) {
			$dir = strtolower($options['direction']);
		} elseif (isset($options['order']) && is_array($options['order'])) {
			$dir = strtolower(current($options['order']));
		}

		if ($dir == 'desc') {
			return 'desc';
		}
		return 'asc';
	}

/**
 * Generates a "previous" link for a set of paged records
 *
 * ### Options:
 *
 * - `tag` The tag wrapping tag you want to use, defaults to 'span'
 * - `escape` Whether you want the contents html entity encoded, defaults to true
 * - `model` The model to use, defaults to PaginatorHelper::defaultModel()
 *
 * @param  string $title Title for the link. Defaults to '<< Previous'.
 * @param  mixed $options Options for pagination link. See #options for list of keys.
 * @param  string $disabledTitle Title when the link is disabled.
 * @param  mixed $disabledOptions Options for the disabled pagination link. See #options for list of keys.
 * @return string A "previous" link or $disabledTitle text if the link is disabled.
 */
	public function prev($title = '<< Previous', $options = array(), $disabledTitle = null, $disabledOptions = array()) {
		return $this->__pagingLink('Prev', $title, $options, $disabledTitle, $disabledOptions);
	}

/**
 * Generates a "next" link for a set of paged records
 *
 * ### Options:
 *
 * - `tag` The tag wrapping tag you want to use, defaults to 'span'
 * - `escape` Whether you want the contents html entity encoded, defaults to true
 * - `model` The model to use, defaults to PaginatorHelper::defaultModel()
 *
 * @param string $title Title for the link. Defaults to 'Next >>'.
 * @param mixed $options Options for pagination link. See above for list of keys.
 * @param string $disabledTitle Title when the link is disabled.
 * @param mixed $disabledOptions Options for the disabled pagination link. See above for list of keys.
 * @return string A "next" link or or $disabledTitle text if the link is disabled.
 */
	public function next($title = 'Next >>', $options = array(), $disabledTitle = null, $disabledOptions = array()) {
		return $this->__pagingLink('Next', $title, $options, $disabledTitle, $disabledOptions);
	}

/**
 * Generates a sorting link. Sets named parameters for the sort and direction.  Handles
 * direction switching automatically.
 *
 * ### Options:
 *
 * - `escape` Whether you want the contents html entity encoded, defaults to true
 * - `model` The model to use, defaults to PaginatorHelper::defaultModel()
 *
 * @param string $title Title for the link.
 * @param string $key The name of the key that the recordset should be sorted.  If $key is null
 *   $title will be used for the key, and a title will be generated by inflection.
 * @param array $options Options for sorting link. See above for list of keys.
 * @return string A link sorting default by 'asc'. If the resultset is sorted 'asc' by the specified
 *  key the returned link will sort by 'desc'.
 */
	public function sort($title, $key = null, $options = array()) {
		$options = array_merge(array('url' => array(), 'model' => null), $options);
		$url = $options['url'];
		unset($options['url']);

		if (empty($key)) {
			$key = $title;
			$title = __(Inflector::humanize(preg_replace('/_id$/', '', $title)));
		}
		$dir = isset($options['direction']) ? $options['direction'] : 'asc';
		unset($options['direction']);

		$sortKey = $this->sortKey($options['model']);
		$defaultModel = $this->defaultModel();
		$isSorted = (
			$sortKey === $key || 
			$sortKey === $defaultModel . '.' . $key ||
			$key === $defaultModel . '.' . $sortKey
		);

		if ($isSorted) {
			$dir = $this->sortDir($options['model']) === 'asc' ? 'desc' : 'asc';
			$class = $dir === 'asc' ? 'desc' : 'asc';
			if (!empty($options['class'])) {
				$options['class'] .= ' ' . $class;
			} else {
				$options['class'] = $class;
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
 * - `update` The Id of the DOM element you wish to update.  Creates Ajax enabled links
 *    with the AjaxHelper.
 * - `escape` Whether you want the contents html entity encoded, defaults to true
 * - `model` The model to use, defaults to PaginatorHelper::defaultModel()
 *
 * @param string $title Title for the link.
 * @param mixed $url Url for the action. See Router::url()
 * @param array $options Options for the link. See #options for list of keys.
 * @return string A link with pagination parameters.
 */
	public function link($title, $url = array(), $options = array()) {
		$options = array_merge(array('model' => null, 'escape' => true), $options);
		$model = $options['model'];
		unset($options['model']);

		if (!empty($this->options)) {
			$options = array_merge($this->options, $options);
		}
		if (isset($options['url'])) {
			$url = array_merge((array)$options['url'], (array)$url);
			unset($options['url']);
		}
		$url = $this->url($url, true, $model);

		$obj = isset($options['update']) ? $this->_ajaxHelperClass : 'Html';
		$url = array_merge(array('page' => $this->current($model)), $url);
		$url = array_merge(Set::filter($url, true), array_intersect_key($url, array('plugin' => true)));
		return $this->{$obj}->link($title, $url, $options);
	}

/**
 * Merges passed URL options with current pagination state to generate a pagination URL.
 *
 * @param array $options Pagination/URL options array
 * @param boolean $asArray Return the url as an array, or a URI string
 * @param string $model Which model to paginate on
 * @return mixed By default, returns a full pagination URL string for use in non-standard contexts (i.e. JavaScript)
 */
	public function url($options = array(), $asArray = false, $model = null) {
		$paging = $this->params($model);
		$url = array_merge(array_filter(Set::diff(array_merge(
			$paging['defaults'], $paging['options']), $paging['defaults'])), $options
		);

		if (isset($url['order'])) {
			$sort = $direction = null;
			if (is_array($url['order'])) {
				list($sort, $direction) = array($this->sortKey($model, $url), current($url['order']));
			}
			unset($url['order']);
			$url = array_merge($url, compact('sort', 'direction'));
		}

		if ($asArray) {
			return $url;
		}
		return parent::url($url);
	}

/**
 * Protected method for generating prev/next links
 *
 */
	protected function __pagingLink($which, $title = null, $options = array(), $disabledTitle = null, $disabledOptions = array()) {
		$check = 'has' . $which;
		$_defaults = array(
			'url' => array(), 'step' => 1, 'escape' => true,
			'model' => null, 'tag' => 'span', 'class' => strtolower($which)
		);
		$options = array_merge($_defaults, (array)$options);
		$paging = $this->params($options['model']);
		if (empty($disabledOptions)) {
			$disabledOptions = $options;
		}

		if (!$this->{$check}($options['model']) && (!empty($disabledTitle) || !empty($disabledOptions))) {
			if (!empty($disabledTitle) && $disabledTitle !== true) {
				$title = $disabledTitle;
			}
			$options = array_merge($_defaults, (array)$disabledOptions);
		} elseif (!$this->{$check}($options['model'])) {
			return null;
		}

		foreach (array_keys($_defaults) as $key) {
			${$key} = $options[$key];
			unset($options[$key]);
		}
		$url = array_merge(array('page' => $paging['page'] + ($which == 'Prev' ? $step * -1 : $step)), $url);

		if ($this->{$check}($model)) {
			return $this->Html->tag($tag, $this->link($title, $url, array_merge($options, compact('escape', 'class'))));
		} else {
			return $this->Html->tag($tag, $title, array_merge($options, compact('escape', 'class')));
		}
	}

/**
 * Returns true if the given result set is not at the first page
 *
 * @param string $model Optional model name. Uses the default if none is specified.
 * @return boolean True if the result set is not at the first page.
 */
	public function hasPrev($model = null) {
		return $this->__hasPage($model, 'prev');
	}

/**
 * Returns true if the given result set is not at the last page
 *
 * @param string $model Optional model name.  Uses the default if none is specified.
 * @return boolean True if the result set is not at the last page.
 */
	public function hasNext($model = null) {
		return $this->__hasPage($model, 'next');
	}

/**
 * Returns true if the given result set has the page number given by $page
 *
 * @param string $model Optional model name.  Uses the default if none is specified.
 * @param int $page The page number - if not set defaults to 1.
 * @return boolean True if the given result set has the specified page number.
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
	protected function __hasPage($model, $page) {
		$params = $this->params($model);
		if (!empty($params)) {
			if ($params["{$page}Page"] == true) {
				return true;
			}
		}
		return false;
	}

/**
 * Gets the default model of the paged sets
 *
 * @return string Model name or null if the pagination isn't initialized.
 */
	public function defaultModel() {
		if ($this->__defaultModel != null) {
			return $this->__defaultModel;
		}
		if (empty($this->request->params['paging'])) {
			return null;
		}
		list($this->__defaultModel) = array_keys($this->request->params['paging']);
		return $this->__defaultModel;
	}

/**
 * Returns a counter string for the paged result set
 *
 * ### Options
 *
 * - `model` The model to use, defaults to PaginatorHelper::defaultModel();
 * - `format` The format string you want to use, defaults to 'pages' Which generates output like '1 of 5'
 *    set to 'range' to generate output like '1 - 3 of 13'.  Can also be set to a custom string, containing
 *    the following placeholders `%page%`, `%pages%`, `%current%`, `%count%`, `%start%`, `%end%` and any
 *    custom content you would like.
 * - `separator` The separator string to use, default to ' of '
 *
 * @param mixed $options Options for the counter string. See #options for list of keys.
 * @return string Counter string.
 */
	public function counter($options = array()) {
		if (is_string($options)) {
			$options = array('format' => $options);
		}

		$options = array_merge(
			array(
				'model' => $this->defaultModel(),
				'format' => 'pages',
				'separator' => __(' of ')
			),
		$options);

		$paging = $this->params($options['model']);
		if ($paging['pageCount'] == 0) {
			$paging['pageCount'] = 1;
		}
		$start = 0;
		if ($paging['count'] >= 1) {
			$start = (($paging['page'] - 1) * $paging['options']['limit']) + 1;
		}
		$end = $start + $paging['options']['limit'] - 1;
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
					'%end%' => $end
				);
				$out = str_replace(array_keys($map), array_values($map), $options['format']);

				$newKeys = array(
					'{:page}', '{:pages}', '{:current}', '{:count}', '{:start}', '{:end}'
				);
				$out = str_replace($newKeys, array_values($map), $out);
			break;
		}
		return $out;
	}

/**
 * Returns a set of numbers for the paged result set
 * uses a modulus to decide how many numbers to show on each side of the current page (default: 8)
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
 *    links to generate
 * - `last` Whether you want last links generated, set to an integer to define the number of 'last'
 *    links to generate
 * - `ellipsis` Ellipsis content, defaults to '...'
 *
 * @param mixed $options Options for the numbers, (before, after, model, modulus, separator)
 * @return string numbers string.
 */
	public function numbers($options = array()) {
		if ($options === true) {
			$options = array(
				'before' => ' | ', 'after' => ' | ', 'first' => 'first', 'last' => 'last'
			);
		}

		$defaults = array(
			'tag' => 'span', 'before' => null, 'after' => null, 'model' => $this->defaultModel(),
			'modulus' => '8', 'separator' => ' | ', 'first' => null, 'last' => null, 'ellipsis' => '...',
		);
		$options += $defaults;

		$params = (array)$this->params($options['model']) + array('page'=> 1);
		unset($options['model']);

		if ($params['pageCount'] <= 1) {
			return false;
		}

		extract($options);
		unset($options['tag'], $options['before'], $options['after'], $options['model'],
			$options['modulus'], $options['separator'], $options['first'], $options['last'],
			$options['ellipsis']
		);

		$out = '';

		if ($modulus && $params['pageCount'] > $modulus) {
			$half = intval($modulus / 2);
			$end = $params['page'] + $half;

			if ($end > $params['pageCount']) {
				$end = $params['pageCount'];
			}
			$start = $params['page'] - ($modulus - ($end - $params['page']));
			if ($start <= 1) {
				$start = 1;
				$end = $params['page'] + ($modulus  - $params['page']) + 1;
			}

			if ($first && $start > 1) {
				$offset = ($start <= (int)$first) ? $start - 1 : $first;
				if ($offset < $start - 1) {
					$out .= $this->first($offset, array('tag' => $tag, 'separator' => $separator, 'ellipsis' => $ellipsis));
				} else {
					$out .= $this->first($offset, array('tag' => $tag, 'after' => $separator, 'separator' => $separator));
				}
			}

			$out .= $before;

			for ($i = $start; $i < $params['page']; $i++) {
				$out .= $this->Html->tag($tag, $this->link($i, array('page' => $i), $options))
					. $separator;
			}

			$out .= $this->Html->tag($tag, $params['page'], array('class' => 'current'));
			if ($i != $params['pageCount']) {
				$out .= $separator;
			}

			$start = $params['page'] + 1;
			for ($i = $start; $i < $end; $i++) {
				$out .= $this->Html->tag($tag, $this->link($i, array('page' => $i), $options))
					. $separator;
			}

			if ($end != $params['page']) {
				$out .= $this->Html->tag($tag, $this->link($i, array('page' => $end), $options));
			}

			$out .= $after;

			if ($last && $end < $params['pageCount']) {
				$offset = ($params['pageCount'] < $end + (int)$last) ? $params['pageCount'] - $end : $last;
				if ($offset <= $last && $params['pageCount'] - $end > $offset) {
					$out .= $this->last($offset, array('tag' => $tag, 'separator' => $separator, 'ellipsis' => $ellipsis));
				} else {
					$out .= $this->last($offset, array('tag' => $tag, 'before' => $separator, 'separator' => $separator));
				}
			}

		} else {
			$out .= $before;

			for ($i = 1; $i <= $params['pageCount']; $i++) {
				if ($i == $params['page']) {
					$out .= $this->Html->tag($tag, $i, array('class' => 'current'));
				} else {
					$out .= $this->Html->tag($tag, $this->link($i, array('page' => $i), $options));
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
 * Returns a first or set of numbers for the first pages
 *
 * ### Options:
 *
 * - `tag` The tag wrapping tag you want to use, defaults to 'span'
 * - `before` Content to insert before the link/tag
 * - `model` The model to use defaults to PaginatorHelper::defaultModel()
 * - `separator` Content between the generated links, defaults to ' | '
 * - `ellipsis` Content for ellipsis, defaults to '...'
 *
 * @param mixed $first if string use as label for the link, if numeric print page numbers
 * @param mixed $options
 * @return string numbers string.
 */
	public function first($first = '<< first', $options = array()) {
		$options = array_merge(
			array(
				'tag' => 'span',
				'after'=> null,
				'model' => $this->defaultModel(),
				'separator' => ' | ',
				'ellipsis' => '...',
			),
		(array)$options);

		$params = array_merge(array('page'=> 1), (array)$this->params($options['model']));
		unset($options['model']);

		if ($params['pageCount'] <= 1) {
			return false;
		}
		extract($options);
		unset($options['tag'], $options['after'], $options['model'], $options['separator'], $options['ellipsis']);

		$out = '';

		if (is_int($first) && $params['page'] > $first) {
			if ($after === null) {
				$after = $ellipsis;
			}
			for ($i = 1; $i <= $first; $i++) {
				$out .= $this->Html->tag($tag, $this->link($i, array('page' => $i), $options));
				if ($i != $first) {
					$out .= $separator;
				}
			}
			$out .= $after;
		} elseif ($params['page'] > 1) {
			$out = $this->Html->tag($tag, $this->link($first, array('page' => 1), $options))
				. $after;
		}
		return $out;
	}

/**
 * Returns a last or set of numbers for the last pages
 *
 * ### Options:
 *
 * - `tag` The tag wrapping tag you want to use, defaults to 'span'
 * - `before` Content to insert before the link/tag
 * - `model` The model to use defaults to PaginatorHelper::defaultModel()
 * - `separator` Content between the generated links, defaults to ' | '
 * - `ellipsis` Content for ellipsis, defaults to '...'
 *
 * @param mixed $last if string use as label for the link, if numeric print page numbers
 * @param mixed $options Array of options
 * @return string numbers string.
 */
	public function last($last = 'last >>', $options = array()) {
		$options = array_merge(
			array(
				'tag' => 'span',
				'before'=> null,
				'model' => $this->defaultModel(),
				'separator' => ' | ',
				'ellipsis' => '...',
			),
		(array)$options);

		$params = array_merge(array('page'=> 1), (array)$this->params($options['model']));
		unset($options['model']);

		if ($params['pageCount'] <= 1) {
			return false;
		}

		extract($options);
		unset($options['tag'], $options['before'], $options['model'], $options['separator'], $options['ellipsis']);

		$out = '';
		$lower = $params['pageCount'] - $last + 1;

		if (is_int($last) && $params['page'] < $lower) {
			if ($before === null) {
				$before = $ellipsis;
			}
			for ($i = $lower; $i <= $params['pageCount']; $i++) {
				$out .= $this->Html->tag($tag, $this->link($i, array('page' => $i), $options));
				if ($i != $params['pageCount']) {
					$out .= $separator;
				}
			}
			$out = $before . $out;
		} elseif ($params['page'] < $params['pageCount']) {
			$out = $before . $this->Html->tag(
				$tag, $this->link($last, array('page' => $params['pageCount']), $options
			));
		}
		return $out;
	}
}
