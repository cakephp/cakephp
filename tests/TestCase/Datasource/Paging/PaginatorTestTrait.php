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
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Paging\Exception\PageOutOfBoundsException;
use Cake\Datasource\Paging\NumericPaginator;
use Cake\Datasource\RepositoryInterface;

trait PaginatorTestTrait
{
    /**
     * @var \Cake\Datasource\Paginator
     */
    protected $Paginator;

    /**
     * @var \Cake\Datasource\RepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $Post;

    /**
     * setup
     */
    public function setUp(): void
    {
        parent::setUp();

        Configure::write('App.namespace', 'TestApp');

        $this->Paginator = new NumericPaginator();

        $this->Post = $this->getMockRepository();
    }

    /**
     * tearDown
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->getTableLocator()->clear();
    }

    /**
     * Test that non-numeric values are rejected for page, and limit
     */
    public function testPageParamCasting(): void
    {
        $this->Post->expects($this->any())
            ->method('getAlias')
            ->will($this->returnValue('Posts'));

        $query = $this->_getMockFindQuery();
        $this->Post->expects($this->any())
            ->method('find')
            ->will($this->returnValue($query));

        $params = ['page' => '1 " onclick="alert(\'xss\');">'];
        $settings = ['limit' => 1, 'maxLimit' => 10];
        $this->Paginator->paginate($this->Post, $params, $settings);
        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertSame(1, $pagingParams['Posts']['page'], 'XSS exploit opened');
    }

    /**
     * test that unknown keys in the default settings are
     * passed to the find operations.
     */
    public function testPaginateExtraParams(): void
    {
        $params = ['page' => '-1'];
        $settings = [
            'PaginatorPosts' => [
                'contain' => ['PaginatorAuthor'],
                'maxLimit' => 10,
                'group' => 'PaginatorPosts.published',
                'order' => ['PaginatorPosts.id' => 'ASC'],
            ],
        ];
        $table = $this->_getMockPosts(['selectQuery']);
        $query = $this->_getMockFindQuery();

        $table->expects($this->once())
            ->method('selectQuery')
            ->will($this->returnValue($query));

        $query->expects($this->once())
            ->method('applyOptions')
            ->with([
                'contain' => ['PaginatorAuthor'],
                'group' => 'PaginatorPosts.published',
                'limit' => 10,
                'order' => ['PaginatorPosts.id' => 'ASC'],
                'page' => 1,
            ]);

        $this->deprecated(function () use ($table, $params, $settings) {
            $this->Paginator->paginate($table, $params, $settings);
        });
    }

    /**
     * Test to make sure options get sent to custom finder methods via paginate
     */
    public function testPaginateCustomFinderOptions(): void
    {
        $settings = [
            'PaginatorPosts' => [
                'finder' => ['author' => ['author_id' => 1]],
            ],
        ];
        $table = $this->getTableLocator()->get('PaginatorPosts');

        $expected = $table
            ->find('author', [
                'conditions' => [
                    'PaginatorPosts.author_id' => 1,
                ],
            ])
            ->count();
        $result = $this->Paginator->paginate($table, [], $settings)->count();

        $this->assertEquals($expected, $result);
    }

    /**
     * Test that nested eager loaders don't trigger invalid SQL errors.
     */
    public function testPaginateNestedEagerLoader(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->belongsToMany('Tags');
        $tags = $this->getTableLocator()->get('Tags');
        $tags->belongsToMany('Authors');
        $articles->getEventManager()->on('Model.beforeFind', function ($event, $query): void {
            $query ->matching('Tags', function ($q) {
                return $q->matching('Authors', function ($q) {
                    return $q->where(['Authors.name' => 'larry']);
                });
            });
        });
        $results = $this->Paginator->paginate($articles);
        $result = $results->first();
        $this->assertInstanceOf(EntityInterface::class, $result);
        $this->assertInstanceOf(EntityInterface::class, $result->_matchingData['Tags']);
        $this->assertInstanceOf(EntityInterface::class, $result->_matchingData['Authors']);
    }

    /**
     * test that flat default pagination parameters work.
     */
    public function testDefaultPaginateParams(): void
    {
        $settings = [
            'order' => ['PaginatorPosts.id' => 'DESC'],
            'maxLimit' => 10,
        ];

        $table = $this->_getMockPosts(['selectQuery']);
        $query = $this->_getMockFindQuery();

        $table->expects($this->once())
            ->method('selectQuery')
            ->will($this->returnValue($query));

        $query->expects($this->once())
            ->method('applyOptions')
            ->with([
                'limit' => 10,
                'page' => 1,
                'order' => ['PaginatorPosts.id' => 'DESC'],
            ]);

        $this->Paginator->paginate($table, [], $settings);

        $result = $this->Paginator->getPagingParams();
        $this->assertEquals('PaginatorPosts.id', $result['PaginatorPosts']['sort']);
        $this->assertEquals('DESC', $result['PaginatorPosts']['direction']);
    }

    /**
     * Tests that flat default pagination parameters work for multi order.
     */
    public function testDefaultPaginateParamsMultiOrder(): void
    {
        $settings = [
            'order' => ['PaginatorPosts.id' => 'DESC', 'PaginatorPosts.title' => 'ASC'],
        ];

        $table = $this->_getMockPosts(['selectQuery']);
        $query = $this->_getMockFindQuery();

        $table->expects($this->once())
            ->method('selectQuery')
            ->will($this->returnValue($query));

        $query->expects($this->once())
            ->method('applyOptions')
            ->with([
                'limit' => 20,
                'page' => 1,
                'order' => $settings['order'],
            ]);

        $this->Paginator->paginate($table, [], $settings);

        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertEquals('PaginatorPosts.id', $pagingParams['PaginatorPosts']['sortDefault']);
        $this->assertEquals('DESC', $pagingParams['PaginatorPosts']['directionDefault']);
    }

    /**
     * test that default sort and default direction are injected into request
     */
    public function testDefaultPaginateParamsIntoRequest(): void
    {
        $settings = [
            'order' => ['PaginatorPosts.id' => 'DESC'],
            'maxLimit' => 10,
        ];

        $table = $this->_getMockPosts(['selectQuery']);
        $query = $this->_getMockFindQuery();

        $table->expects($this->once())
            ->method('selectQuery')
            ->will($this->returnValue($query));

        $query->expects($this->once())
            ->method('applyOptions')
            ->with([
                'limit' => 10,
                'page' => 1,
                'order' => ['PaginatorPosts.id' => 'DESC'],
            ]);

        $this->Paginator->paginate($table, [], $settings);
        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertEquals('PaginatorPosts.id', $pagingParams['PaginatorPosts']['sortDefault']);
        $this->assertEquals('DESC', $pagingParams['PaginatorPosts']['directionDefault']);
    }

    /**
     * test that option merging prefers specific models
     */
    public function testMergeOptionsModelSpecific(): void
    {
        $settings = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
            'Posts' => [
                'page' => 1,
                'limit' => 10,
                'maxLimit' => 50,
            ],
            'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
            'whitelist' => ['limit', 'sort', 'page', 'direction'],
        ];
        $defaults = $this->Paginator->getDefaults('Silly', $settings);
        $result = $this->Paginator->mergeOptions([], $defaults);
        $this->assertEquals($settings + [
            'sortableFields' => null,
            'finder' => 'all',
        ], $result);

        $defaults = $this->Paginator->getDefaults('Posts', $settings);
        $result = $this->Paginator->mergeOptions([], $defaults);
        $expected = [
            'page' => 1,
            'limit' => 10,
            'maxLimit' => 50,
            'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
            'whitelist' => ['limit', 'sort', 'page', 'direction'],
            'sortableFields' => null,
            'finder' => 'all',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test mergeOptions with custom scope
     */
    public function testMergeOptionsCustomScope(): void
    {
        $params = [
            'page' => 10,
            'limit' => 10,
            'scope' => [
                'page' => 2,
                'limit' => 5,
            ],
        ];

        $settings = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
            'finder' => 'myCustomFind',
        ];
        $defaults = $this->Paginator->getDefaults('Post', $settings);
        $result = $this->Paginator->mergeOptions($params, $defaults);
        $expected = [
            'page' => 10,
            'limit' => 10,
            'maxLimit' => 100,
            'finder' => 'myCustomFind',
            'whitelist' => ['limit', 'sort', 'page', 'direction'],
            'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
            'sortableFields' => null,
        ];
        $this->assertEquals($expected, $result);

        $settings = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
            'finder' => 'myCustomFind',
            'scope' => 'nonexistent',
        ];
        $defaults = $this->Paginator->getDefaults('Post', $settings);
        $result = $this->Paginator->mergeOptions($params, $defaults);
        $expected = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
            'finder' => 'myCustomFind',
            'whitelist' => ['limit', 'sort', 'page', 'direction'],
            'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
            'scope' => 'nonexistent',
            'sortableFields' => null,
        ];
        $this->assertEquals($expected, $result);

        $settings = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
            'finder' => 'myCustomFind',
            'scope' => 'scope',
        ];
        $defaults = $this->Paginator->getDefaults('Post', $settings);
        $result = $this->Paginator->mergeOptions($params, $defaults);
        $expected = [
            'page' => 2,
            'limit' => 5,
            'maxLimit' => 100,
            'finder' => 'myCustomFind',
            'whitelist' => ['limit', 'sort', 'page', 'direction'],
            'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
            'scope' => 'scope',
            'sortableFields' => null,
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test mergeOptions with customFind key
     */
    public function testMergeOptionsCustomFindKey(): void
    {
        $params = [
            'page' => 10,
            'limit' => 10,
        ];
        $settings = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
            'finder' => 'myCustomFind',
        ];
        $defaults = $this->Paginator->getDefaults('Post', $settings);
        $result = $this->Paginator->mergeOptions($params, $defaults);
        $expected = [
            'page' => 10,
            'limit' => 10,
            'maxLimit' => 100,
            'finder' => 'myCustomFind',
            'whitelist' => ['limit', 'sort', 'page', 'direction'],
            'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
            'sortableFields' => null,
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test merging options from the querystring.
     */
    public function testMergeOptionsQueryString(): void
    {
        $params = [
            'page' => 99,
            'limit' => 75,
        ];
        $settings = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
        ];
        $defaults = $this->Paginator->getDefaults('Post', $settings);
        $result = $this->Paginator->mergeOptions($params, $defaults);
        $expected = [
            'page' => 99,
            'limit' => 75,
            'maxLimit' => 100,
            'whitelist' => ['limit', 'sort', 'page', 'direction'],
            'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
            'sortableFields' => null,
            'finder' => 'all',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test that the default allowedParameters doesn't let people screw with things they should not be allowed to.
     */
    public function testMergeOptionsDefaultAllowedParameters(): void
    {
        $params = [
            'page' => 10,
            'limit' => 10,
            'fields' => ['bad.stuff'],
            'recursive' => 1000,
            'conditions' => ['bad.stuff'],
            'contain' => ['bad'],
        ];
        $settings = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
        ];
        $defaults = $this->Paginator->getDefaults('Post', $settings);
        $result = $this->Paginator->mergeOptions($params, $defaults);
        $expected = [
            'page' => 10,
            'limit' => 10,
            'maxLimit' => 100,
            'whitelist' => ['limit', 'sort', 'page', 'direction'],
            'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
            'sortableFields' => null,
            'finder' => 'all',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test that modifying the deprecated whitelist works.
     */
    public function testMergeOptionsExtraWhitelist(): void
    {
        $params = [
            'page' => 10,
            'limit' => 10,
            'fields' => ['bad.stuff'],
            'recursive' => 1000,
            'conditions' => ['bad.stuff'],
            'contain' => ['bad'],
        ];
        $settings = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
        ];
        $this->deprecated(function () use ($settings, $params): void {
            $this->Paginator->setConfig('whitelist', ['fields']);
            $defaults = $this->Paginator->getDefaults('Post', $settings);
            $result = $this->Paginator->mergeOptions($params, $defaults);
            $expected = [
                'page' => 10,
                'limit' => 10,
                'maxLimit' => 100,
                'fields' => ['bad.stuff'],
                'whitelist' => ['limit', 'sort', 'page', 'direction', 'fields'],
                'allowedParameters' => ['limit', 'sort', 'page', 'direction', 'fields'],
                'sortableFields' => null,
                'finder' => 'all',
            ];
            $this->assertEquals($expected, $result);
        });
    }

    /**
     * test mergeOptions with limit > maxLimit in code.
     */
    public function testMergeOptionsMaxLimit(): void
    {
        $settings = [
            'limit' => 200,
            'paramType' => 'named',
        ];
        $defaults = $this->Paginator->getDefaults('Post', $settings);
        $result = $this->Paginator->mergeOptions([], $defaults);
        $expected = [
            'page' => 1,
            'limit' => 100,
            'maxLimit' => 100,
            'paramType' => 'named',
            'whitelist' => ['limit', 'sort', 'page', 'direction'],
            'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
            'sortableFields' => null,
            'finder' => 'all',
        ];
        $this->assertEquals($expected, $result);

        $settings = [
            'maxLimit' => 10,
            'paramType' => 'named',
        ];
        $defaults = $this->Paginator->getDefaults('Post', $settings);
        $result = $this->Paginator->mergeOptions([], $defaults);
        $expected = [
            'page' => 1,
            'limit' => 10,
            'maxLimit' => 10,
            'paramType' => 'named',
            'whitelist' => ['limit', 'sort', 'page', 'direction'],
            'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
            'sortableFields' => null,
            'finder' => 'all',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test getDefaults with limit > maxLimit in code.
     */
    public function testGetDefaultMaxLimit(): void
    {
        $settings = [
            'page' => 1,
            'limit' => 2,
            'maxLimit' => 10,
            'order' => [
                'Users.username' => 'asc',
            ],
        ];
        $defaults = $this->Paginator->getDefaults('Post', $settings);
        $result = $this->Paginator->mergeOptions([], $defaults);
        $expected = [
            'page' => 1,
            'limit' => 2,
            'maxLimit' => 10,
            'order' => [
                'Users.username' => 'asc',
            ],
            'whitelist' => ['limit', 'sort', 'page', 'direction'],
            'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
            'sortableFields' => null,
            'finder' => 'all',
        ];
        $this->assertEquals($expected, $result);

        $settings = [
            'page' => 1,
            'limit' => 100,
            'maxLimit' => 10,
            'order' => [
                'Users.username' => 'asc',
            ],
        ];
        $defaults = $this->Paginator->getDefaults('Post', $settings);
        $result = $this->Paginator->mergeOptions([], $defaults);
        $expected = [
            'page' => 1,
            'limit' => 10,
            'maxLimit' => 10,
            'order' => [
                'Users.username' => 'asc',
            ],
            'whitelist' => ['limit', 'sort', 'page', 'direction'],
            'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
            'sortableFields' => null,
            'finder' => 'all',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Integration test to ensure that validateSort is being used by paginate()
     */
    public function testValidateSortInvalid(): void
    {
        $table = $this->_getMockPosts(['selectQuery']);
        $query = $this->_getMockFindQuery();

        $table->expects($this->once())
            ->method('selectQuery')
            ->will($this->returnValue($query));

        $query->expects($this->once())->method('applyOptions')
            ->with([
                'limit' => 20,
                'page' => 1,
                'order' => ['PaginatorPosts.id' => 'asc'],
            ]);

        $params = [
            'page' => 1,
            'sort' => 'id',
            'direction' => 'herp',
        ];
        $this->Paginator->paginate($table, $params);
        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertEquals('id', $pagingParams['PaginatorPosts']['sort']);
        $this->assertEquals('asc', $pagingParams['PaginatorPosts']['direction']);
    }

    /**
     * test that invalid directions are ignored.
     */
    public function testValidateSortInvalidDirection(): void
    {
        $model = $this->getMockRepository();
        $model->expects($this->any())
            ->method('getAlias')
            ->will($this->returnValue('model'));
        $model->expects($this->any())
            ->method('hasField')
            ->will($this->returnValue(true));

        $options = ['sort' => 'something', 'direction' => 'boogers'];
        $result = $this->Paginator->validateSort($model, $options);

        $this->assertEquals('asc', $result['order']['model.something']);
    }

    /**
     * Test that "sort" and "direction" in paging params is properly set based
     * on initial value of "order" in paging settings.
     */
    public function testValidaSortInitialSortAndDirection(): void
    {
        $table = $this->_getMockPosts(['selectQuery']);
        $query = $this->_getMockFindQuery();

        $table->expects($this->once())
            ->method('selectQuery')
            ->will($this->returnValue($query));

        $query->expects($this->once())->method('applyOptions')
            ->with([
                'limit' => 20,
                'page' => 1,
                'order' => ['PaginatorPosts.id' => 'asc'],
            ]);

        $options = [
            'order' => [
                'id' => 'asc',
            ],
            'sortableFields' => ['id'],
        ];
        $this->Paginator->paginate($table, [], $options);
        $pagingParams = $this->Paginator->getPagingParams();

        $this->assertEquals('id', $pagingParams['PaginatorPosts']['sort']);
        $this->assertEquals('asc', $pagingParams['PaginatorPosts']['direction']);
    }

    /**
     * Test that "sort" and "direction" in paging params is properly set based
     * on initial value of "order" in paging settings.
     */
    public function testValidateSortAndDirectionAliased(): void
    {
        $table = $this->_getMockPosts(['selectQuery']);
        $query = $this->_getMockFindQuery();

        $table->expects($this->once())
            ->method('selectQuery')
            ->will($this->returnValue($query));

        $query->expects($this->once())->method('applyOptions')
            ->with([
                'limit' => 20,
                'page' => 1,
                'order' => ['PaginatorPosts.title' => 'asc'],
            ]);

        $options = [
            'order' => [
                'Articles.title' => 'desc',
            ],
        ];
        $queryParams = [
            'page' => 1,
            'sort' => 'title',
            'direction' => 'asc',
        ];

        $this->Paginator->paginate($table, $queryParams, $options);
        $pagingParams = $this->Paginator->getPagingParams();

        $this->assertEquals('title', $pagingParams['PaginatorPosts']['sort']);
        $this->assertEquals('asc', $pagingParams['PaginatorPosts']['direction']);

        $this->assertEquals('Articles.title', $pagingParams['PaginatorPosts']['sortDefault']);
        $this->assertEquals('desc', $pagingParams['PaginatorPosts']['directionDefault']);
    }

    /**
     * testValidateSortRetainsOriginalSortValue
     *
     * @see https://github.com/cakephp/cakephp/issues/11740
     */
    public function testValidateSortRetainsOriginalSortValue(): void
    {
        $table = $this->_getMockPosts(['selectQuery']);
        $query = $this->_getMockFindQuery();

        $table->expects($this->once())
            ->method('selectQuery')
            ->will($this->returnValue($query));

        $query->expects($this->once())->method('applyOptions')
            ->with([
                'limit' => 20,
                'page' => 1,
                'order' => ['PaginatorPosts.id' => 'asc'],
            ]);

        $params = [
            'page' => 1,
            'sort' => 'id',
            'direction' => 'herp',
        ];
        $options = [
            'sortableFields' => ['id'],
        ];
        $this->Paginator->paginate($table, $params, $options);
        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertEquals('id', $pagingParams['PaginatorPosts']['sort']);
    }

    /**
     * Test that a really large page number gets clamped to the max page size.
     */
    public function testOutOfRangePageNumberGetsClamped(): void
    {
        $params['page'] = 3000;

        $table = $this->getTableLocator()->get('PaginatorPosts');
        try {
            $this->Paginator->paginate($table, $params);
            $this->fail('No exception raised');
        } catch (PageOutOfBoundsException $exception) {
            $this->assertEquals(
                'Page number 3000 could not be found.',
                $exception->getMessage()
            );

            $this->assertSame(
                [
                    'requestedPage' => 3000,
                    'pagingParams' => $this->Paginator->getPagingParams(),
                ],
                $exception->getAttributes()
            );
        }
    }

    /**
     * Test that a really REALLY large page number gets clamped to the max page size.
     */
    public function testOutOfVeryBigPageNumberGetsClamped(): void
    {
        $this->expectException(PageOutOfBoundsException::class);
        $params = [
            'page' => '3000000000000000000000000',
        ];

        $table = $this->getTableLocator()->get('PaginatorPosts');
        $this->Paginator->paginate($table, $params);
    }

    /**
     * test that fields not in sortableFields won't be part of order conditions.
     */
    public function testValidateAllowedSortFailure(): void
    {
        $model = $this->mockAliasHasFieldModel();

        $options = [
            'sort' => 'body',
            'direction' => 'asc',
            'sortableFields' => ['title', 'id'],
        ];
        $result = $this->Paginator->validateSort($model, $options);

        $this->assertEquals([], $result['order']);
    }

    /**
     * test that fields not in whitelist won't be part of order conditions.
     */
    public function testValidateSortWhitelistFailure(): void
    {
        $this->deprecated(function (): void {
            $model = $this->mockAliasHasFieldModel();
            $options = [
                'sort' => 'body',
                'direction' => 'asc',
                'sortWhitelist' => ['title', 'id'],
            ];
            $result = $this->Paginator->validateSort($model, $options);
            $this->assertEquals([], $result['order']);
        });
    }

    /**
     * test that fields in the sortableFields are not validated
     */
    public function testValidateAllowedSortTrusted(): void
    {
        $model = $this->mockAliasHasFieldModel();

        $options = [
            'sort' => 'body',
            'direction' => 'asc',
            'allowedsort' => ['body'],
        ];
        $result = $this->Paginator->validateSort($model, $options);

        $expected = ['model.body' => 'asc'];
        $this->assertEquals(
            $expected,
            $result['order'],
            'Trusted fields in schema should be prefixed'
        );
    }

    /**
     * test that sortableFields as empty array does not allow any sorting
     */
    public function testValidateAllowedSortEmpty(): void
    {
        $model = $this->mockAliasHasFieldModel();

        $options = [
            'order' => [
                'body' => 'asc',
                'foo.bar' => 'asc',
            ],
            'sort' => 'body',
            'direction' => 'asc',
            'sortableFields' => [],
        ];
        $result = $this->Paginator->validateSort($model, $options);

        $this->assertSame([], $result['order'], 'No sort should be applied');
    }

    /**
     * test that fields in the sortableFields are not validated
     */
    public function testValidateAllowedSortNotInSchema(): void
    {
        $model = $this->getMockRepository();
        $model->expects($this->any())
            ->method('getAlias')
            ->will($this->returnValue('model'));
        $model->expects($this->once())->method('hasField')
            ->will($this->returnValue(false));

        $options = [
            'sort' => 'score',
            'direction' => 'asc',
            'sortableFields' => ['score'],
        ];
        $result = $this->Paginator->validateSort($model, $options);

        $expected = ['score' => 'asc'];
        $this->assertEquals(
            $expected,
            $result['order'],
            'Trusted fields not in schema should not be altered'
        );
    }

    /**
     * test that multiple fields in the sortableFields are not validated and properly aliased.
     */
    public function testValidateAllowedSortMultiple(): void
    {
        $model = $this->mockAliasHasFieldModel();

        $options = [
            'order' => [
                'body' => 'asc',
                'foo.bar' => 'asc',
            ],
            'sortableFields' => ['body', 'foo.bar'],
        ];
        $result = $this->Paginator->validateSort($model, $options);

        $expected = [
            'model.body' => 'asc',
            'foo.bar' => 'asc',
        ];
        $this->assertEquals($expected, $result['order']);
    }

    /**
     * @return \Cake\Datasource\RepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockRepository()
    {
        $model = $this->getMockBuilder(RepositoryInterface::class)
            ->getMock();

        return $model;
    }

    /**
     * @param string $modelAlias Model alias to use.
     * @return \Cake\Datasource\RepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function mockAliasHasFieldModel($modelAlias = 'model')
    {
        $model = $this->getMockRepository();
        $model->expects($this->any())
            ->method('getAlias')
            ->will($this->returnValue($modelAlias));
        $model->expects($this->any())
            ->method('hasField')
            ->will($this->returnValue(true));

        return $model;
    }

    /**
     * test that multiple sort works.
     */
    public function testValidateSortMultiple(): void
    {
        $model = $this->mockAliasHasFieldModel();

        $options = [
            'order' => [
                'author_id' => 'asc',
                'title' => 'asc',
            ],
        ];
        $result = $this->Paginator->validateSort($model, $options);
        $expected = [
            'model.author_id' => 'asc',
            'model.title' => 'asc',
        ];

        $this->assertEquals($expected, $result['order']);
    }

    /**
     * test that multiple sort adds in query data.
     */
    public function testValidateSortMultipleWithQuery(): void
    {
        $model = $this->mockAliasHasFieldModel();

        $options = [
            'sort' => 'created',
            'direction' => 'desc',
            'order' => [
                'author_id' => 'asc',
                'title' => 'asc',
            ],
        ];
        $result = $this->Paginator->validateSort($model, $options);

        $expected = [
            'model.created' => 'desc',
            'model.author_id' => 'asc',
            'model.title' => 'asc',
        ];
        $this->assertEquals($expected, $result['order']);

        $options = [
            'sort' => 'title',
            'direction' => 'desc',
            'order' => [
                'author_id' => 'asc',
                'title' => 'asc',
            ],
        ];
        $result = $this->Paginator->validateSort($model, $options);

        $expected = [
            'model.title' => 'desc',
            'model.author_id' => 'asc',
        ];
        $this->assertEquals($expected, $result['order']);
    }

    /**
     * Tests that sort query string and model prefixes default match on assoc merging.
     */
    public function testValidateSortMultipleWithQueryAndAliasedDefault(): void
    {
        $model = $this->mockAliasHasFieldModel();

        $options = [
            'sort' => 'created',
            'direction' => 'desc',
            'order' => [
                'model.created' => 'asc',
            ],
        ];
        $result = $this->Paginator->validateSort($model, $options);

        $expected = [
            'sort' => 'created',
            'order' => [
                'model.created' => 'desc',
            ],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests that order strings can used by Paginator
     */
    public function testValidateSortWithString(): void
    {
        $model = $this->mockAliasHasFieldModel();

        $options = [
            'order' => 'model.author_id DESC',
        ];
        $result = $this->Paginator->validateSort($model, $options);
        $expected = 'model.author_id DESC';

        $this->assertEquals($expected, $result['order']);
    }

    /**
     * Test that no sort doesn't trigger an error.
     */
    public function testValidateSortNoSort(): void
    {
        $model = $this->mockAliasHasFieldModel();

        $options = [
            'direction' => 'asc',
            'sortableFields' => ['title', 'id'],
        ];
        $result = $this->Paginator->validateSort($model, $options);
        $this->assertEquals([], $result['order']);
    }

    /**
     * Test sorting with incorrect aliases on valid fields.
     */
    public function testValidateSortInvalidAlias(): void
    {
        $model = $this->mockAliasHasFieldModel();

        $options = ['sort' => 'Derp.id'];
        $result = $this->Paginator->validateSort($model, $options);
        $this->assertEquals([], $result['order']);
    }

    /**
     * @return array
     */
    public function checkLimitProvider(): array
    {
        return [
            'out of bounds' => [
                ['limit' => 1000000, 'maxLimit' => 100],
                100,
            ],
            'limit is nan' => [
                ['limit' => 'sheep!', 'maxLimit' => 100],
                1,
            ],
            'negative limit' => [
                ['limit' => '-1', 'maxLimit' => 100],
                1,
            ],
            'unset limit' => [
                ['limit' => null, 'maxLimit' => 100],
                1,
            ],
            'limit = 0' => [
                ['limit' => 0, 'maxLimit' => 100],
                1,
            ],
            'limit = 0 v2' => [
                ['limit' => 0, 'maxLimit' => 0],
                1,
            ],
            'limit = null' => [
                ['limit' => null, 'maxLimit' => 0],
                1,
            ],
            'bad input, results in 1' => [
                ['limit' => null, 'maxLimit' => null],
                1,
            ],
            'bad input, results in 1 v2' => [
                ['limit' => false, 'maxLimit' => false],
                1,
            ],
        ];
    }

    /**
     * test that maxLimit is respected
     *
     * @dataProvider checkLimitProvider
     */
    public function testCheckLimit(array $input, int $expected): void
    {
        $result = $this->Paginator->checkLimit($input);
        $this->assertSame($expected, $result['limit']);
    }

    /**
     * Integration test for checkLimit() being applied inside paginate()
     */
    public function testPaginateMaxLimit(): void
    {
        $table = $this->getTableLocator()->get('PaginatorPosts');

        $settings = [
            'maxLimit' => 100,
        ];
        $params = [
            'limit' => '1000',
        ];
        $this->Paginator->paginate($table, $params, $settings);
        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertEquals(100, $pagingParams['PaginatorPosts']['limit']);
        $this->assertEquals(100, $pagingParams['PaginatorPosts']['perPage']);

        $params = [
            'limit' => '10',
        ];
        $this->Paginator->paginate($table, $params, $settings);
        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertEquals(10, $pagingParams['PaginatorPosts']['limit']);
        $this->assertEquals(10, $pagingParams['PaginatorPosts']['perPage']);
    }

    /**
     * test paginate() and custom finders to ensure the count + find
     * use the custom type.
     */
    public function testPaginateCustomFindCount(): void
    {
        $settings = [
            'finder' => 'published',
            'limit' => 2,
        ];
        $table = $this->_getMockPosts(['selectQuery']);
        $query = $this->_getMockFindQuery();

        $table->expects($this->once())
            ->method('selectQuery')
            ->will($this->returnValue($query));

        $query->expects($this->once())->method('applyOptions')
            ->with([
                'limit' => 2,
                'page' => 1,
                'order' => [],
            ]);
        $this->Paginator->paginate($table, [], $settings);

        $result = $this->Paginator->getPagingParams();
        $this->assertEquals('published', $result['PaginatorPosts']['finder']);
    }

    /**
     * Tests that it is possible to pass an already made query object to
     * paginate()
     */
    public function testPaginateQuery(): void
    {
        $params = ['page' => '-1'];
        $settings = [
            'PaginatorPosts' => [
                'maxLimit' => 10,
                'order' => ['PaginatorPosts.id' => 'ASC'],
            ],
        ];
        $table = $this->_getMockPosts(['find']);
        $query = $this->_getMockFindQuery($table);
        $table->expects($this->never())->method('find');
        $query->expects($this->once())
            ->method('applyOptions')
            ->with([
                'limit' => 10,
                'order' => ['PaginatorPosts.id' => 'ASC'],
                'page' => 1,
            ]);
        $this->Paginator->paginate($query, $params, $settings);
    }

    /**
     * test paginate() with bind()
     */
    public function testPaginateQueryWithBindValue(): void
    {
        $config = ConnectionManager::getConfig('test');
        $this->skipIf(strpos($config['driver'], 'Sqlserver') !== false, 'Test temporarily broken in SQLServer');
        $table = $this->getTableLocator()->get('PaginatorPosts');
        $query = $table->find()
            ->where(['PaginatorPosts.author_id BETWEEN :start AND :end'])
            ->bind(':start', 1)
            ->bind(':end', 2);

        $results = $this->Paginator->paginate($query, []);

        $result = $results->toArray();
        $this->assertCount(2, $result);
        $this->assertEquals('First Post', $result[0]->title);
        $this->assertEquals('Third Post', $result[1]->title);
    }

    /**
     * Tests that passing a query object with a limit clause set will
     * overwrite it with the passed defaults.
     */
    public function testPaginateQueryWithLimit(): void
    {
        $params = ['page' => '-1'];
        $settings = [
            'PaginatorPosts' => [
                'maxLimit' => 10,
                'limit' => 5,
                'order' => ['PaginatorPosts.id' => 'ASC'],
            ],
        ];
        $table = $this->_getMockPosts(['find']);
        $query = $this->_getMockFindQuery($table);
        $query->limit(2);
        $table->expects($this->never())->method('find');
        $query->expects($this->once())
            ->method('applyOptions')
            ->with([
                'limit' => 5,
                'order' => ['PaginatorPosts.id' => 'ASC'],
                'page' => 1,
            ]);
        $this->Paginator->paginate($query, $params, $settings);
    }

    /**
     * Helper method for making mocks.
     *
     * @param array $methods
     * @return \Cake\ORM\Table|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function _getMockPosts($methods = [])
    {
        return $this->getMockBuilder('TestApp\Model\Table\PaginatorPostsTable')
            ->onlyMethods($methods)
            ->setConstructorArgs([[
                'connection' => ConnectionManager::get('test'),
                'alias' => 'PaginatorPosts',
                'schema' => [
                    'id' => ['type' => 'integer'],
                    'author_id' => ['type' => 'integer', 'null' => false],
                    'title' => ['type' => 'string', 'null' => false],
                    'body' => 'text',
                    'published' => ['type' => 'string', 'length' => 1, 'default' => 'N'],
                    '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
                ],
            ]])
            ->getMock();
    }

    /**
     * Helper method for mocking queries.
     *
     * @param string|null $table
     * @return \Cake\ORM\Query|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function _getMockFindQuery($table = null)
    {
        /** @var \Cake\ORM\Query|\PHPUnit\Framework\MockObject\MockObject $query */
        $query = $this->getMockBuilder('Cake\ORM\Query\SelectQuery')
            ->onlyMethods(['all', 'count', 'applyOptions'])
            ->addMethods(['total'])
            ->disableOriginalConstructor()
            ->getMock();

        $results = $this->getMockBuilder('Cake\ORM\ResultSet')
            ->disableOriginalConstructor()
            ->getMock();

        $query->expects($this->any())
            ->method('count')
            ->will($this->returnValue(2));

        $query->expects($this->any())
            ->method('all')
            ->will($this->returnValue($results));

        if ($table) {
            $query->setRepository($table);
        }

        return $query;
    }
}
