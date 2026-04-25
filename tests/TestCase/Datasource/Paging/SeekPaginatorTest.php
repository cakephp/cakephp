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
namespace Cake\Test\TestCase\Datasource\Paging;

use Cake\Datasource\Paging\CursorPaginatedInterface;
use Cake\Datasource\Paging\Exception\InvalidCursorException;
use Cake\Datasource\Paging\SeekPaginator;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;

/**
 * Seek paginator integration tests backed by the `posts` fixture (3 rows).
 *
 * With `limit=1` we get three pages, enough to exercise forward/backward
 * traversal, token round-tripping, and hasNext/hasPrev transitions.
 */
class SeekPaginatorTest extends TestCase
{
    protected array $fixtures = ['core.Posts'];

    protected SeekPaginator $paginator;

    protected function setUp(): void
    {
        parent::setUp();

        Security::setSalt(str_repeat('a', 32));
        $this->paginator = new SeekPaginator();
    }

    protected function postsTable()
    {
        return $this->getTableLocator()->get('Posts');
    }

    protected function baseQuery()
    {
        return $this->postsTable()->find()
            ->select(['id', 'title'])
            ->orderBy(['Posts.id' => 'ASC']);
    }

    public function testFirstPageHasForwardCursorOnly(): void
    {
        $result = $this->paginator->paginate($this->baseQuery(), [], ['limit' => 1]);

        $this->assertInstanceOf(CursorPaginatedInterface::class, $result);
        $this->assertSame('seek', $result->paginationType());
        $this->assertCount(1, $result);
        $this->assertSame(1, $result->items()->current()->id);

        $this->assertTrue($result->hasNextPage());
        $this->assertFalse($result->hasPrevPage());
        $this->assertSame(['Posts.id' => 1], $result->nextCursor());
        $this->assertNull($result->previousCursor());
        $this->assertNotNull($result->nextCursorToken());
        $this->assertNull($result->previousCursorToken());
    }

    public function testSecondPageViaTokenHasBothCursors(): void
    {
        $first = $this->paginator->paginate($this->baseQuery(), [], ['limit' => 1]);

        $params = ['cursor' => $first->nextCursorToken()];
        $second = $this->paginator->paginate($this->baseQuery(), $params, ['limit' => 1]);

        $this->assertCount(1, $second);
        $this->assertSame(2, $second->items()->current()->id);
        $this->assertTrue($second->hasNextPage());
        $this->assertTrue($second->hasPrevPage());
        $this->assertSame(['Posts.id' => 2], $second->nextCursor());
        $this->assertSame(['Posts.id' => 2], $second->previousCursor());
    }

    public function testLastPageHasNoForwardCursor(): void
    {
        $page1 = $this->paginator->paginate($this->baseQuery(), [], ['limit' => 1]);
        $page2 = $this->paginator->paginate(
            $this->baseQuery(),
            ['cursor' => $page1->nextCursorToken()],
            ['limit' => 1],
        );
        $page3 = $this->paginator->paginate(
            $this->baseQuery(),
            ['cursor' => $page2->nextCursorToken()],
            ['limit' => 1],
        );

        $this->assertCount(1, $page3);
        $this->assertSame(3, $page3->items()->current()->id);
        $this->assertFalse($page3->hasNextPage());
        $this->assertTrue($page3->hasPrevPage());
        $this->assertNull($page3->nextCursor());
    }

    public function testBackwardPagingFetchesPrecedingRecords(): void
    {
        $page1 = $this->paginator->paginate($this->baseQuery(), [], ['limit' => 1]);
        $page2 = $this->paginator->paginate(
            $this->baseQuery(),
            ['cursor' => $page1->nextCursorToken()],
            ['limit' => 1],
        );

        $back = $this->paginator->paginate(
            $this->baseQuery(),
            [
                'cursor' => $page2->previousCursorToken(),
                'direction' => 'before',
            ],
            ['limit' => 1],
        );

        $this->assertCount(1, $back);
        $this->assertSame(1, $back->items()->current()->id);
    }

    public function testDirectionBeforeWithoutCursorReturnsFirstPage(): void
    {
        // `direction=before` without a cursor is normalized to `after` — otherwise
        // we would flip ORDER BY without applying any predicate and silently return
        // the *last* page instead of the first.
        $result = $this->paginator->paginate(
            $this->baseQuery(),
            ['direction' => 'before'],
            ['limit' => 1],
        );

        $this->assertCount(1, $result);
        $this->assertSame(1, $result->items()->current()->id);
        $this->assertFalse($result->hasPrevPage());
        $this->assertTrue($result->hasNextPage());
    }

    public function testAliasScopedSettingsAreHonored(): void
    {
        // Settings keyed by repository alias should be unwrapped (matches the
        // pattern used by NumericPaginator::getDefaults).
        $first = $this->paginator->paginate($this->baseQuery(), [], ['limit' => 1]);

        $second = $this->paginator->paginate(
            $this->baseQuery(),
            [],
            [
                'Posts' => [
                    'cursor' => $first->nextCursorToken(),
                    'limit' => 1,
                ],
            ],
        );

        $this->assertCount(1, $second);
        $this->assertSame(2, $second->items()->current()->id);
    }

    public function testInvalidTokenThrows(): void
    {
        $this->expectException(InvalidCursorException::class);

        $this->paginator->paginate(
            $this->baseQuery(),
            ['cursor' => 'garbage.token'],
            ['limit' => 1],
        );
    }

    public function testMissingOrderByThrows(): void
    {
        $query = $this->postsTable()->find()->select(['id', 'title']);

        $this->expectException(InvalidCursorException::class);
        $this->expectExceptionMessage('requires an explicit `orderBy()`');

        $this->paginator->paginate($query, [], ['limit' => 1]);
    }

    public function testMissingCursorColumnOnBoundaryRowThrowsClearError(): void
    {
        $paginator = new class extends SeekPaginator {
            /**
             * @param array<int, string> $columns
             */
            public function callBuildCursor(array $columns, mixed $row): array
            {
                $this->cursorColumns = $columns;

                return $this->buildCursor($row);
            }
        };

        $this->expectException(InvalidCursorException::class);
        $this->expectExceptionMessage('Cursor column `Posts.body` is not present on the result row');

        // Row is missing `body` entirely — distinct from `body => null`.
        $paginator->callBuildCursor(['Posts.body', 'Posts.id'], ['id' => 7]);
    }

    public function testBoundaryRowWithNullCursorColumnThrows(): void
    {
        // Test the buildCursor() guard directly — driving it via paginate() would
        // depend on driver NULL-ordering (Postgres: NULLs last in ASC; MySQL: NULLs
        // first), which determines whether the boundary row even carries the NULL.
        // The contract being verified is: if a cursor column on the boundary row
        // is `null`, the paginator refuses to emit a corrupt token.
        $paginator = new class extends SeekPaginator {
            /**
             * @param array<int, string> $columns
             */
            public function callBuildCursor(array $columns, mixed $row): array
            {
                $this->cursorColumns = $columns;

                return $this->buildCursor($row);
            }
        };

        $this->expectException(InvalidCursorException::class);
        $this->expectExceptionMessage('Boundary row has `null` in cursor column `Posts.body`');

        $paginator->callBuildCursor(
            ['Posts.body', 'Posts.id'],
            ['body' => null, 'id' => 7],
        );
    }
}
