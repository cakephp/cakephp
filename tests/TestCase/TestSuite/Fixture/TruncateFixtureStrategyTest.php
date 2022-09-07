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
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\TestSuite;

use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\Fixture\TruncateFixtureStrategy;
use Cake\TestSuite\TestCase;

class TruncateFixtureStrategyTest extends TestCase
{
    protected array $fixtures = ['core.Articles'];

    /**
     * Tests truncation strategy.
     */
    public function testStrategy(): void
    {
        /**
         * @var \Cake\Database\Connection $connection
         */
        $connection = ConnectionManager::get('test');
        $connection->deleteQuery()->delete('articles')->execute()->closeCursor();
        $rows = $connection->selectQuery()->select('*')->from('articles')->execute();
        $this->assertEmpty($rows->fetchAll());
        $rows->closeCursor();

        $strategy = new TruncateFixtureStrategy();
        $strategy->setupTest(['core.Articles']);
        $rows = $connection->selectQuery()->select('*')->from('articles')->execute();
        $this->assertNotEmpty($rows->fetchAll());
        $rows->closeCursor();

        $strategy->teardownTest();
        $rows = $connection->selectQuery()->select('*')->from('articles')->execute();
        $this->assertEmpty($rows->fetchAll());
        $rows->closeCursor();
    }
}
