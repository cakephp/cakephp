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
use Cake\Database\Driver\Postgres;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;
use Closure;
use UnexpectedValueException;

/**
 * Helper for managing fixtures.
 *
 * @internal
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
                /** @psalm-suppress PossiblyFalseArgument */
                $name = preg_replace('/Fixture\z/', '', substr(strrchr($fixtureName, '\\'), 1));
            }

            if (isset($fixtures[$className])) {
                throw new UnexpectedValueException("Found duplicate fixture `$fixtureName`.");
            }

            if (!class_exists($className)) {
                throw new UnexpectedValueException("Could not find fixture `$fixtureName`.");
            }

            $fixtures[$className] = new $className();
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
     */
    public function insert(array $fixtures): void
    {
        $this->runPerConnection(function (ConnectionInterface $connection, array $groupFixtures) {
            if ($connection->getDriver() instanceof Postgres) {
                // disabling foreign key constraints is only valid in a transaction
                $connection->transactional(function () use ($connection, $groupFixtures) {
                    $connection->disableConstraints(function () use ($connection, $groupFixtures) {
                        foreach ($groupFixtures as $fixture) {
                            $fixture->insert($connection);
                        }
                    });
                });
            } else {
                $connection->disableConstraints(function () use ($connection, $groupFixtures) {
                    foreach ($groupFixtures as $fixture) {
                        $fixture->insert($connection);
                    }
                });
            }
        }, $fixtures);
    }

    /**
     * Truncates fixture tables.
     *
     * @param array<\Cake\Datasource\FixtureInterface> $fixtures Test fixtures
     * @return void
     */
    public function truncate(array $fixtures): void
    {
        $this->runPerConnection(function (ConnectionInterface $connection, array $groupFixtures) {
            $connection->disableConstraints(function () use ($connection, $groupFixtures) {
                foreach ($groupFixtures as $fixture) {
                    $fixture->truncate($connection);
                }
            });
        }, $fixtures);
    }
}
