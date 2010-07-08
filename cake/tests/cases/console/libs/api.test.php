<?php
/**
 * ApiShellTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright 2006-2010, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2010, Cake Software Foundation, Inc.
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

/**
 * ApiShellTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 */
class ApiShellTest extends CakeTestCase {

/**
 * startTest method
 *
 * @return void
 */
	public function startTest() {
		$this->Dispatcher = $this->getMock(
			'ShellDispatcher', 
			array('getInput', 'stdout', 'stderr', '_stop', '_initEnvironment', 'dispatch')
		);
		$this->Shell = $this->getMock(
			'ApiShell',
			array('in', 'out', 'createFile', 'hr', '_stop'),
			array(&$this->Dispatcher)
		);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function endTest() {
		ClassRegistry::flush();
	}

/**
 * Test that method names are detected properly including those with no arguments.
 *
 * @return void
 */
	public function testMethodNameDetection () {
		$this->Shell->expects($this->any())->method('in')->will($this->returnValue('q'));
		$this->Shell->expects($this->at(0))->method('out')->with('Controller');

		$expected = array(
			'1. afterFilter()',
			'2. beforeFilter()',
			'3. beforeRender()',
			'4. constructClasses()',
			'5. disableCache()',
			'6. flash($message, $url, $pause = 1, $layout = \'flash\')',
			'7. header($status)',
			'8. httpCodes($code = NULL)',
			'9. isAuthorized()',
			'10. loadModel($modelClass = NULL, $id = NULL)',
			'11. paginate($object = NULL, $scope = array (), $whitelist = array ())',
			'12. postConditions($data = array (), $op = NULL, $bool = \'AND\', $exclusive = false)',
			'13. redirect($url, $status = NULL, $exit = true)',
			'14. referer($default = NULL, $local = false)',
			'15. render($action = NULL, $layout = NULL, $file = NULL)',
			'16. set($one, $two = NULL)',
			'17. setAction($action)',
			'18. shutdownProcess()',
			'19. startupProcess()',
			'20. validate()',
			'21. validateErrors()'
		);
		$this->Shell->expects($this->at(2))->method('out')->with($expected);

		$this->Shell->args = array('controller');
		$this->Shell->paths['controller'] = LIBS . 'controller' . DS;
		$this->Shell->main();
	}
}
