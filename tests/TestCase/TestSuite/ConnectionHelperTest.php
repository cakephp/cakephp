<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         4.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\TestSuite;

use Cake\Database\Connection;
use Cake\Database\Driver;
use Cake\Database\DriverFeatureEnum;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\Exception\MissingDatasourceConfigException;
use Cake\TestSuite\ConnectionHelper;
use Cake\TestSuite\TestCase;
use Closure;
use Psr\Log\AbstractLogger;
use Stringable;
use TestApp\Database\Driver\TestDriver;

class ConnectionHelperTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        ConnectionManager::drop('query_logging');
        ConnectionManager::drop('something');
        ConnectionManager::drop('test_something');
        ConnectionManager::dropAlias('something');
    }

    public function testAliasConnections(): void
    {
        ConnectionManager::dropAlias('default');
        ConnectionHelper::addTestAliases();

        $this->assertSame(
            ConnectionManager::get('test'),
            ConnectionManager::get('default'),
        );
    }

    public function testAliasNonDefaultConnections(): void
    {
        $connection = new Connection(['driver' => TestDriver::class]);
        ConnectionManager::setConfig('test_something', $connection);

        ConnectionHelper::addTestAliases();

        // Having a test_ alias defined will generate an alias for the unprefixed
        // connection for simpler CI configuration
        $this->assertSame(
            ConnectionManager::get('test_something'),
            ConnectionManager::get('something'),
        );
    }

    public function testAliasNoTestClass(): void
    {
        $connection = new Connection(['driver' => TestDriver::class]);
        ConnectionManager::setConfig('something', $connection);

        (new ConnectionHelper())->addTestAliases();

        // Should raise as no test connection was defined.
        $this->expectException(MissingDatasourceConfigException::class);
        ConnectionManager::get('test_something');
    }

    public function testAliasNonDefaultConnectionWithTestConnection(): void
    {
        $testConnection = new Connection(['driver' => TestDriver::class]);
        $connection = new Connection(['driver' => TestDriver::class]);
        ConnectionManager::setConfig('something', $connection);
        ConnectionManager::setConfig('test_something', $testConnection);

        (new ConnectionHelper())->addTestAliases();

        // Development connections that have test_ prefix connections defined
        // should have an alias defined for the test_ prefixed name. This allows
        // access to the development connection to resolve to the test prefixed name
        // in tests.
        $this->assertSame($testConnection, ConnectionManager::get('test_something'));
        $this->assertSame($testConnection, ConnectionManager::get('something'));
    }

    public function testEnableQueryLogging(): void
    {
        $connection = new Connection(['driver' => TestDriver::class]);
        ConnectionManager::setConfig('query_logging', $connection);
        $this->assertFalse($connection->getDriver()->log(''));

        ConnectionHelper::enableQueryLogging(['query_logging']);
        $this->assertTrue($connection->getDriver()->log(''));
    }

    /**
     * Drivers like Postgres don't allow disabling constraints outside of a
     * transaction, so runWithoutConstraints() must wrap the call in one.
     *
     * Runs against the real `test` connection so that this is only
     * meaningfully exercised on drivers that actually require it (Postgres),
     * rather than simulating one via mocks. On other drivers this test is
     * skipped in favor of testRunWithoutConstraintsSkipsTransactionWhenDriverSupportsIt().
     *
     * @link https://github.com/cakephp/cakephp/issues/19474
     */
    public function testRunWithoutConstraintsWrapsInTransactionWhenDriverRequiresIt(): void
    {
        $connection = ConnectionManager::get('test');
        assert($connection instanceof Connection);
        $driver = $connection->getWriteDriver();

        $this->skipIf(
            $driver->supports(DriverFeatureEnum::DISABLE_CONSTRAINT_WITHOUT_TRANSACTION),
            'This driver supports disabling constraints without a transaction.',
        );

        $called = false;
        $queries = $this->captureQueries($driver, function () use ($connection, &$called): void {
            ConnectionHelper::runWithoutConstraints($connection, function () use (&$called): void {
                $called = true;
            });
        });

        $this->assertTrue($called, 'Callback should still be invoked.');
        $this->assertSame(
            ['BEGIN', $driver->disableForeignKeySQL(), $driver->enableForeignKeySQL(), 'COMMIT'],
            $this->filterQueries($queries, $driver),
            'Constraint disabling must be wrapped in a transaction for drivers that require it.',
        );
    }

    /**
     * Runs against the real `test` connection; only meaningful for drivers
     * that support disabling constraints without a transaction. On drivers
     * that require one (Postgres), this test is skipped in favor of
     * testRunWithoutConstraintsWrapsInTransactionWhenDriverRequiresIt().
     */
    public function testRunWithoutConstraintsSkipsTransactionWhenDriverSupportsIt(): void
    {
        $connection = ConnectionManager::get('test');
        assert($connection instanceof Connection);
        $driver = $connection->getWriteDriver();

        $this->skipIf(
            !$driver->supports(DriverFeatureEnum::DISABLE_CONSTRAINT_WITHOUT_TRANSACTION),
            'This driver requires a transaction to disable constraints.',
        );

        $called = false;
        $queries = $this->captureQueries($driver, function () use ($connection, &$called): void {
            ConnectionHelper::runWithoutConstraints($connection, function () use (&$called): void {
                $called = true;
            });
        });

        $this->assertTrue($called, 'Callback should still be invoked.');
        $this->assertSame(
            [$driver->disableForeignKeySQL(), $driver->enableForeignKeySQL()],
            $this->filterQueries($queries, $driver),
            'No transaction should be started for drivers that support disabling constraints directly.',
        );
    }

    /**
     * Runs $callback while capturing the SQL statements $driver logs, in order.
     *
     * @return array<string>
     */
    private function captureQueries(Driver $driver, Closure $callback): array
    {
        $logger = new class extends AbstractLogger {
            /**
             * @var array<string>
             */
            public array $queries = [];

            public function log($level, string|Stringable $message, array $context = []): void
            {
                $this->queries[] = (string)$message;
            }
        };
        $driver->setLogger($logger);

        try {
            $callback();
        } finally {
            $driver->disableQueryLogging();
        }

        return $logger->queries;
    }

    /**
     * Narrows a captured query log down to the statements relevant to
     * constraint disabling and transaction boundaries, preserving order.
     *
     * @param array<string> $queries
     * @return array<string>
     */
    private function filterQueries(array $queries, Driver $driver): array
    {
        $relevant = ['BEGIN', 'COMMIT', $driver->disableForeignKeySQL(), $driver->enableForeignKeySQL()];

        return array_values(array_filter(
            $queries,
            fn(string $query): bool => in_array($query, $relevant, true),
        ));
    }
}
