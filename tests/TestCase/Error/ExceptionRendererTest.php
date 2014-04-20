<?php
/**
 * ExceptionRendererTest file
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Error;

use Cake\Controller\Component;
use Cake\Controller\Controller;
use Cake\Controller\Error\MissingActionException;
use Cake\Controller\Error\MissingComponentException;
use Cake\Controller\Error\MissingControllerException;
use Cake\Controller\Error\PrivateActionException;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Error;
use Cake\Error\ExceptionRenderer;
use Cake\Event\Event;
use Cake\Network\Error\SocketException;
use Cake\Network\Request;
use Cake\ORM\Error\MissingBehaviorException;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\Error\MissingHelperException;
use Cake\View\Error\MissingLayoutException;
use Cake\View\Error\MissingViewException;

/**
 * BlueberryComponent class
 *
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
 * @param Event $event
 * @return void
 */
	public function initialize(Event $event) {
		$this->testName = 'BlueberryComponent';
	}

}

/**
 * TestErrorController class
 *
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
	public function beforeRender(Event $event) {
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
 */
class MissingWidgetThingException extends Error\NotFoundException {
}

/**
 * ExceptionRendererTest class
 *
 */
class ExceptionRendererTest extends TestCase {

/**
 * @var bool
 */
	protected $_restoreError = false;

/**
 * setup create a request object to get out of router later.
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Configure::write('Config.language', 'eng');
		Router::reload();

		$request = new Request();
		$request->base = '';
		Router::setRequestInfo($request);
		Configure::write('debug', true);
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
		Configure::write('debug', true);

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
		Configure::write('debug', false);
		$exception = new MissingWidgetThingException('Widget not found');
		$ExceptionRenderer = $this->_mockResponse(new MyCustomExceptionRenderer($exception));

		ob_start();
		$ExceptionRenderer->render();
		$result = ob_get_clean();

		$this->assertEquals('missingWidgetThing', $ExceptionRenderer->method);
		$this->assertEquals('widget thing is missing', $result, 'Method declared in subclass converted to error400');
	}

/**
 * test that ExceptionRenderer subclasses properly convert framework errors.
 *
 * @return void
 */
	public function testSubclassConvertingFrameworkErrors() {
		Configure::write('debug', false);

		$exception = new MissingControllerException('PostsController');
		$ExceptionRenderer = $this->_mockResponse(new MyCustomExceptionRenderer($exception));

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
		$this->assertEquals($exception, $ExceptionRenderer->error);
	}

/**
 * test that exception message gets coerced when debug = 0
 *
 * @return void
 */
	public function testExceptionMessageCoercion() {
		Configure::write('debug', false);
		$exception = new MissingActionException('Secret info not to be leaked');
		$ExceptionRenderer = new ExceptionRenderer($exception);

		$this->assertInstanceOf('Cake\Controller\ErrorController', $ExceptionRenderer->controller);
		$this->assertEquals($exception, $ExceptionRenderer->error);

		ob_start();
		$ExceptionRenderer->render();
		$result = ob_get_clean();

		$this->assertEquals('error400', $ExceptionRenderer->template);
		$this->assertContains('Not Found', $result);
		$this->assertNotContains('Secret info not to be leaked', $result);
	}

/**
 * test that helpers in custom CakeErrorController are not lost
 *
 * @return void
 */
	public function testCakeErrorHelpersNotLost() {
		Configure::write('App.namespace', 'TestApp');
		$exception = new SocketException('socket exception');
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

		$this->assertContains('foul ball.', $result, 'Text should show up as its debug mode.');
	}

/**
 * test that unknown exceptions have messages ignored.
 *
 * @return void
 */
	public function testUnknownExceptionInProduction() {
		Configure::write('debug', false);

		$exception = new \OutOfBoundsException('foul ball.');
		$ExceptionRenderer = new ExceptionRenderer($exception);
		$ExceptionRenderer->controller->response = $this->getMock('Cake\Network\Response', array('statusCode', '_sendHeader'));
		$ExceptionRenderer->controller->response->expects($this->once())
			->method('statusCode')
			->with(500);

		ob_start();
		$ExceptionRenderer->render();
		$result = ob_get_clean();

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

		$this->assertContains('foul ball.', $result, 'Text should show up as its debug mode.');
	}

/**
 * testerror400 method
 *
 * @return void
 */
	public function testError400() {
		Router::reload();

		$request = new Request('posts/view/1000');
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
		Configure::write('debug', false);

		$exception = new Error\NotFoundException('Custom message');
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

		$request = new Request('pages/<span id=333>pink</span></id><script>document.body.style.background = t=document.getElementById(333).innerHTML;window.alert(t);</script>');
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
 * testExceptionResponseHeader method
 *
 * @return void
 */
	public function testExceptionResponseHeader() {
		$exception = new Error\MethodNotAllowedException('Only allowing POST and DELETE');
		$exception->responseHeader(array('Allow: POST, DELETE'));
		$ExceptionRenderer = new ExceptionRenderer($exception);

		//Replace response object with mocked object add back the original headers which had been set in ExceptionRenderer constructor
		$headers = $ExceptionRenderer->controller->response->header();
		$ExceptionRenderer->controller->response = $this->getMock('Cake\Network\Response', array('_sendHeader'));
		$ExceptionRenderer->controller->response->header($headers);

		$ExceptionRenderer->controller->response->expects($this->at(1))->method('_sendHeader')->with('Allow', 'POST, DELETE');
		ob_start();
		$ExceptionRenderer->render();
		ob_get_clean();
	}

/**
 * testMissingController method
 *
 * @return void
 */
	public function testMissingController() {
		$exception = new MissingControllerException(array(
			'class' => 'Posts',
			'prefix' => '',
			'plugin' => '',
		));
		$ExceptionRenderer = $this->_mockResponse(new MyCustomExceptionRenderer($exception));

		ob_start();
		$ExceptionRenderer->render();
		$result = ob_get_clean();

		$this->assertEquals('missingController', $ExceptionRenderer->template);
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
				new MissingActionException(array(
					'controller' => 'PostsController',
					'action' => 'index',
					'prefix' => '',
					'plugin' => '',
				)),
				array(
					'/<h2>Missing Method in PostsController<\/h2>/',
					'/<em>PostsController::index\(\)<\/em>/'
				),
				404
			),
			array(
				new PrivateActionException(array('controller' => 'PostsController', 'action' => '_secretSauce')),
				array(
					'/<h2>Private Method in PostsController<\/h2>/',
					'/<em>PostsController::_secretSauce\(\)<\/em>/'
				),
				404
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
		$exception = new MissingHelperException(array('class' => 'Fail'));
		$ExceptionRenderer = new ExceptionRenderer($exception);

		$ExceptionRenderer->controller = $this->getMock('Cake\Controller\Controller', array('render'));
		$ExceptionRenderer->controller->helpers = array('Fail', 'Boom');
		$ExceptionRenderer->controller->request = new Request;
		$ExceptionRenderer->controller->expects($this->at(0))
			->method('render')
			->with('missingHelper')
			->will($this->throwException($exception));

		$response = $this->getMock('Cake\Network\Response');
		$response->expects($this->once())
			->method('body')
			->with($this->stringContains('Helper class Fail'));

		$ExceptionRenderer->controller->response = $response;
		$ExceptionRenderer->render();
		sort($ExceptionRenderer->controller->helpers);
		$this->assertEquals(array('Form', 'Html', 'Session'), $ExceptionRenderer->controller->helpers);
	}

/**
 * Test that exceptions in beforeRender() are handled by outputMessageSafe
 *
 * @return void
 */
	public function testRenderExceptionInBeforeRender() {
		$exception = new Error\NotFoundException('Not there, sorry');
		$ExceptionRenderer = new ExceptionRenderer($exception);

		$ExceptionRenderer->controller = $this->getMock('Cake\Controller\Controller', array('beforeRender'));
		$ExceptionRenderer->controller->request = new Request;
		$ExceptionRenderer->controller->expects($this->any())
			->method('beforeRender')
			->will($this->throwException($exception));

		$response = $this->getMock('Cake\Network\Response');
		$response->expects($this->once())
			->method('body')
			->with($this->stringContains('Not there, sorry'));

		$ExceptionRenderer->controller->response = $response;
		$ExceptionRenderer->render();
	}

/**
 * Test that missing subDir/layoutPath don't cause other fatal errors.
 *
 * @return void
 */
	public function testMissingSubdirRenderSafe() {
		$exception = new Error\NotFoundException();
		$ExceptionRenderer = new ExceptionRenderer($exception);

		$ExceptionRenderer->controller = $this->getMock('Cake\Controller\Controller', array('render'));
		$ExceptionRenderer->controller->helpers = array('Fail', 'Boom');
		$ExceptionRenderer->controller->layoutPath = 'boom';
		$ExceptionRenderer->controller->subDir = 'boom';
		$ExceptionRenderer->controller->request = new Request;

		$ExceptionRenderer->controller->expects($this->once())
			->method('render')
			->with('error400')
			->will($this->throwException($exception));

		$response = $this->getMock('Cake\Network\Response');
		$response->expects($this->once())
			->method('body')
			->with($this->stringContains('Not Found'));
		$response->expects($this->once())
			->method('type')
			->with('html');

		$ExceptionRenderer->controller->response = $response;

		$ExceptionRenderer->render();
		$this->assertEquals('', $ExceptionRenderer->controller->layoutPath);
		$this->assertEquals('', $ExceptionRenderer->controller->subDir);
		$this->assertEquals('Error', $ExceptionRenderer->controller->viewPath);
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
		$this->assertContains(h('SELECT * from poo_query < 5 and :seven'), $result);
		$this->assertContains("'seven' => (int) 7", $result);
	}
}
