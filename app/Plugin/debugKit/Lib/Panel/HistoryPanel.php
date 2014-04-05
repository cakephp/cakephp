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
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('DebugPanel', 'DebugKit.Lib');

/**
 * Provides debug information on previous requests.
 *
 */
class HistoryPanel extends DebugPanel {

/**
 * Number of history elements to keep
 *
 * @var string
 */
	public $history = 5;

/**
 * Constructor
 *
 * @param array $settings Array of settings.
 * @return \HistoryPanel
 */
	public function __construct($settings) {
		if (isset($settings['history'])) {
			$this->history = $settings['history'];
		}
	}

/**
 * beforeRender callback function
 *
 * @param Controller $controller
 * @return array contents for panel
 */
	public function beforeRender(Controller $controller) {
		$cacheKey = $controller->Toolbar->cacheKey;
		$toolbarHistory = Cache::read($cacheKey, 'debug_kit');
		$historyStates = array();
		if (is_array($toolbarHistory) && !empty($toolbarHistory)) {
			$prefix = array();
			if (!empty($controller->request->params['prefix'])) {
				$prefix[$controller->request->params['prefix']] = false;
			}
			foreach ($toolbarHistory as $i => $state) {
				if (!isset($state['request']['content']['url'])) {
					continue;
				}
				$title = $state['request']['content']['url'];
				$query = @$state['request']['content']['query'];
				if (isset($query['url'])) {
					unset($query['url']);
				}
				if (!empty($query)) {
					$title .= '?' . urldecode(http_build_query($query));
				}
				$historyStates[] = array(
					'title' => $title,
					'url' => array_merge($prefix, array(
						'plugin' => 'debug_kit',
						'controller' => 'toolbar_access',
						'action' => 'history_state',
						$i + 1))
				);
			}
		}
		if (count($historyStates) >= $this->history) {
			array_pop($historyStates);
		}
		return $historyStates;
	}
}
