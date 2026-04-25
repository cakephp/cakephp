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
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.4.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Query;

use Cake\Database\Connection;
use Cake\Database\Exception\DatabaseException;
use Cake\Database\Query\SelectQuery;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

/**
 * Tests for seek/keyset pagination on SelectQuery.
 */
class SelectQuerySeekTest extends TestCase
{
    protected array $fixtures = ['core.Articles'];

    protected Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
    }

    protected function newQuery(): SelectQuery
    {
        return (new SelectQuery($this->connection))
            ->select(['id', 'title'])
            ->from('articles');
    }

    public function testSeekAfterSingleColumnAscFetchesFollowingRows(): void
    {
        $query = $this->newQuery()
            ->orderBy(['id' => 'ASC'])
            ->seekAfter(['id' => 1]);

        $ids = array_column($query->execute()->fetchAll('assoc'), 'id');
        $this->assertSame([2, 3], array_map('intval', $ids));
    }

    public function testSeekAfterSingleColumnDescFetchesFollowingRows(): void
    {
        $query = $this->newQuery()
            ->orderBy(['id' => 'DESC'])
            ->seekAfter(['id' => 3]);

        $ids = array_column($query->execute()->fetchAll('assoc'), 'id');
        $this->assertSame([2, 1], array_map('intval', $ids));
    }

    public function testSeekBeforeSingleColumnAscFetchesPrecedingRows(): void
    {
        // ASC ordering, seek before id=3 → rows with id < 3
        $query = $this->newQuery()
            ->orderBy(['id' => 'ASC'])
            ->seekBefore(['id' => 3]);

        $ids = array_column($query->execute()->fetchAll('assoc'), 'id');
        $this->assertSame([1, 2], array_map('intval', $ids));
    }

    public function testSeekAfterCompositeOrderExpandsLexicographically(): void
    {
        // Articles fixture rows: (id=1, author_id=1), (id=2, author_id=3), (id=3, author_id=1).
        // Order by author_id DESC, id DESC produces: (3,2), (1,3), (1,1).
        // seekAfter at (author_id=3, id=2) expands to:
        //   author_id < 3 OR (author_id = 3 AND id < 2)
        // matching (1,3) and (1,1), returned in the original DESC ordering.
        $query = (new SelectQuery($this->connection))
            ->select(['id', 'author_id'])
            ->from('articles')
            ->orderBy(['author_id' => 'DESC', 'id' => 'DESC'])
            ->seekAfter(['author_id' => 3, 'id' => 2]);

        $ids = array_column($query->execute()->fetchAll('assoc'), 'id');
        $this->assertSame([3, 1], array_map('intval', $ids));
    }

    public function testSeekAfterWithoutOrderByThrows(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('requires at least one `orderBy()` clause');

        $this->newQuery()->seekAfter(['id' => 1]);
    }

    public function testSeekAfterWithEmptyCursorThrows(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('must not be empty');

        $this->newQuery()
            ->orderBy(['id' => 'ASC'])
            ->seekAfter([]);
    }

    public function testSeekAfterWithMismatchedKeysThrows(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('do not match the current ordering');

        $this->newQuery()
            ->orderBy(['id' => 'ASC'])
            ->seekAfter(['title' => 'x']);
    }

    public function testSeekAfterWithWrongKeyOrderThrows(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('do not match the current ordering');

        $this->newQuery()
            ->orderBy(['author_id' => 'DESC', 'id' => 'DESC'])
            ->seekAfter(['id' => 1, 'author_id' => 3]);
    }

    public function testSeekAfterRejectsNullCursorValue(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Seek cursor value for column `published` is `null`');

        $this->newQuery()
            ->orderBy(['published' => 'ASC', 'id' => 'ASC'])
            ->seekAfter(['published' => null, 'id' => 1]);
    }

    public function testSeekBeforeRejectsNullCursorValue(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Seek cursor value for column `id` is `null`');

        $this->newQuery()
            ->orderBy(['id' => 'ASC'])
            ->seekBefore(['id' => null]);
    }
}
