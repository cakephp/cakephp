<?php
/**
 * CakeFixtureManager file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.TestSuite.Fixture
 * @since         CakePHP v 2.5
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('DboSource', 'Model/Datasource');
App::uses('CakeFixtureManager', 'TestSuite/Fixture');
App::uses('UuidFixture', 'Test/Fixture');

/**
 * Test Case for CakeFixtureManager class
 *
 * @package       Cake.Test.Case.TestSuite
 */
class CakeFixtureManagerTest extends CakeTestCase {

/**
 * reset environment.
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->fixtureManager = new CakeFixtureManager();
	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->fixtureManager);
	}

/**
 * testLoadTruncatesTable
 *
 * @return void
 */
	public function testLoadTruncatesTable() {
		$MockFixture = $this->getMock('UuidFixture', array('truncate'));
		$MockFixture
			->expects($this->once())
			->method('truncate')
			->will($this->returnValue(true));

		$fixtureManager = $this->fixtureManager;
		$fixtureManagerReflection = new ReflectionClass($fixtureManager);

		$loadedProperty = $fixtureManagerReflection->getProperty('_loaded');
		$loadedProperty->setAccessible(true);
		$loadedProperty->setValue($fixtureManager, array('core.uuid' => $MockFixture));

		$TestCase = $this->getMock('CakeTestCase');
		$TestCase->fixtures = array('core.uuid');
		$TestCase->autoFixtures = true;
		$TestCase->dropTables = false;

		$fixtureManager->load($TestCase);
	}

/**
 * testLoadSingleTruncatesTable
 *
 * @return void
 */
	public function testLoadSingleTruncatesTable() {
		$MockFixture = $this->getMock('UuidFixture', array('truncate'));
		$MockFixture
			->expects($this->once())
			->method('truncate')
			->will($this->returnValue(true));

		$fixtureManager = $this->fixtureManager;
		$fixtureManagerReflection = new ReflectionClass($fixtureManager);

		$fixtureMapProperty = $fixtureManagerReflection->getProperty('_fixtureMap');
		$fixtureMapProperty->setAccessible(true);
		$fixtureMapProperty->setValue($fixtureManager, array('UuidFixture' => $MockFixture));

		$dboMethods = array_diff(get_class_methods('DboSource'), array('enabled'));
		$dboMethods[] = 'connect';
		$db = $this->getMock('DboSource', $dboMethods);
		$db->config['prefix'] = '';

		$fixtureManager->loadSingle('Uuid', $db, false);
	}
}
