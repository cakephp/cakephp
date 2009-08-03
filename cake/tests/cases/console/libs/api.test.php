<?php
/* SVN FILE: $Id$ */
/**
 * ApiShellTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2006-2008, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2006-2008, Cake Software Foundation, Inc.
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 * @since         CakePHP v 1.2.0.7726
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Core', 'Shell');

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
 * @access public
 * @return void
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
				'6. flash($message, $url, $pause = 1)',
				'7. header($status)',
				'8. isAuthorized()',
				'9. loadModel($modelClass = null, $id = null)',
				'10. paginate($object = null, $scope = array(), $whitelist = array())',
				'11. postConditions($data = array(), $op = null, $bool = \'AND\', $exclusive = false)',
				'12. redirect($url, $status = null, $exit = true)',
				'13. referer($default = null, $local = false)',
				'14. render($action = null, $layout = null, $file = null)',
				'15. set($one, $two = null)',
				'16. setAction($action)',
				'17. validate()',
				'18. validateErrors()'
			)
		);
		$this->Shell->expectAt(1, 'out', $expected);

		$this->Shell->args = array('controller');
		$this->Shell->paths['controller'] = CAKE_CORE_INCLUDE_PATH . DS . LIBS . 'controller' . DS;
		$this->Shell->main();
	}
}
?>