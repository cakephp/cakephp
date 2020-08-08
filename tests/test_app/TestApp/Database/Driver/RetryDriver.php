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
 * @since         4.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Database\Driver;

use Cake\Database\Driver\Sqlserver;
use Cake\Datasource\ConnectionManager;

class RetryDriver extends Sqlserver
{
    /**
     * @inheritDoc
     */
    protected const RETRY_ERROR_CODES = [18456];

    /**
     * @inheritDoc
     */
    public function connect(): bool
    {
        $testConfig = ConnectionManager::get('test')->config() + $this->_baseConfig;
        $dsn = "sqlsrv:Server={$testConfig['host']};Database={$testConfig['database']}";

        $this->_connect($dsn, ['username' => 'invalid', 'password' => '', 'flags' => []]);

        return true;
    }

    public function enabled(): bool
    {
        return true;
    }
}
