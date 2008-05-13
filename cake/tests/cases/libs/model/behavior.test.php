<?php

require_once dirname(__FILE__) . DS . 'models.php';
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class TestBehavior extends ModelBehavior {

	var $mapMethods = array('/test(\w+)/' => 'testMethod', '/look for\s+(.+)/' => 'speakEnglish');

	function setup(&$model, $config = array()) {
		$this->settings[$model->alias] = array_merge(array('before' => 'on', 'after' => 'off'), $config);
	}

	function beforeFind(&$model, $query) {
		$settings = $this->settings[$model->alias];
		switch ($settings['before']) {
			case 'on':
				return false;
			break;
			case 'off':
				return true;
			break;
			case 'test':
				return null;
			break;
			case 'modify':
				$query['fields'] = array('Apple.id', 'Apple.name', 'Apple.mytime');
				$query['recursive'] = -1;
				return $query;
			break;
		}
	}

	function afterFind(&$model, $results, $primary) {
		$settings = $this->settings[$model->alias];
		switch ($settings['after']) {
			case 'on':
				return array();
			break;
			case 'off':
				return $results;
			break;
			case 'test':
				return true;
			break;
			case 'test2':
				return null;
			break;
			case 'modify':
				return Set::extract($results, '{n}.Apple');
			break;
		}
	}

	function beforeValidate(&$model) {
		$settings = $this->settings[$model->alias];
		if (!isset($settings['validate'])) {
			return true;
		}
		switch ($settings['validate']) {
			case 'on':
				$model->invalidate('name');
				return true;
			break;
			case 'off':
				return $results;
			break;
			case 'test':
				return true;
			break;
			case 'test2':
				return null;
			break;
			case 'modify':
				return Set::extract($results, '{n}.Apple');
			break;
		}
	}

	function beforeTest(&$model) {
		$model->beforeTestResult[] = get_class($this);
		return get_class($this);
	}

	function testMethod(&$model, $param = true) {
		if ($param === true) {
			return 'working';
		}
	}

	function testData(&$model) {
		if (!isset($model->data['Apple']['field'])) {
			return false;
		}
		$model->data['Apple']['field_2'] = true;
		return true;
	}

	function validateField(&$model, $field) {
		return current($field) === 'Orange';
	}

	function speakEnglish(&$model, $method, $query) {
		$method = preg_replace('/look for\s+/', 'Item.name = \'', $method);
		$query = preg_replace('/^in\s+/', 'Location.name = \'', $query);
		return $method . '\' AND ' . $query . '\'';
	}
}

class Test2Behavior extends TestBehavior{
	
}

class Test3Behavior extends TestBehavior{
	
}

class BehaviorTest extends CakeTestCase {

	var $fixtures = array('core.apple', 'core.sample');

	function testBehaviorBinding() {
		$this->model = new Apple();
		$this->assertIdentical($this->model->Behaviors->attached(), array());

		$this->model->Behaviors->attach('Test', array('key' => 'value'));
		$this->assertIdentical($this->model->Behaviors->attached(), array('Test'));
		$this->assertEqual(strtolower(get_class($this->model->Behaviors->Test)), 'testbehavior');
		$this->assertEqual($this->model->Behaviors->Test->settings['Apple'], array('before' => 'on', 'after' => 'off', 'key' => 'value'));
		$this->assertEqual(array_keys($this->model->Behaviors->Test->settings), array('Apple'));

		$this->assertIdentical($this->model->Sample->Behaviors->attached(), array());
		$this->model->Sample->Behaviors->attach('Test', array('key2' => 'value2'));
		$this->assertIdentical($this->model->Sample->Behaviors->attached(), array('Test'));
		$this->assertEqual($this->model->Sample->Behaviors->Test->settings['Sample'], array('before' => 'on', 'after' => 'off', 'key2' => 'value2'));

		$this->assertEqual(array_keys($this->model->Behaviors->Test->settings), array('Apple'));
		$this->assertEqual(array_keys($this->model->Sample->Behaviors->Test->settings), array('Sample'));
		$this->assertNotIdentical($this->model->Behaviors->Test->settings['Apple'], $this->model->Sample->Behaviors->Test->settings['Sample']);

		$this->model->Behaviors->attach('Test', array('key2' => 'value2', 'key3' => 'value3', 'before' => 'off'));
		$this->model->Sample->Behaviors->attach('Test', array('key' => 'value', 'key3' => 'value3', 'before' => 'off'));
		$this->assertEqual($this->model->Behaviors->Test->settings['Apple'], array('before' => 'off', 'after' => 'off', 'key' => 'value', 'key2' => 'value2', 'key3' => 'value3'));
		$this->assertEqual($this->model->Behaviors->Test->settings['Apple'], $this->model->Sample->Behaviors->Test->settings['Sample']);

		$this->assertFalse(isset($this->model->Child->Behaviors->Test));
		$this->model->Child->Behaviors->attach('Test', array('key' => 'value', 'key2' => 'value2', 'key3' => 'value3', 'before' => 'off'));
		$this->assertEqual($this->model->Child->Behaviors->Test->settings['Child'], $this->model->Sample->Behaviors->Test->settings['Sample']);

		$this->assertFalse(isset($this->model->Parent->Behaviors->Test));
		$this->model->Parent->Behaviors->attach('Test', array('key' => 'value', 'key2' => 'value2', 'key3' => 'value3', 'before' => 'off'));
		$this->assertEqual($this->model->Parent->Behaviors->Test->settings['Parent'], $this->model->Sample->Behaviors->Test->settings['Sample']);

		$this->model->Parent->Behaviors->attach('Test', array('key' => 'value', 'key2' => 'value', 'key3' => 'value', 'before' => 'off'));
		$this->assertNotEqual($this->model->Parent->Behaviors->Test->settings['Parent'], $this->model->Sample->Behaviors->Test->settings['Sample']);
	}

	function testBehaviorToggling() {
		$this->model = new Apple();
		$this->assertIdentical($this->model->Behaviors->enabled(), array());

		$this->model->Behaviors->attach('Test', array('key' => 'value'));
		$this->assertIdentical($this->model->Behaviors->enabled(), array('Test'));

		$this->model->Behaviors->disable('Test');
		$this->assertIdentical($this->model->Behaviors->attached(), array('Test'));
		$this->assertIdentical($this->model->Behaviors->enabled(), array());

		$this->model->Sample->Behaviors->attach('Test');
		$this->assertIdentical($this->model->Sample->Behaviors->enabled(), array('Test'));
		$this->assertIdentical($this->model->Behaviors->enabled(), array());

		$this->model->Behaviors->enable('Test');
		$this->assertIdentical($this->model->Behaviors->attached(), array('Test'));
		$this->assertIdentical($this->model->Behaviors->enabled(), array('Test'));

		$this->model->Behaviors->disable('Test');
		$this->assertIdentical($this->model->Behaviors->enabled(), array());
		$this->model->Behaviors->attach('Test');
		$this->assertIdentical($this->model->Behaviors->enabled(), array('Test'));
	}

	function testBehaviorFindCallbacks() {
		$this->model = new Apple();
		$expected = $this->model->find('all');

		$this->model->Behaviors->attach('Test');
		$this->assertIdentical($this->model->find('all'), null);

		$this->model->Behaviors->attach('Test', array('before' => false));
		$this->assertIdentical($this->model->find('all'), $expected);

		$this->model->Behaviors->attach('Test', array('before' => 'test'));
		$this->assertIdentical($this->model->find('all'), $expected);

		$this->model->Behaviors->attach('Test', array('before' => 'modify'));
		$expected2 = array(
			array('Apple' => array('id' => '1', 'name' => 'Red Apple 1', 'mytime' => '22:57:17')),
			array('Apple' => array('id' => '2', 'name' => 'Bright Red Apple', 'mytime' => '22:57:17')),
			array('Apple' => array('id' => '3', 'name' => 'green blue', 'mytime' => '22:57:17'))
		);
		$result = $this->model->find('all', array('conditions' => array('Apple.id' => '< 4')));
		$this->assertEqual($result, $expected2);

		$this->model->Behaviors->disable('Test');
		$result = $this->model->find('all');
		$this->assertEqual($result, $expected);

		$this->model->Behaviors->attach('Test', array('before' => 'off', 'after' => 'on'));
		$this->assertIdentical($this->model->find('all'), array());

		$this->model->Behaviors->attach('Test', array('after' => 'off'));
		$this->assertEqual($this->model->find('all'), $expected);

		$this->model->Behaviors->attach('Test', array('after' => 'test'));
		$this->assertEqual($this->model->find('all'), $expected);

		$this->model->Behaviors->attach('Test', array('after' => 'test2'));
		$this->assertEqual($this->model->find('all'), $expected);

		$this->model->Behaviors->attach('Test', array('after' => 'modify'));
		$expected = array(
			array('id' => '1', 'apple_id' => '2', 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
			array('id' => '2', 'apple_id' => '1', 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
			array('id' => '3', 'apple_id' => '2', 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
			array('id' => '4', 'apple_id' => '2', 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
			array('id' => '5', 'apple_id' => '5', 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
			array('id' => '6', 'apple_id' => '4', 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'),
			array('id' => '7', 'apple_id' => '6', 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17')
		);
		$this->assertEqual($this->model->find('all'), $expected);
	}

	function testBehaviorValidateCallback() {
		$this->model =& new Apple();

		$this->model->Behaviors->attach('Test');
		$this->assertIdentical($this->model->validates(), true);

		$this->model->Behaviors->attach('Test', array('validate' => 'on'));
		$this->assertIdentical($this->model->validates(), false);
		$this->assertIdentical($this->model->validationErrors, array('name' => true));
	}

	function testBehaviorValidateMethods() {
		$this->model = new Apple();
		$this->model->Behaviors->attach('Test');
		$this->model->validate['color'] = 'validateField';

		$result = $this->model->save(array('name' => 'Genetically Modified Apple', 'color' => 'Orange'));
		$this->assertEqual(array_keys($result['Apple']), array('name', 'color', 'modified', 'created'));

		$this->model->create();
		$result = $this->model->save(array('name' => 'Regular Apple', 'color' => 'Red'));
		$this->assertFalse($result);
	}

	function testBehaviorMethodDispatching() {
		$this->model = new Apple();
		$this->model->Behaviors->attach('Test');

		$expected = 'working';
		$this->assertEqual($this->model->testMethod(), $expected);
		$this->assertEqual($this->model->Behaviors->dispatchMethod($this->model, 'testMethod'), $expected);

		$result = $this->model->Behaviors->dispatchMethod($this->model, 'wtf');
		$this->assertEqual($result, array('unhandled'));

		$result = $this->model->{'look for the remote'}('in the couch');
		$expected = "Item.name = 'the remote' AND Location.name = 'the couch'";
		$this->assertEqual($result, $expected);
	}

	function testBehaviorMethodDispatchingWithData() {
		$this->model = new Apple();
		$this->model->Behaviors->attach('Test');

		$this->model->set('field', 'value');
		$this->assertTrue($this->model->testData());
		$this->assertTrue($this->model->data['Apple']['field_2']);
	}

	function testBehaviorTrigger() {
		$this->model = new Apple();
		$this->model->Behaviors->attach('Test');
		$this->model->Behaviors->attach('Test2');
		$this->model->Behaviors->attach('Test3');

		$this->model->beforeTestResult = array();
		$this->model->Behaviors->trigger($this->model, 'beforeTest');
		$expected = array('TestBehavior', 'Test2Behavior', 'Test3Behavior');
		$this->assertIdentical($this->model->beforeTestResult, $expected);

		$this->model->beforeTestResult = array();
		$this->model->Behaviors->trigger($this->model, 'beforeTest', array(), array('break' => true, 'breakOn' => 'Test2Behavior'));
		$expected = array('TestBehavior', 'Test2Behavior');
		$this->assertIdentical($this->model->beforeTestResult, $expected);

		$this->model->beforeTestResult = array();
		$this->model->Behaviors->trigger($this->model, 'beforeTest', array(), array('break' => true, 'breakOn' => array('Test2Behavior', 'Test3Behavior')));
		$expected = array('TestBehavior', 'Test2Behavior');
		$this->assertIdentical($this->model->beforeTestResult, $expected);
	}

	function tearDown() {
		unset($this->model);
		ClassRegistry::flush();
	}
}

?>