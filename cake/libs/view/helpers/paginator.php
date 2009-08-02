<?php
/* SVN FILE: $Id$ */
/**
 * Pagination Helper class file.
 *
 * Generates pagination links
 *
 * CakePHP(tm) :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.view.helpers
 * @since         CakePHP(tm) v 1.2.0
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Pagination Helper class for easy generation of pagination links.
 *
 * PaginationHelper encloses all methods needed when working with pagination.
 *
 * @package       cake
 * @subpackage    cake.cake.libs.view.helpers
 */
class PaginatorHelper extends AppHelper {
/**
 * Helper dependencies
 *
 * @var array
 */
	var $helpers = array('Html', 'Ajax');
/**
 * Holds the default model for paged recordsets
 *
 * @var string
 */
	var $__defaultModel = null;
/**
 * Holds the default options for pagination links
 *
 * The values that may be specified are:
 *
 *  - <i>$options['format']</i> Format of the counter. Supported formats are 'range' and 'pages'
 *   and custom (default). In the default mode the supplied string is parsed and constants are replaced
 *   by their actual values.
 *   Constants: %page%, %pages%, %current%, %count%, %start%, %end% .
 *  - <i>$options['separator']</i> The separator of the actual page and number of pages (default: ' of ').
 *  - <i>$options['url']</i> Url of the action. See Router::url()
 *  - <i>$options['url']['sort']</i>  the key that the recordset is sorted.
 *  - <i>$options['url']['direction']</i> Direction of the sorting (default: 'asc').
 *  - <i>$options['url']['page']</i> Page # to display.
 *  - <i>$options['model']</i> The name of the model.
 *  - <i>$options['escape']</i> Defines if the title field for the link should be escaped (default: true).
 *  - <i>$options['update']</i> DOM id of the element updated with the results of the AJAX call.
 *                             If this key isn't specified Paginator will use plain HTML links.
 *  - <i>$options['indicator']</i> DOM id of the element that will be shown when doing AJAX requests.
 *
 * @var array
 */
	var $options = array();
/**
 * Gets the current paging parameters from the resultset for the given model
 *
 * @param  string $model Optional model name.  Uses the default if none is specified.
 * @return array The array of paging parameters for the paginated resultset.
 */
	function params($model = null) {
		if (empty($model)) {
			$model = $this->defaultModel();
		}
		if (!isset($this->params['paging']) || empty($this->params['paging'][$model])) {
			return null;
		}
		return $this->params['paging'][$model];
	}
/**
 * Sets default options for all pagination links
 *
 * @param  mixed $options Default options for pagination links. If a string is supplied - it
 * is used as the DOM id element to update. See #options for list of keys.
 */
	function options($options = array()) {
		if (is_string($options)) {
			$options = array('update' => $options);
		}

		if (!empty($options['paging'])) {
			if (!isset($this->params['paging'])) {
				$this->params['paging'] = array();
			}
			$this->params['paging'] = array_merge($this->params['paging'], $options['paging']);
			unset($options['paging']);
		}
		$model = $this->defaultModel();

		if (!empty($options[$model])) {
			if (!isset($this->params['paging'][$model])) {
				$this->params['paging'][$model] = array();
			}
			$this->params['paging'][$model] = array_merge($this->params['paging'][$model], $options[$model]);
			unset($options[$model]);
		}
		$this->options = array_filter(array_merge($this->options, $options));
	}
/**
 * Gets the current page of the recordset for the given model
 *
 * @param  string $model Optional model name.  Uses the default if none is specified.
 * @return string The current page number of the recordset.
 */
	function current($model = null) {
		$params = $this->params($model);

		if (isset($params['page'])) {
			return $params['page'];
		}
		return 1;
	}
/**
 * Gets the current key by which the recordset is sorted
 *
 * @param  string $model Optional model name.  Uses the default if none is specified.
 * @param  mixed $options Options for pagination links. See #options for list of keys.
 * @return string The name of the key by which the recordset is being sorted, or
 *  null if the results are not currently sorted.
 */
	function sortKey($model = null, $options = array()) {
		if (empty($options)) {
			$params = $this->params($model);
			$options = array_merge($params['defaults'], $params['options']);
		}

		if (isset($options['sort']) && !empty($options['sort'])) {
			if (preg_match('/(?:\w+\.)?(\w+)/', $options['sort'], $result) && isset($result[1])) {
				if ($result[0] == $this->defaultModel()) {
					return $result[1];
				}
			}
			return $options['sort'];
		} elseif (isset($options['order']) && is_array($options['order'])) {
			return key($options['order']);
		} elseif (isset($options['order']) && is_string($options['order'])) {
			if (preg_match('/(?:\w+\.)?(\w+)/', $options['order'], $result) && isset($result[1])) {
				return $result[1];
			}
			return $options['order'];
		}
		return null;
	}
/**
 * Gets the current direction the recordset is sorted
 *
 * @param  string $model Optional model name.  Uses the default if none is specified.
 * @param  mixed $options Options for pagination links. See #options for list of keys.
 * @return string The direction by which the recordset is being sorted, or
 *  null if the results are not currently sorted.
 */
	function sortDir($model = null, $options = array()) {
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
 * @param  string $title Title for the link. Defaults to '<< Previous'.
 * @param  mixed $options Options for pagination link. See #options for list of keys.
 * @param  string $disabledTitle Title when the link is disabled.
 * @param  mixed $disabledOptions Options for the disabled pagination link. See #options for list of keys.
 * @return string A "previous" link or $disabledTitle text if the link is disabled.
 */
	function prev($title = '<< Previous', $options = array(), $disabledTitle = null, $disabledOptions = array()) {
		return $this->__pagingLink('Prev', $title, $options, $disabledTitle, $disabledOptions);
	}
/**
 * Generates a "next" link for a set of paged records
 *
 * @param  string $title Title for the link. Defaults to 'Next >>'.
 * @param  mixed $options Options for pagination link. See #options for list of keys.
 * @param  string $disabledTitle Title when the link is disabled.
 * @param  mixed $disabledOptions Options for the disabled pagination link. See #options for list of keys.
 * @return string A "next" link or or $disabledTitle text if the link is disabled.
 */
	function next($title = 'Next >>', $options = array(), $disabledTitle = null, $disabledOptions = array()) {
		return $this->__pagingLink('Next', $title, $options, $disabledTitle, $disabledOptions);
	}
/**
 * Generates a sorting link
 *
 * @param  string $title Title for the link.
 * @param  string $key The name of the key that the recordset should be sorted.
 * @param  array $options Options for sorting link. See #options for list of keys.
 * @return string A link sorting default by 'asc'. If the resultset is sorted 'asc' by the specified
 *  key the returned link will sort by 'desc'.
 */
	function sort($title, $key = null, $options = array()) {
		$options = array_merge(array('url' => array(), 'model' => null), $options);
		$url = $options['url'];
		unset($options['url']);

		if (empty($key)) {
			$key = $title;
			$title = __(Inflector::humanize(preg_replace('/_id$/', '', $title)), true);
		}
		$dir = 'asc';
		$sortKey = $this->sortKey($options['model']);
		$isSorted = ($sortKey === $key || $sortKey === $this->defaultModel() . '.' . $key);

		if ($isSorted && $this->sortDir($options['model']) === 'asc') {
			$dir = 'desc';
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
 * @param  string $title Title for the link.
 * @param  mixed $url Url for the action. See Router::url()
 * @param  array $options Options for the link. See #options for list of keys.
 * @return string A link with pagination parameters.
 */
	function link($title, $url = array(), $options = array()) {
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

		$obj = isset($options['update']) ? 'Ajax' : 'Html';
		$url = array_merge(array('page' => $this->current($model)), $url);
		$url = array_merge(Set::filter($url, true), array_intersect_key($url, array('plugin'=>true)));
		return $this->{$obj}->link($title, $url, $options);
	}
/**
 * Merges passed URL options with current pagination state to generate a pagination URL.
 *
 * @param  array $options Pagination/URL options array
 * @param  boolean $asArray
 * @param  string $model Which model to paginate on
 * @return mixed By default, returns a full pagination URL string for use in non-standard contexts (i.e. JavaScript)
 */
	function url($options = array(), $asArray = false, $model = null) {
		$paging = $this->params($model);
		$url = array_merge(array_filter(Set::diff(array_merge($paging['defaults'], $paging['options']), $paging['defaults'])), $options);

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
	function __pagingLink($which, $title = null, $options = array(), $disabledTitle = null, $disabledOptions = array()) {
		$check = 'has' . $which;
		$_defaults = array('url' => array(), 'step' => 1, 'escape' => true, 'model' => null, 'tag' => 'div');
		$options = array_merge($_defaults, (array)$options);
		$paging = $this->params($options['model']);

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
			return $this->link($title, $url, array_merge($options, array('escape' => $escape)));
		} else {
			return $this->Html->tag($tag, $title, $options, $escape);
		}
	}
/**
 * Returns true if the given result set is not at the first page
 *
 * @param string $model Optional model name.  Uses the default if none is specified.
 * @return boolean True if the result set is not at the first page.
 */
	function hasPrev($model = null) {
		return $this->__hasPage($model, 'prev');
	}
/**
 * Returns true if the given result set is not at the last page
 *
 * @param string $model Optional model name.  Uses the default if none is specified.
 * @return boolean True if the result set is not at the last page.
 */
	function hasNext($model = null) {
		return $this->__hasPage($model, 'next');
	}
/**
 * Returns true if the given result set has the page number given by $page
 *
 * @param  string $model Optional model name.  Uses the default if none is specified.
 * @param  int $page The page number - if not set defaults to 1.
 * @return boolean True if the given result set has the specified page number.
 */
	function hasPage($model = null, $page = 1) {
		if (is_numeric($model)) {
			$page = $model;
			$model = null;
		}
		$paging = $this->params($model);
		return $page <= $paging['pageCount'];
	}
/**
 * Protected method
 *
 */
	function __hasPage($model, $page) {
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
	function defaultModel() {
		if ($this->__defaultModel != null) {
			return $this->__defaultModel;
		}
		if (empty($this->params['paging'])) {
			return null;
		}
		list($this->__defaultModel) = array_keys($this->params['paging']);
		return $this->__defaultModel;
	}
/**
 * Returns a counter string for the paged result set
 *
 * @param  mixed $options Options for the counter string. See #options for list of keys.
 * @return string Counter string.
 */
	function counter($options = array()) {
		if (is_string($options)) {
			$options = array('format' => $options);
		}

		$options = array_merge(
			array(
				'model' => $this->defaultModel(),
				'format' => 'pages',
				'separator' => ' of '
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
				$out = $start . $options['separator'][0] . $end . $options['separator'][1] . $paging['count'];
			break;
			case 'pages':
				$out = $paging['page'] . $options['separator'] . $paging['pageCount'];
			break;
			default:
				$replace = array(
					'%page%' => $paging['page'],
					'%pages%' => $paging['pageCount'],
					'%current%' => $paging['current'],
					'%count%' => $paging['count'],
					'%start%' => $start,
					'%end%' => $end
				);
				$out = str_replace(array_keys($replace), array_values($replace), $options['format']);
			break;
		}
		return $this->output($out);
	}
/**
 * Returns a set of numbers for the paged result set
 * uses a modulus to decide how many numbers to show on each side of the current page (default: 8)
 *
 * @param  mixed $options Options for the numbers, (before, after, model, modulus, separator)
 * @return string numbers string.
 */
	function numbers($options = array()) {
		if ($options === true) {
			$options = array(
				'before' => ' | ', 'after' => ' | ',
				'first' => 'first', 'last' => 'last',
			);
		}

		$options = array_merge(
			array(
				'tag' => 'span',
				'before'=> null, 'after'=> null,
				'model' => $this->defaultModel(),
				'modulus' => '8', 'separator' => ' | ',
				'first' => null, 'last' => null,
			),
		(array)$options);

		$params = array_merge(array('page'=> 1), (array)$this->params($options['model']));
		unset($options['model']);

		if ($params['pageCount'] <= 1) {
			return false;
		}

		extract($options);
		unset($options['tag'], $options['before'], $options['after'], $options['model'],
			$options['modulus'], $options['separator'], $options['first'], $options['last']);

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
					$out .= $this->first($offset, array('tag' => $tag, 'separator' => $separator));
				} else {
					$out .= $this->first($offset, array('tag' => $tag, 'after' => $separator, 'separator' => $separator));
				}
			}

			$out .= $before;

			for ($i = $start; $i < $params['page']; $i++) {
				$out .= $this->Html->tag($tag, $this->link($i, array('page' => $i), $options)) . $separator;
			}

			$out .= $this->Html->tag($tag, $params['page'], array('class' => 'current'));
			if ($i != $params['pageCount']) {
				$out .= $separator;
			}

			$start = $params['page'] + 1;
			for ($i = $start; $i < $end; $i++) {
				$out .= $this->Html->tag($tag, $this->link($i, array('page' => $i), $options)). $separator;
			}

			if ($end != $params['page']) {
				$out .= $this->Html->tag($tag, $this->link($i, array('page' => $end), $options));
			}

			$out .= $after;

			if ($last && $end < $params['pageCount']) {
				$offset = ($params['pageCount'] < $end + (int)$last) ? $params['pageCount'] - $end : $last;
				if ($offset <= $last && $params['pageCount'] - $end > $offset) {
					$out .= $this->last($offset, array('tag' => $tag, 'separator' => $separator));
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

		return $this->output($out);
	}
/**
 * Returns a first or set of numbers for the first pages
 *
 * @param  mixed $first if string use as label for the link, if numeric print page numbers
 * @param  mixed $options
 * @return string numbers string.
 */
	function first($first = '<< first', $options = array()) {
		$options = array_merge(
			array(
				'tag' => 'span',
				'after'=> null,
				'model' => $this->defaultModel(),
				'separator' => ' | ',
			),
		(array)$options);

		$params = array_merge(array('page'=> 1), (array)$this->params($options['model']));
		unset($options['model']);

		if ($params['pageCount'] <= 1) {
			return false;
		}
		extract($options);
		unset($options['tag'], $options['after'], $options['model'], $options['separator']);

		$out = '';

		if (is_int($first) && $params['page'] > $first) {
			if ($after === null) {
				$after = '...';
			}
			for ($i = 1; $i <= $first; $i++) {
				$out .= $this->Html->tag($tag, $this->link($i, array('page' => $i), $options));
				if ($i != $first) {
					$out .= $separator;
				}
			}
			$out .= $after;
		} elseif ($params['page'] > 1) {
			$out = $this->Html->tag($tag, $this->link($first, array('page' => 1), $options)) . $after;
		}
		return $out;
	}
/**
 * Returns a last or set of numbers for the last pages
 *
 * @param  mixed $last if string use as label for the link, if numeric print page numbers
 * @param  mixed $options
 * @return string numbers string.
 */
	function last($last = 'last >>', $options = array()) {
		$options = array_merge(
			array(
				'tag' => 'span',
				'before'=> null,
				'model' => $this->defaultModel(),
				'separator' => ' | ',
			),
		(array)$options);

		$params = array_merge(array('page'=> 1), (array)$this->params($options['model']));
		unset($options['model']);

		if ($params['pageCount'] <= 1) {
			return false;
		}

		extract($options);
		unset($options['tag'], $options['before'], $options['model'], $options['separator']);

		$out = '';
		$lower = $params['pageCount'] - $last + 1;

		if (is_int($last) && $params['page'] < $lower) {
			if ($before === null) {
				$before = '...';
			}
			for ($i = $lower; $i <= $params['pageCount']; $i++) {
				$out .= $this->Html->tag($tag, $this->link($i, array('page' => $i), $options));
				if ($i != $params['pageCount']) {
					$out .= $separator;
				}
			}
			$out = $before . $out;
		} elseif ($params['page'] < $params['pageCount']) {
			$out = $before . $this->Html->tag($tag, $this->link($last, array('page' => $params['pageCount']), $options));
		}
		return $out;
	}
}
?>
