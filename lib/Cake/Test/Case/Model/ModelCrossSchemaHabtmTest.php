<?php
/**
 * Tests cross database HABTM. Requires $test and $test2 to both be set in DATABASE_CONFIG
 * NOTE: When testing on MySQL, you must set 'persistent' => false on *both* database connections,
 * or one connection will step on the other.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc.
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model
 * @since         CakePHP(tm) v 2.1
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
require_once dirname(__FILE__) . DS . 'ModelTestBase.php';

class ModelCrossSchemaHabtmTest extends BaseModelTest {

/**
 * Fixtures to be used
 *
 * @var array
 */
	public $fixtures = array(
		'core.player', 'core.guild', 'core.guilds_player',
		'core.armor', 'core.armors_player',
	);

/**
 * Don't drop tables if they exist
 *
 * @var boolean
 */
	public $dropTables = false;

/**
 * Don't auto load fixtures
 *
 * @var boolean
 */
	public $autoFixtures = false;

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->_checkConfigs();
	}

/**
 * Check if primary and secondary test databases are configured.
 *
 * @return void
 */
	protected function _checkConfigs() {
		$config = ConnectionManager::enumConnectionObjects();
		$this->skipIf($this->db instanceof Sqlite, 'This test is not compatible with Sqlite.');
		$this->skipIf(
			!isset($config['test']) || !isset($config['test2']),
			'Primary and secondary test databases not configured, ' .
			'skipping cross-database join tests.' .
			' To run these tests, you must define $test and $test2 in your database configuration.'
		);
	}

/**
 * testModelDatasources method
 *
 * @return void
 */
	public function testModelDatasources() {
		$this->loadFixtures('Player', 'Guild', 'GuildsPlayer');

		$Player = ClassRegistry::init('Player');
		$this->assertEquals('test', $Player->useDbConfig);
		$this->assertEquals('test', $Player->Guild->useDbConfig);
		$this->assertEquals('test2', $Player->GuildsPlayer->useDbConfig);

		$this->assertEquals('test', $Player->getDataSource()->configKeyName);
		$this->assertEquals('test', $Player->Guild->getDataSource()->configKeyName);
		$this->assertEquals('test2', $Player->GuildsPlayer->getDataSource()->configKeyName);
	}

/**
 * testHabtmFind method
 *
 * @return void
 */
	public function testHabtmFind() {
		$this->loadFixtures('Player', 'Guild', 'GuildsPlayer');
		$Player = ClassRegistry::init('Player');

		$players = $Player->find('all', array(
			'fields' => array('id', 'name'),
			'contain' => array(
				'Guild' => array(
					'conditions' => array(
						'Guild.name' => 'Wizards',
					),
				),
			),
		));
		$this->assertEquals(4, count($players));
		$wizards = Hash::extract($players, '{n}.Guild.{n}[name=Wizards]');
		$this->assertEquals(1, count($wizards));

		$players = $Player->find('all', array(
			'fields' => array('id', 'name'),
			'conditions' => array(
				'Player.id' => 1,
			),
		));
		$this->assertEquals(1, count($players));
		$wizards = Hash::extract($players, '{n}.Guild.{n}');
		$this->assertEquals(2, count($wizards));
	}

/**
 * testHabtmSave method
 *
 * @return void
 */
	public function testHabtmSave() {
		$this->loadFixtures('Player', 'Guild', 'GuildsPlayer');
		$Player = ClassRegistry::init('Player');
		$players = $Player->find('count');
		$this->assertEquals(4, $players);

		$player = $Player->create(array(
			'name' => 'rchavik',
		));

		$results = $Player->saveAll($player, array('validate' => 'first'));
		$this->assertNotEqual(false, $results);
		$count = $Player->find('count');
		$this->assertEquals(5, $count);

		$count = $Player->GuildsPlayer->find('count');
		$this->assertEquals(3, $count);

		$player = $Player->findByName('rchavik');
		$this->assertEmpty($player['Guild']);

		$player['Guild']['Guild'] = array(1, 2, 3);
		$Player->save($player);

		$player = $Player->findByName('rchavik');
		$this->assertEquals(3, count($player['Guild']));

		$players = $Player->find('all', array(
			'contain' => array(
				'conditions' => array(
					'Guild.name' => 'Rangers',
				),
			),
		));
		$rangers = Hash::extract($players, '{n}.Guild.{n}[name=Rangers]');
		$this->assertEquals(2, count($rangers));
	}

/**
 * testHabtmWithThreeDatabases method
 *
 * @return void
 */
	public function testHabtmWithThreeDatabases() {
		$config = ConnectionManager::enumConnectionObjects();
		$this->skipIf(
			!isset($config['test']) || !isset($config['test2']) || !isset($config['test_database_three']),
			'Primary, secondary, and tertiary test databases not configured,' .
			' skipping test. To run these tests, you must define ' .
			'$test, $test2, and $test_database_three in your database configuration.'
		);

		$this->loadFixtures('Player', 'Guild', 'GuildsPlayer', 'Armor', 'ArmorsPlayer');

		$Player = ClassRegistry::init('Player');
		$Player->bindModel(array(
			'hasAndBelongsToMany' => array(
				'Armor' => array(
					'with' => 'ArmorsPlayer',
					'unique' => true,
				),
			),
		), false);
		$this->assertEquals('test', $Player->useDbConfig);
		$this->assertEquals('test2', $Player->Armor->useDbConfig);
		$this->assertEquals('test_database_three', $Player->ArmorsPlayer->useDbConfig);
		$players = $Player->find('count');
		$this->assertEquals(4, $players);

		$spongebob = $Player->create(array(
			'id' => 10,
			'name' => 'spongebob',
		));
		$spongebob['Armor'] = array('Armor' => array(1, 2, 3, 4));
		$result = $Player->save($spongebob);

		$expected = array(
			'Player' => array(
				'id' => 10,
				'name' => 'spongebob',
			),
			'Armor' => array(
				'Armor' => array(
					1, 2, 3, 4,
				),
			),
		);
		unset($result['Player']['created']);
		unset($result['Player']['updated']);
		$this->assertEquals($expected, $result);

		$spongebob = $Player->find('all', array(
			'conditions' => array(
				'Player.id' => 10,
			)
		));
		$spongeBobsArmors = Hash::extract($spongebob, '{n}.Armor.{n}');
		$this->assertEquals(4, count($spongeBobsArmors));
	}
}
