<?php
/**
 * ApiShellTest file
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
			array($out, $out, $in)
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
			'12. getResponseClass()',
			'13. header($status)',
			'14. httpCodes($code = NULL)',
			'15. implementedEvents()',
			'16. invokeAction($request)',
			'17. loadModel($modelClass = NULL, $id = NULL)',
			'18. paginate($object = NULL, $scope = array (), $whitelist = array ())',
			'19. postConditions($data = array (), $op = NULL, $bool = \'AND\', $exclusive = false)',
			'20. redirect($url, $status = NULL, $exit = true)',
			'21. referer($default = NULL, $local = false)',
			'22. render($view = NULL, $layout = NULL)',
			'23. scaffoldError($method)',
			'24. set($one, $two = NULL)',
			'25. setAction($action)',
			'26. setRequest($request)',
			'27. shutdownProcess()',
			'28. startupProcess()',
			'29. validate()',
			'30. validateErrors()'
		);
		$this->Shell->expects($this->at(2))->method('out')->with($expected);

		$this->Shell->args = array('controller');
		$this->Shell->paths['controller'] = CAKE . 'Controller' . DS;
		$this->Shell->main();
	}
}
