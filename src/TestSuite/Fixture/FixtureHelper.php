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
use Cake\Database\Connection;
use Cake\Database\DriverInterface;
use Cake\Database\Schema\TableSchema;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FixtureInterface;
use Cake\TestSuite\ConnectionHelper;
use Closure;
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
            if (strpos($fixtureName, '.')) {
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
                /** @psalm-var class-string<\Cake\Datasource\FixtureInterface> */
                $className = implode('\\', array_filter($nameSegments));
            } else {
                /** @psalm-var class-string<\Cake\Datasource\FixtureInterface> */
                $className = $fixtureName;
            }

            if (isset($fixtures[$className])) {
                throw new UnexpectedValueException("Found duplicate fixture `$fixtureName`.");
            }

            if (!class_exists($className)) {
                throw new UnexpectedValueException("Could not find fixture `$fixtureName`.");
            }

            if (!isset($cachedFixtures[$className])) {
                $cachedFixtures[$className] = new $className();
            }

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
        $this->runPerConnection(function (ConnectionInterface $connection, array $groupFixtures) {
            if ($connection instanceof Connection) {
                $sortedFixtures = $this->sortByConstraint($connection, $groupFixtures);
                if ($sortedFixtures) {
                    foreach ($sortedFixtures as $fixture) {
                        $fixture->insert($connection);
                    }
                } else {
                    $helper = new ConnectionHelper();
                    $helper->runWithoutConstraints($connection, function (Connection $connection) use ($groupFixtures) {
                        foreach ($groupFixtures as $fixture) {
                            $fixture->insert($connection);
                        }
                    });
                }
            } else {
                foreach ($groupFixtures as $fixture) {
                    $fixture->insert($connection);
                }
            }
        }, $fixtures);
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
        $this->runPerConnection(function (ConnectionInterface $connection, array $groupFixtures) {
            if ($connection instanceof Connection) {
                $sortedFixtures = null;
                if ($connection->getDriver()->supports(DriverInterface::FEATURE_TRUNCATE_WITH_CONSTRAINTS)) {
                    $sortedFixtures = $this->sortByConstraint($connection, $groupFixtures);
                }

                if ($sortedFixtures !== null) {
                    foreach (array_reverse($sortedFixtures) as $fixture) {
                        $fixture->truncate($connection);
                    }
                } else {
                    $helper = new ConnectionHelper();
                    $helper->runWithoutConstraints($connection, function (Connection $connection) use ($groupFixtures) {
                        foreach ($groupFixtures as $fixture) {
                            $fixture->truncate($connection);
                        }
                    });
                }
            } else {
                foreach ($groupFixtures as $fixture) {
                    $fixture->truncate($connection);
                }
            }
        }, $fixtures);
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

        // Check if any fixtures reference another fixture with constrants
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
     * @return array
     */
    protected function getForeignReferences(Connection $connection, FixtureInterface $fixture): array
    {
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
