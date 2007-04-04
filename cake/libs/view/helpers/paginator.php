<?php
/* SVN FILE: $Id$ */
/**
 * Pagination Helper class file.
 *
 * Generates pagination links
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs.view.helpers
 * @since			CakePHP(tm) v 1.2.0
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Pagination Helper class for easy generation of pagination links.
 *
 * PaginationHelper encloses all methods needed when working with pagination.
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
 * The values that may be specified are:
 * - <i>$options['sort']</i>  the key that the recordset is sorted.
 * - <i>$options['direction']</i> Direction of the sorting (default: 'asc').
 * - <i>$options['format']</i> Format of the counter. Supported formats are 'range' and 'pages'
 *                             and custom (default). In the default mode the supplied string is
 *                             parsed and constants are replaced by their actual values.
 *                             Constants: %page%, %pages%, %current%, %count%, %start%, %end% .
 * - <i>$options['separator']</i> The separator of the actual page and number of pages (default: ' of ').
 * - <i>$options['url']</i> Url of the action. See Router::url()
 * - <i>$options['model']</i> The name of the model.
 * - <i>$options['escape']</i> Defines if the title field for the link should be escaped (default: true).
 * - <i>$options['update']</i> DOM id of the element updated with the results of the AJAX call.
 *                             If this key isn't specified Paginator will use plain HTML links.
 * - <i>$options['indicator']</i> DOM id of the element that will be shown when doing AJAX requests.
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
 * @param  mixed $options Default options for pagination links. If a string is supplied - it
 *                        is used as the DOM id element to update. See #options for list of keys.
 * @return void
 */
	function options($options = array()) {
		if (is_string($options)) {
			$options = array('update' => $options);
		}
		
		if(!empty($options['paging'])) {
			if(!isset($this->params['paging'])) {
				$this->params['paging'] = array();
			}
			$this->params['paging'] = am($this->params['paging'], $options['paging']);
			unset($options['paging']);
		}
		
		$model = $this->defaultModel();
		if(!empty($options[$model])) {
			if(!isset($this->params['paging'][$model])) {
				$this->params['paging'][$model] = array();
			}
			$this->params['paging'][$model] = am($this->params['paging'][$model], $options[$model]);
			unset($options[$model]);
		}
		$this->options = array_filter(am($this->options, $options));
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
 * @param  string $model Optional model name.  Uses the default if none is specified.
 * @param  mixed $options Options for pagination links. See #options for list of keys.
 * @return string The direction by which the recordset is being sorted, or
 *                null if the results are not currently sorted.
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
 *                key the returned link will sort by 'desc'.
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
 * @param  string $title Title for the link.
 * @param  mixed $url Url for the action. See Router::url()
 * @param  array $options Options for the link. See #options for list of keys.
 * @return string A link with pagination parameters.
 */
	function link($title, $url = array(), $options = array()) {
		$options = am(array('model' => null, 'escape' => true), $options);
		$model = $options['model'];
		unset($options['model']);

		if(!empty($this->options)) {
			$options = am($this->options, $options);
		}

		$paging = $this->params($model);
		$urlOption = null;
		if(isset($options['url'])) {
			$urlOption = $options['url'];
			unset($options['url']);
		}
		$url = am(array_filter(Set::diff($paging['options'], $paging['defaults'])), $urlOption, $url);

		if (isset($url['order'])) {
			$sort = $direction = null;
			if (is_array($url['order'])) {
				list($sort, $direction) = array($this->sortKey($model, $url), current($url['order']));
			}
			unset($url['order']);
			$url = am($url, compact('sort', 'direction'));
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
 * @param  string $model Optional model name.  Uses the default if none is specified.
 * @return boolean True if the result set is not at the first page.
 */
	function hasPrev($model = null) {
		return $this->__hasPage($model, 'prev');
	}
/**
 * Returns true if the given result set is not at the last page
 *
 * @param  string $model Optional model name.  Uses the default if none is specified.
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

		$options = am(
			array(
				'model' => $this->defaultModel(),
				'format' => 'pages',
				'separator' => ' of '
			),
		$options);

		$paging = $this->params($options['model']);
		$paging['pageCount'] = ife($paging['pageCount'] == 0, 1, $paging['pageCount']);

		$start = ife($paging['count'] >= 1, ($paging['page'] - 1) * ($paging['options']['limit']) + 1, '0');
		$end = ife(($paging['count'] < ($start + $paging['options']['limit'] - 1)), $paging['count'], ($start + $paging['options']['limit'] - 1));

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
/**
 * Returns a set of numbers for the paged result set
 * uses a modulus to decide how many numbers to show on each side of the current page (defautl: 8)
 *
 * @param  mixed $options Options for the counter string. See #options for list of keys.
 * @return string numbers string.
 */
	function numbers($options = array()) { 
		$options = am(
			array(
				'before'=> null,
				'after'=> null,
				'model' => $this->defaultModel(),
				'modulus' => '8',
				'separator' => ' | '
			),
		$options);
		
		$params = $this->params($options['model']);
		unset($options['model']);
		
		if($params['pageCount'] <= 1) {
			return false;
		}
		$before = $options['before'];
		unset($options['before']);
		$after = $options['after'];
		unset($options['after']);
		
		$modulus = $options['modulus'];
		unset($options['modulus']);
		
		$separator = $options['separator'];
		unset($options['separator']);
		
		$out = $before;
		
		if($modulus && $params['pageCount'] > $modulus) {
			$half = intval($modulus / 2);
			$end = $params['page'] + $half;
			if($end > $params['pageCount']) {
				$end = $params['pageCount'];
			}
			$start = $params['page'] - ($modulus - ($end - $params['page']));
			
			if($start <= 1) {
				$start = 1;
				$end = $params['page'] + ($modulus  - $params['page']) + 1;
			}
			
			for ($i = $start; $i < $params['page']; $i++) {
				$out .= $this->link($i, am($options, array('page' => $i)));
				$out .= $separator;
			}

			$out .= $params['page'];
			$out .= $separator;
			
			$start = $params['page'] + 1;
			
			for ($i = $start; $i <= $end; $i++) {
				$out .= $this->link($i, am($options, array('page' => $i)));
				$out .= $separator;
			}
		} else {
			for ($i = 1; $i <= $params['pageCount']; $i++) {
				if($i == $params['page']) {
					$out .= $i;
				} else {
					$out .= $this->link($i, am($options, array('page' => $i)));
				}
				$out .= $separator;
			}
		}
		$out .= $after;
		return $this->output($out);
	}
}
?>