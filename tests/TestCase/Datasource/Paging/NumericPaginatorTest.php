<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Datasource\Paging;

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
    protected $fixtures = [
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

        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertSame(4, $pagingParams['PaginatorPosts']['current']);
        $this->assertSame(4, $pagingParams['PaginatorPosts']['count']);

        $settings = ['finder' => 'published'];
        $result = $this->Paginator->paginate($table, [], $settings);
        $this->assertCount(3, $result, '3 rows should come back');
        $this->assertEquals(['First Post', 'Second Post', 'Third Post'], $titleExtractor($result));

        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertSame(3, $pagingParams['PaginatorPosts']['current']);
        $this->assertSame(3, $pagingParams['PaginatorPosts']['count']);

        $settings = ['finder' => 'published', 'limit' => 2, 'page' => 2];
        $result = $this->Paginator->paginate($table, [], $settings);
        $this->assertCount(1, $result, '1 rows should come back');
        $this->assertEquals(['Third Post'], $titleExtractor($result));

        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertSame(1, $pagingParams['PaginatorPosts']['current']);
        $this->assertSame(3, $pagingParams['PaginatorPosts']['count']);
        $this->assertSame(2, $pagingParams['PaginatorPosts']['pageCount']);

        $settings = ['finder' => 'published', 'limit' => 2];
        $result = $this->Paginator->paginate($table, [], $settings);
        $this->assertCount(2, $result, '2 rows should come back');
        $this->assertEquals(['First Post', 'Second Post'], $titleExtractor($result));

        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertSame(2, $pagingParams['PaginatorPosts']['current']);
        $this->assertSame(3, $pagingParams['PaginatorPosts']['count']);
        $this->assertSame(2, $pagingParams['PaginatorPosts']['pageCount']);
        $this->assertTrue($pagingParams['PaginatorPosts']['nextPage']);
        $this->assertFalse($pagingParams['PaginatorPosts']['prevPage']);
        $this->assertSame(2, $pagingParams['PaginatorPosts']['perPage']);
        $this->assertNull($pagingParams['PaginatorPosts']['limit']);
    }

    /**
     * test paginate() and custom find with fields array, to make sure the correct count is returned.
     */
    public function testPaginateCustomFindFieldsArray(): void
    {
        $table = $this->getTableLocator()->get('PaginatorPosts');
        $data = ['author_id' => 3, 'title' => 'Fourth Article', 'body' => 'Article Body, unpublished', 'published' => 'N'];
        $table->save(new Entity($data));

        $settings = [
            'finder' => 'list',
            'conditions' => ['PaginatorPosts.published' => 'Y'],
            'limit' => 2,
        ];
        $results = $this->Paginator->paginate($table, [], $settings);

        $result = $results->toArray();
        $expected = [
            1 => 'First Post',
            2 => 'Second Post',
        ];
        $this->assertEquals($expected, $result);

        $result = $this->Paginator->getPagingParams()['PaginatorPosts'];
        $this->assertSame(2, $result['current']);
        $this->assertSame(3, $result['count']);
        $this->assertSame(2, $result['pageCount']);
        $this->assertTrue($result['nextPage']);
        $this->assertFalse($result['prevPage']);
    }

    /**
     * Test that special paginate types are called and that the type param doesn't leak out into defaults or options.
     */
    public function testPaginateCustomFinder(): void
    {
        $settings = [
            'PaginatorPosts' => [
                'finder' => 'published',
                'fields' => ['id', 'title'],
                'maxLimit' => 10,
            ],
        ];

        $table = $this->getTableLocator()->get('PaginatorPosts');
        $table->updateAll(['published' => 'N'], ['id' => 2]);

        $this->Paginator->paginate($table, [], $settings);
        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertSame('published', $pagingParams['PaginatorPosts']['finder']);

        $this->assertSame(1, $pagingParams['PaginatorPosts']['start']);
        $this->assertSame(2, $pagingParams['PaginatorPosts']['end']);
        $this->assertFalse($pagingParams['PaginatorPosts']['nextPage']);
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

        $this->Paginator->paginate($table, [], $settings);
        $pagingParams = $this->Paginator->getPagingParams();

        $this->assertSame('Other.title', $pagingParams['PaginatorPosts']['sort']);
        $this->assertNull($pagingParams['PaginatorPosts']['direction']);
    }
}
