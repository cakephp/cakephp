<?php
/**
 * ExceptionRendererTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Error
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Error;
use Cake\Controller\Component;
use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Error;
use Cake\Error\ExceptionRenderer;
use Cake\Network\Request;
use Cake\Routing\Router;
use Cake\TestSuite\Fixture\TestModel;
use Cake\TestSuite\TestCase;

/**
 * Short description for class.
 *
 * @package       Cake.Test.Case.Error
 */
class AuthBlueberryUser extends TestModel {

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
	public function initialize(Controller $controller) {
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
class MissingWidgetThingException extends Error\NotFoundException {
}


/**
 * ExceptionRendererTest class
 *
 * @package       Cake.Test.Case.Error
 */
class ExceptionRendererTest extends TestCase {

	protected $_restoreError = false;

/**
 * setup create a request object to get out of router later.
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		App::build(array(
			'View' => array(
				CAKE . 'Test' . DS . 'TestApp' . DS . 'View' . DS
			)
		), App::RESET);
		Router::reload();

		$request = new Request(null, false);
		$request->base = '';
		Router::setRequestInfo($request);
		Configure::write('debug', 2);
	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
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
		$error->controller->response = $this->getMock('Cake\Network\Response', array('_sendHeader'));
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

		$this->assertEquals('widget thing is missing', $result);
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

		$this->assertEquals('missingWidgetThing', $ExceptionRenderer->method);

		ob_start();
		$ExceptionRenderer->render();
		$result = ob_get_clean();

		$this->assertEquals('widget thing is missing', $result, 'Method declared in subclass converted to error400');
	}

/**
 * test that ExceptionRenderer subclasses properly convert framework errors.
 *
 * @return void
 */
	public function testSubclassConvertingFrameworkErrors() {
		Configure::write('debug', 0);

		$exception = new Error\MissingControllerException('PostsController');
		$ExceptionRenderer = $this->_mockResponse(new MyCustomExceptionRenderer($exception));

		$this->assertEquals('error400', $ExceptionRenderer->method);

		ob_start();
		$ExceptionRenderer->render();
		$result = ob_get_clean();

		$this->assertRegExp('/Not Found/', $result, 'Method declared in error handler not converted to error400. %s');
	}

/**
 * test things in the constructor.
 *
 * @return void
 */
	public function testConstruction() {
		$exception = new Error\NotFoundException('Page not found');
		$ExceptionRenderer = new ExceptionRenderer($exception);

		$this->assertInstanceOf('Cake\Controller\ErrorController', $ExceptionRenderer->controller);
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
		$exception = new Error\MissingActionException('Page not found');
		$ExceptionRenderer = new ExceptionRenderer($exception);

		$this->assertInstanceOf('Cake\Controller\ErrorController', $ExceptionRenderer->controller);
		$this->assertEquals('error400', $ExceptionRenderer->method);
		$this->assertEquals($exception, $ExceptionRenderer->error);
	}

/**
 * test that helpers in custom CakeErrorController are not lost
 */
	public function testCakeErrorHelpersNotLost() {
		$testApp = CAKE . 'Test' . DS . 'TestApp' . DS;
		App::build(array(
			'Controller' => array(
				$testApp . 'Controller' . DS
			),
			'View/Helper' => array(
				$testApp . 'View' . DS . 'Helper' . DS
			),
			'View/Layouts' => array(
				$testApp . 'View' . DS . 'Layouts' . DS
			),
			'Error' => array(
				$testApp . 'Error' . DS
			),
		), App::RESET);

		$exception = new Error\SocketException('socket exception');
		$renderer = new \TestApp\Error\TestAppsExceptionRenderer($exception);

		ob_start();
		$renderer->render();
		$result = ob_get_clean();
		$this->assertContains('<b>peeled</b>', $result);
	}

/**
 * test that unknown exception types with valid status codes are treated correctly.
 *
 * @return void
 */
	public function testUnknownExceptionTypeWithExceptionThatHasA400Code() {
		$exception = new MissingWidgetThingException('coding fail.');
		$ExceptionRenderer = new ExceptionRenderer($exception);
		$ExceptionRenderer->controller->response = $this->getMock('Cake\Network\Response', array('statusCode', '_sendHeader'));
		$ExceptionRenderer->controller->response->expects($this->once())->method('statusCode')->with(404);

		ob_start();
		$ExceptionRenderer->render();
		$result = ob_get_clean();

		$this->assertFalse(method_exists($ExceptionRenderer, 'missingWidgetThing'), 'no method should exist.');
		$this->assertEquals('error400', $ExceptionRenderer->method, 'incorrect method coercion.');
		$this->assertContains('coding fail', $result, 'Text should show up.');
	}

/**
 * test that unknown exception types with valid status codes are treated correctly.
 *
 * @return void
 */
	public function testUnknownExceptionTypeWithNoCodeIsA500() {
		$exception = new \OutOfBoundsException('foul ball.');
		$ExceptionRenderer = new ExceptionRenderer($exception);
		$ExceptionRenderer->controller->response = $this->getMock('Cake\Network\Response', array('statusCode', '_sendHeader'));
		$ExceptionRenderer->controller->response->expects($this->once())
			->method('statusCode')
			->with(500);

		ob_start();
		$ExceptionRenderer->render();
		$result = ob_get_clean();

		$this->assertEquals('error500', $ExceptionRenderer->method, 'incorrect method coercion.');
		$this->assertContains('foul ball.', $result, 'Text should show up as its debug mode.');
	}

/**
 * test that unknown exceptions have messages ignored.
 *
 * @return void
 */
	public function testUnknownExceptionInProduction() {
		Configure::write('debug', 0);

		$exception = new \OutOfBoundsException('foul ball.');
		$ExceptionRenderer = new ExceptionRenderer($exception);
		$ExceptionRenderer->controller->response = $this->getMock('Cake\Network\Response', array('statusCode', '_sendHeader'));
		$ExceptionRenderer->controller->response->expects($this->once())
			->method('statusCode')
			->with(500);

		ob_start();
		$ExceptionRenderer->render();
		$result = ob_get_clean();

		$this->assertEquals('error500', $ExceptionRenderer->method, 'incorrect method coercion.');
		$this->assertNotContains('foul ball.', $result, 'Text should no show up.');
		$this->assertContains('Internal Error', $result, 'Generic message only.');
	}

/**
 * test that unknown exception types with valid status codes are treated correctly.
 *
 * @return void
 */
	public function testUnknownExceptionTypeWithCodeHigherThan500() {
		$exception = new \OutOfBoundsException('foul ball.', 501);
		$ExceptionRenderer = new ExceptionRenderer($exception);
		$ExceptionRenderer->controller->response = $this->getMock('Cake\Network\Response', array('statusCode', '_sendHeader'));
		$ExceptionRenderer->controller->response->expects($this->once())->method('statusCode')->with(501);

		ob_start();
		$ExceptionRenderer->render();
		$result = ob_get_clean();

		$this->assertEquals('error500', $ExceptionRenderer->method, 'incorrect method coercion.');
		$this->assertContains('foul ball.', $result, 'Text should show up as its debug mode.');
	}

/**
 * testerror400 method
 *
 * @return void
 */
	public function testError400() {
		Router::reload();

		$request = new Request('posts/view/1000', false);
		Router::setRequestInfo($request);

		$exception = new Error\NotFoundException('Custom message');
		$ExceptionRenderer = new ExceptionRenderer($exception);
		$ExceptionRenderer->controller->response = $this->getMock('Cake\Network\Response', array('statusCode', '_sendHeader'));
		$ExceptionRenderer->controller->response->expects($this->once())->method('statusCode')->with(404);

		ob_start();
		$ExceptionRenderer->render();
		$result = ob_get_clean();

		$this->assertRegExp('/<h2>Custom message<\/h2>/', $result);
		$this->assertRegExp("/<strong>'.*?\/posts\/view\/1000'<\/strong>/", $result);
	}

/**
 * test that error400 only modifies the messages on Cake Exceptions.
 *
 * @return void
 */
	public function testerror400OnlyChangingCakeException() {
		Configure::write('debug', 0);

		$exception = new Error\NotFoundException('Custom message');
		$ExceptionRenderer = $this->_mockResponse(new ExceptionRenderer($exception));

		ob_start();
		$ExceptionRenderer->render();
		$result = ob_get_clean();
		$this->assertContains('Custom message', $result);

		$exception = new Error\MissingActionException(array('controller' => 'PostsController', 'action' => 'index'));
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

		$request = new Request('pages/<span id=333>pink</span></id><script>document.body.style.background = t=document.getElementById(333).innerHTML;window.alert(t);</script>', false);
		Router::setRequestInfo($request);

		$exception = new Error\NotFoundException('Custom message');
		$ExceptionRenderer = $this->_mockResponse(new ExceptionRenderer($exception));

		ob_start();
		$ExceptionRenderer->render();
		$result = ob_get_clean();

		$this->assertNotRegExp('#<script>document#', $result);
		$this->assertNotRegExp('#alert\(t\);</script>#', $result);
	}

/**
 * testError500 method
 *
 * @return void
 */
	public function testError500Message() {
		$exception = new Error\InternalErrorException('An Internal Error Has Occurred');
		$ExceptionRenderer = new ExceptionRenderer($exception);
		$ExceptionRenderer->controller->response = $this->getMock('Cake\Network\Response', array('statusCode', '_sendHeader'));
		$ExceptionRenderer->controller->response->expects($this->once())->method('statusCode')->with(500);

		ob_start();
		$ExceptionRenderer->render();
		$result = ob_get_clean();

		$this->assertRegExp('/<h2>An Internal Error Has Occurred<\/h2>/', $result);
	}

/**
 * testMissingController method
 *
 * @return void
 */
	public function testMissingController() {
		$exception = new Error\MissingControllerException(array('class' => 'PostsController'));
		$ExceptionRenderer = $this->_mockResponse(new ExceptionRenderer($exception));

		ob_start();
		$ExceptionRenderer->render();
		$result = ob_get_clean();

		$this->assertRegExp('/<h2>Missing Controller<\/h2>/', $result);
		$this->assertRegExp('/<em>PostsController<\/em>/', $result);
	}

/**
 * Returns an array of tests to run for the various Cake Exception classes.
 *
 * @return void
 */
	public static function testProvider() {
		return array(
			array(
				new Error\MissingActionException(array('controller' => 'PostsController', 'action' => 'index')),
				array(
					'/<h2>Missing Method in PostsController<\/h2>/',
					'/<em>PostsController::<\/em><em>index\(\)<\/em>/'
				),
				404
			),
			array(
				new Error\PrivateActionException(array('controller' => 'PostsController' , 'action' => '_secretSauce')),
				array(
					'/<h2>Private Method in PostsController<\/h2>/',
					'/<em>PostsController::<\/em><em>_secretSauce\(\)<\/em>/'
				),
				404
			),
			array(
				new Error\MissingTableException(array('table' => 'articles', 'class' => 'Article', 'ds' => 'test')),
				array(
					'/<h2>Missing Database Table<\/h2>/',
					'/Table <em>articles<\/em> for model <em>Article<\/em> was not found in datasource <em>test<\/em>/'
				),
				500
			),
			array(
				new Error\MissingDatabaseException(array('connection' => 'default')),
				array(
					'/<h2>Missing Database Connection<\/h2>/',
					'/Confirm you have created the file/'
				),
				500
			),
			array(
				new Error\MissingViewException(array('file' => '/posts/about.ctp')),
				array(
					"/posts\/about.ctp/"
				),
				500
			),
			array(
				new Error\MissingLayoutException(array('file' => 'layouts/my_layout.ctp')),
				array(
					"/Missing Layout/",
					"/layouts\/my_layout.ctp/"
				),
				500
			),
			array(
				new Error\MissingConnectionException(array('class' => 'Article')),
				array(
					'/<h2>Missing Database Connection<\/h2>/',
					'/Article requires a database connection/'
				),
				500
			),
			array(
				new Error\MissingConnectionException(array('class' => 'Mysql', 'enabled' => false)),
				array(
					'/<h2>Missing Database Connection<\/h2>/',
					'/Mysql requires a database connection/',
					'/Mysql driver is NOT enabled/'
				),
				500
			),
			array(
				new Error\MissingDatasourceConfigException(array('config' => 'default')),
				array(
					'/<h2>Missing Datasource Configuration<\/h2>/',
					'/The datasource configuration <em>default<\/em> was not found in database.php/'
				),
				500
			),
			array(
				new Error\MissingDatasourceException(array('class' => 'MyDatasource', 'plugin' => 'MyPlugin')),
				array(
					'/<h2>Missing Datasource<\/h2>/',
					'/Datasource class <em>MyPlugin.MyDatasource<\/em> could not be found/'
				),
				500
			),
			array(
				new Error\MissingHelperException(array('class' => 'MyCustomHelper')),
				array(
					'/<h2>Missing Helper<\/h2>/',
					'/<em>MyCustomHelper<\/em> could not be found./',
					'/Create the class <em>MyCustomHelper<\/em> below in file:/',
					'/(\/|\\\)MyCustomHelper.php/'
				),
				500
			),
			array(
				new Error\MissingBehaviorException(array('class' => 'MyCustomBehavior')),
				array(
					'/<h2>Missing Behavior<\/h2>/',
					'/Create the class <em>MyCustomBehavior<\/em> below in file:/',
					'/(\/|\\\)MyCustomBehavior.php/'
				),
				500
			),
			array(
				new Error\MissingComponentException(array('class' => 'SideboxComponent')),
				array(
					'/<h2>Missing Component<\/h2>/',
					'/Create the class <em>SideboxComponent<\/em> below in file:/',
					'/(\/|\\\)SideboxComponent.php/'
				),
				500
			),
			array(
				new \Exception('boom'),
				array(
					'/Internal Error/'
				),
				500
			),
			array(
				new \RuntimeException('another boom'),
				array(
					'/Internal Error/'
				),
				500
			),
			array(
				new Error\Exception('base class'),
				array('/Internal Error/'),
				500
			),
			array(
				new Error\ConfigureException('No file'),
				array('/Internal Error/'),
				500
			)
		);
	}

/**
 * Test the various Cake Exception sub classes
 *
 * @dataProvider testProvider
 * @return void
 */
	public function testCakeExceptionHandling($exception, $patterns, $code) {
		$ExceptionRenderer = new ExceptionRenderer($exception);
		$ExceptionRenderer->controller->response = $this->getMock('Cake\Network\Response', array('statusCode', '_sendHeader'));
		$ExceptionRenderer->controller->response->expects($this->once())
			->method('statusCode')
			->with($code);

		ob_start();
		$ExceptionRenderer->render();
		$result = ob_get_clean();

		foreach ($patterns as $pattern) {
			$this->assertRegExp($pattern, $result);
		}
	}

/**
 * Test exceptions being raised when helpers are missing.
 *
 * @return void
 */
	public function testMissingRenderSafe() {
		$exception = new Error\MissingHelperException(array('class' => 'Fail'));
		$ExceptionRenderer = new ExceptionRenderer($exception);

		$ExceptionRenderer->controller = $this->getMock('Cake\Controller\Controller');
		$ExceptionRenderer->controller->helpers = array('Fail', 'Boom');
		$ExceptionRenderer->controller->request = $this->getMock('Cake\Network\Request');
		$ExceptionRenderer->controller->expects($this->at(2))
			->method('render')
			->with('missingHelper')
			->will($this->throwException($exception));

		$ExceptionRenderer->controller->expects($this->at(4))
			->method('render')
			->with('error500')
			->will($this->returnValue(true));

		$ExceptionRenderer->controller->response = $this->getMock('Cake\Network\Response');
		$ExceptionRenderer->render();
		sort($ExceptionRenderer->controller->helpers);
		$this->assertEquals(array('Form', 'Html', 'Session'), $ExceptionRenderer->controller->helpers);
	}

/**
 * Test that missing subDir/layoutPath don't cause other fatal errors.
 *
 * @return void
 */
	public function testMissingSubdirRenderSafe() {
		$exception = new Error\NotFoundException();
		$ExceptionRenderer = new ExceptionRenderer($exception);

		$ExceptionRenderer->controller = $this->getMock('Cake\Controller\Controller');
		$ExceptionRenderer->controller->helpers = array('Fail', 'Boom');
		$ExceptionRenderer->controller->layoutPath = 'json';
		$ExceptionRenderer->controller->subDir = 'json';
		$ExceptionRenderer->controller->viewClass = 'Json';
		$ExceptionRenderer->controller->request = $this->getMock('Cake\Network\Request');

		$ExceptionRenderer->controller->expects($this->at(1))
			->method('render')
			->with('error400')
			->will($this->throwException($exception));

		$ExceptionRenderer->controller->expects($this->at(3))
			->method('render')
			->with('error500')
			->will($this->returnValue(true));

		$ExceptionRenderer->controller->response = $this->getMock('Cake\Network\Response');
		$ExceptionRenderer->controller->response->expects($this->once())
			->method('type')
			->with('html');

		$ExceptionRenderer->render();
		$this->assertEquals('', $ExceptionRenderer->controller->layoutPath);
		$this->assertEquals('', $ExceptionRenderer->controller->subDir);
		$this->assertEquals('View', $ExceptionRenderer->controller->viewClass);
		$this->assertEquals('Errors/', $ExceptionRenderer->controller->viewPath);
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

		$exception = new \Exception('Terrible');
		$ExceptionRenderer = new ExceptionRenderer($exception);
		$ExceptionRenderer->controller->response = $this->getMock('Cake\Network\Response', array('statusCode', '_sendHeader'));
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
		$exception = new \PDOException('There was an error in the SQL query');
		$exception->queryString = 'SELECT * from poo_query < 5 and :seven';
		$exception->params = array('seven' => 7);
		$ExceptionRenderer = new ExceptionRenderer($exception);
		$ExceptionRenderer->controller->response = $this->getMock('Cake\Network\Response', array('statusCode', '_sendHeader'));
		$ExceptionRenderer->controller->response->expects($this->once())->method('statusCode')->with(500);

		ob_start();
		$ExceptionRenderer->render();
		$result = ob_get_clean();

		$this->assertContains('<h2>Database Error</h2>', $result);
		$this->assertContains('There was an error in the SQL query', $result);
		$this->assertContains('SELECT * from poo_query < 5 and :seven', $result);
		$this->assertContains("'seven' => (int) 7", $result);
	}
}
