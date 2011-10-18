<?php
/**
 * ExceptionRendererTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Case.Error
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('ExceptionRenderer', 'Error');
App::uses('Controller', 'Controller');
App::uses('AppController', 'Controller');
App::uses('Component', 'Controller');
App::uses('Router', 'Routing');

/**
 * Short description for class.
 *
 * @package       Cake.Test.Case.Error
 */
class AuthBlueberryUser extends CakeTestModel {

/**
 * name property
 *
 * @var string 'AuthBlueberryUser'
 */
	public $name = 'AuthBlueberryUser';

/**
 * useTable property
 *
 * @var string
 */
	public $useTable = false;
}

/**
 * BlueberryComponent class
 *
 * @package       Cake.Test.Case.Error
 */
class BlueberryComponent extends Component {

/**
 * testName property
 *
 * @return void
 */
	public $testName = null;

/**
 * initialize method
 *
 * @return void
 */
	public function initialize(&$controller) {
		$this->testName = 'BlueberryComponent';
	}
}

/**
 * TestErrorController class
 *
 * @package       Cake.Test.Case.Error
 */
class TestErrorController extends Controller {

/**
 * uses property
 *
 * @var array
 */
	public $uses = array();

/**
 * components property
 *
 * @return void
 */
	public $components = array('Blueberry');

/**
 * beforeRender method
 *
 * @return void
 */
	public function beforeRender() {
		echo $this->Blueberry->testName;
	}

/**
 * index method
 *
 * @return void
 */
	public function index() {
		$this->autoRender = false;
		return 'what up';
	}
}

/**
 * MyCustomExceptionRenderer class
 *
 * @package       Cake.Test.Case.Error
 */
class MyCustomExceptionRenderer extends ExceptionRenderer {

/**
 * custom error message type.
 *
 * @return void
 */
	public function missingWidgetThing() {
		echo 'widget thing is missing';
	}
}
/**
 * Exception class for testing app error handlers and custom errors.
 *
 * @package       Cake.Test.Case.Error
 */
class MissingWidgetThingException extends NotFoundException { }


/**
 * ExceptionRendererTest class
 *
 * @package       Cake.Test.Case.Error
 */
class ExceptionRendererTest extends CakeTestCase {

	public $_restoreError = false;
/**
 * setup create a request object to get out of router later.
 *
 * @return void
 */
	public function setUp() {
		App::build(array(
			'views' => array(
				CAKE . 'Test' . DS . 'test_app' . DS . 'View'. DS
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
	public function teardown() {
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
	public function testSubclassMethodsNotBeingConvertedToError() {
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
	public function testSubclassMethodsNotBeingConvertedDebug0() {
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
	public function testSubclassConvertingFrameworkErrors() {
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
	public function testConstruction() {
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
	public function testErrorMethodCoercion() {
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
	public function testUnknownExceptionTypeWithExceptionThatHasA400Code() {
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
	public function testUnknownExceptionTypeWithNoCodeIsA500() {
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
	public function testUnknownExceptionTypeWithCodeHigherThan500() {
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
 * @return void
 */
	public function testError400() {
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
		$this->assertPattern("/<strong>'.*?\/posts\/view\/1000'<\/strong>/", $result);
	}

/**
 * test that error400 only modifies the messages on CakeExceptions.
 *
 * @return void
 */
	public function testerror400OnlyChangingCakeException() {
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
	public function testError400NoInjection() {
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
 * @return void
 */
	public function testError500Message() {
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
 * @return void
 */
	public function testMissingController() {
		$exception = new MissingControllerException(array('class' => 'PostsController'));
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
				new MissingDatasourceConfigException(array('config' => 'default')),
				array(
					'/<h2>Missing Datasource Configuration<\/h2>/',
					'/The datasource configuration <em>default<\/em> was not found in database.php/'
				),
				500
			),
			array(
				new MissingDatasourceException(array('class' => 'MyDatasource', 'plugin' => 'MyPlugin')),
				array(
					'/<h2>Missing Datasource<\/h2>/',
					'/Datasource class <em>MyPlugin.MyDatasource<\/em> could not be found/'
				),
				500
			),
			array(
				new MissingHelperException(array('class' => 'MyCustomHelper')),
				array(
					'/<h2>Missing Helper<\/h2>/',
					'/<em>MyCustomHelper<\/em> could not be found./',
					'/Create the class <em>MyCustomHelper<\/em> below in file:/',
					'/(\/|\\\)MyCustomHelper.php/'
				),
				500
			),
			array(
				new MissingBehaviorException(array('class' => 'MyCustomBehavior')),
				array(
					'/<h2>Missing Behavior<\/h2>/',
					'/Create the class <em>MyCustomBehavior<\/em> below in file:/',
					'/(\/|\\\)MyCustomBehavior.php/'
				),
				500
			),
			array(
				new MissingComponentException(array('class' => 'SideboxComponent')),
				array(
					'/<h2>Missing Component<\/h2>/',
					'/Create the class <em>SideboxComponent<\/em> below in file:/',
					'/(\/|\\\)SideboxComponent.php/'
				),
				500
			),
			array(
				new Exception('boom'),
				array(
					'/Internal Error/'
				),
				500
			),
			array(
				new RuntimeException('another boom'),
				array(
					'/Internal Error/'
				),
				500
			),
			array(
				new CakeException('base class'),
				array('/Internal Error/'),
				500
			),
			array(
				new ConfigureException('No file'),
				array('/Internal Error/'),
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
	public function testCakeExceptionHandling($exception, $patterns, $code) {
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

/**
 * Test exceptions being raised when helpers are missing.
 *
 * @return void
 */
	public function testMissingRenderSafe() {
		$exception = new MissingHelperException(array('class' => 'Fail'));
		$ExceptionRenderer = new ExceptionRenderer($exception);

		$ExceptionRenderer->controller = $this->getMock('Controller');
		$ExceptionRenderer->controller->helpers = array('Fail', 'Boom');
		$ExceptionRenderer->controller->request = $this->getMock('CakeRequest');
		$ExceptionRenderer->controller->expects($this->at(2))
			->method('render')
			->with('missingHelper')
			->will($this->throwException($exception));

		$ExceptionRenderer->controller->expects($this->at(3))
			->method('render')
			->with('error500')
			->will($this->returnValue(true));

		$ExceptionRenderer->controller->response = $this->getMock('CakeResponse');
		$ExceptionRenderer->render();
		sort($ExceptionRenderer->controller->helpers);
		$this->assertEquals(array('Form', 'Html', 'Session'), $ExceptionRenderer->controller->helpers);
	}

/**
 * Test that exceptions can be rendered when an request hasn't been registered
 * with Router
 *
 * @return void
 */
	public function testRenderWithNoRequest() {
		Router::reload();
		$this->assertNull(Router::getRequest(false));

		$exception = new Exception('Terrible');
		$ExceptionRenderer = new ExceptionRenderer($exception);
		$ExceptionRenderer->controller->response = $this->getMock('CakeResponse', array('statusCode', '_sendHeader'));
		$ExceptionRenderer->controller->response->expects($this->once())
			->method('statusCode')
			->with(500);

		ob_start();
		$ExceptionRenderer->render();
		$result = ob_get_clean();

		$this->assertContains('Internal Error', $result);
	}

/**
 * Tests the output of rendering a PDOException
 *
 * @return void
 */
	public function testPDOException() {
		$exception = new PDOException('There was an error in the SQL query');
		$exception->queryString = 'SELECT * from poo_query < 5 and :seven';
		$exception->params = array('seven' => 7);
		$ExceptionRenderer = new ExceptionRenderer($exception);
		$ExceptionRenderer->controller->response = $this->getMock('CakeResponse', array('statusCode', '_sendHeader'));
		$ExceptionRenderer->controller->response->expects($this->once())->method('statusCode')->with(500);

		ob_start();
		$ExceptionRenderer->render();
		$result = ob_get_clean();

		$this->assertPattern('/<h2>Database Error<\/h2>/', $result);
		$this->assertPattern('/There was an error in the SQL query/', $result);
		$this->assertPattern('/SELECT \* from poo_query < 5 and :seven/', $result);
		$this->assertPattern('/"seven" => 7/', $result);
	}
}
