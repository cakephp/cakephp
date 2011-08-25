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
 * @package       Cake.Test.Case.Console.Command
 * @since         CakePHP v 1.2.0.7726
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('ShellDispatcher', 'Console');
App::uses('Shell', 'Console');
App::uses('ApiShell', 'Console/Command');

/**
 * ApiShellTest class
 *
 * @package       Cake.Test.Case.Console.Command
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
			'2. afterScaffoldSave($method)',
			'3. afterScaffoldSaveError($method)',
			'4. beforeFilter()',
			'5. beforeRedirect($url, $status = NULL, $exit = true)',
			'6. beforeRender()',
			'7. beforeScaffold($method)',
			'8. constructClasses()',
			'9. disableCache()',
			'10. flash($message, $url, $pause = 1, $layout = \'flash\')',
			'11. header($status)',
			'12. httpCodes($code = NULL)',
			'13. invokeAction($request)',
			'14. loadModel($modelClass = NULL, $id = NULL)',
			'15. paginate($object = NULL, $scope = array (), $whitelist = array ())',
			'16. postConditions($data = array (), $op = NULL, $bool = \'AND\', $exclusive = false)',
			'17. redirect($url, $status = NULL, $exit = true)',
			'18. referer($default = NULL, $local = false)',
			'19. render($view = NULL, $layout = NULL)',
			'20. scaffoldError($method)',
			'21. set($one, $two = NULL)',
			'22. setAction($action)',
			'23. setRequest($request)',
			'24. shutdownProcess()',
			'25. startupProcess()',
			'26. validate()',
			'27. validateErrors()'
		);
		$this->Shell->expects($this->at(2))->method('out')->with($expected);

		$this->Shell->args = array('controller');
		$this->Shell->paths['controller'] = CAKE . 'Controller' . DS;
		$this->Shell->main();
	}
}
