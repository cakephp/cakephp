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
 * Provides debug information on the SQL logs and provides links to an ajax explain interface.
 *
 */
class SqlLogPanel extends DebugPanel {

/**
 * Minimum number of Rows Per Millisecond that must be returned by a query before an explain
 * is done.
 *
 * @var integer
 */
	public $slowRate = 20;

/**
 * Gets the connection names that should have logs + dumps generated.
 *
 * @param \Controller|string $controller
 * @return array
 */
	public function beforeRender(Controller $controller) {
		if (!class_exists('ConnectionManager')) {
			return array();
		}
		$connections = array();

		$dbConfigs = ConnectionManager::sourceList();
		foreach ($dbConfigs as $configName) {
			$driver = null;
			$db = ConnectionManager::getDataSource($configName);
			if (
				(empty($db->config['driver']) && empty($db->config['datasource'])) ||
				!method_exists($db, 'getLog')
			) {
				continue;
			}
			if (isset($db->config['datasource'])) {
				$driver = $db->config['datasource'];
			}
			$explain = false;
			$isExplainable = (preg_match('/(Mysql|Postgres)$/', $driver));
			if ($isExplainable) {
				$explain = true;
			}
			$connections[$configName] = $explain;
		}
		return array('connections' => $connections, 'threshold' => $this->slowRate);
	}
}
