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
 * @since         5.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\TestSuite\Fixture;

use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\Fixture\DeleteStrategy;
use Cake\TestSuite\TestCase;

class DeleteStrategyTest extends TestCase
{
    protected array $fixtures = ['core.Articles'];

    /**
     * Tests delete strategy.
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

        $strategy = new DeleteStrategy();
        $strategy->setupTest(['core.Articles']);
        $rows = $connection->selectQuery()->select('*')->from('articles')->execute();
        $this->assertNotEmpty($rows->fetchAll());
        $rows->closeCursor();

        $strategy->teardownTest();
        $rows = $connection->selectQuery()->select('*')->from('articles')->execute();
        $this->assertEmpty($rows->fetchAll());
        $rows->closeCursor();
    }

    /**
     * Tests that tables referenced by other fixtures can be emptied.
     */
    public function testStrategyWithConstraints(): void
    {
        /**
         * @var \Cake\Database\Connection $connection
         */
        $connection = ConnectionManager::get('test');

        $strategy = new DeleteStrategy();
        $strategy->setupTest(['core.Articles', 'core.ArticlesTags', 'core.Tags']);
        $strategy->teardownTest();

        foreach (['articles', 'articles_tags', 'tags'] as $table) {
            $rows = $connection->selectQuery()->select('*')->from($table)->execute();
            $this->assertEmpty($rows->fetchAll(), sprintf('Table `%s` was not emptied.', $table));
            $rows->closeCursor();
        }
    }

    /**
     * Tests that no fixtures is a no-op.
     */
    public function testStrategyWithoutFixtures(): void
    {
        $this->expectNotToPerformAssertions();

        $strategy = new DeleteStrategy();
        $strategy->setupTest([]);
        $strategy->teardownTest();
    }
}
