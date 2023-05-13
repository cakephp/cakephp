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
 * @since         3.9.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Datasource\Paging;

use Cake\Core\Configure;
use Cake\Datasource\Paging\SimplePaginator;
use Cake\ORM\Entity;

class SimplePaginatorTest extends NumericPaginatorTest
{
    public function setUp(): void
    {
        parent::setUp();

        Configure::write('App.namespace', 'TestApp');

        $this->Paginator = new SimplePaginator();
    }

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
        $this->assertNull($pagingParams['PaginatorPosts']['count']);

        $settings = ['finder' => 'published'];
        $result = $this->Paginator->paginate($table, [], $settings);
        $this->assertCount(3, $result, '3 rows should come back');
        $this->assertEquals(['First Post', 'Second Post', 'Third Post'], $titleExtractor($result));

        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertSame(3, $pagingParams['PaginatorPosts']['current']);
        $this->assertNull($pagingParams['PaginatorPosts']['count']);

        $settings = ['finder' => 'published', 'limit' => 2, 'page' => 2];
        $result = $this->Paginator->paginate($table, [], $settings);
        $this->assertCount(1, $result, '1 rows should come back');
        $this->assertEquals(['Third Post'], $titleExtractor($result));

        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertSame(1, $pagingParams['PaginatorPosts']['current']);
        $this->assertNull($pagingParams['PaginatorPosts']['count']);
        $this->assertSame(0, $pagingParams['PaginatorPosts']['pageCount']);

        $settings = ['finder' => 'published', 'limit' => 2];
        $result = $this->Paginator->paginate($table, [], $settings);
        $this->assertCount(2, $result, '2 rows should come back');
        $this->assertEquals(['First Post', 'Second Post'], $titleExtractor($result));

        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertSame(2, $pagingParams['PaginatorPosts']['current']);
        $this->assertNull($pagingParams['PaginatorPosts']['count']);
        $this->assertSame(0, $pagingParams['PaginatorPosts']['pageCount']);
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
        $this->deprecated(function () {
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
            $this->assertNull($result['count']);
            $this->assertSame(0, $result['pageCount']);
            $this->assertTrue($result['nextPage']);
            $this->assertFalse($result['prevPage']);
        });
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
        $table->updateAll(['published' => 'N'], ['id' => 2]);

        $this->Paginator->paginate($table, [], $settings);
        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertSame('published', $pagingParams['PaginatorPosts']['finder']);

        $this->assertSame(1, $pagingParams['PaginatorPosts']['start']);
        $this->assertSame(2, $pagingParams['PaginatorPosts']['end']);
        // nextPage will be always true for SimplePaginator
        $this->assertTrue($pagingParams['PaginatorPosts']['nextPage']);
    }
}
