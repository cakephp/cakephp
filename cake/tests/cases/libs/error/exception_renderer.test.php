<?php
/**
 * ExceptionRendererTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.cases.libs
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::import('Core', array('ExceptionRenderer', 'Controller', 'Component'));

/**
 * Short description for class.
 *
 * @package       cake.tests.cases.libs
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

/**
 * BlueberryComponent class
 *
 * @package       cake.tests.cases.libs
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
	function initialize($controller) {
		$this->testName = 'BlueberryComponent';
	}
}

/**
 * TestErrorController class
 *
 * @package       cake.tests.cases.libs
 */
class TestErrorController extends Controller {

/**
 * uses property
 *
 * @var array
 * @access public
 */
	public $uses = array();

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
 * MyCustomExceptionRenderer class
 *
 * @package       cake.tests.cases.libs
 */
class MyCustomExceptionRenderer extends ExceptionRenderer {

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
class MissingWidgetThingException extends NotFoundException { }


/**
 * ExceptionRendererTest class
 *
 * @package       cake.tests.cases.libs
 */
class ExceptionRendererTest extends CakeTestCase {

	var $_restoreError = false;
/**
 * setup create a request object to get out of router later.
 *
 * @return void
 */
	function setUp() {
		App::build(array(
			'views' => array(
				TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS,
				TEST_CAKE_CORE_INCLUDE_PATH . 'libs' . DS . 'view' . DS
			)
		), true);
		Router::reload();

		$request = new CakeRequest(null, false);
		$request->base = '';
		Router::setRequestInfo($request);
		$this->_debug = Configure::read('debug');
		$this->_error = Configure::read('Error');
		Configure::write('debug', 2);
	}

/**
 * teardown
 *
 * @return void
 */
	function teardown() {
		Configure::write('debug', $this->_debug);
		Configure::write('Error', $this->_error);
		App::build();
		if ($this->_restoreError) {
			restore_error_handler();
		}
	}

/**
 * Mocks out the response on the ExceptionRenderer object so headers aren't modified.
 *
 * @return void
 */
	protected function _mockResponse($error) {
		$error->controller->response = $this->getMock('CakeResponse', array('_sendHeader'));
		return $error;
	}

/**
 * test that methods declared in an ExceptionRenderer subclass are not converted
 * into error400 when debug > 0
 *
 * @return void
 */
	function testSubclassMethodsNotBeingConvertedToError() {
		Configure::write('debug', 2);
		
		$exception = new MissingWidgetThingException('Widget not found');
		$ExceptionRenderer = $this->_mockResponse(new MyCustomExceptionRenderer($exception));

		ob_start();
		$ExceptionRenderer->render();
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
		$ExceptionRenderer = $this->_mockResponse(new MyCustomExceptionRenderer($exception));

		$this->assertEqual('missingWidgetThing', $ExceptionRenderer->method);

		ob_start();
		$ExceptionRenderer->render();
		$result = ob_get_clean();

		$this->assertEqual($result, 'widget thing is missing', 'Method declared in subclass converted to error400');
	}

/**
 * test that ExceptionRenderer subclasses properly convert framework errors.
 *
 * @return void
 */
	function testSubclassConvertingFrameworkErrors() {
		Configure::write('debug', 0);
		
		$exception = new MissingControllerException('PostsController');
		$ExceptionRenderer = $this->_mockResponse(new MyCustomExceptionRenderer($exception));
		
		$this->assertEqual('error400', $ExceptionRenderer->method);

		ob_start();
		$ExceptionRenderer->render();
		$result = ob_get_clean();

		$this->assertPattern('/Not Found/', $result, 'Method declared in error handler not converted to error400. %s');
	}

/**
 * test things in the constructor.
 *
 * @return void
 */
	function testConstruction() {
		$exception = new NotFoundException('Page not found');
		$ExceptionRenderer = new ExceptionRenderer($exception);

		$this->assertInstanceOf('CakeErrorController', $ExceptionRenderer->controller);
		$this->assertEquals('error400', $ExceptionRenderer->method);
		$this->assertEquals($exception, $ExceptionRenderer->error);
	}

/**
 * test that method gets coerced when debug = 0
 *
 * @return void
 */
	function testErrorMethodCoercion() {
		Configure::write('debug', 0);
		$exception = new MissingActionException('Page not found');
		$ExceptionRenderer = new ExceptionRenderer($exception);

		$this->assertInstanceOf('CakeErrorController', $ExceptionRenderer->controller);
		$this->assertEquals('error400', $ExceptionRenderer->method);
		$this->assertEquals($exception, $ExceptionRenderer->error);
	}

/**
 * test that unknown exception types with valid status codes are treated correctly.
 *
 * @return void
 */
	function testUnknownExceptionTypeWithExceptionThatHasA400Code() {
		$exception = new MissingWidgetThingException('coding fail.');
		$ExceptionRenderer = new ExceptionRenderer($exception);
		$ExceptionRenderer->controller->response = $this->getMock('CakeResponse', array('statusCode', '_sendHeader'));
		$ExceptionRenderer->controller->response->expects($this->once())->method('statusCode')->with(404);

		ob_start();
		$ExceptionRenderer->render();
		$results = ob_get_clean();

		$this->assertFalse(method_exists($ExceptionRenderer, 'missingWidgetThing'), 'no method should exist.');
		$this->assertEquals('error400', $ExceptionRenderer->method, 'incorrect method coercion.');
	}

/**
 * test that unknown exception types with valid status codes are treated correctly.
 *
 * @return void
 */
	function testUnknownExceptionTypeWithNoCodeIsA500() {
		$exception = new OutOfBoundsException('foul ball.');
		$ExceptionRenderer = new ExceptionRenderer($exception);
		$ExceptionRenderer->controller->response = $this->getMock('CakeResponse', array('statusCode', '_sendHeader'));
		$ExceptionRenderer->controller->response->expects($this->once())->method('statusCode')->with(500);

		ob_start();
		$ExceptionRenderer->render();
		$results = ob_get_clean();

		$this->assertEquals('error500', $ExceptionRenderer->method, 'incorrect method coercion.');
	}

/**
 * test that unknown exception types with valid status codes are treated correctly.
 *
 * @return void
 */
	function testUnknownExceptionTypeWithCodeHigherThan500() {
		$exception = new OutOfBoundsException('foul ball.', 501);
		$ExceptionRenderer = new ExceptionRenderer($exception);
		$ExceptionRenderer->controller->response = $this->getMock('CakeResponse', array('statusCode', '_sendHeader'));
		$ExceptionRenderer->controller->response->expects($this->once())->method('statusCode')->with(501);

		ob_start();
		$ExceptionRenderer->render();
		$results = ob_get_clean();

		$this->assertEquals('error500', $ExceptionRenderer->method, 'incorrect method coercion.');
	}

/**
 * testerror400 method
 *
 * @access public
 * @return void
 */
	function testError400() {
		Router::reload();

		$request = new CakeRequest('posts/view/1000', false);
		Router::setRequestInfo($request);

		$exception = new NotFoundException('Custom message');
		$ExceptionRenderer = new ExceptionRenderer($exception);
		$ExceptionRenderer->controller->response = $this->getMock('CakeResponse', array('statusCode', '_sendHeader'));
		$ExceptionRenderer->controller->response->expects($this->once())->method('statusCode')->with(404);

		ob_start();
		$ExceptionRenderer->render();
		$result = ob_get_clean();

		$this->assertPattern('/<h2>Custom message<\/h2>/', $result);
		$this->assertPattern("/<strong>'\/posts\/view\/1000'<\/strong>/", $result);
	}

/**
 * test that error400 only modifies the messages on CakeExceptions.
 *
 * @return void
 */
	function testerror400OnlyChangingCakeException() {
		Configure::write('debug', 0);

		$exception = new NotFoundException('Custom message');
		$ExceptionRenderer = $this->_mockResponse(new ExceptionRenderer($exception));

		ob_start();
		$ExceptionRenderer->render();
		$result = ob_get_clean();
		$this->assertContains('Custom message', $result);

		$exception = new MissingActionException(array('controller' => 'PostsController', 'action' => 'index'));
		$ExceptionRenderer = $this->_mockResponse(new ExceptionRenderer($exception));

		ob_start();
		$ExceptionRenderer->render();
		$result = ob_get_clean();
		$this->assertContains('Not Found', $result);
	}
/**
 * test that error400 doesn't expose XSS
 *
 * @return void
 */
	function testError400NoInjection() {
		Router::reload();

		$request = new CakeRequest('pages/<span id=333>pink</span></id><script>document.body.style.background = t=document.getElementById(333).innerHTML;window.alert(t);</script>', false);
		Router::setRequestInfo($request);

		$exception = new NotFoundException('Custom message');
		$ExceptionRenderer = $this->_mockResponse(new ExceptionRenderer($exception));

		ob_start();
		$ExceptionRenderer->render();
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
		$exception = new InternalErrorException('An Internal Error Has Occurred');
		$ExceptionRenderer = new ExceptionRenderer($exception);
		$ExceptionRenderer->controller->response = $this->getMock('CakeResponse', array('statusCode', '_sendHeader'));
		$ExceptionRenderer->controller->response->expects($this->once())->method('statusCode')->with(500);

		ob_start();
		$ExceptionRenderer->render();
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
		$exception = new MissingControllerException(array('controller' => 'PostsController'));
		$ExceptionRenderer = $this->_mockResponse(new ExceptionRenderer($exception));

		ob_start();
		$ExceptionRenderer->render();
		$result = ob_get_clean();

		$this->assertPattern('/<h2>Missing Controller<\/h2>/', $result);
		$this->assertPattern('/<em>PostsController<\/em>/', $result);
	}

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
		$ExceptionRenderer = new ExceptionRenderer($exception);
		$ExceptionRenderer->controller->response = $this->getMock('CakeResponse', array('statusCode', '_sendHeader'));
		$ExceptionRenderer->controller->response->expects($this->once())
			->method('statusCode')
			->with($code);

		ob_start();
		$ExceptionRenderer->render();
		$result = ob_get_clean();

		foreach ($patterns as $pattern) {
			$this->assertPattern($pattern, $result);
		}
	}
}
