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
use Cake\Datasource\RepositoryInterface;
use Cake\ORM\Entity;

class SimplePaginatorTest extends NumericPaginatorTest
{
    public function setUp(): void
    {
        parent::setUp();

        Configure::write('App.namespace', 'TestApp');

        $this->Paginator = new class extends SimplePaginator {
            public function getDefaults(string $alias, array $settings): array
            {
                return parent::getDefaults($alias, $settings);
            }

            public function mergeOptions(array $params, array $settings): array
            {
                return parent::mergeOptions($params, $settings);
            }

            public function validateSort(RepositoryInterface $object, array $options): array
            {
                return parent::validateSort($object, $options);
            }

            public function checkLimit(array $options): array
            {
                return parent::checkLimit($options);
            }
        };
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

        $pagingParams = $result->pagingParams();
        $this->assertSame(1, $pagingParams['currentPage']);
        $this->assertNull($pagingParams['totalCount']);

        $settings = ['finder' => 'published'];
        $result = $this->Paginator->paginate($table, [], $settings);
        $this->assertCount(3, $result, '3 rows should come back');
        $this->assertEquals(['First Post', 'Second Post', 'Third Post'], $titleExtractor($result));

        $pagingParams = $result->pagingParams();
        $this->assertSame(1, $pagingParams['currentPage']);
        $this->assertNull($pagingParams['totalCount']);

        $settings = ['finder' => 'published', 'limit' => 2, 'page' => 2];
        $result = $this->Paginator->paginate($table, [], $settings);
        $this->assertCount(1, $result, '1 rows should come back');
        $this->assertEquals(['Third Post'], $titleExtractor($result));

        $pagingParams = $result->pagingParams();
        $this->assertSame(2, $pagingParams['currentPage']);
        $this->assertNull($pagingParams['totalCount']);
        $this->assertNull($pagingParams['pageCount']);

        $settings = ['finder' => 'published', 'limit' => 2];
        $result = $this->Paginator->paginate($table, [], $settings);
        $this->assertCount(2, $result, '2 rows should come back');
        $this->assertEquals(['First Post', 'Second Post'], $titleExtractor($result));

        $pagingParams = $result->pagingParams();
        $this->assertSame(1, $pagingParams['currentPage']);
        $this->assertNull($pagingParams['totalCount']);
        $this->assertNull($pagingParams['pageCount']);
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
        $this->assertSame(2, count($result));
        $this->assertSame(2, $pagingParams['count']);
        $this->assertFalse($pagingParams['hasNextPage']);
    }
}
