<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Fixture;

use Cake\Datasource\ConnectionInterface;
use Cake\TestSuite\TestCase;

/**
 * Abstract base class and singleton container for fixture
 * managers.
 */
abstract class FixtureLoader
{
    /**
     * @var \Cake\TestSuite\Fixture\FixtureLoader|null
     */
    private static $instance;

    /**
     * Set the shared instance
     *
     * @param \Cake\TestSuite\Fixture\FixtureLoader $instance The instance to set.
     * @return void
     */
    public static function setInstance(FixtureLoader $instance): void
    {
        static::$instance = $instance;
    }

    /**
     * Get the shared instance
     *
     * @return \Cake\TestSuite\Fixture\FixtureLoader|null
     */
    public static function getInstance(): ?self
    {
        return static::$instance;
    }

    /**
     * Loads the data for a single fixture.
     *
     * @param string $name of the fixture
     * @param \Cake\Datasource\ConnectionInterface|null $connection Connection instance or null
     *  to get a Connection from the fixture.
     * @param bool $dropTables Whether or not tables should be dropped. Not all implementations
     *   support this parameter.
     * @return void
     * @throws \UnexpectedValueException if $name is not a previously fixtures class
     */
    abstract public function loadSingle(
        string $name,
        ?ConnectionInterface $connection = null,
        bool $dropTables = false
    ): void;

    /**
     * Creates records defined in a test case's fixtures.
     *
     * @param \Cake\TestSuite\TestCase $test The test to inspect for fixture loading.
     * @return void
     * @throws \Cake\Core\Exception\CakeException When fixture records cannot be inserted.
     * @throws \RuntimeException
     */
    abstract public function load(TestCase $test): void;

    /**
     * Setup fixtures for the provided test.
     *
     * Called by TestCase during its setUp() method.
     *
     * @param \Cake\TestSuite\TestCase $test The test to inspect for fixture loading.
     * @return void
     */
    abstract public function setupTest(TestCase $test): void;

    /**
     * Teardown fixtures for the provided test.
     *
     * Called by TestCase during its tearDown() method.
     *
     * @param \Cake\TestSuite\TestCase $test The test to inspect for fixture loading.
     * @return void
     */
    abstract public function teardownTest(TestCase $test): void;

    /**
     * Get the list of all fixtures that have been loaded.
     *
     * @return array<\Cake\Datasource\FixtureInterface>
     */
    abstract public function loaded(): array;

    /**
     * Get the list of fixture tables that were loaded since the last call to setupTest.
     *
     * @return array<string>
     */
    abstract public function getInserted(): array;
}
