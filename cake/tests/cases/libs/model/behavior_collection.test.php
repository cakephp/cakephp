<?php
/**
 * BehaviorTest file
 *
 * Long description for behavior.test.php
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.tests.cases.libs.model
 * @since         1.2
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Model', 'AppModel');
require_once dirname(__FILE__) . DS . 'models.php';

/**
 * TestBehavior class
 *
 * @package       cake.tests.cases.libs.model
 */
class TestBehavior extends ModelBehavior {

/**
 * mapMethods property
 *
 * @var array
 * @access public
 */
	public $mapMethods = array('/test(\w+)/' => 'testMethod', '/look for\s+(.+)/' => 'speakEnglish');

/**
 * setup method
 *
 * @param mixed $model
 * @param array $config
 * @access public
 * @return void
 */
	function setup($model, $config = array()) {
		parent::setup($model, $config);
		if (isset($config['mangle'])) {
			$config['mangle'] .= ' mangled';
		}
		$this->settings[$model->alias] = array_merge(array('beforeFind' => 'on', 'afterFind' => 'off'), $config);
	}

/**
 * beforeFind method
 *
 * @param mixed $model
 * @param mixed $query
 * @access public
 * @return void
 */
	function beforeFind($model, $query) {
		$settings = $this->settings[$model->alias];
		if (!isset($settings['beforeFind']) || $settings['beforeFind'] == 'off') {
			return parent::beforeFind($model, $query);
		}
		switch ($settings['beforeFind']) {
			case 'on':
				return false;
			break;
			case 'test':
				return null;
			break;
			case 'modify':
				$query['fields'] = array($model->alias . '.id', $model->alias . '.name', $model->alias . '.mytime');
				$query['recursive'] = -1;
				return $query;
			break;
		}
	}

/**
 * afterFind method
 *
 * @param mixed $model
 * @param mixed $results
 * @param mixed $primary
 * @access public
 * @return void
 */
	function afterFind($model, $results, $primary) {
		$settings = $this->settings[$model->alias];
		if (!isset($settings['afterFind']) || $settings['afterFind'] == 'off') {
			return parent::afterFind($model, $results, $primary);
		}
		switch ($settings['afterFind']) {
			case 'on':
				return array();
			break;
			case 'test':
				return true;
			break;
			case 'test2':
				return null;
			break;
			case 'modify':
				return Set::extract($results, "{n}.{$model->alias}");
			break;
		}
	}

/**
 * beforeSave method
 *
 * @param mixed $model
 * @access public
 * @return void
 */
	function beforeSave($model) {
		$settings = $this->settings[$model->alias];
		if (!isset($settings['beforeSave']) || $settings['beforeSave'] == 'off') {
			return parent::beforeSave($model);
		}
		switch ($settings['beforeSave']) {
			case 'on':
				return false;
			break;
			case 'test':
				return true;
			break;
			case 'modify':
				$model->data[$model->alias]['name'] .= ' modified before';
				return true;
			break;
		}
	}

/**
 * afterSave method
 *
 * @param mixed $model
 * @param mixed $created
 * @access public
 * @return void
 */
	function afterSave($model, $created) {
		$settings = $this->settings[$model->alias];
		if (!isset($settings['afterSave']) || $settings['afterSave'] == 'off') {
			return parent::afterSave($model, $created);
		}
		$string = 'modified after';
		if ($created) {
			$string .= ' on create';
		}
		switch ($settings['afterSave']) {
			case 'on':
				$model->data[$model->alias]['aftersave'] = $string;
			break;
			case 'test':
				unset($model->data[$model->alias]['name']);
			break;
			case 'test2':
				return false;
			break;
			case 'modify':
				$model->data[$model->alias]['name'] .= ' ' . $string;
			break;
		}
	}

/**
 * beforeValidate method
 *
 * @param mixed $model
 * @access public
 * @return void
 */
	function beforeValidate($model) {
		$settings = $this->settings[$model->alias];
		if (!isset($settings['validate']) || $settings['validate'] == 'off') {
			return parent::beforeValidate($model);
		}
		switch ($settings['validate']) {
			case 'on':
				$model->invalidate('name');
				return true;
			break;
			case 'test':
				return null;
			break;
			case 'whitelist':
				$this->_addToWhitelist($model, array('name'));
				return true;
			break;
			case 'stop':
				$model->invalidate('name');
				return false;
			break;
		}
	}

/**
 * beforeDelete method
 *
 * @param mixed $model
 * @param bool $cascade
 * @access public
 * @return void
 */
	function beforeDelete($model, $cascade = true) {
		$settings = $this->settings[$model->alias];
		if (!isset($settings['beforeDelete']) || $settings['beforeDelete'] == 'off') {
			return parent::beforeDelete($model, $cascade);
		}
		switch ($settings['beforeDelete']) {
			case 'on':
				return false;
			break;
			case 'test':
				return null;
			break;
			case 'test2':
				echo 'beforeDelete success';
				if ($cascade) {
					echo ' (cascading) ';
				}
				return true;
			break;
		}
	}

/**
 * afterDelete method
 *
 * @param mixed $model
 * @access public
 * @return void
 */
	function afterDelete($model) {
		$settings = $this->settings[$model->alias];
		if (!isset($settings['afterDelete']) || $settings['afterDelete'] == 'off') {
			return parent::afterDelete($model);
		}
		switch ($settings['afterDelete']) {
			case 'on':
				echo 'afterDelete success';
			break;
		}
	}

/**
 * onError method
 *
 * @param mixed $model
 * @access public
 * @return void
 */
	function onError($model, $error) {
		$settings = $this->settings[$model->alias];
		if (!isset($settings['onError']) || $settings['onError'] == 'off') {
			return parent::onError($model, $error);
		}
		echo "onError trigger success";
	}
/**
 * beforeTest method
 *
 * @param mixed $model
 * @access public
 * @return void
 */
	function beforeTest($model) {
		if (!isset($model->beforeTestResult)) {
			$model->beforeTestResult = array();
		}
		$model->beforeTestResult[] = strtolower(get_class($this));
		return strtolower(get_class($this));
	}

/**
 * testMethod method
 *
 * @param mixed $model
 * @param bool $param
 * @access public
 * @return void
 */
	function testMethod(Model $model, $param = true) {
		if ($param === true) {
			return 'working';
		}
	}

/**
 * testData method
 *
 * @param mixed $model
 * @access public
 * @return void
 */
	function testData(Model $model) {
		if (!isset($model->data['Apple']['field'])) {
			return false;
		}
		$model->data['Apple']['field_2'] = true;
		return true;
	}

/**
 * validateField method
 *
 * @param mixed $model
 * @param mixed $field
 * @access public
 * @return void
 */
	function validateField(Model $model, $field) {
		return current($field) === 'Orange';
	}

/**
 * speakEnglish method
 *
 * @param mixed $model
 * @param mixed $method
 * @param mixed $query
 * @access public
 * @return void
 */
	function speakEnglish(Model $model, $method, $query) {
		$method = preg_replace('/look for\s+/', 'Item.name = \'', $method);
		$query = preg_replace('/^in\s+/', 'Location.name = \'', $query);
		return $method . '\' AND ' . $query . '\'';
	}
}

/**
 * Test2Behavior class
 *
 * @package       cake.tests.cases.libs.model
 */
class Test2Behavior extends TestBehavior{
}

/**
 * Test3Behavior class
 *
 * @package       cake.tests.cases.libs.model
 */
class Test3Behavior extends TestBehavior{
}

/**
 * Test4Behavior class
 *
 * @package       cake.tests.cases.libs.model
 */
class Test4Behavior extends ModelBehavior{
	function setup($model, $config = null) {
		$model->bindModel(
			array('hasMany' => array('Comment'))
		);
	}
}

/**
 * Test5Behavior class
 *
 * @package       cake.tests.cases.libs.model
 */
class Test5Behavior extends ModelBehavior{
	function setup($model, $config = null) {
		$model->bindModel(
			array('belongsTo' => array('User'))
		);
	}
}

/**
 * Test6Behavior class
 *
 * @package       cake.tests.cases.libs.model
 */
class Test6Behavior extends ModelBehavior{
	function setup($model, $config = null) {
		$model->bindModel(
			array('hasAndBelongsToMany' => array('Tag'))
		);
	}
}

/**
 * Test7Behavior class
 *
 * @package       cake.tests.cases.libs.model
 */
class Test7Behavior extends ModelBehavior{
	function setup($model, $config = null) {
		$model->bindModel(
			array('hasOne' => array('Attachment'))
		);
	}
}

/**
 * BehaviorCollection class
 *
 * @package       cake.tests.cases.libs.model
 */
class BehaviorCollectionTest extends CakeTestCase {

/**
 * fixtures property
 *
 * @var array
 * @access public
 */
	public $fixtures = array(
		'core.apple', 'core.sample', 'core.article', 'core.user', 'core.comment',
		'core.attachment', 'core.tag', 'core.articles_tag'
	);

/**
 * testBehaviorBinding method
 *
 * @access public
 * @return void
 */
	function testBehaviorBinding() {
		$Apple = new Apple();
		$this->assertIdentical($Apple->Behaviors->attached(), array());

		$Apple->Behaviors->attach('Test', array('key' => 'value'));
		$this->assertIdentical($Apple->Behaviors->attached(), array('Test'));
		$this->assertEqual(strtolower(get_class($Apple->Behaviors->Test)), 'testbehavior');
		$expected = array('beforeFind' => 'on', 'afterFind' => 'off', 'key' => 'value');
		$this->assertEqual($Apple->Behaviors->Test->settings['Apple'], $expected);
		$this->assertEqual(array_keys($Apple->Behaviors->Test->settings), array('Apple'));

		$this->assertIdentical($Apple->Sample->Behaviors->attached(), array());
		$Apple->Sample->Behaviors->attach('Test', array('key2' => 'value2'));
		$this->assertIdentical($Apple->Sample->Behaviors->attached(), array('Test'));
		$this->assertEqual($Apple->Sample->Behaviors->Test->settings['Sample'], array('beforeFind' => 'on', 'afterFind' => 'off', 'key2' => 'value2'));

		$this->assertEqual(array_keys($Apple->Behaviors->Test->settings), array('Apple', 'Sample'));
		$this->assertIdentical(
			$Apple->Sample->Behaviors->Test->settings,
			$Apple->Behaviors->Test->settings
		);
		$this->assertNotIdentical($Apple->Behaviors->Test->settings['Apple'], $Apple->Sample->Behaviors->Test->settings['Sample']);

		$Apple->Behaviors->attach('Test', array('key2' => 'value2', 'key3' => 'value3', 'beforeFind' => 'off'));
		$Apple->Sample->Behaviors->attach('Test', array('key' => 'value', 'key3' => 'value3', 'beforeFind' => 'off'));
		$this->assertEqual($Apple->Behaviors->Test->settings['Apple'], array('beforeFind' => 'off', 'afterFind' => 'off', 'key' => 'value', 'key2' => 'value2', 'key3' => 'value3'));
		$this->assertEqual($Apple->Behaviors->Test->settings['Apple'], $Apple->Sample->Behaviors->Test->settings['Sample']);

		$this->assertFalse(isset($Apple->Child->Behaviors->Test));
		$Apple->Child->Behaviors->attach('Test', array('key' => 'value', 'key2' => 'value2', 'key3' => 'value3', 'beforeFind' => 'off'));
		$this->assertEqual($Apple->Child->Behaviors->Test->settings['Child'], $Apple->Sample->Behaviors->Test->settings['Sample']);

		$this->assertFalse(isset($Apple->Parent->Behaviors->Test));
		$Apple->Parent->Behaviors->attach('Test', array('key' => 'value', 'key2' => 'value2', 'key3' => 'value3', 'beforeFind' => 'off'));
		$this->assertEqual($Apple->Parent->Behaviors->Test->settings['Parent'], $Apple->Sample->Behaviors->Test->settings['Sample']);

		$Apple->Parent->Behaviors->attach('Test', array('key' => 'value', 'key2' => 'value', 'key3' => 'value', 'beforeFind' => 'off'));
		$this->assertNotEqual($Apple->Parent->Behaviors->Test->settings['Parent'], $Apple->Sample->Behaviors->Test->settings['Sample']);

		$Apple->Behaviors->attach('Plugin.Test', array('key' => 'new value'));
		$expected = array(
			'beforeFind' => 'off', 'afterFind' => 'off', 'key' => 'new value',
			'key2' => 'value2', 'key3' => 'value3'
		);
		$this->assertEqual($Apple->Behaviors->Test->settings['Apple'], $expected);

		$current = $Apple->Behaviors->Test->settings['Apple'];
		$expected = array_merge($current, array('mangle' => 'trigger mangled'));
		$Apple->Behaviors->attach('Test', array('mangle' => 'trigger'));
		$this->assertEqual($Apple->Behaviors->Test->settings['Apple'], $expected);

		$Apple->Behaviors->attach('Test');
		$expected = array_merge($current, array('mangle' => 'trigger mangled mangled'));

		$this->assertEqual($Apple->Behaviors->Test->settings['Apple'], $expected);
		$Apple->Behaviors->attach('Test', array('mangle' => 'trigger'));
		$expected = array_merge($current, array('mangle' => 'trigger mangled'));
		$this->assertEqual($Apple->Behaviors->Test->settings['Apple'], $expected);
	}

/**
 * test that attach()/detach() works with plugin.banana
 *
 * @return void
 */
	function testDetachWithPluginNames() {
		$Apple = new Apple();
		$Apple->Behaviors->attach('Plugin.Test');
		$this->assertTrue(isset($Apple->Behaviors->Test), 'Missing behavior');
		$this->assertEqual($Apple->Behaviors->attached(), array('Test'));

		$Apple->Behaviors->detach('Plugin.Test');
		$this->assertEqual($Apple->Behaviors->attached(), array());

		$Apple->Behaviors->attach('Plugin.Test');
		$this->assertTrue(isset($Apple->Behaviors->Test), 'Missing behavior');
		$this->assertEqual($Apple->Behaviors->attached(), array('Test'));

		$Apple->Behaviors->detach('Test');
		$this->assertEqual($Apple->Behaviors->attached(), array());
	}

/**
 * test that attaching a non existant Behavior triggers a cake error.
 *
 * @expectedException MissingBehaviorFileException
 * @return void
 */
	function testInvalidBehaviorCausingCakeError() {
		$Apple = new Apple();
		$Apple->Behaviors->attach('NoSuchBehavior');
	}

/**
 * testBehaviorToggling method
 *
 * @access public
 * @return void
 */
	function testBehaviorToggling() {
		$Apple = new Apple();
		$expected = $Apple->find('all');
		$this->assertIdentical($Apple->Behaviors->enabled(), array());

		$Apple->Behaviors->init('Apple', array('Test' => array('key' => 'value')));
		$this->assertIdentical($Apple->Behaviors->enabled(), array('Test'));

		$Apple->Behaviors->disable('Test');
		$this->assertIdentical($Apple->Behaviors->attached(), array('Test'));
		$this->assertIdentical($Apple->Behaviors->enabled(), array());

		$Apple->Sample->Behaviors->attach('Test');
		$this->assertIdentical($Apple->Sample->Behaviors->enabled('Test'), true);
		$this->assertIdentical($Apple->Behaviors->enabled(), array());

		$Apple->Behaviors->enable('Test');
		$this->assertIdentical($Apple->Behaviors->attached('Test'), true);
		$this->assertIdentical($Apple->Behaviors->enabled(), array('Test'));

		$Apple->Behaviors->disable('Test');
		$this->assertIdentical($Apple->Behaviors->enabled(), array());
		$Apple->Behaviors->attach('Test', array('enabled' => true));
		$this->assertIdentical($Apple->Behaviors->enabled(), array('Test'));
		$Apple->Behaviors->attach('Test', array('enabled' => false));
		$this->assertIdentical($Apple->Behaviors->enabled(), array());
		$Apple->Behaviors->detach('Test');
		$this->assertIdentical($Apple->Behaviors->enabled(), array());
	}

/**
 * testBehaviorFindCallbacks method
 *
 * @access public
 * @return void
 */
	function testBehaviorFindCallbacks() {
		$Apple = new Apple();
		$expected = $Apple->find('all');

		$Apple->Behaviors->attach('Test');
		$this->assertIdentical($Apple->find('all'), null);

		$Apple->Behaviors->attach('Test', array('beforeFind' => 'off'));
		$this->assertIdentical($Apple->find('all'), $expected);

		$Apple->Behaviors->attach('Test', array('beforeFind' => 'test'));
		$this->assertIdentical($Apple->find('all'), $expected);

		$Apple->Behaviors->attach('Test', array('beforeFind' => 'modify'));
		$expected2 = array(
			array('Apple' => array('id' => '1', 'name' => 'Red Apple 1', 'mytime' => '22:57:17')),
			array('Apple' => array('id' => '2', 'name' => 'Bright Red Apple', 'mytime' => '22:57:17')),
			array('Apple' => array('id' => '3', 'name' => 'green blue', 'mytime' => '22:57:17'))
		);
		$result = $Apple->find('all', array('conditions' => array('Apple.id <' => '4')));
		$this->assertEqual($result, $expected2);

		$Apple->Behaviors->disable('Test');
		$result = $Apple->find('all');
		$this->assertEqual($result, $expected);

		$Apple->Behaviors->attach('Test', array('beforeFind' => 'off', 'afterFind' => 'on'));
		$this->assertIdentical($Apple->find('all'), array());

		$Apple->Behaviors->attach('Test', array('afterFind' => 'off'));
		$this->assertEqual($Apple->find('all'), $expected);

		$Apple->Behaviors->attach('Test', array('afterFind' => 'test'));
		$this->assertEqual($Apple->find('all'), $expected);

		$Apple->Behaviors->attach('Test', array('afterFind' => 'test2'));
		$this->assertEqual($Apple->find('all'), $expected);

		$Apple->Behaviors->attach('Test', array('afterFind' => 'modify'));
		$expected = array(
			array('id' => '1', 'apple_id' => '2', 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
			array('id' => '2', 'apple_id' => '1', 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
			array('id' => '3', 'apple_id' => '2', 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
			array('id' => '4', 'apple_id' => '2', 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
			array('id' => '5', 'apple_id' => '5', 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
			array('id' => '6', 'apple_id' => '4', 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'),
			array('id' => '7', 'apple_id' => '6', 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17')
		);
		$this->assertEqual($Apple->find('all'), $expected);
	}

/**
 * testBehaviorHasManyFindCallbacks method
 *
 * @access public
 * @return void
 */
	function testBehaviorHasManyFindCallbacks() {
		$Apple = new Apple();
		$Apple->unbindModel(array('hasOne' => array('Sample'), 'belongsTo' => array('Parent')), false);
		$expected = $Apple->find('all');

		$Apple->unbindModel(array('hasMany' => array('Child')));
		$wellBehaved = $Apple->find('all');
		$Apple->Child->Behaviors->attach('Test', array('afterFind' => 'modify'));
		$Apple->unbindModel(array('hasMany' => array('Child')));
		$this->assertIdentical($Apple->find('all'), $wellBehaved);

		$Apple->Child->Behaviors->attach('Test', array('before' => 'off'));
		$this->assertIdentical($Apple->find('all'), $expected);

		$Apple->Child->Behaviors->attach('Test', array('before' => 'test'));
		$this->assertIdentical($Apple->find('all'), $expected);

		$expected2 = array(
			array(
				'Apple' => array('id' => 1),
				'Child' => array(
					array('id' => 2,'name' => 'Bright Red Apple', 'mytime' => '22:57:17'))),
			array(
				'Apple' => array('id' => 2),
				'Child' => array(
					array('id' => 1, 'name' => 'Red Apple 1', 'mytime' => '22:57:17'),
					array('id' => 3, 'name' => 'green blue', 'mytime' => '22:57:17'),
					array('id' => 4, 'name' => 'Test Name', 'mytime' => '22:57:17'))),
			array(
				'Apple' => array('id' => 3),
				'Child' => array())
		);

		$Apple->Child->Behaviors->attach('Test', array('before' => 'modify'));
		$result = $Apple->find('all', array('fields' => array('Apple.id'), 'conditions' => array('Apple.id <' => '4')));
		//$this->assertEqual($result, $expected2);

		$Apple->Child->Behaviors->disable('Test');
		$result = $Apple->find('all');
		$this->assertEqual($result, $expected);

		$Apple->Child->Behaviors->attach('Test', array('before' => 'off', 'after' => 'on'));
		//$this->assertIdentical($Apple->find('all'), array());

		$Apple->Child->Behaviors->attach('Test', array('after' => 'off'));
		$this->assertEqual($Apple->find('all'), $expected);

		$Apple->Child->Behaviors->attach('Test', array('after' => 'test'));
		$this->assertEqual($Apple->find('all'), $expected);

		$Apple->Child->Behaviors->attach('Test', array('after' => 'test2'));
		$this->assertEqual($Apple->find('all'), $expected);

		$Apple->Child->Behaviors->attach('Test', array('after' => 'modify'));
		$expected = array(
			array('id' => '1', 'apple_id' => '2', 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
			array('id' => '2', 'apple_id' => '1', 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
			array('id' => '3', 'apple_id' => '2', 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
			array('id' => '4', 'apple_id' => '2', 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
			array('id' => '5', 'apple_id' => '5', 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
			array('id' => '6', 'apple_id' => '4', 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'),
			array('id' => '7', 'apple_id' => '6', 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17')
		);
		//$this->assertEqual($Apple->find('all'), $expected);

	}
	/**
 * testBehaviorHasOneFindCallbacks method
 *
 * @access public
 * @return void
 */
	function testBehaviorHasOneFindCallbacks() {
		$Apple = new Apple();
		$Apple->unbindModel(array('hasMany' => array('Child'), 'belongsTo' => array('Parent')), false);
		$expected = $Apple->find('all');

		$Apple->unbindModel(array('hasOne' => array('Sample')));
		$wellBehaved = $Apple->find('all');
		$Apple->Sample->Behaviors->attach('Test');
		$Apple->unbindModel(array('hasOne' => array('Sample')));
		$this->assertIdentical($Apple->find('all'), $wellBehaved);

		$Apple->Sample->Behaviors->attach('Test', array('before' => 'off'));
		$this->assertIdentical($Apple->find('all'), $expected);

		$Apple->Sample->Behaviors->attach('Test', array('before' => 'test'));
		$this->assertIdentical($Apple->find('all'), $expected);

		$Apple->Sample->Behaviors->attach('Test', array('before' => 'modify'));
		$expected2 = array(
			array(
				'Apple' => array('id' => 1),
				'Child' => array(
					array('id' => 2,'name' => 'Bright Red Apple', 'mytime' => '22:57:17'))),
			array(
				'Apple' => array('id' => 2),
				'Child' => array(
					array('id' => 1, 'name' => 'Red Apple 1', 'mytime' => '22:57:17'),
					array('id' => 3, 'name' => 'green blue', 'mytime' => '22:57:17'),
					array('id' => 4, 'name' => 'Test Name', 'mytime' => '22:57:17'))),
			array(
				'Apple' => array('id' => 3),
				'Child' => array())
		);
		$result = $Apple->find('all', array('fields' => array('Apple.id'), 'conditions' => array('Apple.id <' => '4')));
		//$this->assertEqual($result, $expected2);

		$Apple->Sample->Behaviors->disable('Test');
		$result = $Apple->find('all');
		$this->assertEqual($result, $expected);

		$Apple->Sample->Behaviors->attach('Test', array('before' => 'off', 'after' => 'on'));
		//$this->assertIdentical($Apple->find('all'), array());

		$Apple->Sample->Behaviors->attach('Test', array('after' => 'off'));
		$this->assertEqual($Apple->find('all'), $expected);

		$Apple->Sample->Behaviors->attach('Test', array('after' => 'test'));
		$this->assertEqual($Apple->find('all'), $expected);

		$Apple->Sample->Behaviors->attach('Test', array('after' => 'test2'));
		$this->assertEqual($Apple->find('all'), $expected);

		$Apple->Sample->Behaviors->attach('Test', array('after' => 'modify'));
		$expected = array(
			array('id' => '1', 'apple_id' => '2', 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
			array('id' => '2', 'apple_id' => '1', 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
			array('id' => '3', 'apple_id' => '2', 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
			array('id' => '4', 'apple_id' => '2', 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
			array('id' => '5', 'apple_id' => '5', 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
			array('id' => '6', 'apple_id' => '4', 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'),
			array('id' => '7', 'apple_id' => '6', 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17')
		);
		//$this->assertEqual($Apple->find('all'), $expected);
	}
	/**
 * testBehaviorBelongsToFindCallbacks method
 *
 * @access public
 * @return void
 */
	function testBehaviorBelongsToFindCallbacks() {
		$Apple = new Apple();
		$Apple->unbindModel(array('hasMany' => array('Child'), 'hasOne' => array('Sample')), false);
		$expected = $Apple->find('all');

		$Apple->unbindModel(array('belongsTo' => array('Parent')));
		$wellBehaved = $Apple->find('all');
		$Apple->Parent->Behaviors->attach('Test');
		$Apple->unbindModel(array('belongsTo' => array('Parent')));
		$this->assertIdentical($Apple->find('all'), $wellBehaved);

		$Apple->Parent->Behaviors->attach('Test', array('before' => 'off'));
		$this->assertIdentical($Apple->find('all'), $expected);

		$Apple->Parent->Behaviors->attach('Test', array('before' => 'test'));
		$this->assertIdentical($Apple->find('all'), $expected);

		$Apple->Parent->Behaviors->attach('Test', array('before' => 'modify'));
		$expected2 = array(
			array(
				'Apple' => array('id' => 1),
				'Parent' => array('id' => 2,'name' => 'Bright Red Apple', 'mytime' => '22:57:17')),
			array(
				'Apple' => array('id' => 2),
				'Parent' => array('id' => 1, 'name' => 'Red Apple 1', 'mytime' => '22:57:17')),
			array(
				'Apple' => array('id' => 3),
				'Parent' => array('id' => 2,'name' => 'Bright Red Apple', 'mytime' => '22:57:17'))
		);
		$result = $Apple->find('all', array(
			'fields' => array('Apple.id', 'Parent.id', 'Parent.name', 'Parent.mytime'),
			'conditions' => array('Apple.id <' => '4')
		));
		$this->assertEqual($result, $expected2);

		$Apple->Parent->Behaviors->disable('Test');
		$result = $Apple->find('all');
		$this->assertEqual($result, $expected);

		$Apple->Parent->Behaviors->attach('Test', array('after' => 'off'));
		$this->assertEqual($Apple->find('all'), $expected);

		$Apple->Parent->Behaviors->attach('Test', array('after' => 'test'));
		$this->assertEqual($Apple->find('all'), $expected);

		$Apple->Parent->Behaviors->attach('Test', array('after' => 'test2'));
		$this->assertEqual($Apple->find('all'), $expected);

		$Apple->Parent->Behaviors->attach('Test', array('after' => 'modify'));
		$expected = array(
			array('id' => '1', 'apple_id' => '2', 'color' => 'Red 1', 'name' => 'Red Apple 1', 'created' => '2006-11-22 10:38:58', 'date' => '1951-01-04', 'modified' => '2006-12-01 13:31:26', 'mytime' => '22:57:17'),
			array('id' => '2', 'apple_id' => '1', 'color' => 'Bright Red 1', 'name' => 'Bright Red Apple', 'created' => '2006-11-22 10:43:13', 'date' => '2014-01-01', 'modified' => '2006-11-30 18:38:10', 'mytime' => '22:57:17'),
			array('id' => '3', 'apple_id' => '2', 'color' => 'blue green', 'name' => 'green blue', 'created' => '2006-12-25 05:13:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:24', 'mytime' => '22:57:17'),
			array('id' => '4', 'apple_id' => '2', 'color' => 'Blue Green', 'name' => 'Test Name', 'created' => '2006-12-25 05:23:36', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:23:36', 'mytime' => '22:57:17'),
			array('id' => '5', 'apple_id' => '5', 'color' => 'Green', 'name' => 'Blue Green', 'created' => '2006-12-25 05:24:06', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:16', 'mytime' => '22:57:17'),
			array('id' => '6', 'apple_id' => '4', 'color' => 'My new appleOrange', 'name' => 'My new apple', 'created' => '2006-12-25 05:29:39', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:29:39', 'mytime' => '22:57:17'),
			array('id' => '7', 'apple_id' => '6', 'color' => 'Some wierd color', 'name' => 'Some odd color', 'created' => '2006-12-25 05:34:21', 'date' => '2006-12-25', 'modified' => '2006-12-25 05:34:21', 'mytime' => '22:57:17')
		);
		//$this->assertEqual($Apple->find('all'), $expected);
	}

/**
 * testBehaviorSaveCallbacks method
 *
 * @access public
 * @return void
 */
	function testBehaviorSaveCallbacks() {
		$Sample = new Sample();
		$record = array('Sample' => array('apple_id' => 6, 'name' => 'sample99'));

		$Sample->Behaviors->attach('Test', array('beforeSave' => 'on'));
		$Sample->create();
		$this->assertIdentical($Sample->save($record), false);

		$Sample->Behaviors->attach('Test', array('beforeSave' => 'off'));
		$Sample->create();
		$this->assertIdentical($Sample->save($record), $record);

		$Sample->Behaviors->attach('Test', array('beforeSave' => 'test'));
		$Sample->create();
		$this->assertIdentical($Sample->save($record), $record);

		$Sample->Behaviors->attach('Test', array('beforeSave' => 'modify'));
		$expected = Set::insert($record, 'Sample.name', 'sample99 modified before');
		$Sample->create();
		$this->assertIdentical($Sample->save($record), $expected);

		$Sample->Behaviors->disable('Test');
		$this->assertIdentical($Sample->save($record), $record);

		$Sample->Behaviors->attach('Test', array('beforeSave' => 'off', 'afterSave' => 'on'));
		$expected = Set::merge($record, array('Sample' => array('aftersave' => 'modified after on create')));
		$Sample->create();
		$this->assertIdentical($Sample->save($record), $expected);

		$Sample->Behaviors->attach('Test', array('beforeSave' => 'modify', 'afterSave' => 'modify'));
		$expected = Set::merge($record, array('Sample' => array('name' => 'sample99 modified before modified after on create')));
		$Sample->create();
		$this->assertIdentical($Sample->save($record), $expected);

		$Sample->Behaviors->attach('Test', array('beforeSave' => 'off', 'afterSave' => 'test'));
		$Sample->create();
		$this->assertIdentical($Sample->save($record), $record);

		$Sample->Behaviors->attach('Test', array('afterSave' => 'test2'));
		$Sample->create();
		$this->assertIdentical($Sample->save($record), $record);

		$Sample->Behaviors->attach('Test', array('beforeFind' => 'off', 'afterFind' => 'off'));
		$Sample->recursive = -1;
		$record2 = $Sample->read(null, 1);

		$Sample->Behaviors->attach('Test', array('afterSave' => 'on'));
		$expected = Set::merge($record2, array('Sample' => array('aftersave' => 'modified after')));
		$Sample->create();
		$this->assertIdentical($Sample->save($record2), $expected);

		$Sample->Behaviors->attach('Test', array('afterSave' => 'modify'));
		$expected = Set::merge($record2, array('Sample' => array('name' => 'sample1 modified after')));
		$Sample->create();
		$this->assertIdentical($Sample->save($record2), $expected);
	}

/**
 * testBehaviorDeleteCallbacks method
 *
 * @access public
 * @return void
 */
	function testBehaviorDeleteCallbacks() {
		$Apple = new Apple();

		$Apple->Behaviors->attach('Test', array('beforeFind' => 'off', 'beforeDelete' => 'off'));
		$this->assertIdentical($Apple->delete(6), true);

		$Apple->Behaviors->attach('Test', array('beforeDelete' => 'on'));
		$this->assertIdentical($Apple->delete(4), false);

		$Apple->Behaviors->attach('Test', array('beforeDelete' => 'test2'));

		ob_start();
		$results = $Apple->delete(4);
		$this->assertIdentical(trim(ob_get_clean()), 'beforeDelete success (cascading)');
		$this->assertIdentical($results, true);

		ob_start();
		$results = $Apple->delete(3, false);
		$this->assertIdentical(trim(ob_get_clean()), 'beforeDelete success');
		$this->assertIdentical($results, true);


		$Apple->Behaviors->attach('Test', array('beforeDelete' => 'off', 'afterDelete' => 'on'));
		ob_start();
		$results = $Apple->delete(2, false);
		$this->assertIdentical(trim(ob_get_clean()), 'afterDelete success');
		$this->assertIdentical($results, true);
	}

/**
 * testBehaviorOnErrorCallback method
 *
 * @access public
 * @return void
 */
	function testBehaviorOnErrorCallback() {
		$Apple = new Apple();

		$Apple->Behaviors->attach('Test', array('beforeFind' => 'off', 'onError' => 'on'));
		ob_start();
		$Apple->Behaviors->Test->onError($Apple, '');
		$this->assertIdentical(trim(ob_get_clean()), 'onError trigger success');
	}

/**
 * testBehaviorValidateCallback method
 *
 * @access public
 * @return void
 */
	function testBehaviorValidateCallback() {
		$Apple = new Apple();

		$Apple->Behaviors->attach('Test');
		$this->assertIdentical($Apple->validates(), true);

		$Apple->Behaviors->attach('Test', array('validate' => 'on'));
		$this->assertIdentical($Apple->validates(), false);
		$this->assertIdentical($Apple->validationErrors, array('name' => true));

		$Apple->Behaviors->attach('Test', array('validate' => 'stop'));
		$this->assertIdentical($Apple->validates(), false);
		$this->assertIdentical($Apple->validationErrors, array('name' => true));

		$Apple->Behaviors->attach('Test', array('validate' => 'whitelist'));
		$Apple->validates();
		$this->assertIdentical($Apple->whitelist, array());

		$Apple->whitelist = array('unknown');
		$Apple->validates();
		$this->assertIdentical($Apple->whitelist, array('unknown', 'name'));
	}

/**
 * testBehaviorValidateMethods method
 *
 * @access public
 * @return void
 */
	function testBehaviorValidateMethods() {
		$Apple = new Apple();
		$Apple->Behaviors->attach('Test');
		$Apple->validate['color'] = 'validateField';

		$result = $Apple->save(array('name' => 'Genetically Modified Apple', 'color' => 'Orange'));
		$this->assertEqual(array_keys($result['Apple']), array('name', 'color', 'modified', 'created'));

		$Apple->create();
		$result = $Apple->save(array('name' => 'Regular Apple', 'color' => 'Red'));
		$this->assertFalse($result);
	}

/**
 * testBehaviorMethodDispatching method
 *
 * @access public
 * @return void
 */
	function testBehaviorMethodDispatching() {
		$Apple = new Apple();
		$Apple->Behaviors->attach('Test');

		$expected = 'working';
		$this->assertEqual($Apple->testMethod(), $expected);
		$this->assertEqual($Apple->Behaviors->dispatchMethod($Apple, 'testMethod'), $expected);

		$result = $Apple->Behaviors->dispatchMethod($Apple, 'wtf');
		$this->assertEqual($result, array('unhandled'));

		$result = $Apple->{'look for the remote'}('in the couch');
		$expected = "Item.name = 'the remote' AND Location.name = 'the couch'";
		$this->assertEqual($result, $expected);

		$result = $Apple->{'look for THE REMOTE'}('in the couch');
		$expected = "Item.name = 'THE REMOTE' AND Location.name = 'the couch'";
		$this->assertEqual($result, $expected, 'Mapped method was lowercased.');
	}

/**
 * testBehaviorMethodDispatchingWithData method
 *
 * @access public
 * @return void
 */
	function testBehaviorMethodDispatchingWithData() {
		$Apple = new Apple();
		$Apple->Behaviors->attach('Test');

		$Apple->set('field', 'value');
		$this->assertTrue($Apple->testData());
		$this->assertTrue($Apple->data['Apple']['field_2']);

		$this->assertTrue($Apple->testData('one', 'two', 'three', 'four', 'five', 'six'));
	}

/**
 * testBehaviorTrigger method
 *
 * @access public
 * @return void
 */
	function testBehaviorTrigger() {
		$Apple = new Apple();
		$Apple->Behaviors->attach('Test');
		$Apple->Behaviors->attach('Test2');
		$Apple->Behaviors->attach('Test3');

		$Apple->beforeTestResult = array();
		$Apple->Behaviors->trigger('beforeTest', array(&$Apple));
		$expected = array('testbehavior', 'test2behavior', 'test3behavior');
		$this->assertIdentical($Apple->beforeTestResult, $expected);

		$Apple->beforeTestResult = array();
		$Apple->Behaviors->trigger('beforeTest', array(&$Apple), array('break' => true, 'breakOn' => 'test2behavior'));
		$expected = array('testbehavior', 'test2behavior');
		$this->assertIdentical($Apple->beforeTestResult, $expected);

		$Apple->beforeTestResult = array();
		$Apple->Behaviors->trigger('beforeTest', array($Apple), array('break' => true, 'breakOn' => array('test2behavior', 'test3behavior')));
		$expected = array('testbehavior', 'test2behavior');
		$this->assertIdentical($Apple->beforeTestResult, $expected);
	}

/**
 * undocumented function
 *
 * @return void
 */
	public function testBindModelCallsInBehaviors() {
		$this->loadFixtures('Article', 'Comment');

		// hasMany
		$Article = new Article();
		$Article->unbindModel(array('hasMany' => array('Comment')));
		$result = $Article->find('first');
		$this->assertFalse(array_key_exists('Comment', $result));

		$Article->Behaviors->attach('Test4');
		$result = $Article->find('first');
		$this->assertTrue(array_key_exists('Comment', $result));

		// belongsTo
		$Article->unbindModel(array('belongsTo' => array('User')));
		$result = $Article->find('first');
		$this->assertFalse(array_key_exists('User', $result));

		$Article->Behaviors->attach('Test5');
		$result = $Article->find('first');
		$this->assertTrue(array_key_exists('User', $result));

		// hasAndBelongsToMany
		$Article->unbindModel(array('hasAndBelongsToMany' => array('Tag')));
		$result = $Article->find('first');
		$this->assertFalse(array_key_exists('Tag', $result));

		$Article->Behaviors->attach('Test6');
		$result = $Article->find('first');
		$this->assertTrue(array_key_exists('Comment', $result));

		// hasOne
		$Comment = new Comment();
		$Comment->unbindModel(array('hasOne' => array('Attachment')));
		$result = $Comment->find('first');
		$this->assertFalse(array_key_exists('Attachment', $result));

		$Comment->Behaviors->attach('Test7');
		$result = $Comment->find('first');
		$this->assertTrue(array_key_exists('Attachment', $result));
	}

/**
 * Test attach and detaching
 *
 * @access public
 * @return void
 */
	function testBehaviorAttachAndDetach() {
		$Sample = new Sample();
		$Sample->actsAs = array('Test3' => array('bar'), 'Test2' => array('foo', 'bar'));
		$Sample->Behaviors->init($Sample->alias, $Sample->actsAs);
		$Sample->Behaviors->attach('Test2');
		$Sample->Behaviors->detach('Test3');

		$Sample->Behaviors->trigger('beforeTest', array(&$Sample));
	}
}
