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
 * @since         2.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Routing\Filter;

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Routing\DispatcherFilter;
use Cake\Utility\Inflector;
use Cake\View\View;

/**
 * This filter will check whether the response was previously cached in the file system
 * and served it back to the client if appropriate.
 *
 */
class CacheDispatcher extends DispatcherFilter {

/**
 * Default priority for all methods in this filter
 * This filter should run before the request gets parsed by router
 *
 * @var int
 */
	public $priority = 9;

/**
 * Checks whether the response was cached and set the body accordingly.
 *
 * @param \Cake\Event\Event $event containing the request and response object
 * @return \Cake\Network\Response with cached content if found, null otherwise
 */
	public function beforeDispatch(Event $event) {
		if (Configure::read('Cache.check') !== true) {
			return;
		}

		$path = $event->data['request']->here();
		if ($path === '/') {
			$path = 'home';
		}
		$prefix = Configure::read('Cache.viewPrefix');
		if ($prefix) {
			$path = $prefix . '_' . $path;
		}
		$path = strtolower(Inflector::slug($path));

		$filename = CACHE . 'views/' . $path . '.php';

		if (!file_exists($filename)) {
			$filename = CACHE . 'views/' . $path . '_index.php';
		}
		if (file_exists($filename)) {
			$controller = null;
			$view = new View($controller);
			$view->response = $event->data['response'];
			$result = $view->renderCache($filename, microtime(true));
			if ($result !== false) {
				$event->stopPropagation();
				$event->data['response']->body($result);
				return $event->data['response'];
			}
		}
	}

}
