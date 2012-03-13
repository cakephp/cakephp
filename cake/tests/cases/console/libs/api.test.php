<?php
/**
 * ApiShellTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 * @since         CakePHP v 1.2.0.7726
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Shell', 'Shell', false);

if (!defined('DISABLE_AUTO_DISPATCH')) {
	define('DISABLE_AUTO_DISPATCH', true);
}

if (!class_exists('ShellDispatcher')) {
	ob_start();
	$argv = false;
	require CAKE . 'console' .  DS . 'cake.php';
	ob_end_clean();
}

if (!class_exists('ApiShell')) {
	require CAKE . 'console' .  DS . 'libs' . DS . 'api.php';
}

Mock::generatePartial(
	'ShellDispatcher', 'ApiShellMockShellDispatcher',
	array('getInput', 'stdout', 'stderr', '_stop', '_initEnvironment')
);
Mock::generatePartial(
	'ApiShell', 'MockApiShell',
	array('in', 'out', 'createFile', 'hr', '_stop')
);

/**
 * ApiShellTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 */
class ApiShellTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 * @access public
 */
	function startTest() {
		$this->Dispatcher =& new ApiShellMockShellDispatcher();
		$this->Shell =& new MockApiShell($this->Dispatcher);
		$this->Shell->Dispatch =& $this->Dispatcher;
	}

/**
 * tearDown method
 *
 * @return void
 * @access public
 */
	function endTest() {
		ClassRegistry::flush();
	}

/**
 * Test that method names are detected properly including those with no arguments.
 *
 * @return void
 * @access public
 */
	function testMethodNameDetection () {
		$this->Shell->setReturnValueAt(0, 'in', 'q');
		$this->Shell->expectAt(0, 'out', array('Controller'));
		$expected = array(
			array(
				'1. afterFilter()',
				'2. beforeFilter()',
				'3. beforeRender()',
				'4. constructClasses()',
				'5. disableCache()',
				'6. flash($message, $url, $pause = 1, $layout = \'flash\')',
				'7. header($status)',
				'8. httpCodes($code = null)',
				'9. isAuthorized()',
				'10. loadModel($modelClass = null, $id = null)',
				'11. paginate($object = null, $scope = array(), $whitelist = array())',
				'12. postConditions($data = array(), $op = null, $bool = \'AND\', $exclusive = false)',
				'13. redirect($url, $status = null, $exit = true)',
				'14. referer($default = null, $local = false)',
				'15. render($action = null, $layout = null, $file = null)',
				'16. set($one, $two = null)',
				'17. setAction($action)',
				'18. shutdownProcess()', 
				'19. startupProcess()',
				'20. validate()',
				'21. validateErrors()'
			)
		);
		$this->Shell->expectAt(1, 'out', $expected);

		$this->Shell->args = array('controller');
		$this->Shell->paths['controller'] = CAKE_CORE_INCLUDE_PATH . DS . LIBS . 'controller' . DS;
		$this->Shell->main();
	}
}
