<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         DebugKit 0.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('DebugKitDebugger', 'DebugKit.Lib');
App::uses('AppHelper', 'View/Helper');
App::uses('ConnectionManager', 'Model');

/**
 * Provides Base methods for content specific debug toolbar helpers.
 * Acts as a facade for other toolbars helpers as well.
 *
 * @since         DebugKit 0.1
 */
class ToolbarHelper extends AppHelper {

/**
 * settings property to be overloaded. Subclasses should specify a format
 *
 * @var array
 */
	public $settings = array();

/**
 * flag for whether or not cache is enabled.
 *
 * @var boolean
 */
	protected $_cacheEnabled = false;

/**
 * Construct the helper and make the backend helper.
 *
 * @param $View
 * @param array|string $options
 * @return \ToolbarHelper
 */
	public function __construct($View, $options = array()) {
		$this->_myName = strtolower(get_class($this));
		$this->settings = array_merge($this->settings, $options);

		if ($this->_myName !== 'toolbarhelper') {
			parent::__construct($View, $options);
			return;
		}

		if (!isset($options['output'])) {
			$options['output'] = 'DebugKit.HtmlToolbar';
		}
		$className = $options['output'];
		if (strpos($options['output'], '.') !== false) {
			list($plugin, $className) = explode('.', $options['output']);
		}
		$this->_backEndClassName = $className;
		$this->helpers[$options['output']] = $options;
		if (isset($options['cacheKey']) && isset($options['cacheConfig'])) {
			$this->_cacheKey = $options['cacheKey'];
			$this->_cacheConfig = $options['cacheConfig'];
			$this->_cacheEnabled = true;
		}

		parent::__construct($View, $options);
	}

/**
 * afterLayout callback
 *
 * @param string $layoutFile
 * @return void
 */
	public function afterLayout($layoutFile) {
		if (!$this->request->is('requested')) {
			$this->send();
		}
	}

/**
 * Get the name of the backend Helper
 * used to conditionally trigger toolbar output
 *
 * @return string
 */
	public function getName() {
		return $this->_backEndClassName;
	}

/**
 * call__
 *
 * Allows method calls on backend helper
 *
 * @param string $method
 * @param mixed $params
 * @return mixed|void
 */
	public function __call($method, $params) {
		if (method_exists($this->{$this->_backEndClassName}, $method)) {
			return $this->{$this->_backEndClassName}->dispatchMethod($method, $params);
		}
	}

/**
 * Allows for writing to panel cache from view.
 * Some panels generate all variables in the view by
 * necessity ie. Timer. Using this method, will allow you to replace in full
 * the content for a panel.
 *
 * @param string $name Name of the panel you are replacing.
 * @param string $content Content to write to the panel.
 * @return boolean Success of write.
 */
	public function writeCache($name, $content) {
		if (!$this->_cacheEnabled) {
			return false;
		}
		$existing = (array)Cache::read($this->_cacheKey, $this->_cacheConfig);
		$existing[0][$name]['content'] = $content;
		return Cache::write($this->_cacheKey, $existing, $this->_cacheConfig);
	}

/**
 * Read the toolbar
 *
 * @param string $name Name of the panel you want cached data for
 * @param integer $index
 * @return mixed Boolean false on failure, array of data otherwise.
 */
	public function readCache($name, $index = 0) {
		if (!$this->_cacheEnabled) {
			return false;
		}
		$existing = (array)Cache::read($this->_cacheKey, $this->_cacheConfig);
		if (!isset($existing[$index][$name]['content'])) {
			return false;
		}
		return $existing[$index][$name]['content'];
	}

/**
 * Gets the query logs for the given connection names.
 *
 * ### Options
 *
 * - explain - Whether explain links should be generated for this connection.
 * - cache - Whether the toolbar_state Cache should be updated.
 * - threshold - The threshold at which a visual 'maybe slow' flag should be added.
 *   results with rows/ms lower than $threshold will be marked.
 *
 * @param string $connection Connection name to get logs for.
 * @param array $options Options for the query log retrieval.
 * @return array Array of data to be converted into a table.
 */
	public function getQueryLogs($connection, $options = array()) {
		$options += array('explain' => false, 'cache' => true, 'threshold' => 20);
		$db = ConnectionManager::getDataSource($connection);

		if (!method_exists($db, 'getLog')) {
			return array();
		}

		$log = $db->getLog();

		$out = array(
			'queries' => array(),
			'count' => $log['count'],
			'time' => $log['time']
		);
		foreach ($log['log'] as $i => $query) {
			$isSlow = (
				$query['took'] > 0 &&
				$query['numRows'] / $query['took'] != 1 &&
				$query['numRows'] / $query['took'] <= $options['threshold']
			);
			$query['actions'] = '';
			$isHtml = ($this->getName() === 'HtmlToolbar');
			if ($isSlow && $isHtml) {
				$query['actions'] = sprintf(
					'<span class="slow-query">%s</span>',
					__d('debug_kit', 'maybe slow')
				);
			} elseif ($isSlow) {
				$query['actions'] = '*';
			}
			if ($options['explain'] && $isHtml) {
				$query['actions'] .= $this->explainLink($query['query'], $connection);
			}
			if ($isHtml) {
				$query['query'] = h($query['query']);
				if (!empty($query['params']) && is_array($query['params'])) {
					$bindParam = $bindType = null;
					if (preg_match('/.+ :.+/', $query['query'])) {
						$bindType = true;
					}
					foreach ($query['params'] as $bindKey => $bindVal) {
						if ($bindType === true) {
							$bindParam .= h($bindKey) . " => " . h($bindVal) . ", ";
						} else {
							$bindParam .= h($bindVal) . ", ";
						}
					}
					$query['query'] .= " [ " . rtrim($bindParam, ', ') . " ]";
				}
			}
			unset($query['params']);
			$out['queries'][] = $query;
		}
		if ($options['cache']) {
			$existing = $this->readCache('sql_log');
			$existing[$connection] = $out;
			$this->writeCache('sql_log', $existing);
		}
		return $out;
	}

}
