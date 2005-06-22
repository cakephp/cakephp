<?PHP

uses('controller');

class ControllerTest extends UnitTestCase
{
	var $controller;

	// constructor of the test suite
	function ControllerTest()
	{
		$this->UnitTestCase('Controller test');
	}

	// called before the test functions will be executed
	// this function is defined in PHPUnit_TestCase and overwritten
	// here
	function setUp()
	{
		$this->controller = new Controller();
		$this->controller->base = '/ease';

		$data = array('foo'=>'foo_value', 'foobar'=>'foobar_value', 'tofu'=>'1');
		$params = array('controller'=>'Test', 'action'=>'test_action', 'data'=>$data);
		$here = '/cake/test';
		$this->controller->params = $params;
		$this->controller->data = $data;
		$this->controller->here = $here;

		$this->controller->action = $this->controller->params['action'];
		$this->controller->passed_args = null;
	}

	// called after the test functions are executed
	// this function is defined in PHPUnit_TestCase and overwritten
	// here
	function tearDown()
	{
		unset($this->controller);
	}
}

?>