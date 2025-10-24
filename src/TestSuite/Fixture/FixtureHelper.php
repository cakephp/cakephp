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

use Cake\Core\Configure;
use Cake\Core\Exception\CakeException;
use Cake\Database\Connection;
use Cake\Database\DriverFeatureEnum;
use Cake\Database\Schema\TableSchema;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FixtureInterface;
use Cake\TestSuite\ConnectionHelper;
use Closure;
use PDOException;
use UnexpectedValueException;

/**
 * Helper for managing fixtures.
 */
class FixtureHelper
{
    /**
     * Finds fixtures from their TestCase names such as 'core.Articles'.
     *
     * @param array<string> $fixtureNames Fixture names from test case
     * @return array<\Cake\Datasource\FixtureInterface>
     */
    public function loadFixtures(array $fixtureNames): array
    {
        static $cachedFixtures = [];

        $fixtures = [];
        foreach ($fixtureNames as $fixtureName) {
            if (str_contains($fixtureName, '.')) {
                [$type, $pathName] = explode('.', $fixtureName, 2);
                $path = explode('/', $pathName);
                $name = array_pop($path);
                $additionalPath = implode('\\', $path);

                if ($type === 'core') {
                    $baseNamespace = 'Cake';
                } elseif ($type === 'app') {
                    $baseNamespace = Configure::read('App.namespace');
                } elseif ($type === 'plugin') {
                    [$plugin, $name] = explode('.', $pathName);
                    $baseNamespace = str_replace('/', '\\', $plugin);
                    $additionalPath = null;
                } else {
                    $baseNamespace = '';
                    $name = $fixtureName;
                }

                if (strpos($name, '/') > 0) {
                    $name = str_replace('/', '\\', $name);
                }

                $nameSegments = [
                    $baseNamespace,
                    'Test\Fixture',
                    $additionalPath,
                    $name . 'Fixture',
                ];
                /** @var class-string<\Cake\Datasource\FixtureInterface> $className */
                $className = implode('\\', array_filter($nameSegments));
            } else {
                /** @var class-string<\Cake\Datasource\FixtureInterface> $className */
                $className = $fixtureName;
            }

            if (isset($fixtures[$className])) {
                throw new UnexpectedValueException(sprintf('Found duplicate fixture `%s`.', $fixtureName));
            }

            if (!class_exists($className)) {
                throw new UnexpectedValueException(sprintf('Could not find fixture `%s`.', $fixtureName));
            }

            $cachedFixtures[$className] ??= new $className();
            $fixtures[$className] = $cachedFixtures[$className];
        }

        return $fixtures;
    }

    /**
     * Runs the callback once per connection.
     *
     * The callback signature:
     * ```
     * function callback(ConnectionInterface $connection, array $fixtures)
     * ```
     *
     * @param \Closure $callback Callback run per connection
     * @param array<\Cake\Datasource\FixtureInterface> $fixtures Test fixtures
     * @return void
     */
    public function runPerConnection(Closure $callback, array $fixtures): void
    {
        $groups = [];
        foreach ($fixtures as $fixture) {
            $groups[$fixture->connection()][] = $fixture;
        }

        foreach ($groups as $connectionName => $fixtures) {
            $callback(ConnectionManager::get($connectionName), $fixtures);
        }
    }

    /**
     * Inserts fixture data.
     *
     * @param array<\Cake\Datasource\FixtureInterface> $fixtures Test fixtures
     * @return void
     * @internal
     */
    public function insert(array $fixtures): void
    {
        $this->runPerConnection(function (ConnectionInterface $connection, array $groupFixtures): void {
            if ($connection instanceof Connection) {
                $sortedFixtures = $this->sortByConstraint($connection, $groupFixtures);
                if ($sortedFixtures) {
                    $this->insertConnection($connection, $sortedFixtures);
                } else {
                    ConnectionHelper::runWithoutConstraints(
                        $connection,
                        fn(Connection $connection) => $this->insertConnection($connection, $groupFixtures),
                    );
                }
            } else {
                $this->insertConnection($connection, $groupFixtures);
            }
        }, $fixtures);
    }

    /**
     * Inserts all fixtures for a connection and provides friendly errors for bad data.
     *
     * @param \Cake\Datasource\ConnectionInterface $connection Fixture connection
     * @param array<\Cake\Datasource\FixtureInterface> $fixtures Connection fixtures
     * @return void
     */
    protected function insertConnection(ConnectionInterface $connection, array $fixtures): void
    {
        foreach ($fixtures as $fixture) {
            try {
                $fixture->insert($connection);
            } catch (PDOException $exception) {
                $message = sprintf(
                    'Unable to insert rows for table `%s`.'
                        . " Fixture records might have invalid data or unknown constraints.\n%s",
                    $fixture->sourceName(),
                    $exception->getMessage(),
                );
                throw new CakeException($message);
            }
        }
    }

    /**
     * Truncates fixture tables.
     *
     * @param array<\Cake\Datasource\FixtureInterface> $fixtures Test fixtures
     * @return void
     * @internal
     */
    public function truncate(array $fixtures): void
    {
        $this->runPerConnection(function (ConnectionInterface $connection, array $groupFixtures): void {
            if ($connection instanceof Connection) {
                $sortedFixtures = null;
                if ($connection->getDriver()->supports(DriverFeatureEnum::TRUNCATE_WITH_CONSTRAINTS)) {
                    $sortedFixtures = $this->sortByConstraint($connection, $groupFixtures);
                }

                if ($sortedFixtures !== null) {
                    $this->truncateConnection($connection, array_reverse($sortedFixtures));
                } else {
                    $helper = new ConnectionHelper();
                    $helper->runWithoutConstraints(
                        $connection,
                        fn(Connection $connection) => $this->truncateConnection($connection, $groupFixtures),
                    );
                }
            } else {
                $this->truncateConnection($connection, $groupFixtures);
            }
        }, $fixtures);
    }

    /**
     * Truncates all fixtures for a connection and provides friendly errors for bad data.
     *
     * @param \Cake\Datasource\ConnectionInterface $connection Fixture connection
     * @param array<\Cake\Datasource\FixtureInterface> $fixtures Connection fixtures
     * @return void
     */
    protected function truncateConnection(ConnectionInterface $connection, array $fixtures): void
    {
        foreach ($fixtures as $fixture) {
            try {
                $fixture->truncate($connection);
            } catch (PDOException $exception) {
                $message = sprintf(
                    'Unable to truncate table `%s`.'
                        . " Fixture records might have invalid data or unknown constraints.\n%s",
                    $fixture->sourceName(),
                    $exception->getMessage(),
                );
                throw new CakeException($message);
            }
        }
    }

    /**
     * Sort fixtures with foreign constraints last if possible, otherwise returns null.
     *
     * @param \Cake\Database\Connection $connection Database connection
     * @param array<\Cake\Datasource\FixtureInterface> $fixtures Database fixtures
     * @return array|null
     */
    protected function sortByConstraint(Connection $connection, array $fixtures): ?array
    {
        $constrained = [];
        $unconstrained = [];
        foreach ($fixtures as $fixture) {
            $references = $this->getForeignReferences($connection, $fixture);
            if ($references) {
                $constrained[$fixture->sourceName()] = ['references' => $references, 'fixture' => $fixture];
            } else {
                $unconstrained[] = $fixture;
            }
        }

        // Check if any fixtures reference another fixture with constraints
        // If they do, then there might be cross-dependencies which we don't support sorting
        foreach ($constrained as ['references' => $references]) {
            foreach ($references as $reference) {
                if (isset($constrained[$reference])) {
                    return null;
                }
            }
        }

        return array_merge($unconstrained, array_column($constrained, 'fixture'));
    }

    /**
     * Gets array of foreign references for fixtures table.
     *
     * @param \Cake\Database\Connection $connection Database connection
     * @param \Cake\Datasource\FixtureInterface $fixture Database fixture
     * @return array<string>
     */
    protected function getForeignReferences(Connection $connection, FixtureInterface $fixture): array
    {
        /** @var array<string, \Cake\Database\Schema\TableSchemaInterface> $schemas */
        static $schemas = [];

        // Get and cache off the schema since TestFixture generates a fake schema based on $fields
        $tableName = $fixture->sourceName();
        if (!isset($schemas[$tableName])) {
            $schemas[$tableName] = $connection->getSchemaCollection()->describe($tableName);
        }
        $schema = $schemas[$tableName];

        $references = [];
        foreach ($schema->constraints() as $constraintName) {
            $constraint = $schema->getConstraint($constraintName);

            if ($constraint && $constraint['type'] === TableSchema::CONSTRAINT_FOREIGN) {
                $references[] = $constraint['references'][0];
            }
        }

        return $references;
    }
}
