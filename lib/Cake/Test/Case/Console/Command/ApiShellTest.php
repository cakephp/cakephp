<?php
/**
 * ApiShellTest file
 *
 * PHP 5
 *
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       cake.tests.cases.console.libs.tasks
 * @since         CakePHP v 1.2.0.7726
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('ShellDispatcher', 'Console');
App::uses('Shell', 'Console');
App::uses('ApiShell', 'Console/Command');

/**
 * ApiShellTest class
 *
 * @package       cake.tests.cases.console.libs.tasks
 */
class ApiShellTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);

		$this->Shell = $this->getMock(
			'ApiShell',
			array('in', 'out', 'createFile', 'hr', '_stop'),
			array(	$out, $out, $in)
		);
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
			'3. beforeRedirect($url, $status = NULL, $exit = true)',
			'4. beforeRender()',
			'5. constructClasses()',
			'6. disableCache()',
			'7. flash($message, $url, $pause = 1, $layout = \'flash\')',
			'8. getResponse()',
			'9. header($status)',
			'10. httpCodes($code = NULL)',
			'11. invokeAction($request)',
			'12. loadModel($modelClass = NULL, $id = NULL)',
			'13. paginate($object = NULL, $scope = array (), $whitelist = array ())',
			'14. postConditions($data = array (), $op = NULL, $bool = \'AND\', $exclusive = false)',
			'15. redirect($url, $status = NULL, $exit = true)',
			'16. referer($default = NULL, $local = false)',
			'17. render($view = NULL, $layout = NULL)',
			'18. set($one, $two = NULL)',
			'19. setAction($action)',
			'20. setRequest($request)',
			'21. shutdownProcess()',
			'22. startupProcess()',
			'23. validate()',
			'24. validateErrors()'
		);
		$this->Shell->expects($this->at(2))->method('out')->with($expected);

		$this->Shell->args = array('controller');
		$this->Shell->paths['controller'] = CAKE . 'Controller' . DS;
		$this->Shell->main();
	}
}
