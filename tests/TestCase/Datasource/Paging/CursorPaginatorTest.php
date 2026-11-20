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
 * @since         5.5.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Datasource\Paging;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Exception\CakeException;
use Cake\Datasource\Paging\CursorPaginatedInterface;
use Cake\Datasource\Paging\CursorPaginator;
use Cake\TestSuite\TestCase;

class CursorPaginatorTest extends TestCase
{
    /**
     * @var array<string>
     */
    protected array $fixtures = ['core.Posts', 'core.Comments'];

    protected CursorPaginator $Paginator;

    protected function setUp(): void
    {
        parent::setUp();
        Configure::write('App.namespace', 'TestApp');
        $this->Paginator = new CursorPaginator();

        $posts = $this->getTableLocator()->get('Posts');
        foreach ([[2, 'Fourth Post'], [1, 'Fifth Post'], [3, 'Sixth Post'], [1, 'Seventh Post']] as [$author, $title]) {
            $posts->saveOrFail($posts->newEntity([
                'author_id' => $author,
                'title' => $title,
                'body' => '',
                'published' => 'Y',
            ]));
        }
    }

    /**
     * Walk all pages forward and then backward, collecting the ids.
     *
     * @return array{0: array<array<int>>, 1: array<array<int>>}
     */
    protected function walk(mixed $target, array $params, array $settings): array
    {
        $forward = [];
        $cursor = null;
        $pages = 0;
        $result = null;
        do {
            $result = $this->Paginator->paginate($target, $params + ['cursor' => $cursor], $settings);
            $this->assertInstanceOf(CursorPaginatedInterface::class, $result);
            $forward[] = $result->items()->extract('id')->toList();
            $pages++;
            $cursor = $result->nextCursor();
            $this->assertSame($cursor !== null, $result->hasNextPage());
        } while ($cursor !== null && $pages < 20);

        $backward = [$forward[count($forward) - 1]];
        $cursor = $result->prevCursor();
        while ($cursor !== null && $pages-- > 0) {
            $result = $this->Paginator->paginate($target, $params + ['cursor' => $cursor], $settings);
            $backward[] = $result->items()->extract('id')->toList();
            $cursor = $result->prevCursor();
            $this->assertSame($cursor !== null, $result->hasPrevPage());
        }

        return [$forward, array_reverse($backward)];
    }

    public function testPaginateForwardAndBackward(): void
    {
        $table = $this->getTableLocator()->get('Posts');
        [$forward, $backward] = $this->walk($table, [], ['limit' => 3, 'order' => ['author_id' => 'asc']]);

        // author_id asc, tiebreaker id asc
        $this->assertSame([[1, 3, 5], [7, 4, 2], [6]], $forward);
        $this->assertSame($forward, $backward);
    }

    public function testFirstPageParams(): void
    {
        $table = $this->getTableLocator()->get('Posts');
        $result = $this->Paginator->paginate($table, [], ['limit' => 3]);

        $this->assertSame([1, 2, 3], $result->items()->extract('id')->toList());
        $this->assertSame(3, $result->count());
        $this->assertSame(1, $result->currentPage());
        $this->assertNull($result->totalCount());
        $this->assertNull($result->pageCount());
        $this->assertFalse($result->hasPrevPage());
        $this->assertTrue($result->hasNextPage());
        $this->assertNull($result->prevCursor());
        $this->assertNull($result->pagingParam('cursor'));
        $this->assertSame(['Posts.id' => 'asc'], $result->pagingParam('completeSort'));
    }

    public function testPaginateMultipleFieldsDescending(): void
    {
        $table = $this->getTableLocator()->get('Posts');
        $settings = ['limit' => 2, 'order' => ['author_id' => 'desc', 'title' => 'asc']];
        [$forward, $backward] = $this->walk($table, [], $settings);

        // author_id desc, title asc, id asc
        $this->assertSame([[2, 6], [4, 5], [1, 7], [3]], $forward);
        $this->assertSame($forward, $backward);
    }

    public function testPaginateDateTimeField(): void
    {
        $table = $this->getTableLocator()->get('Comments');
        $query = $table->find()->where(['published' => 'Y']);
        [$forward, $backward] = $this->walk($query, [], ['limit' => 2, 'order' => ['created' => 'desc']]);

        $this->assertSame([[6, 5], [3, 2], [1]], $forward);
        $this->assertSame($forward, $backward);
    }

    public function testQueryOrderIsReplaced(): void
    {
        $table = $this->getTableLocator()->get('Posts');
        $query = $table->find()->orderBy(['title' => 'asc']);
        [$forward] = $this->walk($query, [], ['limit' => 4, 'order' => ['id' => 'desc']]);

        $this->assertSame([[7, 6, 5, 4], [3, 2, 1]], $forward);
    }

    public function testInvalidCursorIsIgnored(): void
    {
        $table = $this->getTableLocator()->get('Posts');
        $settings = ['limit' => 3];

        foreach (['nope', '!!!', base64_encode('{"d":"n","k":"x","v":[1]}'), base64_encode('[1]')] as $cursor) {
            $result = $this->Paginator->paginate($table, ['cursor' => $cursor], $settings);
            $this->assertSame([1, 2, 3], $result->items()->extract('id')->toList());
            $this->assertNull($result->pagingParam('cursor'));
        }

        // A cursor generated for a different order is ignored.
        $result = $this->Paginator->paginate($table, ['sort' => 'title'], $settings);
        $cursor = $result->nextCursor();
        $result = $this->Paginator->paginate($table, ['cursor' => $cursor], $settings);
        $this->assertSame([1, 2, 3], $result->items()->extract('id')->toList());

        $result = $this->Paginator->paginate($table, ['cursor' => $cursor, 'sort' => 'title'], $settings);
        $this->assertSame($cursor, $result->pagingParam('cursor'));
        $this->assertTrue($result->hasPrevPage());
    }

    public function testEmptyResult(): void
    {
        $table = $this->getTableLocator()->get('Posts');
        $result = $this->Paginator->paginate($table->find()->where(['id' => 0]));

        $this->assertSame(0, $result->count());
        $this->assertFalse($result->hasNextPage());
        $this->assertFalse($result->hasPrevPage());
    }

    public function testNullValuesAreRejected(): void
    {
        $table = $this->getTableLocator()->get('Posts');
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('Cursor pagination does not support `NULL` values in sort fields.');
        // `title` is not selected, so its value is NULL.
        $this->Paginator->paginate($table->find()->select(['id']), [], ['limit' => 2, 'order' => ['title' => 'asc']]);
    }

    public function testExpressionOrderIsRejected(): void
    {
        $table = $this->getTableLocator()->get('Posts');

        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('Cursor pagination only supports orders that map field names');
        $this->Paginator->paginate($table, [], ['order' => ['id' => 'asc', 'RAND()']]);
    }

    public function testQueryIsNotModified(): void
    {
        $query = $this->getTableLocator()->get('Posts')->find();
        $sql = $query->sql();
        $result = $this->Paginator->paginate($query, [], ['limit' => 2]);
        $this->Paginator->paginate($query, ['cursor' => $result->nextCursor()], ['limit' => 2]);

        $this->assertSame($sql, $query->sql());
    }

    public function testPageParamIsIgnored(): void
    {
        $table = $this->getTableLocator()->get('Posts');
        $result = $this->Paginator->paginate($table, ['page' => 3], ['limit' => 2]);

        $this->assertSame([1, 2], $result->items()->extract('id')->toList());
    }

    public function testClassNameResolution(): void
    {
        $this->assertSame(CursorPaginator::class, App::className('Cursor', 'Datasource/Paging', 'Paginator'));
    }
}
