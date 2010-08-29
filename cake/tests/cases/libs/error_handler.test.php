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
App::import('Core', array('ErrorHandler', 'Controller', 'Component'));

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
 * TestErrorHandler class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class TestErrorHandler extends ErrorHandler {

/**
 * stop method
 *
 * @access public
 * @return void
 */
	function _stop() {
		return;
	}
}

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
 * into error404 when debug == 0
 *
 * @return void
 */
	function testSubclassMethodsNotBeingConvertedToError() {
		$this->markTestIncomplete('Not implemented now');
		$back = Configure::read('debug');
		Configure::write('debug', 2);
		ob_start();
		$ErrorHandler = new MyCustomErrorHandler('missingWidgetThing', array('message' => 'doh!'));
		$result = ob_get_clean();
		$this->assertEqual($result, 'widget thing is missing');

		Configure::write('debug', 0);
		ob_start();
		$ErrorHandler = new MyCustomErrorHandler('missingWidgetThing', array('message' => 'doh!'));
		$result = ob_get_clean();
		$this->assertEqual($result, 'widget thing is missing', 'Method declared in subclass converted to error404. %s');

		Configure::write('debug', 0);
		ob_start();
		$ErrorHandler = new MyCustomErrorHandler('missingController', array(
			'className' => 'Missing', 'message' => 'Page not found'
		));
		$result = ob_get_clean();
		$this->assertPattern('/Not Found/', $result, 'Method declared in error handler not converted to error404. %s');

		Configure::write('debug', $back);
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

		ob_start();
		$ErrorHandler->render();
		$result = ob_get_clean();

		$this->assertPattern('/<h2>Custom message<\/h2>/', $result);
		$this->assertPattern("/<strong>'\/posts\/view\/1000'<\/strong>/", $result);
		
		App::build();
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
	function testError500() {
		$this->markTestIncomplete('Not implemented now');
		ob_start();
		$ErrorHandler = new ErrorHandler('error500', array(
			'message' => 'An Internal Error Has Occurred'
		));
		$result = ob_get_clean();
		$this->assertPattern('/<h2>An Internal Error Has Occurred<\/h2>/', $result);

		ob_start();
		$ErrorHandler = new ErrorHandler('error500', array(
			'message' => 'An Internal Error Has Occurred',
			'code' => '500'
		));
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
		$this->markTestIncomplete('Not implemented now');
		$this->skipIf(defined('APP_CONTROLLER_EXISTS'), '%s Need a non-existent AppController');

		ob_start();
		$ErrorHandler = new ErrorHandler('missingController', array('className' => 'PostsController'));
		$result = ob_get_clean();
		$this->assertPattern('/<h2>Missing Controller<\/h2>/', $result);
		$this->assertPattern('/<em>PostsController<\/em>/', $result);
		$this->assertPattern('/BlueberryComponent/', $result);
	}

/**
 * testMissingAction method
 *
 * @access public
 * @return void
 */
	function testMissingAction() {
		$exception = new MissingActionException('PostsController::index()');
		$ErrorHandler = new ErrorHandler($exception);

		ob_start();
		$ErrorHandler->render();
		$result = ob_get_clean();

		$this->assertPattern('/<h2>Missing Method in PostsController<\/h2>/', $result);
		$this->assertPattern('/<em>PostsController::<\/em><em>index\(\)<\/em>/', $result);

		/* TODO: Integration test that needs to be moved
		ob_start();
		$dispatcher = new Dispatcher('/blueberry/inexistent');
		$result = ob_get_clean();
		$this->assertPattern('/<h2>Missing Method in BlueberryController<\/h2>/', $result);
		$this->assertPattern('/<em>BlueberryController::<\/em><em>inexistent\(\)<\/em>/', $result);
		$this->assertNoPattern('/Location: (.*)\/users\/login/', $result);
		$this->assertNoPattern('/Stopped with status: 0/', $result);
		*/
	}

/**
 * testPrivateAction method
 *
 * @access public
 * @return void
 */
	function testPrivateAction() {
		$exception = new PrivateActionException('PostsController::_secretSauce()');
		$ErrorHandler = new ErrorHandler($exception);

		ob_start();
		$ErrorHandler->render();
		$result = ob_get_clean();

		$this->assertPattern('/<h2>Private Method in PostsController<\/h2>/', $result);
		$this->assertPattern('/<em>PostsController::<\/em><em>_secretSauce\(\)<\/em>/', $result);
	}

/**
 * testMissingTable method
 *
 * @access public
 * @return void
 */
	function testMissingTable() {
		$exception = new MissingTableException('Article', 'articles');
		$ErrorHandler = new ErrorHandler($exception);

		ob_start();
		$ErrorHandler->render();
		$result = ob_get_clean();

		$this->assertPattern('/HTTP\/1\.0 500 Internal Server Error/', $result);
		$this->assertPattern('/<h2>Missing Database Table<\/h2>/', $result);
		$this->assertPattern('/table <em>articles<\/em> for model <em>Article<\/em>/', $result);
	}

/**
 * testMissingDatabase method
 *
 * @access public
 * @return void
 */
	function testMissingDatabase() {
		$exception = new MissingDatabaseException('default');
		$ErrorHandler = new ErrorHandler($exception);

		ob_start();
		$ErrorHandler->render();
		$result = ob_get_clean();

		$this->assertPattern('/HTTP\/1\.0 500 Internal Server Error/', $result);
		$this->assertPattern('/<h2>Missing Database Connection<\/h2>/', $result);
		$this->assertPattern('/Confirm you have created the file/', $result);
	}

/**
 * testMissingView method
 *
 * @access public
 * @return void
 */
	function testMissingView() {
		$exception = new MissingViewException('/posts/about.ctp');
		$ErrorHandler = new ErrorHandler($exception);

		ob_start();
		$ErrorHandler->render();
		$result = ob_get_clean();

		$this->assertPattern("/posts\/about.ctp/", $result);
	}

/**
 * testMissingLayout method
 *
 * @access public
 * @return void
 */
	function testMissingLayout() {
		$exception = new MissingLayoutException('layouts/my_layout.ctp');
		$ErrorHandler = new ErrorHandler($exception);

		ob_start();
		$ErrorHandler->render();
		$result = ob_get_clean();

		$this->assertPattern("/Missing Layout/", $result);
		$this->assertPattern("/layouts\/my_layout.ctp/", $result);
	}

/**
 * testMissingConnection method
 *
 * @access public
 * @return void
 */
	function testMissingConnection() {
		$exception = new MissingConnectionException('Article');
		$ErrorHandler = new ErrorHandler($exception);

		ob_start();
		$ErrorHandler->render();
		$result = ob_get_clean();

		$this->assertPattern('/<h2>Missing Database Connection<\/h2>/', $result);
		$this->assertPattern('/Article requires a database connection/', $result);
	}

/**
 * testMissingHelperFile method
 *
 * @access public
 * @return void
 */
	function testMissingHelperFile() {
		$exception = new MissingHelperFileException('my_custom.php');
		$ErrorHandler = new ErrorHandler($exception);

		ob_start();
		$ErrorHandler->render();
		$result = ob_get_clean();

		$this->assertPattern('/<h2>Missing Helper File<\/h2>/', $result);
		$this->assertPattern('/Create the class below in file:/', $result);
		$this->assertPattern('/(\/|\\\)my_custom.php/', $result);
	}

/**
 * testMissingHelperClass method
 *
 * @access public
 * @return void
 */
	function testMissingHelperClass() {
		$exception = new MissingHelperClassException('MyCustomHelper');
		$ErrorHandler = new ErrorHandler($exception);

		ob_start();
		$ErrorHandler->render();
		$result = ob_get_clean();

		$this->assertPattern('/<h2>Missing Helper Class<\/h2>/', $result);
		$this->assertPattern('/The helper class <em>MyCustomHelper<\/em> can not be found or does not exist./', $result);
		$this->assertPattern('/(\/|\\\)my_custom.php/', $result);
	}

/**
 * test missingBehaviorFile method
 *
 * @access public
 * @return void
 */
	function testMissingBehaviorFile() {
		$exception = new MissingBehaviorFileException('my_custom.php');
		$ErrorHandler = new ErrorHandler($exception);

		ob_start();
		$ErrorHandler->render();
		$result = ob_get_clean();

		$this->assertPattern('/<h2>Missing Behavior File<\/h2>/', $result);
		$this->assertPattern('/Create the class below in file:/', $result);
		$this->assertPattern('/(\/|\\\)my_custom.php/', $result);
	}

/**
 * test MissingBehaviorClass method
 *
 * @access public
 * @return void
 */
	function testMissingBehaviorClass() {
		$exception = new MissingBehaviorClassException('MyCustomBehavior');
		$ErrorHandler = new ErrorHandler($exception);

		ob_start();
		$ErrorHandler->render();
		$result = ob_get_clean();

		$this->assertPattern('/The behavior class <em>MyCustomBehavior<\/em> can not be found or does not exist./', $result);
		$this->assertPattern('/(\/|\\\)my_custom.php/', $result);
	}

/**
 * testMissingComponentFile method
 *
 * @access public
 * @return void
 */
	function testMissingComponentFile() {
		$exception = new MissingComponentFileException('sidebox.php');
		$ErrorHandler = new ErrorHandler($exception);

		ob_start();
		$ErrorHandler->render();
		$result = ob_get_clean();

		$this->assertPattern('/<h2>Missing Component File<\/h2>/', $result);
		$this->assertPattern('/Create the class <em>SideboxComponent<\/em> in file:/', $result);
		$this->assertPattern('/(\/|\\\)sidebox.php/', $result);
	}

/**
 * testMissingComponentClass method
 *
 * @access public
 * @return void
 */
	function testMissingComponentClass() {
		$exception = new MissingComponentClassException('SideboxComponent');
		$ErrorHandler = new ErrorHandler($exception);

		ob_start();
		$ErrorHandler->render();
		$result = ob_get_clean();

		$this->assertPattern('/<h2>Missing Component Class<\/h2>/', $result);
		$this->assertPattern('/Create the class <em>SideboxComponent<\/em> in file:/', $result);
		$this->assertPattern('/(\/|\\\)sidebox.php/', $result);
	}

/**
 * testMissingModel method
 *
 * @access public
 * @return void
 */
	function testMissingModel() {
		$this->markTestIncomplete('Not implemented now');
		ob_start();
		$ErrorHandler = new ErrorHandler('missingModel', array('className' => 'Article', 'file' => 'article.php'));
		$result = ob_get_clean();
		$this->assertPattern('/<h2>Missing Model<\/h2>/', $result);
		$this->assertPattern('/<em>Article<\/em> could not be found./', $result);
		$this->assertPattern('/(\/|\\\)article.php/', $result);
	}
}
