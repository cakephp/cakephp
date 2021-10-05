<?php
namespace Cake\TestSuite;

use Cake\Datasource\ConnectionManager;

class DatabaseFactory
{
    /**
     * Creates test databases if they do not exist.
     *
     * @param string $connectionName Connection name that has privilleges to create databases.
     * @param string[] $testSchemas List of test database names.
     * @return void
     */
    public function createTestDatabases(string $connectionName, array $testSchemas): void
    {
        foreach ($testSchemas as $schema) {
            if (getenv('TEST_TOKEN') !== false) { // Using paratest
                $schema .= getenv('TEST_TOKEN');
            }
            $sql = "CREATE DATABASE IF NOT EXISTS `$schema` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
            $Connection = ConnectionManager::get($connectionName);
            $Connection->query($sql);
        }
    }
}
