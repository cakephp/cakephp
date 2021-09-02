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
namespace Cake\Test\TestCase\TestSuite;

use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\Fixture\TransactionStrategy;
use Cake\TestSuite\TestCase;

class TransactionStrategyTest extends TestCase
{
    protected $fixtures = ['core.Articles'];

    /**
     * Tests truncation strategy.
     */
    public function testStrategy(): void
    {
        /**
         * @var \Cake\Database\Connection $connection
         */
        $connection = ConnectionManager::get('test');
        $connection->newQuery()->delete('articles')->execute()->closeCursor();
        $rows = $connection->newQuery()->select('*')->from('articles')->execute();
        $this->assertEmpty($rows->fetchAll());
        $rows->closeCursor();

        $strategy = new TransactionStrategy();
        $strategy->setupTest(['core.Articles']);
        $rows = $connection->newQuery()->select('*')->from('articles')->execute();
        $this->assertNotEmpty($rows->fetchAll());
        $rows->closeCursor();

        $strategy->teardownTest();
        $rows = $connection->newQuery()->select('*')->from('articles')->execute();
        $this->assertEmpty($rows->fetchAll());
        $rows->closeCursor();
    }
}
