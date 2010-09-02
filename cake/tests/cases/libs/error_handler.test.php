<?php
/**
 * ErrorHandlerTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

App::import('Core', array('ErrorHandler', 'Controller', 'Component'));

/**
 * Short description for class.
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class AuthBlueberryUser extends CakeTestModel {

/**
 * name property
 *
 * @var string 'AuthBlueberryUser'
 * @access public
 */
	public $name = 'AuthBlueberryUser';

/**
 * useTable property
 *
 * @var string
 * @access public
 */
	public $useTable = false;
}
if (!class_exists('AppController')) {
	/**
	 * AppController class
	 *
	 * @package       cake
	 * @subpackage    cake.tests.cases.libs
	 */
	class AppController extends Controller {
	/**
	 * components property
	 *
	 * @access public
	 * @return void
	 */
		public $components = array('Blueberry');
	/**
	 * beforeRender method
	 *
	 * @access public
	 * @return void
	 */
		function beforeRender() {
			echo $this->Blueberry->testName;
		}
	/**
	 * header method
	 *
	 * @access public
	 * @return void
	 */
		function header($header) {
			echo $header;
		}
	/**
	 * _stop method
	 *
	 * @access public
	 * @return void
	 */
		function _stop($status = 0) {
			echo 'Stopped with status: ' . $status;
		}
	}
} elseif (!defined('APP_CONTROLLER_EXISTS')){
	define('APP_CONTROLLER_EXISTS', true);
}

/**
 * BlueberryComponent class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class BlueberryComponent extends Component {

/**
 * testName property
 *
 * @access public
 * @return void
 */
	public $testName = null;

/**
 * initialize method
 *
 * @access public
 * @return void
 */
	function initialize(&$controller) {
		$this->testName = 'BlueberryComponent';
	}
}

/**
 * TestErrorController class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class TestErrorController extends AppController {

/**
 * uses property
 *
 * @var array
 * @access public
 */
	public $uses = array();

/**
 * index method
 *
 * @access public
 * @return void
 */
	function index() {
		$this->autoRender = false;
		return 'what up';
	}
}

/**
 * BlueberryController class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class BlueberryController extends AppController {

/**
 * name property
 *
 * @access public
 * @return void
 */
	public $name = 'BlueberryController';

/**
 * uses property
 *
 * @access public
 * @return void
 */
	public $uses = array();
}

/**
 * MyCustomErrorHandler class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class MyCustomErrorHandler extends ErrorHandler {

/**
 * custom error message type.
 *
 * @return void
 */
	function missingWidgetThing() {
		echo 'widget thing is missing';
	}
}
/**
 * Exception class for testing app error handlers and custom errors.
 *
 * @package cake.test.cases.libs
 */
class MissingWidgetThingException extends Error404Exception { }


/**
 * ErrorHandlerTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class ErrorHandlerTest extends CakeTestCase {

/**
 * skip method
 *
 * @access public
 * @return void
 */
	function skip() {
		$this->skipIf(PHP_SAPI === 'cli', '%s Cannot be run from console');
	}

/**
 * setup create a request object to get out of router later.
 *
 * @return void
 */
	function setUp() {
		$request = new CakeRequest(null, false);
		$request->base = '';
		Router::setRequestInfo($request);
		$this->_debug = Configure::read('debug');
	}

	function teardown() {
		Configure::write('debug', $this->_debug);
	} 

/**
 * test handleException generating a page.
 *
 * @return void
 */
	function testHandleException() {
		$error = new Error404Exception('Kaboom!');
		ob_start();
		ErrorHandler::handleException($error);
		$result = ob_get_clean();
		$this->assertPattern('/Kaboom!/', $result, 'message missing.');
	}

/**
 * test that methods declared in an ErrorHandler subclass are not converted
 * into error404 when debug > 0
 *
 * @return void
 */
	function testSubclassMethodsNotBeingConvertedToError() {
		Configure::write('debug', 2);
		
		$exception = new MissingWidgetThingException('Widget not found');
		$ErrorHandler = new MyCustomErrorHandler($exception);

		ob_start();
		$ErrorHandler->render();
		$result = ob_get_clean();

		$this->assertEqual($result, 'widget thing is missing');
	}

/**
 * test that subclass methods are not converted when debug = 0
 *
 * @return void
 */
	function testSubclassMethodsNotBeingConvertedDebug0() {
		Configure::write('debug', 0);
		$exception = new MissingWidgetThingException('Widget not found');
		$ErrorHandler = new MyCustomErrorHandler($exception);

		$this->assertEqual('missingWidgetThing', $ErrorHandler->method);

		ob_start();
		$ErrorHandler->render();
		$result = ob_get_clean();

		$this->assertEqual($result, 'widget thing is missing', 'Method declared in subclass converted to error404');
	}

/**
 * test that ErrorHandler subclasses properly convert framework errors.
 *
 * @return void
 */
	function testSubclassConvertingFrameworkErrors() {
		Configure::write('debug', 0);
		
		$exception = new MissingControllerException('PostsController');
		$ErrorHandler = new MyCustomErrorHandler($exception);
		
		$this->assertEqual('error404', $ErrorHandler->method);

		ob_start();
		$ErrorHandler->render();
		$result = ob_get_clean();

		$this->assertPattern('/Not Found/', $result, 'Method declared in error handler not converted to error404. %s');
	}

/**
 * test things in the constructor.
 *
 * @return void
 */
	function testConstruction() {
		$exception = new Error404Exception('Page not found');
		$ErrorHandler = new ErrorHandler($exception);

		$this->assertType('CakeErrorController', $ErrorHandler->controller);
		$this->assertEquals('error404', $ErrorHandler->method);
		$this->assertEquals($exception, $ErrorHandler->error);
	}

/**
 * test that method gets coerced when debug = 0
 *
 * @return void
 */
	function testErrorMethodCoercion() {
		Configure::write('debug', 0);
		$exception = new MissingActionException('Page not found');
		$ErrorHandler = new ErrorHandler($exception);

		$this->assertType('CakeErrorController', $ErrorHandler->controller);
		$this->assertEquals('error404', $ErrorHandler->method);
		$this->assertEquals($exception, $ErrorHandler->error);
	}

/**
 * test that unknown exception types are captured and converted to 500
 *
 * @return void
 */
	function testUnknownExceptionType() {
		$exception = new MissingWidgetThingException('coding fail.');
		$ErrorHandler = new ErrorHandler($exception);

		$this->assertFalse(method_exists($ErrorHandler, 'missingWidgetThing'), 'no method should exist.');
		$this->assertEquals('error500', $ErrorHandler->method, 'incorrect method coercion.');
	}

/**
 * testError method
 *
 * @access public
 * @return void
 */
	function testError() {
		$exception = new Exception('Page not found');
		$ErrorHandler = new ErrorHandler($exception);

		ob_start();
		$ErrorHandler->error($exception);
		$result = ob_get_clean();
		$this->assertPattern("/<h2>Page not found<\/h2>/", $result);
	}

/**
 * testError404 method
 *
 * @access public
 * @return void
 */
	function testError404() {
		App::build(array(
			'views' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'libs' . DS . 'view' . DS)
		), true);
		Router::reload();

		$request = new CakeRequest('posts/view/1000', false);
		Router::setRequestInfo($request);

		$exception = new Error404Exception('Custom message');
		$ErrorHandler = new ErrorHandler($exception);
		$ErrorHandler->controller->response = $this->getMock('CakeResponse', array('statusCode'));
		$ErrorHandler->controller->response->expects($this->once())->method('statusCode')->with(404);

		ob_start();
		$ErrorHandler->render();
		$result = ob_get_clean();

		$this->assertPattern('/<h2>Custom message<\/h2>/', $result);
		$this->assertPattern("/<strong>'\/posts\/view\/1000'<\/strong>/", $result);
		
		App::build();
	}

/**
 * test that error404 only modifies the messages on CakeExceptions.
 *
 * @return void
 */
	function testError404OnlyChangingCakeException() {
		Configure::write('debug', 0);

		$exception = new Error404Exception('Custom message');
		$ErrorHandler = new ErrorHandler($exception);

		ob_start();
		$ErrorHandler->render();
		$result = ob_get_clean();
		$this->assertContains('Custom message', $result);

		$exception = new MissingActionException(array('controller' => 'PostsController', 'action' => 'index'));
		$ErrorHandler = new ErrorHandler($exception);

		ob_start();
		$ErrorHandler->render();
		$result = ob_get_clean();
		$this->assertContains('Not Found', $result);
	}
/**
 * test that error404 doesn't expose XSS
 *
 * @return void
 */
	function testError404NoInjection() {
		Router::reload();

		$request = new CakeRequest('pages/<span id=333>pink</span></id><script>document.body.style.background = t=document.getElementById(333).innerHTML;window.alert(t);</script>', false);
		Router::setRequestInfo($request);

		$exception = new Error404Exception('Custom message');
		$ErrorHandler = new ErrorHandler($exception);

		ob_start();
		$ErrorHandler->render();
		$result = ob_get_clean();

		$this->assertNoPattern('#<script>document#', $result);
		$this->assertNoPattern('#alert\(t\);</script>#', $result);
	}

/**
 * testError500 method
 *
 * @access public
 * @return void
 */
	function testError500Message() {
		$exception = new Error500Exception('An Internal Error Has Occurred');
		$ErrorHandler = new ErrorHandler($exception);
		$ErrorHandler->controller->response = $this->getMock('CakeResponse', array('statusCode'));
		$ErrorHandler->controller->response->expects($this->once())->method('statusCode')->with(500);

		ob_start();
		$ErrorHandler->render();
		$result = ob_get_clean();

		$this->assertPattern('/<h2>An Internal Error Has Occurred<\/h2>/', $result);
	}

/**
 * testMissingController method
 *
 * @access public
 * @return void
 */
	function testMissingController() {
		$this->skipIf(defined('APP_CONTROLLER_EXISTS'), '%s Need a non-existent AppController');

		$exception = new MissingControllerException(array('controller' => 'PostsController'));
		$ErrorHandler = new ErrorHandler($exception);

		ob_start();
		$ErrorHandler->render();
		$result = ob_get_clean();

		$this->assertPattern('/<h2>Missing Controller<\/h2>/', $result);
		$this->assertPattern('/<em>PostsController<\/em>/', $result);
		$this->assertPattern('/BlueberryComponent/', $result);
	}


	/* TODO: Integration test that needs to be moved
	ob_start();
	$dispatcher = new Dispatcher('/blueberry/inexistent');
	$result = ob_get_clean();
	$this->assertPattern('/<h2>Missing Method in BlueberryController<\/h2>/', $result);
	$this->assertPattern('/<em>BlueberryController::<\/em><em>inexistent\(\)<\/em>/', $result);
	$this->assertNoPattern('/Location: (.*)\/users\/login/', $result);
	$this->assertNoPattern('/Stopped with status: 0/', $result);
	*/

/**
 * Returns an array of tests to run for the various CakeException classes.
 *
 * @return void
 */
	public static function testProvider() {
		return array(
			array(
				new MissingActionException(array('controller' => 'PostsController', 'action' => 'index')),
				array(
					'/<h2>Missing Method in PostsController<\/h2>/',
					'/<em>PostsController::<\/em><em>index\(\)<\/em>/'
				),
				404
			),
			array(
				new PrivateActionException(array('controller' => 'PostsController' , 'action' => '_secretSauce')),
				array(
					'/<h2>Private Method in PostsController<\/h2>/',
					'/<em>PostsController::<\/em><em>_secretSauce\(\)<\/em>/'
				),
				404
			),
			array(
				new MissingTableException(array('table' => 'articles', 'class' => 'Article')),
				array(
					'/<h2>Missing Database Table<\/h2>/',
					'/table <em>articles<\/em> for model <em>Article<\/em>/'
				),
				500
			),
			array(
				new MissingDatabaseException(array('connection' => 'default')),
				array(
					'/<h2>Missing Database Connection<\/h2>/',
					'/Confirm you have created the file/'
				),
				500
			),
			array(
				new MissingViewException(array('file' => '/posts/about.ctp')),
				array(
					"/posts\/about.ctp/"
				),
				500
			),
			array(
				new MissingLayoutException(array('file' => 'layouts/my_layout.ctp')),
				array(
					"/Missing Layout/",
					"/layouts\/my_layout.ctp/"
				),
				500
			),
			array(
				new MissingConnectionException(array('class' => 'Article')),
				array(
					'/<h2>Missing Database Connection<\/h2>/',
					'/Article requires a database connection/'
				),
				500
			),
			array(
				new MissingHelperFileException(array('file' => 'my_custom.php', 'class' => 'MyCustomHelper')),
				array(
					'/<h2>Missing Helper File<\/h2>/',
					'/Create the class below in file:/',
					'/(\/|\\\)my_custom.php/'
				),
				500
			),
			array(
				new MissingHelperClassException(array('file' => 'my_custom.php', 'class' => 'MyCustomHelper')),
				array(
					'/<h2>Missing Helper Class<\/h2>/',
					'/The helper class <em>MyCustomHelper<\/em> can not be found or does not exist./',
					'/(\/|\\\)my_custom.php/',
				),
				500
			),
			array(
				new MissingBehaviorFileException(array('file' => 'my_custom.php', 'class' => 'MyCustomBehavior')),
				array(
					'/<h2>Missing Behavior File<\/h2>/',
					'/Create the class below in file:/',
					'/(\/|\\\)my_custom.php/',
				),
				500
			),
			array(
				new MissingBehaviorClassException(array('file' => 'my_custom.php', 'class' => 'MyCustomBehavior')),
				array(
					'/The behavior class <em>MyCustomBehavior<\/em> can not be found or does not exist./',
					'/(\/|\\\)my_custom.php/'
				),
				500
			),
			array(
				new MissingComponentFileException(array('file' => 'sidebox.php', 'class' => 'SideboxComponent')),
				array(
					'/<h2>Missing Component File<\/h2>/',
					'/Create the class <em>SideboxComponent<\/em> in file:/',
					'/(\/|\\\)sidebox.php/'
				),
				500
			),
			array(
				new MissingComponentClassException(array('file' => 'sidebox.php', 'class' => 'SideboxComponent')),
				array(
					'/<h2>Missing Component Class<\/h2>/',
					'/Create the class <em>SideboxComponent<\/em> in file:/',
					'/(\/|\\\)sidebox.php/'
				),
				500
			)
			
		);
	}

/**
 * Test the various CakeException sub classes
 *
 * @dataProvider testProvider
 * @return void
 */
	function testCakeExceptionHandling($exception, $patterns, $code) {
		$ErrorHandler = new ErrorHandler($exception);
		$ErrorHandler->controller->response = $this->getMock('CakeResponse', array('statusCode'));
		$ErrorHandler->controller->response->expects($this->once())
			->method('statusCode')
			->with($code);

		ob_start();
		$ErrorHandler->render();
		$result = ob_get_clean();

		foreach ($patterns as $pattern) {
			$this->assertPattern($pattern, $result);
		}
	}
}
