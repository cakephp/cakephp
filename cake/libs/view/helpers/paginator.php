<?php
/* SVN FILE: $Id$ */
/**
 * Pagination Helper class file.
 *
 * Generates pagination links
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c)	2006, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package			cake
 * @subpackage		cake.cake.libs.view.helpers
 * @since			CakePHP v 1.2.0
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Html Helper class for easy use of HTML widgets.
 *
 * HtmlHelper encloses all methods needed while working with HTML pages.
 *
 * @package		cake
 * @subpackage	cake.cake.libs.view.helpers
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
 * @var array
 */
	var $options = array();
/**
 * Gets the current page of the in the recordset for the given model
 *
 * @param  string $model Optional model name.  Uses the default if none is specified.
 * @return string The current page number of the paginated resultset.
 */
	function params($model = null) {
		if ($model == null) {
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
 * @param  mixed $options
 * @return void
 */
	function options($options = array()) {
		if (is_string($options)) {
			$options = array('update' => $options);
		}
		$this->options = array_filter(am($this->options, $options));
	}
/**
 * Gets the current page of the in the recordset for the given model
 *
 * @param  string $model Optional model name.  Uses the default if none is specified.
 * @return string The current page number of the paginated resultset.
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
 * @return string The name of the key by which the resultset is being sorted, or
 *                null if the results are not currently sorted.
 */
	function sortKey($model = null, $options = array()) {
		if (empty($options)) {
			$params = $this->params($model);
			$options = am($params['defaults'], $params['options']);
		}

		if (isset($options['sort'])) {
			return $options['sort'];
		} elseif (isset($options['order']) && is_array($options['order'])) {
			return preg_replace('/.*\./', '', key($options['order']));
		}
		return null;
	}
/**
 * Gets the current direction the recordset is sorted
 *
 * @param  string $model
 * @return string
 */
	function sortDir($model = null, $options = array()) {
		if (empty($options)) {
			$params = $this->params($model);
			$options = am($params['defaults'], $params['options']);
		}

		if (isset($options['direction'])) {
			$dir = low($options['direction']);
		} elseif (isset($options['order']) && is_array($options['order'])) {
			$dir = low(current($options['order']));
		}

		if ($dir == 'desc') {
			return 'desc';
		} else {
			return 'asc';
		}
		return null;
	}
/**
 * Generates a "previous" link for a set of paged records
 *
 * @param  string $title
 * @param  array $options
 * @param  string $disabledTitle
 * @param  array $disabledOptions
 * @return string
 */
	function prev($title = '<< Previous', $options = array(), $disabledTitle = null, $disabledOptions = array()) {
		return $this->__pagingLink('Prev', $title, $options, $disabledTitle, $disabledOptions);
	}
/**
 * Generates a "next" link for a set of paged records
 *
 * @param  string $title
 * @param  array $options
 * @param  string $disabledTitle
 * @param  array $disabledOptions
 * @return string
 */
	function next($title = 'Next >>', $options = array(), $disabledTitle = null, $disabledOptions = array()) {
		return $this->__pagingLink('Next', $title, $options, $disabledTitle, $disabledOptions);
	}
/**
 * Generates a sorting link
 *
 * @param  string $title
 * @param  string $key
 * @param  array $options
 * @return string
 */
	function sort($title, $key = null, $options = array()) {
		$options = am(array('url' => array(), 'model' => null), $options);
		$url = $options['url'];
		unset($options['url']);

		if (empty($key)) {
			$key = $title;
			$title = Inflector::humanize(preg_replace('/_id$/', '', $title));
		}

		$dir = 'asc';
		if ($this->sortKey($options['model']) == $key && $this->sortDir($options['model']) == 'asc') {
			$dir = 'desc';
		}

		$url = am(array('sort' => $key, 'direction' => $dir), $url, array('order' => null));
		return $this->link($title, $url, $options);
	}
/**
 * Generates a plain or Ajax link with pagination parameters
 *
 * @param  string $title
 * @param  mixed $url
 * @param  array $options
 * @return string
 */
	function link($title, $url = array(), $options = array()) {
		$options = am(array('model' => null, 'escape' => true), $options);
		$model = $options['model'];
		unset($options['model']);

		$paging = $this->params($model);
		$url = am(array_filter(Set::diff($paging['options'], $paging['defaults'])), $url);

		if (isset($url['order'])) {
			$sort = $direction = null;
			if (is_array($url['order'])) {
				list($sort, $direction) = array($this->sortKey($model, $url), current($url['order']));
			}
			unset($url['order']);
			$url = am($url, compact('sort', 'direction'));
		}
		if(!empty($this->options)) {
			$options = am($this->options, $options);
		}

		$obj = isset($options['update']) ? 'Ajax' : 'Html';
		$url = am(array('page' => $this->current($model)), $url);
		return $this->{$obj}->link($title, $url, $options);
	}
/**
 * Protected method for generating prev/next links
 *
 */
	function __pagingLink($which, $title = null, $options = array(), $disabledTitle = null, $disabledOptions = array()) {
		$check = 'has' . $which;
		$_defaults = array(
			'url' => array(), 'step' => 1,
			'escape' => true, 'model' => null
		);
		$options = am($_defaults, $options);
		$paging = $this->params($options['model']);

		if (!$this->{$check}() && (!empty($disabledTitle) || !empty($disabledOptions))) {
			if (!empty($disabledTitle) && $disabledTitle !== true) {
				$title = $disabledTitle;
			}
			$options = am($options, $disabledOptions);
		} elseif (!$this->{$check}()) {
			return null;
		}

		foreach (array_keys($_defaults) as $key) {
			${$key} = $options[$key];
			unset($options[$key]);
		}
		$url = am(array('page' => $paging['page'] + ($which == 'Prev' ? $step * -1 : $step)), $url);

		if ($this->{$check}()) {
			return $this->link($title, $url, am($options, array('escape' => $escape)));
		} else {
			return $this->Html->div(null, $title, $options, $escape);
		}
	}
/**
 * Returns true if the given result set is not at the first page
 *
 * @param  string $model
 * @return boolean
 */
	function hasPrev($model = null) {
		return $this->__hasPage($model, 'prev');
	}
/**
 * Returns true if the given result set is not at the last page
 *
 * @param  string $model
 * @return boolean
 */
	function hasNext($model = null) {
		return $this->__hasPage($model, 'next');
	}
/**
 * Returns true if the given result set has the page number given by $page
 *
 * @param  string $model
 * @param  int $page
 * @return boolean
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
 * @return string
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
 * @param  array $options
 * @return string
 */
	function counter($options = array()) {
		if (is_string($options)) {
			$options = array('format' => $options);
		}

		$options = am(
			array(
				'model' => $this->defaultModel(),
				'format' => 'pages',
				'separator' => ' of '
			),
		$options);

		$paging = $this->params($options['model']);
		$start = $paging['page'] > 1 ? ($paging['page'] - 1) * ($paging['options']['limit']) + 1 : '1';
		$end = ($paging['count'] < ($start + $paging['options']['limit'] - 1)) ? $paging['count'] : ($start + $paging['options']['limit'] - 1);

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
				$out = r(array_keys($replace), array_values($replace), $options['format']);
			break;
		}
		return $this->output($out);
	}
}

?>