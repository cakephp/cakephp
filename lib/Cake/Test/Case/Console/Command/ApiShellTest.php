<?php
/**
 * ApiShellTest file
 *
 * PHP 5
 *
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright 2006-2010, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2010, Cake Software Foundation, Inc.
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
			'11. loadModel($modelClass = NULL, $id = NULL)',
			'12. paginate($object = NULL, $scope = array (), $whitelist = array ())',
			'13. postConditions($data = array (), $op = NULL, $bool = \'AND\', $exclusive = false)',
			'14. redirect($url, $status = NULL, $exit = true)',
			'15. referer($default = NULL, $local = false)',
			'16. render($view = NULL, $layout = NULL)',
			'17. set($one, $two = NULL)',
			'18. setAction($action)',
			'19. setRequest($request)',
			'20. shutdownProcess()',
			'21. startupProcess()',
			'22. validate()',
			'23. validateErrors()'
		);
		$this->Shell->expects($this->at(2))->method('out')->with($expected);

		$this->Shell->args = array('controller');
		$this->Shell->paths['controller'] = CAKE . 'Controller' . DS;
		$this->Shell->main();
	}
}
