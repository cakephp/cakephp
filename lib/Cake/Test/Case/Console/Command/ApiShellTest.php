<?php
/**
 * ApiShellTest file
 *
 * PHP 5
 *
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.Console.Command
 * @since         CakePHP v 1.2.0.7726
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('ConsoleOutput', 'Console');
App::uses('ConsoleInput', 'Console');
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
	public function testMethodNameDetection() {
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
			'11. getEventManager()',
			'12. header($status)',
			'13. httpCodes($code = NULL)',
			'14. implementedEvents()',
			'15. invokeAction($request)',
			'16. loadModel($modelClass = NULL, $id = NULL)',
			'17. paginate($object = NULL, $scope = array (), $whitelist = array ())',
			'18. postConditions($data = array (), $op = NULL, $bool = \'AND\', $exclusive = false)',
			'19. redirect($url, $status = NULL, $exit = true)',
			'20. referer($default = NULL, $local = false)',
			'21. render($view = NULL, $layout = NULL)',
			'22. scaffoldError($method)',
			'23. set($one, $two = NULL)',
			'24. setAction($action)',
			'25. setRequest($request)',
			'26. shutdownProcess()',
			'27. startupProcess()',
			'28. validate()',
			'29. validateErrors()'
		);
		$this->Shell->expects($this->at(2))->method('out')->with($expected);

		$this->Shell->args = array('controller');
		$this->Shell->paths['controller'] = CAKE . 'Controller' . DS;
		$this->Shell->main();
	}
}
