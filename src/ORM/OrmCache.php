<?php
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
 * @since         3.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM;

use Cake\Cache\Cache;
use Cake\Datasource\ConnectionManager;

/**
 * ORM Cache.
 *
 * This tool is intended to be used by deployment scripts so that you
 * can prevent thundering herd effects on the metadata cache when new
 * versions of your application are deployed, or when migrations
 * requiring updated metadata are required.
 */
class OrmCache
{

    /**
     * Schema
     *
     * @var \Cake\Datasource\SchemaInterface
     */
    protected $_schema;

    /**
     * Constructor
     *
     * @param string $connection Connection name
     */
    public function __construct($connection)
    {
        $this->_schema = $this->getSchema($connection);
    }

    /**
     * Build metadata.
     *
     * @param string|null $name The name of the table to build cache data for.
     * @return array Returns a list build table caches
     */
    public function build($name = null)
    {
        $tables = [$name];
        if (empty($name)) {
            $tables = $this->_schema->listTables();
        }

        foreach ($tables as $table) {
            $this->_schema->describe($table, ['forceRefresh' => true]);
        }

        return $tables;
    }

    /**
     * Clear metadata.
     *
     * @param string|null $name The name of the table to clear cache data for.
     * @return array Returns a list of cleared table caches
     */
    public function clear($name = null)
    {
        $tables = [$name];
        if (empty($name)) {
            $tables = $this->_schema->listTables();
        }
        $configName = $this->_schema->getCacheMetadata();

        foreach ($tables as $table) {
            $key = $this->_schema->cacheKey($table);
            Cache::delete($key, $configName);
        }

        return $tables;
    }

    /**
     * Helper method to get the schema collection.
     *
     * @param string $connection Connection name to get the schema for
     * @return false|\Cake\Database\Schema\Collection|\Cake\Database\Schema\CachedCollection
     */
    public function getSchema($connection)
    {
        /* @var \Cake\Database\Connection $source */
        $source = ConnectionManager::get($connection);
        if (!method_exists($source, 'schemaCollection')) {
            throw new RuntimeException(sprintf(
                'The "%s" connection is not compatible with orm caching, ' .
                'as it does not implement a "schemaCollection()" method.',
                $connection
            ));
        }

        $config = $source->config();
        if (empty($config['cacheMetadata'])) {
            $source->cacheMetadata(true);
        }

        return $source->getSchemaCollection();
    }
}
