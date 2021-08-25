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

use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use PHPUnit\Runner\BeforeFirstTestHook;

/**
 * PHPUnit extension to integrate CakePHP's data-only fixtures.
 */
class PHPUnitExtension implements BeforeFirstTestHook
{
    /**
     * Constructor. Set the record only fixture manager as the singleton.
     */
    public function __construct()
    {
        FixtureLoader::setInstance(new FixtureDataManager());

        $enableLogging = in_array('--debug', $_SERVER['argv'] ?? [], true);
        $this->aliasConnections($enableLogging);
        if ($enableLogging) {
            Log::setConfig('queries', [
                'className' => 'Console',
                'stream' => 'php://stderr',
                'scopes' => ['queriesLog'],
            ]);
        }
    }

    /**
     * Alias non test connections to the test ones
     * so that models reach the test database connections instead.
     *
     * @param bool $enableLogging Whether or not to enable query logging.
     * @return void
     */
    protected function aliasConnections(bool $enableLogging): void
    {
        $connections = ConnectionManager::configured();
        $map = [
            'test' => 'default',
        ];
        foreach ($connections as $connection) {
            if ($connection === 'test' || $connection === 'default') {
                continue;
            }
            if (isset($map[$connection])) {
                continue;
            }
            if (strpos($connection, 'test_') === 0) {
                $map[$connection] = substr($connection, 5);
            } else {
                $map['test_' . $connection] = $connection;
            }
        }
        foreach ($map as $testConnection => $normal) {
            ConnectionManager::alias($testConnection, $normal);
            $connection = ConnectionManager::get($normal);
            if ($connection instanceof Connection && $enableLogging) {
                $connection->enableQueryLogging();
            }
        }
    }

    /**
     * First test hook.
     *
     * @return void
     */
    public function executeBeforeFirstTest(): void
    {
        // Do nothing as we setup in the constructor
        // to avoid applications hitting non-test DB
        // during bootstrap.
    }
}
