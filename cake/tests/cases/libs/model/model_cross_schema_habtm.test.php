<?php

require_once dirname(__FILE__) . DS . 'model.test.php';
App::import('Core', 'DboSource');

/**
 * Tests cross database HABTM.  Requires $test and $test2 to both be set in DATABASE_CONFIG
 * NOTE: When testing on MySQL, you must set 'persistent' => false on *both* database connections,
 * or one connection will step on the other.
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.operations
 */
class ModelCrossSchemaHabtmTest extends BaseModelTest {

	var $fixtures = array('core.something', 'core.something_else', 'core.join_thing');

	var $autoFixtures = false;

	var $dropTables = false;

	var $skip = false;

	function start() {
		parent::start();
		$this->_checkConfigs();
		$this->_setupFixtures();
	}

	function end() {
		$this->_cleanupFixtures();
		parent::end();
	}

	function _checkConfigs() {
		$config = new DATABASE_CONFIG();

		$this->skip = $this->skipIf(
			!isset($config->test) || !isset($config->test2),
			 '%s Primary and secondary test databases not configured, skipping cross-database '
			.'join tests.'
			.' To run these tests, you must define $test and $test2 in your database configuration.'
		);
	}

	function _setupFixtures() {
		if ($this->skip) { return; }
		$db  =& ConnectionManager::getDataSource('test');
		$db2 =& ConnectionManager::getDataSource('test2');
		$this->_fixtures[$this->_fixtureClassMap['Something']]->drop($db);
		$this->_fixtures[$this->_fixtureClassMap['Something']]->create($db);
		$this->_fixtures[$this->_fixtureClassMap['Something']]->insert($db);
		$this->_fixtures[$this->_fixtureClassMap['SomethingElse']]->drop($db);
		$this->_fixtures[$this->_fixtureClassMap['SomethingElse']]->create($db);
		$this->_fixtures[$this->_fixtureClassMap['SomethingElse']]->insert($db);
		$this->_fixtures[$this->_fixtureClassMap['JoinThing']]->db = $db2;
		$this->_fixtures[$this->_fixtureClassMap['JoinThing']]->drop($db2);
		$this->_fixtures[$this->_fixtureClassMap['JoinThing']]->create($db2);
		$this->_fixtures[$this->_fixtureClassMap['JoinThing']]->insert($db2);
	}

	function _cleanupFixtures() {
		if ($this->skip) { return; }
		$db  =& ConnectionManager::getDataSource('test');
		$db2 =& ConnectionManager::getDataSource('test2');
		$this->_fixtures[$this->_fixtureClassMap['Something']]->drop($db);
		$this->_fixtures[$this->_fixtureClassMap['SomethingElse']]->drop($db);
		$this->_fixtures[$this->_fixtureClassMap['JoinThing']]->drop($db2);
	}

	function testHabtmFind() {
		if ($this->skip) { return; }

		$this->_setupFixtures();

		$Something =& ClassRegistry::init(array('class' => 'JoinThing', 'ds' => 'test2'));
		$Something =& ClassRegistry::init('Something');
		$Something->Behaviors->attach('Containable');
		$Something->SomethingElse->Behaviors->attach('Containable');
		$Something->SomethingElse->JoinThing->Behaviors->attach('Containable');

		$count = $Something->JoinThing->find('count');
		$this->assertEqual(3, $count);

		$expected = array(
			array(
				'Something' => array(
					'id' => 1,
					'title' => 'First Post',
					),
				'SomethingElse' => array(
					array(
						'id' => 2,
						'title' => 'Second Post',
						'JoinThing' => array(
							'doomed' => 1,
							'something_id' => 1,
							'something_else_id' => 2,
							),
						),
					),
				),
			);
		$options = array(
			'conditions' => array('Something.id' => 1),
			'fields' => array('id', 'title'),
			'contain' => array(
				'SomethingElse' => array(
					'fields' => array('id', 'title'),
					'JoinThing' =>  array(
						'fields' => array('something_id', 'something_else_id'),
						),
					),
				),
			);
		$results = $Something->find('all', $options);
		$this->assertEqual($results, $expected);
	}


	function testHabtmSave() {
		if ($this->skip) { return; }

		$this->_setupFixtures();

		$Something =& ClassRegistry::init(array('class' => 'JoinThing', 'ds' => 'test2'));
		$Something =& ClassRegistry::init('Something');
		$count = $Something->JoinThing->find('count');
		$this->assertEqual(3, $count);

		$data = $Something->create(array(
			'Something' => array(
				'id' => 1,
				),
			'SomethingElse' => array(
				'SomethingElse' => array(2,3)
				)
			));

		$results = $Something->saveAll($data, array('validate' => 'first'));

		$this->assertNotEqual($results, false);

		$count = $Something->JoinThing->find('count');
		$this->assertEqual(4, $count);

		$Something->Behaviors->attach('Containable');
		$Something->SomethingElse->Behaviors->attach('Containable');
		$Something->SomethingElse->JoinThing->Behaviors->attach('Containable');
		$expected = array(
			array(
				'Something' => array(
					'id' => 1,
					'title' => 'First Post',
					),
				'SomethingElse' => array(
					array(
						'id' => 2,
						'title' => 'Second Post',
						'JoinThing' => array(
							'doomed' => 0,
							'something_id' => 1,
							'something_else_id' => 2,
							),
						),
					array(
						'id' => 3,
						'title' => 'Third Post',
						'JoinThing' => array(
							'doomed' => 0,
							'something_id' => 1,
							'something_else_id' => 3,
							),
						),
					),
				),
			);
		$options = array(
			'conditions' => array('Something.id' => 1),
			'fields' => array('id', 'title'),
			'contain' => array(
				'SomethingElse' => array(
					'fields' => array('id', 'title'),
					'JoinThing' =>  array(
						'fields' => array('something_id', 'something_else_id'),
						),
					),
				),
			);
		$results = $Something->find('all', $options);
		$this->assertEqual($results, $expected);
	}

}
