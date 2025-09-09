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
 * @since         3.5.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Datasource\Paging;

use Cake\Core\Exception\CakeException;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;

class NumericPaginatorTest extends TestCase
{
    use PaginatorTestTrait;

    /**
     * fixtures property
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'core.Posts', 'core.Articles', 'core.Tags', 'core.ArticlesTags',
        'core.Authors', 'core.AuthorsTags',
    ];

    /**
     * test paginate() and custom find, to make sure the correct count is returned.
     */
    public function testPaginateCustomFind(): void
    {
        $titleExtractor = function ($result) {
            $ids = [];
            foreach ($result as $record) {
                $ids[] = $record->title;
            }

            return $ids;
        };

        $table = $this->getTableLocator()->get('PaginatorPosts');
        $data = ['author_id' => 3, 'title' => 'Fourth Post', 'body' => 'Article Body, unpublished', 'published' => 'N'];
        $result = $table->save(new Entity($data));
        $this->assertNotEmpty($result);

        $result = $this->Paginator->paginate($table);
        $this->assertCount(4, $result, '4 rows should come back');
        $this->assertEquals(['First Post', 'Second Post', 'Third Post', 'Fourth Post'], $titleExtractor($result));

        $pagingParams = $result->pagingParams();
        $this->assertSame(4, $pagingParams['count']);
        $this->assertSame(4, $pagingParams['totalCount']);

        $settings = ['finder' => 'published'];
        $result = $this->Paginator->paginate($table, [], $settings);
        $this->assertCount(3, $result, '3 rows should come back');
        $this->assertEquals(['First Post', 'Second Post', 'Third Post'], $titleExtractor($result));

        $settings = ['finder' => 'published'];
        $result = $this->Paginator->paginate($table->find(), [], $settings);
        $this->assertCount(3, $result, '3 rows should come back');
        $this->assertEquals(['First Post', 'Second Post', 'Third Post'], $titleExtractor($result));

        $pagingParams = $result->pagingParams();
        $this->assertSame(3, $pagingParams['count']);
        $this->assertSame(3, $pagingParams['totalCount']);

        $settings = ['finder' => 'published', 'limit' => 2, 'page' => 2];
        $result = $this->Paginator->paginate($table, [], $settings);
        $this->assertCount(1, $result, '1 rows should come back');
        $this->assertEquals(['Third Post'], $titleExtractor($result));

        $pagingParams = $result->pagingParams();
        $this->assertSame(1, $pagingParams['count']);
        $this->assertSame(3, $pagingParams['totalCount']);
        $this->assertSame(2, $pagingParams['pageCount']);

        $settings = ['finder' => 'published', 'limit' => 2];
        $result = $this->Paginator->paginate($table, [], $settings);
        $this->assertCount(2, $result, '2 rows should come back');
        $this->assertEquals(['First Post', 'Second Post'], $titleExtractor($result));

        $pagingParams = $result->pagingParams();
        $this->assertSame(2, $pagingParams['count']);
        $this->assertSame(3, $pagingParams['totalCount']);
        $this->assertSame(2, $pagingParams['pageCount']);
        $this->assertTrue($pagingParams['hasNextPage']);
        $this->assertFalse($pagingParams['hasPrevPage']);
        $this->assertSame(2, $pagingParams['perPage']);
        $this->assertNull($pagingParams['limit']);
    }

    /**
     * Test that special paginate types are called and that the type param doesn't leak out into defaults or options.
     */
    public function testPaginateCustomFinder(): void
    {
        $settings = [
            'PaginatorPosts' => [
                'finder' => 'published',
                'maxLimit' => 10,
            ],
        ];

        $table = $this->getTableLocator()->get('PaginatorPosts');
        $this->assertSame(3, $table->find('published')->count());
        $table->updateAll(['published' => 'N'], ['id' => 2]);

        $result = $this->Paginator->paginate($table, [], $settings);
        $pagingParams = $result->pagingParams();

        $this->assertSame(1, $pagingParams['start']);
        $this->assertSame(2, $pagingParams['end']);
        $this->assertSame(2, $pagingParams['totalCount']);
        $this->assertFalse($pagingParams['hasNextPage']);
    }

    /**
     * test direction setting.
     */
    public function testPaginateDefaultDirection(): void
    {
        $settings = [
            'PaginatorPosts' => [
                'order' => ['Other.title' => 'ASC'],
            ],
        ];

        $table = $this->getTableLocator()->get('PaginatorPosts');

        $result = $this->Paginator->paginate($table, [], $settings);
        $pagingParams = $result->pagingParams();

        $this->assertSame('Other.title', $pagingParams['sort']);
        $this->assertNull($pagingParams['direction']);
    }

    /**
     * https://github.com/cakephp/cakephp/issues/16909
     *
     * @return void
     */
    public function testPaginateOrderWithNumericKeyAndSortSpecified(): void
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage(
            'The `order` config must be an associative array.'
            . ' Found invalid value with numeric key: `PaginatorPosts.title ASC`',
        );

        $settings = [
            'PaginatorPosts' => [
                'order' => ['PaginatorPosts.title ASC'],
            ],
        ];

        $table = $this->getTableLocator()->get('PaginatorPosts');

        $this->Paginator->paginate($table, ['sort' => 'title'], $settings);
    }

    public function testDeprecationWarningForExtraSettings(): void
    {
        $this->expectWarningMessageMatches(
            '/Passing query options as paginator settings is no longer supported/',
            function (): void {
                $table = $this->getTableLocator()->get('PaginatorPosts');
                $this->Paginator->paginate($table, [], ['fields' => ['title']]);
            },
        );
    }

    /**
     * Test sortMap with simple 1:1 mapping
     */
    public function testSortMapSimpleMapping(): void
    {
        $table = $this->getTableLocator()->get('PaginatorPosts');
        $settings = [
            'sortMap' => [
                'name' => 'PaginatorPosts.title',
                'content' => 'PaginatorPosts.body',
            ],
        ];

        // Test sorting by mapped key 'name'
        $params = ['sort' => 'name', 'direction' => 'asc'];
        $result = $this->Paginator->paginate($table, $params, $settings);
        $pagingParams = $result->pagingParams();

        $this->assertEquals('name', $pagingParams['sort']);
        $this->assertEquals('asc', $pagingParams['direction']);
        $this->assertEquals(['PaginatorPosts.title' => 'asc'], $pagingParams['completeSort']);

        // Test sorting by mapped key 'content' with desc direction
        $params = ['sort' => 'content', 'direction' => 'desc'];
        $result = $this->Paginator->paginate($table, $params, $settings);
        $pagingParams = $result->pagingParams();

        $this->assertEquals('content', $pagingParams['sort']);
        $this->assertEquals('desc', $pagingParams['direction']);
        $this->assertEquals(['PaginatorPosts.body' => 'desc'], $pagingParams['completeSort']);
    }

    /**
     * Test sortMap with shorthand numeric array syntax for 1:1 mapping
     */
    public function testSortMapShorthandSyntax(): void
    {
        $table = $this->getTableLocator()->get('PaginatorPosts');
        $settings = [
            'sortMap' => [
                'title',
                'body',
                'name' => 'PaginatorPosts.title',
            ],
        ];

        // Test sorting by shorthand mapped key 'title'
        $params = ['sort' => 'title', 'direction' => 'asc'];
        $result = $this->Paginator->paginate($table, $params, $settings);
        $pagingParams = $result->pagingParams();

        $this->assertEquals('title', $pagingParams['sort']);
        $this->assertEquals('asc', $pagingParams['direction']);
        // Shorthand fields still get prefixed with table name for actual query
        $this->assertEquals(['PaginatorPosts.title' => 'asc'], $pagingParams['completeSort']);

        // Test sorting by shorthand mapped key 'body'
        $params = ['sort' => 'body', 'direction' => 'desc'];
        $result = $this->Paginator->paginate($table, $params, $settings);
        $pagingParams = $result->pagingParams();

        $this->assertEquals('body', $pagingParams['sort']);
        $this->assertEquals('desc', $pagingParams['direction']);
        // Shorthand fields still get prefixed with table name for actual query
        $this->assertEquals(['PaginatorPosts.body' => 'desc'], $pagingParams['completeSort']);

        // Test that regular mapping still works alongside shorthand
        $params = ['sort' => 'name', 'direction' => 'asc'];
        $result = $this->Paginator->paginate($table, $params, $settings);
        $pagingParams = $result->pagingParams();

        $this->assertEquals('name', $pagingParams['sort']);
        $this->assertEquals('asc', $pagingParams['direction']);
        $this->assertEquals(['PaginatorPosts.title' => 'asc'], $pagingParams['completeSort']);
    }

    /**
     * Test sortMap with multi-column sorting
     */
    public function testSortMapMultiColumnSorting(): void
    {
        $table = $this->getTableLocator()->get('PaginatorPosts');
        $settings = [
            'sortMap' => [
                'titleauthor' => ['PaginatorPosts.title', 'PaginatorPosts.author_id'],
                'relevance' => ['PaginatorPosts.id', 'PaginatorPosts.body'],
            ],
        ];

        // Test multi-column sorting with 'titleauthor'
        $params = ['sort' => 'titleauthor', 'direction' => 'desc'];
        $result = $this->Paginator->paginate($table, $params, $settings);
        $pagingParams = $result->pagingParams();

        $this->assertEquals('titleauthor', $pagingParams['sort']);
        $this->assertEquals('desc', $pagingParams['direction']);
        $this->assertEquals([
            'PaginatorPosts.title' => 'desc',
            'PaginatorPosts.author_id' => 'desc',
        ], $pagingParams['completeSort']);

        // Test multi-column sorting with 'relevance'
        $params = ['sort' => 'relevance', 'direction' => 'asc'];
        $result = $this->Paginator->paginate($table, $params, $settings);
        $pagingParams = $result->pagingParams();

        $this->assertEquals('relevance', $pagingParams['sort']);
        $this->assertEquals('asc', $pagingParams['direction']);
        $this->assertEquals([
            'PaginatorPosts.id' => 'asc',
            'PaginatorPosts.body' => 'asc',
        ], $pagingParams['completeSort']);
    }

    /**
     * Test sortMap with fixed direction sorting
     */
    public function testSortMapFixedDirectionSorting(): void
    {
        $table = $this->getTableLocator()->get('PaginatorPosts');
        $settings = [
            'sortMap' => [
                'fresh' => [
                    'PaginatorPosts.title',
                    'PaginatorPosts.body' => 'desc',
                ],
                'popularity' => [
                    'PaginatorPosts.id' => 'desc',
                    'PaginatorPosts.author_id' => 'asc',
                ],
            ],
        ];

        // Test 'fresh' with mixed directions (querystring direction for title, fixed desc for body)
        $params = ['sort' => 'fresh', 'direction' => 'asc'];
        $result = $this->Paginator->paginate($table, $params, $settings);
        $pagingParams = $result->pagingParams();

        $this->assertEquals('fresh', $pagingParams['sort']);
        $this->assertEquals('asc', $pagingParams['direction']); // The first non-fixed field's direction
        $this->assertEquals([
            'PaginatorPosts.title' => 'asc', // Uses querystring direction
            'PaginatorPosts.body' => 'desc', // Fixed direction
        ], $pagingParams['completeSort']);

        // Test 'popularity' with all fixed directions
        $params = ['sort' => 'popularity', 'direction' => 'asc']; // Direction should be ignored
        $result = $this->Paginator->paginate($table, $params, $settings);
        $pagingParams = $result->pagingParams();

        $this->assertEquals('popularity', $pagingParams['sort']);
        $this->assertEquals([
            'PaginatorPosts.id' => 'desc',
            'PaginatorPosts.author_id' => 'asc',
        ], $pagingParams['completeSort']);
    }

    /**
     * Test that unmapped keys are rejected when sortMap is defined
     */
    public function testSortMapRejectsUnmappedKeys(): void
    {
        $table = $this->getTableLocator()->get('PaginatorPosts');
        $settings = [
            'sortMap' => [
                'name' => 'PaginatorPosts.title',
            ],
        ];

        // Try to sort by unmapped field
        $params = ['sort' => 'body', 'direction' => 'asc'];
        $result = $this->Paginator->paginate($table, $params, $settings);
        $pagingParams = $result->pagingParams();

        // Sort should be cleared as it's not in sortMap
        $this->assertNull($pagingParams['sort']);
        $this->assertNull($pagingParams['direction']);
        $this->assertEquals([], $pagingParams['completeSort']);
    }

    /**
     * Test backward compatibility when sortMap is not configured
     */
    public function testBackwardCompatibilityWithoutSortMap(): void
    {
        $table = $this->getTableLocator()->get('PaginatorPosts');

        // Test without sortMap - should work as before
        $params = ['sort' => 'title', 'direction' => 'desc'];
        $result = $this->Paginator->paginate($table, $params);
        $pagingParams = $result->pagingParams();

        $this->assertEquals('title', $pagingParams['sort']);
        $this->assertEquals('desc', $pagingParams['direction']);
        $this->assertEquals(['PaginatorPosts.title' => 'desc'], $pagingParams['completeSort']);
    }

    /**
     * Test sortMap with association sorting
     */
    public function testSortMapWithAssociations(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        // Association is already set up in the Articles table

        $settings = [
            'sortMap' => [
                'author' => 'Authors.name',
                'author_article' => ['Authors.name', 'Articles.title'],
            ],
        ];

        // Test association field mapping
        $params = ['sort' => 'author', 'direction' => 'asc'];
        $query = $table->find()->contain(['Authors']);
        $result = $this->Paginator->paginate($query, $params, $settings);
        $pagingParams = $result->pagingParams();

        $this->assertEquals('author', $pagingParams['sort']);
        $this->assertEquals('asc', $pagingParams['direction']);
        $this->assertEquals(['Authors.name' => 'asc'], $pagingParams['completeSort']);
    }
}
