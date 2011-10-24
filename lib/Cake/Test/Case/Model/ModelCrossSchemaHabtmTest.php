<?php

require_once dirname(__FILE__) . DS . 'ModelTestBase.php';
App::uses('DboSource', 'Database');

/**
 * Tests cross database HABTM.  Requires $test and $test2 to both be set in DATABASE_CONFIG
 * NOTE: When testing on MySQL, you must set 'persistent' => false on *both* database connections,
 * or one connection will step on the other.
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.operations
 */
class ModelCrossSchemaHabtmTest extends BaseModelTest {

	public $fixtures = array('core.something', 'core.something_else', 'core.join_thing');

	public $autoFixtures = false;

	public $dropTables = false;

	protected $skip = false;

	function setUp() {
		parent::setUp();
		$this->_checkConfigs();
		$this->_setupFixtures();
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
		$this->fixtureManager->loadSingle('Something');
		$this->fixtureManager->loadSingle('SomethingElse');
		$this->fixtureManager->loadSingle('JoinThing', $db2);
	}

	function testHabtmFind() {
		if ($this->skip) { return; }

		$this->_setupFixtures();

		$JoinThing =& ClassRegistry::init(array('class' => 'JoinThing', 'ds' => 'test2'));
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
							'doomed' => true,
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
							'doomed' => false,
							'something_id' => 1,
							'something_else_id' => 2,
							),
						),
					array(
						'id' => 3,
						'title' => 'Third Post',
						'JoinThing' => array(
							'doomed' => false,
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
