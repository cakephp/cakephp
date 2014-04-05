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
 * @since         DebugKit 1.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Security', 'Utility');
App::uses('DebugKitAppController', 'DebugKit.Controller');

/**
 * DebugKit ToolbarAccess Controller
 *
 * Allows retrieval of information from the debugKit internals.
 *
 * @since         DebugKit 1.1
 */
class ToolbarAccessController extends DebugKitAppController {

/**
 * name
 *
 * @var string
 */
	public $name = 'ToolbarAccess';

/**
 * Helpers
 *
 * @var array
 */
	public $helpers = array(
		'DebugKit.Toolbar' => array('output' => 'DebugKit.HtmlToolbar'),
		'Js', 'Number', 'DebugKit.SimpleGraph'
	);

/**
 * Components
 *
 * @var array
 */
	public $components = array('RequestHandler', 'DebugKit.Toolbar');

/**
 * Uses
 *
 * @var array
 */
	public $uses = array('DebugKit.ToolbarAccess');

/**
 * beforeFilter callback
 *
 * @return void
 */
	public function beforeFilter() {
		parent::beforeFilter();
		if (isset($this->Toolbar)) {
			$this->Components->disable('Toolbar');
		}
		$this->helpers['DebugKit.Toolbar']['cacheKey'] = $this->Toolbar->cacheKey;
		$this->helpers['DebugKit.Toolbar']['cacheConfig'] = 'debug_kit';

		if (isset($this->Auth) && method_exists($this->Auth, 'mapActions')) {
			$this->Auth->mapActions(array(
				'read' => array('history_state', 'sql_explain')
			));
		}
	}

/**
 * Get a stored history state from the toolbar cache.
 *
 * @param null $key
 * @return void
 */
	public function history_state($key = null) {
		if (Configure::read('debug') == 0) {
			return $this->redirect($this->referer());
		}
		$oldState = $this->Toolbar->loadState($key);
		$this->set('toolbarState', $oldState);
		$this->set('debugKitInHistoryMode', true);
	}

/**
 * Run SQL explain/profiling on queries. Checks the hash + the hashed queries,
 * if there is mismatch a 404 will be rendered. If debug == 0 a 404 will also be
 * rendered. No explain will be run if a 404 is made.
 *
 * @throws BadRequestException
 * @return void
 */
	public function sql_explain() {
		if (
			!$this->request->is('post') ||
			empty($this->request->data['log']['sql']) ||
			empty($this->request->data['log']['ds']) ||
			empty($this->request->data['log']['hash']) ||
			Configure::read('debug') == 0
		) {
			throw new BadRequestException('Invalid parameters');
		}
		$hash = Security::hash($this->request->data['log']['sql'] . $this->request->data['log']['ds'], 'sha1', true);
		if ($hash !== $this->request->data['log']['hash']) {
			throw new BadRequestException('Invalid parameters');
		}
		$result = $this->ToolbarAccess->explainQuery($this->request->data['log']['ds'], $this->request->data['log']['sql']);
		$this->set(compact('result'));
	}

}
