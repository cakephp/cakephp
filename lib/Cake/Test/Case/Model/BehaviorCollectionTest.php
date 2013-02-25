<?php
/**
 * BehaviorTest file
 *
 * Long description for behavior.test.php
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Model
 * @since         1.2
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('AppModel', 'Model');
require_once dirname(__FILE__) . DS . 'models.php';

/**
 * TestBehavior class
 *
 * @package       Cake.Test.Case.Model
 */
class TestBehavior extends ModelBehavior {

/**
 * mapMethods property
 *
 * @var array
 */
	public $mapMethods = array('/test(\w+)/' => 'testMethod', '/look for\s+(.+)/' => 'speakEnglish');

/**
 * setup method
 *
 * @param Model $model
 * @param array $config
 * @return void
 */
	public function setup(Model $model, $config = array()) {
		parent::setup($model, $config);
		if (isset($config['mangle'])) {
			$config['mangle'] .= ' mangled';
		}
		$this->settings[$model->alias] = array_merge(array('beforeFind' => 'on', 'afterFind' => 'off'), $config);
	}

/**
 * beforeFind method
 *
 * @param Model $model
 * @param array $query
 * @return void
 */
	public function beforeFind(Model $model, $query) {
		$settings = $this->settings[$model->alias];
		if (!isset($settings['beforeFind']) || $settings['beforeFind'] === 'off') {
			return parent::beforeFind($model, $query);
		}
		switch ($settings['beforeFind']) {
			case 'on':
				return false;
			case 'test':
				return null;
			case 'modify':
				$query['fields'] = array($model->alias . '.id', $model->alias . '.name', $model->alias . '.mytime');
				$query['recursive'] = -1;
				return $query;
		}
	}

/**
 * afterFind method
 *
 * @param Model $model
 * @param array $results
 * @param boolean $primary
 * @return void
 */
	public function afterFind(Model $model, $results, $primary) {
		$settings = $this->settings[$model->alias];
		if (!isset($settings['afterFind']) || $settings['afterFind'] === 'off') {
			return parent::afterFind($model, $results, $primary);
		}
		switch ($settings['afterFind']) {
			case 'on':
				return array();
			case 'test':
				return true;
			case 'test2':
				return null;
			case 'modify':
				return Hash::extract($results, "{n}.{$model->alias}");
		}
	}

/**
 * beforeSave method
 *
 * @param Model $model
 * @return void
 */
	public function beforeSave(Model $model) {
		$settings = $this->settings[$model->alias];
		if (!isset($settings['beforeSave']) || $settings['beforeSave'] === 'off') {
			return parent::beforeSave($model);
		}
		switch ($settings['beforeSave']) {
			case 'on':
				return false;
			case 'test':
				return true;
			case 'modify':
				$model->data[$model->alias]['name'] .= ' modified before';
				return true;
		}
	}

/**
 * afterSave method
 *
 * @param Model $model
 * @param boolean $created
 * @return void
 */
	public function afterSave(Model $model, $created) {
		$settings = $this->settings[$model->alias];
		if (!isset($settings['afterSave']) || $settings['afterSave'] === 'off') {
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
			case 'modify':
				$model->data[$model->alias]['name'] .= ' ' . $string;
			break;
		}
	}

/**
 * beforeValidate method
 *
 * @param Model $model
 * @return void
 */
	public function beforeValidate(Model $model) {
		$settings = $this->settings[$model->alias];
		if (!isset($settings['validate']) || $settings['validate'] === 'off') {
			return parent::beforeValidate($model);
		}
		switch ($settings['validate']) {
			case 'on':
				$model->invalidate('name');
				return true;
			case 'test':
				return null;
			case 'whitelist':
				$this->_addToWhitelist($model, array('name'));
				return true;
			case 'stop':
				$model->invalidate('name');
				return false;
		}
	}

/**
 * afterValidate method
 *
 * @param Model $model
 * @param bool $cascade
 * @return void
 */
	public function afterValidate(Model $model) {
		$settings = $this->settings[$model->alias];
		if (!isset($settings['afterValidate']) || $settings['afterValidate'] === 'off') {
			return parent::afterValidate($model);
		}
		switch ($settings['afterValidate']) {
			case 'on':
				return false;
			case 'test':
				$model->data = array('foo');
				return true;
		}
	}

/**
 * beforeDelete method
 *
 * @param Model $model
 * @param bool $cascade
 * @return void
 */
	public function beforeDelete(Model $model, $cascade = true) {
		$settings = $this->settings[$model->alias];
		if (!isset($settings['beforeDelete']) || $settings['beforeDelete'] === 'off') {
			return parent::beforeDelete($model, $cascade);
		}
		switch ($settings['beforeDelete']) {
			case 'on':
				return false;
			case 'test':
				return null;
			case 'test2':
				echo 'beforeDelete success';
				if ($cascade) {
					echo ' (cascading) ';
				}
				return true;
		}
	}

/**
 * afterDelete method
 *
 * @param Model $model
 * @return void
 */
	public function afterDelete(Model $model) {
		$settings = $this->settings[$model->alias];
		if (!isset($settings['afterDelete']) || $settings['afterDelete'] === 'off') {
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
 * @param Model $model
 * @return void
 */
	public function onError(Model $model, $error) {
		$settings = $this->settings[$model->alias];
		if (!isset($settings['onError']) || $settings['onError'] === 'off') {
			return parent::onError($model, $error);
		}
		echo "onError trigger success";
	}

/**
 * beforeTest method
 *
 * @param Model $model
 * @return void
 */
	public function beforeTest(Model $model) {
		if (!isset($model->beforeTestResult)) {
			$model->beforeTestResult = array();
		}
		$model->beforeTestResult[] = strtolower(get_class($this));
		return strtolower(get_class($this));
	}

/**
 * testMethod method
 *
 * @param Model $model
 * @param bool $param
 * @return void
 */
	public function testMethod(Model $model, $param = true) {
		if ($param === true) {
			return 'working';
		}
	}

/**
 * testData method
 *
 * @param Model $model
 * @return void
 */
	public function testData(Model $model) {
		if (!isset($model->data['Apple']['field'])) {
			return false;
		}
		$model->data['Apple']['field_2'] = true;
		return true;
	}

/**
 * validateField method
 *
 * @param Model $model
 * @param string|array $field
 * @return void
 */
	public function validateField(Model $model, $field) {
		return current($field) === 'Orange';
	}

/**
 * speakEnglish method
 *
 * @param Model $model
 * @param string $method
 * @param string $query
 * @return void
 */
	public function speakEnglish(Model $model, $method, $query) {
		$method = preg_replace('/look for\s+/', 'Item.name = \'', $method);
		$query = preg_replace('/^in\s+/', 'Location.name = \'', $query);
		return $method . '\' AND ' . $query . '\'';
	}

}

/**
 * Test2Behavior class
 *
 * @package       Cake.Test.Case.Model
 */
class Test2Behavior extends TestBehavior {

	public $mapMethods = array('/mappingRobot(\w+)/' => 'mapped');

	public function resolveMethod(Model $model, $stuff) {
	}

	public function mapped(Model $model, $method, $query) {
	}

}

/**
 * Test3Behavior class
 *
 * @package       Cake.Test.Case.Model
 */
class Test3Behavior extends TestBehavior{
}

/**
 * Test4Behavior class
 *
 * @package       Cake.Test.Case.Model
 */
class Test4Behavior extends ModelBehavior{

	public function setup(Model $model, $config = null) {
		$model->bindModel(
			array('hasMany' => array('Comment'))
		);
	}

}

/**
 * Test5Behavior class
 *
 * @package       Cake.Test.Case.Model
 */
class Test5Behavior extends ModelBehavior{

	public function setup(Model $model, $config = null) {
		$model->bindModel(
			array('belongsTo' => array('User'))
		);
	}

}

/**
 * Test6Behavior class
 *
 * @package       Cake.Test.Case.Model
 */
class Test6Behavior extends ModelBehavior{

	public function setup(Model $model, $config = null) {
		$model->bindModel(
			array('hasAndBelongsToMany' => array('Tag'))
		);
	}

}

/**
 * Test7Behavior class
 *
 * @package       Cake.Test.Case.Model
 */
class Test7Behavior extends ModelBehavior{

	public function setup(Model $model, $config = null) {
		$model->bindModel(
			array('hasOne' => array('Attachment'))
		);
	}

}

/**
 * Extended TestBehavior
 */
class TestAliasBehavior extends TestBehavior {
}

/**
 * FirstBehavior
 */
class FirstBehavior extends ModelBehavior {

	public function beforeFind(Model $model, $query = array()) {
		$model->called[] = get_class($this);
		return $query;
	}

}

/**
 * SecondBehavior
 */
class SecondBehavior extends FirstBehavior {
}

/**
 * ThirdBehavior
 */
class ThirdBehavior extends FirstBehavior {
}

/**
 * Orangutan Model
 */
class Orangutan extends Monkey {

	public $called = array();

}

/**
 * BehaviorCollection class
 *
 * @package       Cake.Test.Case.Model
 */
class BehaviorCollectionTest extends CakeTestCase {

/**
 * fixtures property
 *
 * @var array
 */
	public $fixtures = array(
		'core.apple', 'core.sample', 'core.article', 'core.user', 'core.comment',
		'core.attachment', 'core.tag', 'core.articles_tag', 'core.translate',
		'core.device'
	);

/**
 * Test load() with enabled => false
 *
 */
	public function testLoadDisabled() {
		$Apple = new Apple();
		$this->assertSame(array(), $Apple->Behaviors->loaded());

		$Apple->Behaviors->load('Translate', array('enabled' => false));
		$this->assertTrue($Apple->Behaviors->loaded('Translate'));
		$this->assertFalse($Apple->Behaviors->enabled('Translate'));
	}

/**
 * Tests loading aliased behaviors
 */
	public function testLoadAlias() {
		$Apple = new Apple();
		$this->assertSame(array(), $Apple->Behaviors->loaded());

		$Apple->Behaviors->load('Test', array('className' => 'TestAlias', 'somesetting' => true));
		$this->assertSame(array('Test'), $Apple->Behaviors->loaded());
		$this->assertInstanceOf('TestAliasBehavior', $Apple->Behaviors->Test);
		$this->assertTrue($Apple->Behaviors->Test->settings['Apple']['somesetting']);

		$this->assertEquals('working', $Apple->Behaviors->Test->testMethod($Apple, true));
		$this->assertEquals('working', $Apple->testMethod(true));
		$this->assertEquals('working', $Apple->Behaviors->dispatchMethod($Apple, 'testMethod'));

		App::build(array('Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)));
		CakePlugin::load('TestPlugin');
		$this->assertTrue($Apple->Behaviors->load('SomeOther', array('className' => 'TestPlugin.TestPluginPersisterOne')));
		$this->assertInstanceOf('TestPluginPersisterOneBehavior', $Apple->Behaviors->SomeOther);

		$result = $Apple->Behaviors->loaded();
		$this->assertEquals(array('Test', 'SomeOther'), $result, 'loaded() results are wrong.');
		App::build();
		CakePlugin::unload();
	}

/**
 * testBehaviorBinding method
 *
 * @return void
 */
	public function testBehaviorBinding() {
		$Apple = new Apple();
		$this->assertSame(array(), $Apple->Behaviors->loaded());

		$Apple->Behaviors->attach('Test', array('key' => 'value'));
		$this->assertSame(array('Test'), $Apple->Behaviors->loaded());
		$this->assertEquals('testbehavior', strtolower(get_class($Apple->Behaviors->Test)));
		$expected = array('beforeFind' => 'on', 'afterFind' => 'off', 'key' => 'value');
		$this->assertEquals($expected, $Apple->Behaviors->Test->settings['Apple']);
		$this->assertEquals(array('priority', 'Apple'), array_keys($Apple->Behaviors->Test->settings));

		$this->assertSame($Apple->Sample->Behaviors->loaded(), array());
		$Apple->Sample->Behaviors->attach('Test', array('key2' => 'value2'));
		$this->assertSame($Apple->Sample->Behaviors->loaded(), array('Test'));
		$this->assertEquals(array('beforeFind' => 'on', 'afterFind' => 'off', 'key2' => 'value2'), $Apple->Sample->Behaviors->Test->settings['Sample']);

		$this->assertEquals(array('priority', 'Apple', 'Sample'), array_keys($Apple->Behaviors->Test->settings));
		$this->assertSame(
			$Apple->Sample->Behaviors->Test->settings,
			$Apple->Behaviors->Test->settings
		);
		$this->assertNotSame($Apple->Behaviors->Test->settings['Apple'], $Apple->Sample->Behaviors->Test->settings['Sample']);

		$Apple->Behaviors->attach('Test', array('key2' => 'value2', 'key3' => 'value3', 'beforeFind' => 'off'));
		$Apple->Sample->Behaviors->attach('Test', array('key' => 'value', 'key3' => 'value3', 'beforeFind' => 'off'));
		$this->assertEquals(array('beforeFind' => 'off', 'afterFind' => 'off', 'key' => 'value', 'key2' => 'value2', 'key3' => 'value3'), $Apple->Behaviors->Test->settings['Apple']);
		$this->assertEquals($Apple->Behaviors->Test->settings['Apple'], $Apple->Sample->Behaviors->Test->settings['Sample']);

		$this->assertFalse(isset($Apple->Child->Behaviors->Test));
		$Apple->Child->Behaviors->attach('Test', array('key' => 'value', 'key2' => 'value2', 'key3' => 'value3', 'beforeFind' => 'off'));
		$this->assertEquals($Apple->Child->Behaviors->Test->settings['Child'], $Apple->Sample->Behaviors->Test->settings['Sample']);

		$this->assertFalse(isset($Apple->Parent->Behaviors->Test));
		$Apple->Parent->Behaviors->attach('Test', array('key' => 'value', 'key2' => 'value2', 'key3' => 'value3', 'beforeFind' => 'off'));
		$this->assertEquals($Apple->Parent->Behaviors->Test->settings['Parent'], $Apple->Sample->Behaviors->Test->settings['Sample']);

		$Apple->Parent->Behaviors->attach('Test', array('key' => 'value', 'key2' => 'value', 'key3' => 'value', 'beforeFind' => 'off'));
		$this->assertNotEquals($Apple->Parent->Behaviors->Test->settings['Parent'], $Apple->Sample->Behaviors->Test->settings['Sample']);

		$Apple->Behaviors->attach('Plugin.Test', array('key' => 'new value'));
		$expected = array(
			'beforeFind' => 'off', 'afterFind' => 'off', 'key' => 'new value',
			'key2' => 'value2', 'key3' => 'value3'
		);
		$this->assertEquals($expected, $Apple->Behaviors->Test->settings['Apple']);

		$current = $Apple->Behaviors->Test->settings['Apple'];
		$expected = array_merge($current, array('mangle' => 'trigger mangled'));
		$Apple->Behaviors->attach('Test', array('mangle' => 'trigger'));
		$this->assertEquals($expected, $Apple->Behaviors->Test->settings['Apple']);

		$Apple->Behaviors->attach('Test');
		$expected = array_merge($current, array('mangle' => 'trigger mangled mangled'));

		$this->assertEquals($expected, $Apple->Behaviors->Test->settings['Apple']);
		$Apple->Behaviors->attach('Test', array('mangle' => 'trigger'));
		$expected = array_merge($current, array('mangle' => 'trigger mangled'));
		$this->assertEquals($expected, $Apple->Behaviors->Test->settings['Apple']);
	}

/**
 * test that attach()/detach() works with plugin.banana
 *
 * @return void
 */
	public function testDetachWithPluginNames() {
		$Apple = new Apple();
		$Apple->Behaviors->attach('Plugin.Test');
		$this->assertTrue(isset($Apple->Behaviors->Test), 'Missing behavior');
		$this->assertEquals(array('Test'), $Apple->Behaviors->loaded());

		$Apple->Behaviors->detach('Plugin.Test');
		$this->assertEquals(array(), $Apple->Behaviors->loaded());

		$Apple->Behaviors->attach('Plugin.Test');
		$this->assertTrue(isset($Apple->Behaviors->Test), 'Missing behavior');
		$this->assertEquals(array('Test'), $Apple->Behaviors->loaded());

		$Apple->Behaviors->detach('Test');
		$this->assertEquals(array(), $Apple->Behaviors->loaded());
	}

/**
 * test that attaching a non existent Behavior triggers a cake error.
 *
 * @expectedException MissingBehaviorException
 * @return void
 */
	public function testInvalidBehaviorCausingCakeError() {
		$Apple = new Apple();
		$Apple->Behaviors->attach('NoSuchBehavior');
	}

/**
 * testBehaviorToggling method
 *
 * @return void
 */
	public function testBehaviorToggling() {
		$Apple = new Apple();
		$this->assertSame($Apple->Behaviors->enabled(), array());

		$Apple->Behaviors->init('Apple', array('Test' => array('key' => 'value')));
		$this->assertSame($Apple->Behaviors->enabled(), array('Test'));

		$Apple->Behaviors->disable('Test');
		$this->assertSame(array('Test'), $Apple->Behaviors->loaded());
		$this->assertSame($Apple->Behaviors->enabled(), array());

		$Apple->Sample->Behaviors->attach('Test');
		$this->assertSame($Apple->Sample->Behaviors->enabled('Test'), true);
		$this->assertSame($Apple->Behaviors->enabled(), array());

		$Apple->Behaviors->enable('Test');
		$this->assertSame($Apple->Behaviors->loaded('Test'), true);
		$this->assertSame($Apple->Behaviors->enabled(), array('Test'));

		$Apple->Behaviors->disable('Test');
		$this->assertSame($Apple->Behaviors->enabled(), array());
		$Apple->Behaviors->attach('Test', array('enabled' => true));
		$this->assertSame($Apple->Behaviors->enabled(), array('Test'));
		$Apple->Behaviors->attach('Test', array('enabled' => false));
		$this->assertSame($Apple->Behaviors->enabled(), array());
		$Apple->Behaviors->detach('Test');
		$this->assertSame($Apple->Behaviors->enabled(), array());
	}

/**
 * testBehaviorFindCallbacks method
 *
 * @return void
 */
	public function testBehaviorFindCallbacks() {
		$this->skipIf($this->db instanceof Sqlserver, 'This test is not compatible with SQL Server.');

		$Apple = new Apple();
		$expected = $Apple->find('all');

		$Apple->Behaviors->attach('Test');
		$this->assertSame($Apple->find('all'), null);

		$Apple->Behaviors->attach('Test', array('beforeFind' => 'off'));
		$this->assertSame($expected, $Apple->find('all'));

		$Apple->Behaviors->attach('Test', array('beforeFind' => 'test'));
		$this->assertSame($expected, $Apple->find('all'));

		$Apple->Behaviors->attach('Test', array('beforeFind' => 'modify'));
		$expected2 = array(
			array('Apple' => array('id' => '1', 'name' => 'Red Apple 1', 'mytime' => '22:57:17')),
			array('Apple' => array('id' => '2', 'name' => 'Bright Red Apple', 'mytime' => '22:57:17')),
			array('Apple' => array('id' => '3', 'name' => 'green blue', 'mytime' => '22:57:17'))
		);
		$result = $Apple->find('all', array('conditions' => array('Apple.id <' => '4')));
		$this->assertEquals($expected2, $result);

		$Apple->Behaviors->disable('Test');
		$result = $Apple->find('all');
		$this->assertEquals($expected, $result);

		$Apple->Behaviors->attach('Test', array('beforeFind' => 'off', 'afterFind' => 'on'));
		$this->assertSame($Apple->find('all'), array());

		$Apple->Behaviors->attach('Test', array('afterFind' => 'off'));
		$this->assertEquals($expected, $Apple->find('all'));

		$Apple->Behaviors->attach('Test', array('afterFind' => 'test'));
		$this->assertEquals($expected, $Apple->find('all'));

		$Apple->Behaviors->attach('Test', array('afterFind' => 'test2'));
		$this->assertEquals($expected, $Apple->find('all'));

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
		$this->assertEquals($expected, $Apple->find('all'));
	}

/**
 * testBehaviorHasManyFindCallbacks method
 *
 * @return void
 */
	public function testBehaviorHasManyFindCallbacks() {
		$Apple = new Apple();
		$Apple->unbindModel(array('hasOne' => array('Sample'), 'belongsTo' => array('Parent')), false);
		$expected = $Apple->find('all');

		$Apple->unbindModel(array('hasMany' => array('Child')));
		$wellBehaved = $Apple->find('all');
		$Apple->Child->Behaviors->attach('Test', array('afterFind' => 'modify'));
		$Apple->unbindModel(array('hasMany' => array('Child')));
		$this->assertSame($Apple->find('all'), $wellBehaved);

		$Apple->Child->Behaviors->attach('Test', array('before' => 'off'));
		$this->assertSame($expected, $Apple->find('all'));

		$Apple->Child->Behaviors->attach('Test', array('before' => 'test'));
		$this->assertSame($expected, $Apple->find('all'));

		$Apple->Child->Behaviors->attach('Test', array('before' => 'modify'));
		$result = $Apple->find('all', array('fields' => array('Apple.id'), 'conditions' => array('Apple.id <' => '4')));

		$Apple->Child->Behaviors->disable('Test');
		$result = $Apple->find('all');
		$this->assertEquals($expected, $result);

		$Apple->Child->Behaviors->attach('Test', array('before' => 'off', 'after' => 'on'));

		$Apple->Child->Behaviors->attach('Test', array('after' => 'off'));
		$this->assertEquals($expected, $Apple->find('all'));

		$Apple->Child->Behaviors->attach('Test', array('after' => 'test'));
		$this->assertEquals($expected, $Apple->find('all'));

		$Apple->Child->Behaviors->attach('Test', array('after' => 'test2'));
		$this->assertEquals($expected, $Apple->find('all'));
	}

/**
 * testBehaviorHasOneFindCallbacks method
 *
 * @return void
 */
	public function testBehaviorHasOneFindCallbacks() {
		$Apple = new Apple();
		$Apple->unbindModel(array('hasMany' => array('Child'), 'belongsTo' => array('Parent')), false);
		$expected = $Apple->find('all');

		$Apple->unbindModel(array('hasOne' => array('Sample')));
		$wellBehaved = $Apple->find('all');
		$Apple->Sample->Behaviors->attach('Test');
		$Apple->unbindModel(array('hasOne' => array('Sample')));
		$this->assertSame($Apple->find('all'), $wellBehaved);

		$Apple->Sample->Behaviors->attach('Test', array('before' => 'off'));
		$this->assertSame($expected, $Apple->find('all'));

		$Apple->Sample->Behaviors->attach('Test', array('before' => 'test'));
		$this->assertSame($expected, $Apple->find('all'));

		$Apple->Sample->Behaviors->disable('Test');
		$result = $Apple->find('all');
		$this->assertEquals($expected, $result);

		$Apple->Sample->Behaviors->attach('Test', array('after' => 'off'));
		$this->assertEquals($expected, $Apple->find('all'));

		$Apple->Sample->Behaviors->attach('Test', array('after' => 'test'));
		$this->assertEquals($expected, $Apple->find('all'));

		$Apple->Sample->Behaviors->attach('Test', array('after' => 'test2'));
		$this->assertEquals($expected, $Apple->find('all'));
	}

/**
 * testBehaviorBelongsToFindCallbacks method
 *
 * @return void
 */
	public function testBehaviorBelongsToFindCallbacks() {
		$this->skipIf($this->db instanceof Sqlserver, 'This test is not compatible with SQL Server.');

		$conditions = array('order' => 'Apple.id ASC');
		$Apple = new Apple();
		$Apple->unbindModel(array('hasMany' => array('Child'), 'hasOne' => array('Sample')), false);
		$expected = $Apple->find('all', $conditions);

		$Apple->unbindModel(array('belongsTo' => array('Parent')));
		$wellBehaved = $Apple->find('all', $conditions);
		$Apple->Parent->Behaviors->attach('Test');
		$Apple->unbindModel(array('belongsTo' => array('Parent')));
		$this->assertSame($Apple->find('all', $conditions), $wellBehaved);

		$Apple->Parent->Behaviors->attach('Test', array('before' => 'off'));
		$this->assertSame($expected, $Apple->find('all', $conditions));

		$Apple->Parent->Behaviors->attach('Test', array('before' => 'test'));
		$this->assertSame($expected, $Apple->find('all', $conditions));

		$Apple->Parent->Behaviors->attach('Test', array('before' => 'modify'));
		$expected2 = array(
			array(
				'Apple' => array('id' => 1),
				'Parent' => array('id' => 2, 'name' => 'Bright Red Apple', 'mytime' => '22:57:17')),
			array(
				'Apple' => array('id' => 2),
				'Parent' => array('id' => 1, 'name' => 'Red Apple 1', 'mytime' => '22:57:17')),
			array(
				'Apple' => array('id' => 3),
				'Parent' => array('id' => 2, 'name' => 'Bright Red Apple', 'mytime' => '22:57:17'))
		);
		$result2 = $Apple->find('all', array(
			'fields' => array('Apple.id', 'Parent.id', 'Parent.name', 'Parent.mytime'),
			'conditions' => array('Apple.id <' => '4'),
			'order' => 'Apple.id ASC',
		));
		$this->assertEquals($expected2, $result2);

		$Apple->Parent->Behaviors->disable('Test');
		$result = $Apple->find('all', $conditions);
		$this->assertEquals($expected, $result);

		$Apple->Parent->Behaviors->attach('Test', array('after' => 'off'));
		$this->assertEquals($expected, $Apple->find('all', $conditions));

		$Apple->Parent->Behaviors->attach('Test', array('after' => 'test'));
		$this->assertEquals($expected, $Apple->find('all', $conditions));

		$Apple->Parent->Behaviors->attach('Test', array('after' => 'test2'));
		$this->assertEquals($expected, $Apple->find('all', $conditions));
	}

/**
 * testBehaviorSaveCallbacks method
 *
 * @return void
 */
	public function testBehaviorSaveCallbacks() {
		$Sample = new Sample();
		$record = array('Sample' => array('apple_id' => 6, 'name' => 'sample99'));

		$Sample->Behaviors->attach('Test', array('beforeSave' => 'on'));
		$Sample->create();
		$this->assertSame(false, $Sample->save($record));

		$Sample->Behaviors->attach('Test', array('beforeSave' => 'off'));
		$Sample->create();
		$result = $Sample->save($record);
		$expected = $record;
		$expected['Sample']['id'] = $Sample->id;
		$this->assertSame($expected, $result);

		$Sample->Behaviors->attach('Test', array('beforeSave' => 'test'));
		$Sample->create();
		$result = $Sample->save($record);
		$expected = $record;
		$expected['Sample']['id'] = $Sample->id;
		$this->assertSame($expected, $result);

		$Sample->Behaviors->attach('Test', array('beforeSave' => 'modify'));
		$expected = Hash::insert($record, 'Sample.name', 'sample99 modified before');
		$Sample->create();
		$result = $Sample->save($record);
		$expected['Sample']['id'] = $Sample->id;
		$this->assertSame($expected, $result);

		$Sample->Behaviors->disable('Test');
		$this->assertSame($record, $Sample->save($record));

		$Sample->Behaviors->attach('Test', array('beforeSave' => 'off', 'afterSave' => 'on'));
		$expected = Hash::merge($record, array('Sample' => array('aftersave' => 'modified after on create')));
		$Sample->create();
		$result = $Sample->save($record);
		$expected['Sample']['id'] = $Sample->id;
		$this->assertEquals($expected, $result);

		$Sample->Behaviors->attach('Test', array('beforeSave' => 'modify', 'afterSave' => 'modify'));
		$expected = Hash::merge($record, array('Sample' => array('name' => 'sample99 modified before modified after on create')));
		$Sample->create();
		$result = $Sample->save($record);
		$expected['Sample']['id'] = $Sample->id;
		$this->assertSame($expected, $result);

		$Sample->Behaviors->attach('Test', array('beforeSave' => 'off', 'afterSave' => 'test'));
		$Sample->create();
		$expected = $record;
		unset($expected['Sample']['name']);
		$result = $Sample->save($record);
		$expected['Sample']['id'] = $Sample->id;
		$this->assertSame($expected, $result);

		$Sample->Behaviors->attach('Test', array('afterSave' => 'test2'));
		$Sample->create();
		$expected = $record;
		$result = $Sample->save($record);
		$expected['Sample']['id'] = $Sample->id;
		$this->assertSame($expected, $result);

		$Sample->Behaviors->attach('Test', array('beforeFind' => 'off', 'afterFind' => 'off'));
		$Sample->recursive = -1;
		$record2 = $Sample->read(null, 1);

		$Sample->Behaviors->attach('Test', array('afterSave' => 'on'));
		$expected = Hash::merge($record2, array('Sample' => array('aftersave' => 'modified after')));
		$Sample->create();
		$this->assertSame($expected, $Sample->save($record2));

		$Sample->Behaviors->attach('Test', array('afterSave' => 'modify'));
		$expected = Hash::merge($record2, array('Sample' => array('name' => 'sample1 modified after')));
		$Sample->create();
		$this->assertSame($expected, $Sample->save($record2));
	}

/**
 * testBehaviorDeleteCallbacks method
 *
 * @return void
 */
	public function testBehaviorDeleteCallbacks() {
		$Apple = new Apple();

		$Apple->Behaviors->attach('Test', array('beforeFind' => 'off', 'beforeDelete' => 'off'));
		$this->assertSame($Apple->delete(6), true);

		$Apple->Behaviors->attach('Test', array('beforeDelete' => 'on'));
		$this->assertSame($Apple->delete(4), false);

		$Apple->Behaviors->attach('Test', array('beforeDelete' => 'test2'));

		ob_start();
		$results = $Apple->delete(4);
		$this->assertSame(trim(ob_get_clean()), 'beforeDelete success (cascading)');
		$this->assertSame($results, true);

		ob_start();
		$results = $Apple->delete(3, false);
		$this->assertSame(trim(ob_get_clean()), 'beforeDelete success');
		$this->assertSame($results, true);

		$Apple->Behaviors->attach('Test', array('beforeDelete' => 'off', 'afterDelete' => 'on'));
		ob_start();
		$results = $Apple->delete(2, false);
		$this->assertSame(trim(ob_get_clean()), 'afterDelete success');
		$this->assertSame($results, true);
	}

/**
 * testBehaviorOnErrorCallback method
 *
 * @return void
 */
	public function testBehaviorOnErrorCallback() {
		$Apple = new Apple();

		$Apple->Behaviors->attach('Test', array('beforeFind' => 'off', 'onError' => 'on'));
		ob_start();
		$Apple->Behaviors->Test->onError($Apple, '');
		$this->assertSame(trim(ob_get_clean()), 'onError trigger success');
	}

/**
 * testBehaviorValidateCallback method
 *
 * @return void
 */
	public function testBehaviorValidateCallback() {
		$Apple = new Apple();

		$Apple->Behaviors->attach('Test');
		$this->assertSame($Apple->validates(), true);

		$Apple->Behaviors->attach('Test', array('validate' => 'on'));
		$this->assertSame($Apple->validates(), false);
		$this->assertSame($Apple->validationErrors, array('name' => array(true)));

		$Apple->Behaviors->attach('Test', array('validate' => 'stop'));
		$this->assertSame($Apple->validates(), false);
		$this->assertSame($Apple->validationErrors, array('name' => array(true, true)));

		$Apple->Behaviors->attach('Test', array('validate' => 'whitelist'));
		$Apple->validates();
		$this->assertSame($Apple->whitelist, array());

		$Apple->whitelist = array('unknown');
		$Apple->validates();
		$this->assertSame($Apple->whitelist, array('unknown', 'name'));
	}

/**
 * testBehaviorValidateAfterCallback method
 *
 * @return void
 */
	public function testBehaviorValidateAfterCallback() {
		$Apple = new Apple();

		$Apple->Behaviors->attach('Test');
		$this->assertSame($Apple->validates(), true);

		$Apple->Behaviors->attach('Test', array('afterValidate' => 'on'));
		$this->assertSame($Apple->validates(), true);
		$this->assertSame($Apple->validationErrors, array());

		$Apple->Behaviors->attach('Test', array('afterValidate' => 'test'));
		$Apple->data = array('bar');
		$Apple->validates();
		$this->assertEquals(array('foo'), $Apple->data);
	}

/**
 * testBehaviorValidateMethods method
 *
 * @return void
 */
	public function testBehaviorValidateMethods() {
		$Apple = new Apple();
		$Apple->Behaviors->attach('Test');
		$Apple->validate['color'] = 'validateField';

		$result = $Apple->save(array('name' => 'Genetically Modified Apple', 'color' => 'Orange'));
		$this->assertEquals(array('name', 'color', 'modified', 'created', 'id'), array_keys($result['Apple']));

		$Apple->create();
		$result = $Apple->save(array('name' => 'Regular Apple', 'color' => 'Red'));
		$this->assertFalse($result);
	}

/**
 * testBehaviorMethodDispatching method
 *
 * @return void
 */
	public function testBehaviorMethodDispatching() {
		$Apple = new Apple();
		$Apple->Behaviors->attach('Test');

		$expected = 'working';
		$this->assertEquals($expected, $Apple->testMethod());
		$this->assertEquals($expected, $Apple->Behaviors->dispatchMethod($Apple, 'testMethod'));

		$result = $Apple->Behaviors->dispatchMethod($Apple, 'wtf');
		$this->assertEquals(array('unhandled'), $result);

		$result = $Apple->{'look for the remote'}('in the couch');
		$expected = "Item.name = 'the remote' AND Location.name = 'the couch'";
		$this->assertEquals($expected, $result);

		$result = $Apple->{'look for THE REMOTE'}('in the couch');
		$expected = "Item.name = 'THE REMOTE' AND Location.name = 'the couch'";
		$this->assertEquals($expected, $result, 'Mapped method was lowercased.');
	}

/**
 * testBehaviorMethodDispatchingWithData method
 *
 * @return void
 */
	public function testBehaviorMethodDispatchingWithData() {
		$Apple = new Apple();
		$Apple->Behaviors->attach('Test');

		$Apple->set('field', 'value');
		$this->assertTrue($Apple->testData());
		$this->assertTrue($Apple->data['Apple']['field_2']);

		$this->assertTrue($Apple->testData('one', 'two', 'three', 'four', 'five', 'six'));
	}

/**
 * undocumented function
 *
 * @return void
 */
	public function testBindModelCallsInBehaviors() {
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
 * @return void
 */
	public function testBehaviorAttachAndDetach() {
		$Sample = new Sample();
		$Sample->actsAs = array('Test3' => array('bar'), 'Test2' => array('foo', 'bar'));
		$Sample->Behaviors->init($Sample->alias, $Sample->actsAs);
		$Sample->Behaviors->attach('Test2');
		$Sample->Behaviors->detach('Test3');

		$Sample->Behaviors->trigger('beforeTest', array(&$Sample));
	}

/**
 * test that hasMethod works with basic functions.
 *
 * @return void
 */
	public function testHasMethodBasic() {
		new Sample();
		$Collection = new BehaviorCollection();
		$Collection->init('Sample', array('Test', 'Test2'));

		$this->assertTrue($Collection->hasMethod('testMethod'));
		$this->assertTrue($Collection->hasMethod('resolveMethod'));

		$this->assertFalse($Collection->hasMethod('No method'));
	}

/**
 * test that hasMethod works with mapped methods.
 *
 * @return void
 */
	public function testHasMethodMappedMethods() {
		new Sample();
		$Collection = new BehaviorCollection();
		$Collection->init('Sample', array('Test', 'Test2'));

		$this->assertTrue($Collection->hasMethod('look for the remote in the couch'));
		$this->assertTrue($Collection->hasMethod('mappingRobotOnTheRoof'));
	}

/**
 * test hasMethod returning a 'callback'
 *
 * @return void
 */
	public function testHasMethodAsCallback() {
		new Sample();
		$Collection = new BehaviorCollection();
		$Collection->init('Sample', array('Test', 'Test2'));

		$result = $Collection->hasMethod('testMethod', true);
		$expected = array('Test', 'testMethod');
		$this->assertEquals($expected, $result);

		$result = $Collection->hasMethod('resolveMethod', true);
		$expected = array('Test2', 'resolveMethod');
		$this->assertEquals($expected, $result);

		$result = $Collection->hasMethod('mappingRobotOnTheRoof', true);
		$expected = array('Test2', 'mapped', 'mappingRobotOnTheRoof');
		$this->assertEquals($expected, $result);
	}

/**
 * Test that behavior priority
 */
	public function testBehaviorOrderCallbacks() {
		$model = ClassRegistry::init('Orangutan');
		$model->Behaviors->init('Orangutan', array(
			'Second' => array('priority' => 9),
			'Third',
			'First' => array('priority' => 8),
		));

		$this->assertEmpty($model->called);

		$model->find('first');
		$expected = array(
			'FirstBehavior',
			'SecondBehavior',
			'ThirdBehavior',
		);
		$this->assertEquals($expected, $model->called);

		$model->called = array();
		$model->Behaviors->load('Third', array('priority' => 1));

		$model->find('first');
		$expected = array(
			'ThirdBehavior',
			'FirstBehavior',
			'SecondBehavior'
		);
		$this->assertEquals($expected, $model->called);

		$model->called = array();
		$model->Behaviors->load('First');

		$model->find('first');
		$expected = array(
			'ThirdBehavior',
			'SecondBehavior',
			'FirstBehavior'
		);
		$this->assertEquals($expected, $model->called);

		$model->called = array();
		$model->Behaviors->unload('Third');

		$model->find('first');
		$expected = array(
			'SecondBehavior',
			'FirstBehavior'
		);
		$this->assertEquals($expected, $model->called);

		$model->called = array();
		$model->Behaviors->disable('Second');

		$model->find('first');
		$expected = array(
			'FirstBehavior'
		);
		$this->assertEquals($expected, $model->called);

		$model->called = array();
		$model->Behaviors->enable('Second');

		$model->find('first');
		$expected = array(
			'SecondBehavior',
			'FirstBehavior'
		);
		$this->assertEquals($expected, $model->called);
	}

}
